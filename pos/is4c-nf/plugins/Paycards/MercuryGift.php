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
 * Mercury Gift Card processing module
 *
 */

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

define('MERCURY_GTERMINAL_ID',"");
define('MERCURY_GPASSWORD',"");

class MercuryGift extends BasicCCModule 
{
    private $temp;
    protected $SOAPACTION = "http://www.mercurypay.com/GiftTransaction";
    private $second_try;
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
        try {
            $enabled = PaycardDialogs::enabledCheck();

            // error checks based on processing mode
            if (CoreLocal::get("paycard_mode") == PaycardLib::PAYCARD_MODE_VOID) {
                $pan4 = substr($this->getPAN(), -4);
                $trans = array(CoreLocal::get('CashierNo'), CoreLocal::get('laneno'), CoreLocal::get('transno'));
                $result = PaycardDialogs::voidableCheck($pan4, $trans);
                return $this->paycard_void($result,-1,-1,$json);
            }

            // check card data for anything else
            if ($validate) {
                $valid = PaycardDialogs::validateCard(CoreLocal::get('paycard_PAN'), false, false);
            }
        } catch (Exception $ex) {
            $json['output'] = $ex->getMessage();
            return $json;
        }

        // other modes
        switch (CoreLocal::get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                return PaycardLib::setupAuthJson($json);
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                CoreLocal::set("paycard_amount",0);
                CoreLocal::set("paycard_id",CoreLocal::get("LastID")+1); // kind of a hack to anticipate it this way..
                $plugin_info = new Paycards();
                $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgGift.php';

                return $json;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $plugin_info = new Paycards();
                $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgBalance.php';

                return $json;
        } // switch mode
    
        // if we're still here, it's an error
        PaycardLib::paycard_reset();
        $json['output'] = PaycardDialogs::invalidMode();

        return $json;
    }

    /* doSend()
     * Process the paycard request and return
     * an error value as defined in paycardLib.php.
     *
     * On success, return PaycardLib::PAYCARD_ERR_OK.
     * On failure, return anything else and set any
     * error messages to be displayed in
     * CoreLocal::["boxMsg"].
     */
    public function doSend($type)
    {
        $this->second_try = false;
        switch($type) {
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
        switch (CoreLocal::get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $resp = CoreLocal::get("paycard_response");
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>Gift card balance: $" . $resp["Balance"] . "
                                           <p>\"rp\" to print
                                           <br>[enter] to continue</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $ttl = CoreLocal::get("paycard_amount");
                $dept = CoreLocal::get('PaycardDepartmentGift');
                $dept = $dept == '' ? 902 : $dept;
                COREPOS\pos\lib\DeptLib::deptkey($ttl*100, $dept . '0');
                $resp = CoreLocal::get("paycard_response");    
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                $amt = "".(-1*(CoreLocal::get("paycard_amount")));
                $record_id = $this->last_paycard_transaction_id;
                $charflag = ($record_id != 0) ? 'PT' : '';
                $tcode = CoreLocal::get('PaycardsTenderCodeGift');
                $tcode = $tcode == '' ? 'GD' : $tcode;
                TransRecord::addFlaggedTender("Gift Card", $tcode, $amt, $record_id, $charflag);
                $resp = CoreLocal::get("paycard_response");
                CoreLocal::set("boxMsg","<b>Approved</b><font size=-1>
                                           <p>Used: $" . CoreLocal::get("paycard_amount") . "
                                           <br />New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip
                                           <br>[clear] to cancel and void</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
            case PaycardLib::PAYCARD_MODE_VOIDITEM:
                $v = new Void();
                $v->voidid(CoreLocal::get("paycard_id"), array());
                $resp = CoreLocal::get("paycard_response");
                CoreLocal::set("boxMsg","<b>Voided</b><font size=-1>
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
        $this->voidTrans = "";
        $this->voidRef = "";
        $ret = self::ccVoid($transID, $laneNo, $transNo, $json);

        // save the details
        CoreLocal::set("paycard_PAN",$request['PAN']);
        CoreLocal::set("paycard_type",PaycardLib::PAYCARD_TYPE_GIFT);
        if ($lineitem['trans_type'] == "T" && $lineitem['trans_subtype'] == "GD") {
            CoreLocal::set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
        } else {
            CoreLocal::set("paycard_mode",PaycardLib::PAYCARD_MODE_VOIDITEM);
        }
    
        return $ret;
    }

    // END INTERFACE METHODS
    
    private function send_auth($domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }

        // prepare data for the request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $amount = CoreLocal::get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = "";
        $authMethod = "";
        $logged_mode = $mode;
        switch (CoreLocal::get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                if ($amount < 0) {
                    $mode = 'refund';
                    $authMethod = 'Return';
                    $logged_mode = 'Return';
                } else {
                    $mode = 'tender';
                    $authMethod = 'NoNSFSale';  
                    $logged_mode = 'Sale';
                }
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $mode = 'addvalue';
                $authMethod = 'Reload';
                $logged_mode = 'Reload';
                break;
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $mode = 'activate';
                $authMethod = 'Issue';
                $logged_mode = 'Issue';
                break;
            default:
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }
        $termID = $this->getTermID();
        $password = $this->getPw();
        $live = 0;
        $manual = (CoreLocal::get("paycard_manual") ? 1 : 0);
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $identifier = $this->valutecIdentifier($transID); // valutec allows 10 digits; this uses lanenum-transnum-transid since we send cashiernum in another field
        
        /**
          Log transaction in newer table
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
                    'MercuryGift', $identifier, $live, 'Gift', $logged_mode,
                    $amountText, $cardPAN,
                    'Mercury', 'Cardholder', $manual, $now);
        $insR = $dbTrans->query($insQ);
        if ($insR) {
            $this->last_paycard_transaction_id = $dbTrans->insertID();
        } else {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }

        $msgXml = "<?xml version=\"1.0\"".'?'.">
            <TStream>
            <Transaction>
            <IpPort>9100</IpPort>
            <MerchantID>$termID</MerchantID>
            <TranType>PrePaid</TranType>
            <TranCode>$authMethod</TranCode>
            <InvoiceNo>$identifier</InvoiceNo>
            <RefNo>$identifier</RefNo>
            <Memo>CORE POS 1.0.0</Memo>
            <Account>";
        if ($cardTr2) {
            $msgXml .= "<Track2>$cardTr2</Track2>";
        } else {
            $msgXml .= "<AcctNo>$cardPAN</AcctNo>";
        }
        $msgXml .= "</Account>
            <Amount>
            <Purchase>$amountText</Purchase>
            </Amount>
            </Transaction>
            </TStream>";
        

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        if (CoreLocal::get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        } else {
            $this->GATEWAY = "https://$domain/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    private function send_void($domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $amount = CoreLocal::get("paycard_amount");
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
        $log = $dbTrans->fetchRow($search);
        $authcode = $log['xAuthorizationCode'];
        $this->temp = $authcode;
        
        // look up original transaction type
        $sql = "SELECT transType AS mode 
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
        $row = $dbTrans->fetchRow($search);
        $vdMethod = "";
        switch ($row['mode']) {
            case 'tender':
            case 'Sale':
                $vdMethod='VoidSale';
                break;
            case 'refund':
            case 'Return':
                $vdMethod='VoidReturn';
                break;
            case 'addvalue':
            case 'Reload':
                $vdMethod='VoidReload';
                break;
            case 'activate':
            case 'Issue':
                $vdMethod='VoidIssue';
                break;
        }

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
            $this->last_paycard_transaction_id = $dbTrans->insertID();
        } else {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }

        $msgXml = "<?xml version=\"1.0\"".'?'.">
            <TStream>
            <Transaction>
            <IpPort>9100</IpPort>
            <MerchantID>$termID</MerchantID>
            <TranType>PrePaid</TranType>
            <TranCode>$vdMethod</TranCode>
            <InvoiceNo>$identifier</InvoiceNo>
            <RefNo>$authcode</RefNo>
            <Memo>CORE POS 1.0.0</Memo>
            <Account>";
        if ($cardTr2) {
            $msgXml .= "<Track2>$cardTr2</Track2>";
        } else {
            $msgXml .= "<AcctNo>$cardPAN</AcctNo>";
        }
        $msgXml .= "</Account>
            <Amount>
            <Purchase>$amountText</Purchase>
            </Amount>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        if (CoreLocal::get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        } else {
            $this->GATEWAY = "https://$domain/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    private function send_balance($domain="w1.mercurypay.com")
    {
        // prepare data for the request
        $cashierNo = CoreLocal::get("CashierNo");
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $identifier = date('mdHis'); // the balance check itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
        $termID = $this->getTermID();
        $password = $this->getPw();

        $msgXml = "<?xml version=\"1.0\"?>
            <TStream>
            <Transaction>
            <IpPort>9100</IpPort>
            <MerchantID>$termID</MerchantID>
            <TranType>PrePaid</TranType>
            <TranCode>Balance</TranCode>
            <InvoiceNo>$identifier</InvoiceNo>
            <RefNo>$identifier</RefNo>
            <Memo>CORE POS</Memo>
            <Account>";
        if ($cardTr2) {
            $msgXml .= "<Track2>$cardTr2</Track2>";
        } else {
            $msgXml .= "<AcctNo>$cardPAN</AcctNo>";
        }
        $msgXml .= "</Account>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        if (CoreLocal::get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        } else {
            $this->GATEWAY = "https://$domain/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    public function handleResponse($authResult)
    {
        switch(CoreLocal::get("paycard_mode")) {
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
        $resp = $this->desoapify("GiftTransactionResult",
            $authResult["response"]);
        $xml = new xmlData($resp);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $program = 'Gift';
        $identifier = $this->valutecIdentifier($transID); // valutec allows 10 digits; this uses lanenum-transnum-transid since we send cashiernum in another field

        $validResponse = ($xml->isValid()) ? 1 : 0;
        $errorMsg = $xml->get_first("TEXTRESPONSE");
        $balance = $xml->get_first("BALANCE");

        if ($validResponse) {
            // verify that echo'd fields match our request
            if ($xml->get('TRANTYPE') && $xml->get('TRANTYPE') == "PrePaid"
                && $xml->get('INVOICENO') && $xml->get('INVOICENO') == $identifier
                && $xml->get('CMDSTATUS')
            ) {
                $validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
            } else {
                if (!$xml->get('CMDSTATUS')) {
                    $validResponse = -2; // response was parsed as XML but fields didn't match
                } else if (!$xml->get('TRANTYPE')) {
                    $validResponse = -3; // response was parsed as XML but fields didn't match
                } else if (!$xml->get('INVOICENO')) {
                    $validResponse = -4; // response was parsed as XML but fields didn't match
                }
            }
        }

        $normalized = ($validResponse == 0) ? 4 : 0;
        $status = $xml->get('CMDSTATUS');
        $rMsg = $status;
        $resultCode = 0;
        if ($status == 'Approved') {
            $normalized = 1;
            $resultCode = 1;
            $rMsg = 'Approved';
        } else if ($status == 'Declined') {
            $normalized = 2;
            $resultCode = 2;
            $rMsg = 'Declined';
        } else if ($status == 'Error') {
            $normalized = 3;
            $resultCode = 0;
            $rMsg = substr($errorMsg, 0, 100);
        }
        $apprNumber = $xml->get_first('REFNO');

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
                if (!$this->second_try) {
                    $this->second_try = true;

                    return $this->send_auth("w2.backuppay.com");
                } else {
                    CoreLocal::set("boxMsg","No response from processor
                                               <br />The transaction did not go through"
                    );

                    return PaycardLib::PAYCARD_ERR_PROC;
                }
            }

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

         // check for data errors (any failure to parse response XML or echo'd field mismatch
        if ($validResponse != 1) {
            // invalid server response, we don't know if the transaction was processed (use carbon)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA); 
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        CoreLocal::set("paycard_response",array());
        CoreLocal::set("paycard_response",$xml->array_dump());
        $temp = CoreLocal::get("paycard_response");
        $temp["Balance"] = isset($temp['BALANCE']) ? $temp["BALANCE"] : 0;
        CoreLocal::set("paycard_response",$temp);
        /**
          Update authorized amount based on response. If
          the transaction was a refund ("Return") then the
          amount needs to be negative for POS to handle
          it correctly.
        */
        if ($xml->get_first("AUTHORIZE")) {
            CoreLocal::set("paycard_amount",$xml->get_first("AUTHORIZE"));
            if ($xml->get_first('TRANCODE') && $xml->get_first('TRANCODE') == 'Return') {
                CoreLocal::set("paycard_amount",-1*$xml->get_first("AUTHORIZE"));
            }
            $correctionQ = sprintf("UPDATE PaycardTransactions SET amount=%f WHERE
                dateID=%s AND refNum='%s'",
                $xml->get_first("AUTHORIZE"),date("Ymd"),$identifier);
            $dbTrans->query($correctionQ);
        }

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('CMDSTATUS') == 'Approved' && $xml->get('REFNO') != '' ) {
            return PaycardLib::PAYCARD_ERR_OK; // authorization approved, no error
        }

        // the authorizor gave us some failure code
        // authorization failed, response fields in $_SESSION["paycard_response"]
        CoreLocal::set("boxMsg","Processor error: ".$errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseVoid($vdResult)
    {
        $resp = $this->desoapify("GiftTransactionResult",
            $vdResult["response"]);
        $xml = new xmlData($resp);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $amount = CoreLocal::get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = 'void';
        $authcode = $this->temp;
        $program = "Gift";

        $validResponse = 0;
        // verify that echo'd fields match our request
        if ($xml->get('TRANTYPE') 
            && $xml->get('CMDSTATUS')
            && $xml->get('BALANCE')
        ) {
            // response was parsed normally, echo'd fields match, and other required fields are present
            $validResponse = 1;
        } else {
            // response was parsed as XML but fields didn't match
            $validResponse = -2; 
        }

        $normalized = ($validResponse == 0) ? 4 : 0;
        $status = $xml->get('CMDSTATUS');
        if ($status == 'Approved') {
            $normalized = 1;
            $resultCode = 1;
            $rMsg = 'Approved';
        } else if ($status == 'Declined') {
            $normalized = 2;
            $resultCode = 2;
            $rMsg = 'Declined';
        } else if ($status == 'Error') {
            $normalized = 3;
            $resultCode = 0;
            $rMsg = substr($xml->get_first('TEXTRESPONSE'), 0, 100);
        }
        $apprNumber = $xml->get_first('REFNO');
        $finishQ = sprintf("UPDATE PaycardTransactions SET
                                responseDatetime='%s',
                                seconds=%f,
                                curlErr=%d,
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
                                $xml->get('BALANCE'),
                                $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        if ($vdResult['curlErr'] != CURLE_OK || $vdResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0'){
                if (!$this->second_try){
                    $this->second_try = true;

                    return $this->send_void("w2.backuppay.com");
                } else {
                    CoreLocal::set("boxMsg","No response from processor<br />
                                The transaction did not go through");

                    return PaycardLib::PAYCARD_ERR_PROC;
                }
            }
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }

        // check for data errors (any failure to parse response XML or echo'd field mismatch)
        if ($validResponse != 1) {
            // invalid server response, we don't know if the transaction was voided (use carbon)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA);
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        CoreLocal::set("paycard_response",array());
        CoreLocal::set("paycard_response",$xml->array_dump());
        $temp = CoreLocal::get("paycard_response");
        $temp["Balance"] = isset($temp['BALANCE']) ? $temp["BALANCE"] : 0;
        CoreLocal::set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('CMDSTATUS') == 'Approved' && $xml->get('REFNO') != '' ) {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        // the authorizor gave us some failure code
        CoreLocal::set("boxMsg","PROCESSOR ERROR: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseBalance($balResult)
    {
        $resp = $this->desoapify("GiftTransactionResult",
            $balResult["response"]);
        $xml = new xmlData($resp);
        $program = 'Gift';

        if ($balResult['curlErr'] != CURLE_OK || $balResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0'){
                if (!$this->second_try) {
                    $this->second_try = true;

                    return $this->send_balance("w2.backuppay.com");
                } else {
                    CoreLocal::set("boxMsg","No response from processor<br />
                                The transaction did not go through");

                    return PaycardLib::PAYCARD_ERR_PROC;
                }
            }
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }

        CoreLocal::set("paycard_response",array());
        CoreLocal::set("paycard_response",$xml->array_dump());
        $resp = CoreLocal::get("paycard_response");
        if (isset($resp["BALANCE"])) {
            $resp["Balance"] = $resp["BALANCE"];
            CoreLocal::set("paycard_response",$resp);
        }

        // there's less to verify for balance checks, just make sure all the fields are there
        if($xml->isValid() 
           && $xml->get('TRANTYPE') && $xml->get('TRANTYPE') == 'PrePaid'
           && $xml->get('CMDSTATUS') && $xml->get('CMDSTATUS') == 'Approved'
           && $xml->get('BALANCE')
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // balance checked, no error
        }

        // the authorizor gave us some failure code
        CoreLocal::set("boxMsg","Processor error: ".$xml->get_first("TEXTRESPONSE"));

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    // generate a partially-daily-unique identifier number according to the gift card processor's limitations
    // along with their CashierID field, it will be a daily-unique identifier on the transaction
    private function valutecIdentifier($transID) 
    {
        $transNo   = (int)CoreLocal::get("transno");
        $laneNo    = (int)CoreLocal::get("laneno");
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
        if (CoreLocal::get("training") == 1) {
            return "595901";
        } else {
            return MERCURY_GTERMINAL_ID;
        }
    }

    private function getPw()
    {
        if (CoreLocal::get("training") == 1) {
            return "xyz";
        } else {
            return MERCURY_GPASSWORD;
        }
    }

    private function getPAN()
    {
        if (CoreLocal::get("training") == 1) {
            return "6050110000000296951";
        } else {
            return CoreLocal::get("paycard_PAN");
        }
    }

    private function getTrack2()
    {
        if (CoreLocal::get("training") == 1) {
            return false;
        } else {
            return CoreLocal::get("paycard_tr2");
        }
    }
}

