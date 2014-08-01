<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*
 * Valutec processing module
 *
 */
if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

class Valutec extends BasicCCModule 
{
    private $temp;
    // BEGIN INTERFACE METHODS

    /* handlesType($type)
     * $type is a constant as defined in paycardLib.php.
     * If you class can handle the given type, return
     * True
     */
    public function handlesType($type)
    {
        if ($type == PaycardLib::PAYCARD_TYPE_GIFT) {
            return true;
        } else {
            return false;
        }
    }

    /* entered($validate)
     * This function is called in paycardEntered()
     * [paycardEntered.php]. This function exists
     * to move all type-specific handling code out
     * of the paycard* files
     */
    public function entered($validate,$json)
    {
        global $CORE_LOCAL;
        // error checks based on card type
        if ($CORE_LOCAL->get("CCintegrate") != 1) { // credit card integration must be enabled
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Card Integration Disabled",
                                                         "Please process gift cards in standalone",
                                                         "[clear] to cancel"
            );

            return $json;
        }

        // error checks based on processing mode
        if ($CORE_LOCAL->get("paycard_mode") == PaycardLib::PAYCARD_MODE_VOID) {
            // use the card number to find the trans_id
            $dbTrans = Database::tDataConnect();
            $today = date('Ymd'); // numeric date only, in an int field
            $pan = $this->getPAN();
            $cashier = $CORE_LOCAL->get("CashierNo");
            $lane = $CORE_LOCAL->get("laneno");
            $trans = $CORE_LOCAL->get("transno");
            $sql = "SELECT transID 
                    FROM PaycardTransactions 
                    WHERE dateID=" . $today . " 
                        AND PAN='" . $pan . "' 
                        AND empNo=" . $cashier . "
                        AND registerNo=" . $lane . "
                        AND transNo=" . $trans;
            $search = $dbTrans->query($sql);
            $num = $dbTrans->num_rows($search);
            if ($num < 1) {
                $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                             "Card Not Used",
                                                             "That card number was not used in this transaction",
                                                             "[clear] to cancel"
                );

                return $json;
            } else if ($num > 1) {
                $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                             "Multiple Uses",
                                                             "That card number was used more than once in this transaction; select the payment and press VOID",
                                                             "[clear] to cancel"
                );

                return $json;
            }
            $payment = $dbTrans->fetch_array($search);

            return $this->paycard_void($payment['transID'],$json);
        }

        // check card data for anything else
        if ($validate) {
            if (PaycardLib::paycard_validNumber($CORE_LOCAL->get("paycard_PAN")) != 1) {
                $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                             "Invalid Card Number",
                                                             "Swipe again or type in manually",
                                                             "[clear] to cancel"
                );

                return $json;
            } else if( PaycardLib::paycard_accepted($CORE_LOCAL->get("paycard_PAN"), !PaycardLib::paycard_live(PaycardLib::PAYCARD_TYPE_GIFT)) != 1) {
                $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                             "Unsupported Card Type",
                                                             "We cannot process " . $CORE_LOCAL->get("paycard_issuer") . " cards",
                                                             "[clear] to cancel"
                );

                return $json;
            }
        }

        // other modes
        switch ($CORE_LOCAL->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                $CORE_LOCAL->set("paycard_amount",$CORE_LOCAL->get("amtdue"));
                $CORE_LOCAL->set("paycard_id",$CORE_LOCAL->get("LastID")+1); // kind of a hack to anticipate it this way..
                $plugin_info = new Paycards();
                $json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgAuth.php';
                return $json;
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $CORE_LOCAL->set("paycard_amount",0);
                $CORE_LOCAL->set("paycard_id",$CORE_LOCAL->get("LastID")+1); // kind of a hack to anticipate it this way..
                $plugin_info = new Paycards();
                $json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgGift.php';
                return $json;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgBalance.php';
                return $json;
        } // switch mode
    
        // if we're still here, it's an error
        $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                     "Invalid Mode",
                                                     "This card type does not support that processing mode",
                                                     "[clear] to cancel"
        );

        return $json;
    }

    /* doSend()
     * Process the paycard request and return
     * an error value as defined in paycardLib.php.
     *
     * On success, return PaycardLib::PAYCARD_ERR_OK.
     * On failure, return anything else and set any
     * error messages to be displayed in
     * $CORE_LOCAL->["boxMsg"].
     */
    public function doSend($type)
    {
        switch ($type) {
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_AUTH: 
                return $this->send_auth();
            case PaycardLib::PAYCARD_MODE_VOID:
            case PaycardLib::PAYCARD_MODE_VOIDITEM:
                return $this->send_void();
            case PaycardLib::PAYCARD_MODE_BALANCE:
                return $this->send_balance();
            default:
                return $this->setErrorMsg(0);
        }
    }

    /* cleanup()
     * This function is called when doSend() returns
     * PaycardLib::PAYCARD_ERR_OK. (see paycardAuthorize.php)
     * I use it for tendering, printing
     * receipts, etc, but it's really only for code
     * cleanliness. You could leave this as is and
     * do all the everything inside doSend()
     */
    public function cleanup($json)
    {
        global $CORE_LOCAL;
        switch ($CORE_LOCAL->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $resp = $CORE_LOCAL->get("paycard_response");
                $CORE_LOCAL->set("boxMsg","<b>Success</b><font size=-1>
                                           <p>Gift card balance: $" . $resp["Balance"] . "
                                           <p>\"rp\" to print
                                           <br>[enter] to continue</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $CORE_LOCAL->set("autoReprint",1);
                $ttl = $CORE_LOCAL->get("paycard_amount");
                PrehLib::deptkey($ttl*100,9020);
                $resp = $CORE_LOCAL->get("paycard_response");    
                $CORE_LOCAL->set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                $CORE_LOCAL->set("autoReprint",1);
                $record_id = $this->last_paycard_transaction_id;
                $charflag = ($record_id != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender("Gift Card", "GD", $amt, $record_id, $charflag);
                $resp = $CORE_LOCAL->get("paycard_response");
                $CORE_LOCAL->set("boxMsg","<b>Approved</b><font size=-1>
                                           <p>Used: $" . $CORE_LOCAL->get("paycard_amount") . "
                                           <br />New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip
                                           <br>[void] to cancel and void</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
            case PaycardLib::PAYCARD_MODE_VOIDITEM:
                $CORE_LOCAL->set("autoReprint",1);
                $v = new Void();
                $v->voidid($CORE_LOCAL->get("paycard_id"));
                $resp = $CORE_LOCAL->get("paycard_response");
                $CORE_LOCAL->set("boxMsg","<b>Voided</b><font size=-1>
                                           <p>New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
        }

        return $json;
    }

    /* paycard_void($transID)
     * Argument is trans_id to be voided
     * Again, this is for removing type-specific
     * code from paycard*.php files.
     */
    public function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        global $CORE_LOCAL;
        // situation checking
        if ($CORE_LOCAL->get("CCintegrate") != 1) { // gift card integration must be enabled
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Card Integration Disabled",
                                                         "Please process gift cards in standalone",
                                                         "[clear] to cancel"
            );

            return $json;
        }
        
        // initialize
        $dbTrans = Database::tDataConnect();
        $today = date('Ymd');
        $cashier = $CORE_LOCAL->get("CashierNo");
        $lane = $CORE_LOCAL->get("laneno");
        $trans = $CORE_LOCAL->get("transno");

        // look up the request using transID (within this transaction)
        $sql = "SELECT live,
                    PAN,
                    transType AS mode,
                    amount 
                FROM PaycardTransactions 
                WHERE dateID=" . $today . "
                    AND empNo=" . $cashier . "
                    AND registerNo=" . $lane . "
                    AND transNo=" . $trans . " 
                    AND transID=" . $transID;
        $search = $dbTrans->query($sql);
        $num = $dbTrans->num_rows($search);
        if ($num < 1) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Internal Error",
                                                         "Card request not found, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($num > 1) {
            $json['output'] =  PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                          "Internal Error",
                                                          "Card request not distinct, unable to void",
                                                          "[clear] to cancel"
            );

            return $json;
        }
        $request = $dbTrans->fetch_array($search);

        // look up the response
        $sql = "SELECT commErr,
                    httpCode,
                    validResponse,
                    xResultMessage AS xAuthorized,
                    xApprovalNumber AS xAuthorizationCode
                FROM PaycardTransactions 
                WHERE dateID=" . $today . " 
                    AND empNo=" . $cashier . "
                    AND registerNo=" . $lane ."
                    AND transNo=" . $trans . "
                    AND transID=" . $transID;
        $search = $dbTrans->query($sql);
        $num = $dbTrans->num_rows($search);
        if ($num < 1) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Internal Error",
                                                         "Card response not found, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($num > 1) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Internal Error",
                                                         "Card response not distinct, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        }
        $response = $dbTrans->fetch_array($search);

        // look up any previous successful voids
        $sql = "SELECT transID 
                FROM PaycardTransactions 
                WHERE dateID=" . $today . "
                    AND empNo=" . $cashier . "
                    AND cashierNo=" . $lane . "
                    AND transNo=" . $trans . "
                    AND transID=" . $transID . "
                    AND transType='VOID'
                    AND xResultCode=1";
        $search = $dbTrans->query($sql);
        $voided = $dbTrans->num_rows($search);
        // look up the transaction tender line-item
        $sql = "SELECT trans_type,
                    trans_subtype,
                    trans_status,
                    voided
                FROM localtemptrans 
                WHERE trans_id=" . $transID;
        $search = $dbTrans->query($sql);
        $num = $dbTrans->num_rows($search);
        if ($num < 1) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Internal Error",
                                                         "Transaction item not found, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($num > 1) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Internal Error",
                                                         "Transaction item not distinct, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        }
        $lineitem = $dbTrans->fetch_array($search);

        // make sure the gift card transaction is applicable to void
        if (!$response || $response['commErr'] != 0 || 
             $response['httpCode'] != 200 || $response['validResponse'] != 1) {
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Unable to Void",
                                                         "Card transaction not successful",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($voided > 0) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Unable to Void",
                                                         "Card transaction already voided",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($request['live'] != PaycardLib::paycard_live(PaycardLib::PAYCARD_TYPE_GIFT)) {
            // this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
            // I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Unable to Void",
                                                         "Processor platform mismatch",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($response['xAuthorized'] != 'true' && !stristr($response['xAuthorized'],'Appro')) {
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Unable to Void",
                                                         "Card transaction not approved",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if( $response['xAuthorizationCode'] < 1) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,"Internal Error",
                "Invalid authorization number","[clear] to cancel");
            return $json;
        }

        // make sure the transaction line-item is applicable to void
        if( $lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Internal Error",
                                                         "Invalid authorization number",
                                                         "[clear] to cancel"
            );
            
            return $json;
        }

        // save the details
        $CORE_LOCAL->set("paycard_PAN",$request['PAN']);
        if ($request['mode'] == 'refund' || $request['mode'] == 'Return') {
            $CORE_LOCAL->set("paycard_amount",-$request['amount']);
        } else {
            $CORE_LOCAL->set("paycard_amount",$request['amount']);
        }
        $CORE_LOCAL->set("paycard_id",$transID);
        $CORE_LOCAL->set("paycard_type",PaycardLib::PAYCARD_TYPE_GIFT);
        if ($lineitem['trans_type'] == "T" && $lineitem['trans_subtype'] == "GD") {
            $CORE_LOCAL->set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
        } else {
            $CORE_LOCAL->set("paycard_mode",PaycardLib::PAYCARD_MODE_VOIDITEM);
        }
    
        // display FEC code box
        $plugin_info = new Paycards();
        $json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgVoid.php';

        return $json;
    }

    // END INTERFACE METHODS
    
    private function send_auth()
    {
        global $CORE_LOCAL;
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }

        // prepare data for the request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = $CORE_LOCAL->get("CashierNo");
        $laneNo = $CORE_LOCAL->get("laneno");
        $transNo = $CORE_LOCAL->get("transno");
        $transID = $CORE_LOCAL->get("paycard_id");
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $amount = $CORE_LOCAL->get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = "";
        $logged_mode = $mode;
        $authMethod = "";
        switch ($CORE_LOCAL->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                $mode = (($amount < 0) ? 'refund' : 'tender');
                $logged_mode = (($amount < 0) ? 'Return' : 'Sale');
                $authMethod = (($amount < 0) ? 'AddValue' : 'Sale');
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $mode = 'addvalue';
                $logged_mode = 'Reload';
                $authMethod = 'AddValue';
                break;
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $mode = 'activate';
                $logged_mode = 'Issue';
                $authMethod = 'ActivateCard';
                break;
            default:
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }
        $termID = $this->getTermID();
        $live = $this->isLive();
        $manual = ($CORE_LOCAL->get("paycard_manual") ? 1 : 0);
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $identifier = $this->valutecIdentifier($transID); // valutec allows 10 digits; this uses lanenum-transnum-transid since we send cashiernum in another field
        
        /**
          Log transaction before sending request
        */
        $insQ = sprintf("INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID,
                    processor, refNum, live, cardType, transType,
                    amount, PAN, issuer, name, manual, requestDateTime)
                 VALUES (
                    %d,     %d,    %d,         %d,      %d,
                    '%s',     '%s',    %d,   '%s',     '%s',
                    %.2f,  '%s', '%s',  '%s',  %d,     '%s')",
                    $today, $cashierNo, $laneNo, $transNo, $transID,
                    'Valutec', $identifier, $live, 'Gift', $logged_mode,
                    $amountText, $cardPAN,
                    'Valutec', 'Cardholder', $manual, $now);
        $insR = $dbTrans->query($insQ);
        if ($insR) {
            $this->last_paycard_transaction_id = $dbTrans->insert_id();
        } else {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
                
        $authFields = array(
            'ProgramType'       => $program,
            'CardNumber'        => (($cardTr2) ? $cardTr2 : $cardPAN),
            'Amount'            => $amountText,
            'ServerID'          => $cashierNo,
            'Identifier'        => $identifier
        );

        $this->GATEWAY = "https://www.valutec.net/customers/transactions/valutec.asmx/";

        $getData = urlencode($authMethod)."?";
        $getData .= "TerminalID=".urlencode($termID);
        foreach ($authFields as $field => $value) {
            $getData .= "&".urlencode($field)."=".urlencode($value);
        }

        return $this->curlSend($getData,'GET');
    }

    private function send_void()
    {
        global $CORE_LOCAL;
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = $CORE_LOCAL->get("CashierNo");
        $laneNo = $CORE_LOCAL->get("laneno");
        $transNo = $CORE_LOCAL->get("transno");
        $transID = $CORE_LOCAL->get("paycard_id");
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $amount = $CORE_LOCAL->get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = 'void';
        $cardPAN = $this->getPAN();
        $identifier = date('mdHis'); // the void itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
        $termID = $this->getTermID();

        // look up the auth code from the original response 
        // (card number and amount should already be in session vars)
        $sql = "SELECT 
                    xApprovalNumber AS xAuthorizationCode 
                FROM PaycardTransactions 
                WHERE dateID='" . $today . "'
                    AND empNo=" . $cashierNo . "
                    AND registerNo=" . $laneNo . "
                    AND transNo=" . $transNo . "
                    AND transID=" . $transID;
        $search = $dbTrans->query($sql);
        if (!$search || $dbTrans->num_rows($search) != 1) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $log = $dbTrans->fetch_array($search);
        $authcode = $log['xAuthorizationCode'];
        $this->temp = $authcode;

        /**
          populate a void record in PaycardTransactions
        */
        $initQ = "INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID,
                    previousPaycardTransactionID, processor, refNum,
                    live, cardType, transType, amount, PAN, issuer,
                    name, manual, requestDateTime)
                  SELECT dateID, empNo, registerNo, transNo, transID,
                    paycardTransactionID, processor, refNum,
                    live, cardType, 'VOID', amount, PAN, issuer,
                    name, manual, " . $dbTrans->now() . "
                  FROM PaycardTransactions
                  WHERE
                    dateID=" . $today . "
                    AND empNo=" . $cashierNo . "
                    AND registerNo=" . $laneNo . "
                    AND transNo=" . $transNo . "
                    AND transID=" . $transID;
        $initR = $dbTrans->query($initQ);
        if ($initR) {
            $this->last_paycard_transaction_id = $dbTrans->insert_id();
        } else {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }

        // assemble and send void request
        $vdMethod = 'Void';
        $vdFields = array(
            'ProgramType'       => $program,
            'CardNumber'        => $cardPAN,
            'RequestAuthCode'   => $authcode,
            'ServerID'          => $cashierNo,
            'Identifier'        => $identifier
        );

        $this->GATEWAY = "https://www.valutec.net/customers/transactions/valutec.asmx/";

        $getData = urlencode($vdMethod)."?";
        $getData .= "TerminalID=".urlencode($termID);
        foreach ($vdFields as $field=>$value) {
            $getData .= "&".urlencode($field)."=".urlencode($value);
        }

        return $this->curlSend($getData,'GET');
    }

    private function send_balance()
    {
        global $CORE_LOCAL;
        // prepare data for the request
        $cashierNo = $CORE_LOCAL->get("CashierNo");
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $identifier = date('mdHis'); // the balance check itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
        $termID = $this->getTermID();

        // assemble and send balance check
        $balMethod = 'CardBalance';
        $balFields = array(
            'ProgramType'       => $program,
            'CardNumber'        => (($cardTr2) ? $cardTr2 : $cardPAN),
            'ServerID'          => $cashierNo,
            'Identifier'        => $identifier
        );

        $this->GATEWAY = "https://www.valutec.net/customers/transactions/valutec.asmx/";

        $getData = urlencode($balMethod)."?";
        $getData .= "TerminalID=".urlencode($termID);
        foreach ($balFields as $field=>$value) {
            $getData .= "&".urlencode($field)."=".urlencode($value);
        }

        return $this->curlSend($getData,'GET');
    }

    public function handleResponse($authResult)
    {
        global $CORE_LOCAL;
        switch ($CORE_LOCAL->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                return $this->handleResponseAuth($authResult);
            case PaycardLib::PAYCARD_MODE_VOID:
            case PaycardLib::PAYCARD_MODE_VOIDITEM:
                return $this->handleResponseVoid($authResult);
            case PaycardLib::PAYCARD_MODE_BALANCE:
                return $this->handleResponseBalance($authResult);
        }
    }

    private function handleResponseAuth($authResult)
    {
        global $CORE_LOCAL;
        $xml = new xmlData($authResult["response"]);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = $CORE_LOCAL->get("CashierNo");
        $laneNo = $CORE_LOCAL->get("laneno");
        $transNo = $CORE_LOCAL->get("transno");
        $transID = $CORE_LOCAL->get("paycard_id");
        $program = 'Gift';
        $identifier = $this->valutecIdentifier($transID); // valutec allows 10 digits; this uses lanenum-transnum-transid since we send cashiernum in another field

        $validResponse = ($xml->isValid()) ? 1 : 0;
        $errorMsg = $xml->get_first("ERRORMSG");
        $balance = $xml->get("BALANCE");

        if ($validResponse) {
            /*
            tendering more than the available balance returns an "NSF" error message, 
            but no Balance field however, the available balance is buried in the 
            RawOutput field, so we can dig it out and fill in the missing Balance field
            -- as of 1/22/08, valutec appears to now be returning the Balance field normally 
            (in its own XML field, not in RawOutput), but we still need to append it to 
            the Message so the cashier can see it
             */
            if ($errorMsg && substr($errorMsg,0,3) == "NSF") {
                if (!$balance || $balance === "") {
                    $rawOutput = $xml->get("RAWOUTPUT");    
                    $begin = strpos($rawOutput, "%1cBAL%3a");
                    if ($begin !== false) {
                        $end = strpos($rawOutput, "%1c", $begin+1);
                        if ($end !== false && $end > $begin) {
                            $balance = trim(urldecode(substr($rawOutput,$begin+9,($end-$begin)-9)));
                        }
                    }       
                } else if ($balance && $balance !== "") {
                    $errorMsg = "NSF, BAL: ".PaycardLib::paycard_moneyFormat($balance);    
                }
            }

            // verify that echo'd fields match our request
            if ($xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == $program
                && $xml->get('IDENTIFIER') && $xml->get('IDENTIFIER') == $identifier
                && $xml->get('AUTHORIZED')
            ) {
                $validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
            } else {
                $validResponse = 4; // response was parsed as XML but fields didn't match
            }
        }

        $resultCode = 0;
        $apprNumber = $xml->get('AUTHORIZATIONCODE');
        $rMsg = '';
        if ($apprNumber != '' && $xml->get('AUTHORIZED') == 'true') {
            $validResponse = 1;
            $resultCode = 1;
            $rMsg = 'Approved';
        } else {
            $rMsg = substr($xml->get_first('ERRORMSG'), 0, 100);
        }
        $finishQ = sprintf("UPDATE PaycardTransactions SET
                                responseDatetime='%s',
                                seconds=%f,
                                commErr=%d,
                                httpCode=%d,
                                validResponse=%d,
                                xResultCode=%d,
                                xApprovalNumber='%s',
                                xResponseCode=%d,
                                xResultMessage='%s',
                                xTransactionID='%s',
                                xBalance=%.2f
                            WHERE paycardTransactionID=%d",
                                $now,
                                $authResult['curlTime'],
                                $authResult['curlErr'],
                                $authResult['curlHTTP'],
                                $normalized,
                                $resultCode,
                                $apprNumber,
                                $resultCode,
                                $rMsg,
                                $apprNumber,
                                $balance,
                                $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        // check for communication errors (any cURL error or any HTTP code besides 200)
        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0') {
                    $CORE_LOCAL->set("boxMsg","No response from processor<br />
                                The transaction did not go through");

                    return PaycardLib::PAYCARD_ERR_PROC;
            }

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

         // check for data errors (any failure to parse response XML or echo'd field mismatch
        if ($validResponse != 1) {
            // invalid server response, we don't know if the transaction was processed (use carbon)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA);
        }

        $amtUsed = $xml->get('CARDAMOUNTUSED');
        if ($amtUsed) {
            $CORE_LOCAL->set("paycard_amount",$amtUsed);
            $correctionQ = sprintf("UPDATE PaycardTransactions SET amount=%f WHERE
                dateID=%s AND refNum='%s'",
                $amtUsed,date("Ymd"),$identifier);
            $dbTrans->query($correctQ);
        }

        // put the parsed response into $CORE_LOCAL so the caller, receipt printer, etc can get the data they need
        $CORE_LOCAL->set("paycard_response",array());
        $CORE_LOCAL->set("paycard_response",$xml->array_dump());
        $temp = $CORE_LOCAL->get("paycard_response");
        $temp["Balance"] = $temp["BALANCE"];
        $CORE_LOCAL->set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('AUTHORIZED') == 'true' && $xml->get('AUTHORIZATIONCODE') != '' 
            && $xml->get_first('ERRORMSG') == ''
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // authorization approved, no error
        }

        // the authorizor gave us some failure code
        // authorization failed, response fields in $_SESSION["paycard_response"]
        $CORE_LOCAL->set("boxMsg","Processor error: ".$errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    private function handleResponseVoid($vdResult)
    {
        global $CORE_LOCAL;
        $xml = new xmlData($vdResult["response"]);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = $CORE_LOCAL->get("CashierNo");
        $laneNo = $CORE_LOCAL->get("laneno");
        $transNo = $CORE_LOCAL->get("transno");
        $transID = $CORE_LOCAL->get("paycard_id");
        $amount = $CORE_LOCAL->get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = 'void';
        $authcode = $this->temp;
        $program = "Gift";

        $validResponse = 0;
        // verify that echo'd fields match our request
        if ($xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == $program
                && $xml->get('AUTHORIZED')
                && $xml->get('AUTHORIZATIONCODE')
                && $xml->get('BALANCE')
        ) {
            $validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
        } else {
            $validResponse = 4; // response was parsed as XML but fields didn't match
        }

        $resultCode = 0;
        $apprNumber = $xml->get('AUTHORIZATIONCODE');
        $rMsg = '';
        if ($apprNumber != '' && $xml->get('AUTHORIZED') == 'true') {
            $validResponse = 1;
            $resultCode = 1;
            $rMsg = 'Voided';
        } else {
            $rMsg = substr($xml->get_first('ERRORMSG'), 0, 100);
        }
        $finishQ = sprintf("UPDATE PaycardTransactions SET
                                responseDatetime='%s',
                                seconds=%f,
                                commErr=%d,
                                httpCode=%d,
                                validResponse=%d,
                                xResultCode=%d,
                                xApprovalNumber='%s',
                                xResponseCode=%d,
                                xResultMessage='%s',
                                xTransactionID='%s',
                                xBalance=%.2f
                            WHERE paycardTransactionID=%d",
                                $now,
                                $authResult['curlTime'],
                                $authResult['curlErr'],
                                $authResult['curlHTTP'],
                                $validResponse,
                                $resultCode,
                                $apprNumber,
                                $resultCode,
                                $rMsg,
                                $apprNumber,
                                $xml->get('BALANCE'),
                                $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        if ($vdResult['curlErr'] != CURLE_OK || $vdResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0') {
                $CORE_LOCAL->set("boxMsg","No response from processor<br />
                                The transaction did not go through");

                return PaycardLib::PAYCARD_ERR_PROC;
            }

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }
        // check for data errors (any failure to parse response XML or echo'd field mismatch)
        // invalid server response, we don't know if the transaction was voided (use carbon)
        if ($validResponse != 1) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA);
        }

        // put the parsed response into $CORE_LOCAL so the caller, receipt printer, etc can get the data they need
        $CORE_LOCAL->set("paycard_response",array());
        $CORE_LOCAL->set("paycard_response",$xml->array_dump());
        $temp = $CORE_LOCAL->get("paycard_response");
        $temp["Balance"] = $temp["BALANCE"];
        $CORE_LOCAL->set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('AUTHORIZED') == 'true' && $xml->get('AUTHORIZATIONCODE') != '' 
            && $xml->get_first('ERRORMSG') == '') {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        // the authorizor gave us some failure code
        $CORE_LOCAL->set("boxMsg","PROCESSOR ERROR: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseBalance($balResult)
    {
        global $CORE_LOCAL;
        $xml = new xmlData($balResult["response"]);
        $program = 'Gift';

        if ($balResult['curlErr'] != CURLE_OK || $balResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0'){
                $CORE_LOCAL->set("boxMsg","No response from processor<br />
                                          The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }

        $CORE_LOCAL->set("paycard_response",array());
        $CORE_LOCAL->set("paycard_response",$xml->array_dump());
        $resp = $CORE_LOCAL->get("paycard_response");
        if (isset($resp["BALANCE"])) {
            $resp["Balance"] = $resp["BALANCE"];
            $CORE_LOCAL->set("paycard_response",$resp);
        }

        // there's less to verify for balance checks, just make sure all the fields are there
        if ($xml->isValid()
            && $xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == $program
            && $xml->get('AUTHORIZED') && $xml->get('AUTHORIZED') == 'true'
            && (!$xml->get('ERRORMSG') || $xml->get_first('ERRORMSG') == '')
            && $xml->get('BALANCE')
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // balance checked, no error
        }

        // the authorizor gave us some failure code
        $CORE_LOCAL->set("boxMsg","Processor error: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    // generate a partially-daily-unique identifier number according to the gift card processor's limitations
    // along with their CashierID field, it will be a daily-unique identifier on the transaction
    private function valutecIdentifier($transID) 
    {
        global $CORE_LOCAL;
        $transNo   = (int)$CORE_LOCAL->get("transno");
        $laneNo    = (int)$CORE_LOCAL->get("laneno");
        // fail if any field is too long (we don't want to truncate, since that might produce a non-unique refnum and cause bigger problems)
        if ($transID > 999 || $transNo > 999 || $laneNo > 99) {
            return "";
        }
        // assemble string
        $ref = "00"; // fill all 10 digits, since they will if we don't and we want to compare with == later
        $ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
        $ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
        $ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);
        return $ref;
    } // valutecIdentifier()
    
    private function getTermID()
    {
        global $CORE_LOCAL;
        if ($CORE_LOCAL->get("training") == 1) {
            return "45095";
        } else {
            return $CORE_LOCAL->get("gcTermID");
        }
    }

    private function getPAN()
    {
        global $CORE_LOCAL;
        if ($CORE_LOCAL->get("training") == 1) {
            return "7018525936200000012";
        } else {
            return $CORE_LOCAL->get("paycard_PAN");
        }
    }

    private function isLive()
    {
        global $CORE_LOCAL;
        if ($CORE_LOCAL->get("training") == 1) {
            return 0;
        } else {
            return 1;
        }
    }

    private function getTrack2()
    {
        global $CORE_LOCAL;
        if ($CORE_LOCAL->get("training") == 1) {
            return "7018525936200000012=68893620";
        } else {
            return $CORE_LOCAL->get("paycard_tr2");
        }
    }
}

