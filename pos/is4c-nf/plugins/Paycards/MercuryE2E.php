<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
2014-04-01:
 Mercury has a public webservices implementation on github:
 https://github.com/MercuryPay

 If they can post one, I think we can too. No new information about
 the service is being exposed.
*/

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("xmlData")) include_once(realpath(dirname(__FILE__)."/lib/xmlData.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

class MercuryE2E extends BasicCCModule 
{

    private $voidTrans;
    private $voidRef;
    protected $SOAPACTION = "http://www.mercurypay.com/CreditTransaction";
    private $second_try;

    const PRIMARY_URL = 'w1.mercurypay.com';
    const BACKUP_URL = 'w2.backuppay.com';

    public function handlesType($type)
    {
        if ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Updated for E2E
      Status: done
    */
    public function entered($validate,$json)
    {
        // error checks based on card type
        if (CoreLocal::get("CCintegrate") != 1) { // credit card integration must be enabled
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                                                         "Card Integration Disabled",
                                                         "Please process credit cards in standalone",
                                                         "[clear] to cancel"
            );

            return $json;
        }

        // error checks based on processing mode
        switch( CoreLocal::get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_VOID:
                // use the card number to find the trans_id
                $dbTrans = PaycardLib::paycard_db();
                $today = date('Ymd');
                $pan4 = substr($this->$trans_pan['pan'],-4);
                $cashier = CoreLocal::get("CashierNo");
                $lane = CoreLocal::get("laneno");
                $trans = CoreLocal::get("transno");
                $sql = 'SELECT transID
                        FROM PaycardTransactions
                        WHERE dateID=' . $today . '
                            AND empNo=' . $cashier . '
                            AND registerNo=' . $lane . '
                            AND transNo=' . $trans . '
                            AND PAN LIKE \'%' . $pan4 . '\'';
                if (!$dbTrans->table_exists('PaycardTransactions')) {
                    $sql = "SELECT transID,cashierNo,laneNo,transNo FROM efsnetRequest WHERE "
                        .$dbTrans->identifier_escape('date')."='".$today."' AND (PAN LIKE '%".$pan4."')"; 
                }
                $search = PaycardLib::paycard_db_query($sql, $dbTrans);
                $num = PaycardLib::paycard_db_num_rows($search);
                if ($num < 1) {
                    PaycardLib::paycard_reset();
                    $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                                 "Card Not Used",
                                                                 "That card number was not used in this transaction",
                                                                 "[clear] to cancel"
                    );

                    return $json;
                } else if ($num > 1) {
                    PaycardLib::paycard_reset();
                    $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                                 "Multiple Uses",
                                                                 "That card number was used more than once in this transaction; select the payment and press VOID",
                                                                 "[clear] to cancel"
                    );

                    return $json;
                }
                $payment = PaycardLib::paycard_db_fetch_row($search);

                return $this->paycard_void($payment['transID'],$lane,$trans,$json);
                break;

            case PaycardLib::PAYCARD_MODE_AUTH:
                // set initial variables
                //Database::getsubtotals();
                $e2e = $this->parseEncBlock(CoreLocal::get('paycard_PAN'));
                if (empty($e2e['Block']) || empty($e2e['Key'])){
                    PaycardLib::paycard_reset();
                    $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                                 "Swipe Error",
                                                                 "Error reading card. Swipe again.",
                                                                 "[clear] to cancel"
                    );
                    UdpComm::udpSend('termReset');

                    return $json;
                }
                if (CoreLocal::get("paycard_amount") == 0) {
                    CoreLocal::set("paycard_amount",CoreLocal::get("amtdue"));
                }
                CoreLocal::set("paycard_id",CoreLocal::get("LastID")+1); // kind of a hack to anticipate it this way..
                $plugin_info = new Paycards();
                $json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgAuth.php';
                $json['output'] = '';

                return $json;
                break;
        } // switch mode
    
        // if we're still here, it's an error
        PaycardLib::paycard_reset();
        $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                     "Invalid Mode",
                                                     "This card type does not support that processing mode",
                                                     "[clear] to cancel"
        );

        return $json;
    }

    /**
      Updated for E2E
    */
    public function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        $this->voidTrans = "";
        $this->voidRef = "";
        // situation checking
        if (CoreLocal::get("CCintegrate") != 1) { // credit card integration must be enabled
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Card Integration Disabled",
                                                         "Please process credit cards in standalone",
                                                         "[clear] to cancel"
            );

            return $json;
        }
    
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        $today = date('Ymd');
        $cashier = CoreLocal::get("CashierNo");
        $lane = CoreLocal::get("laneno");
        $trans = CoreLocal::get("transno");
        if ($laneNo != -1) $lane = $laneNo;
        if ($transNo != -1) $trans = $transNo;
    
        // look up the request using transID (within this transaction)
        $sql = "SELECT live,
                    PAN,
                    transType AS mode,
                    amount,
                    name
                FROM PaycardTransactions
                WHERE dateID=" . $today . "
                    AND empNo=" . $cashier . "
                    AND registerNo=" . $lane . "
                    AND transNo=" . $trans . " 
                    AND transID=" . $transID;
        // @deprecated table 5May14
        if (!$dbTrans->table_exists('PaycardTransactions')) {
            $sql = "SELECT live,PAN,mode,amount,name FROM efsnetRequest 
                WHERE ".$dbTrans->identifier_escape('date')."='".$today."' AND cashierNo=".$cashier." AND 
                laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
        }
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $num = PaycardLib::paycard_db_num_rows($search);
        if ($num < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Card request not found, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($num > 1) {
            PaycardLib::paycard_reset();
            $json['output'] =  PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                          "Internal Error",
                                                          "Card request not distinct, unable to void",
                                                          "[clear] to cancel"
            );

            return $json;
        }
        $request = PaycardLib::paycard_db_fetch_row($search);

        // look up the response
        $sql = "SELECT commErr,
                    httpCode,
                    validResponse,
                    xResultCode AS xResponseCode,
                    xTransactionID
                FROM PaycardTransactions 
                WHERE dateID=" . $today . " 
                    AND empNo=" . $cashier . "
                    AND registerNo=" . $lane ."
                    AND transNo=" . $trans . "
                    AND transID=" . $transID;
        // @deprecated table 5May14
        if (!$dbTrans->table_exists('PaycardTransactions')) {
            $sql = "SELECT commErr,httpCode,validResponse,xResponseCode,
                xTransactionID FROM efsnetResponse WHERE ".$dbTrans->identifier_escape('date')."='".$today."' 
                AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
        }
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $num = PaycardLib::paycard_db_num_rows($search);
        if ($num < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Card response not found, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($num > 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Card response not distinct, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        }
        $response = PaycardLib::paycard_db_fetch_row($search);

        // look up any previous successful voids
        $sql = "SELECT transID 
                FROM PaycardTransactions 
                WHERE dateID=" . $today . "
                    AND empNo=" . $cashier . "
                    AND registerNo=" . $lane . "
                    AND transNo=" . $trans . "
                    AND transID=" . $transID . "
                    AND transType='VOID'
                    AND xResultCode=1";
        // @deprecated table 5May14
        if (!$dbTrans->table_exists('PaycardTransactions')) {
            $sql = "SELECT transID FROM efsnetRequestMod WHERE "
                    .$dbTrans->identifier_escape('date')."=".$today
                    ." AND cashierNo=".$cashier." AND laneNo=".$lane
                    ." AND transNo=".$trans." AND transID=".$transID
                    ." AND mode='void' AND xResponseCode=0";
        }
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $voided = PaycardLib::paycard_db_num_rows($search);
        if( $voided > 0) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Unable to Void",
                                                         "Card transaction already voided",
                                                         "[clear] to cancel"
            );

            return $json;
        }

        // look up the transaction tender line-item
        $sql = "SELECT trans_type,
                    trans_subtype,
                    trans_status,
                    voided
                FROM localtemptrans 
                WHERE trans_id=" . $transID;
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $num = PaycardLib::paycard_db_num_rows($search);
        if ($num < 1) {
            $sql = "SELECT * FROM localtranstoday WHERE trans_id=".$transID." and emp_no=".$cashier
                ." and register_no=".$lane." and trans_no=".$trans
                ." AND datetime >= " . $dbTrans->curdate();
            $search = PaycardLib::paycard_db_query($sql, $dbTrans);
            $num = PaycardLib::paycard_db_num_rows($search);
            if ($num != 1){
                PaycardLib::paycard_reset();
                $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                             "Internal Error",
                                                             "Transaction item not found, unable to void",
                                                             "[clear] to cancel"
                );

                return $json;
            }
        } else if ($num > 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Transaction item not distinct, unable to void",
                                                         "[clear] to cancel"
            );

            return $json;
        }
        $lineitem = PaycardLib::paycard_db_fetch_row($search);

        // make sure the payment is applicable to void
        if ($response['commErr'] != 0 || $response['httpCode'] != 200 || $response['validResponse'] != 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Unable to Void",
                                                         "Card transaction not successful",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($request['live'] != PaycardLib::paycard_live(PaycardLib::PAYCARD_TYPE_ENCRYPTED)) {
            // this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
            // I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Unable to Void",
                                                         "Processor platform mismatch",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if( $response['xResponseCode'] != 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Unable to Void",
                                                         "Card transaction not approved",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if( $response['xTransactionID'] < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Invalid reference number",
                                                         "[clear] to cancel"
            );

            return $json;
        }

        // make sure the tender line-item is applicable to void
        if ($lineitem['trans_type'] != "T" || ($lineitem['trans_subtype'] != "CC" && $lineitem['trans_subtype'] != 'DC'
            && $lineitem['trans_subtype'] != 'EF' && $lineitem['trans_subtype'] != 'EC' && $lineitem['trans_subtype'] != 'AX') ) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Authorization and tender records do not match $transID",
                                                         "[clear] to cancel"
            );

            return $json;
        } else if ($lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                         "Internal Error",
                                                         "Void records do not match",
                                                         "[clear] to cancel"
            );

            return $json;
        }
    
        // save the details
        CoreLocal::set("paycard_amount",(($request['mode']=='Return') ? -1 : 1) * $request['amount']);
        CoreLocal::set("paycard_id",$transID);
        CoreLocal::set("paycard_trans",$cashier."-".$lane."-".$trans);
        CoreLocal::set("paycard_type",PaycardLib::PAYCARD_TYPE_ENCRYPTED);
        CoreLocal::set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
        CoreLocal::set("paycard_name",$request['name']);
    
        // display FEC code box
        CoreLocal::set("inputMasked",1);
        $plugin_info = new Paycards();
        $json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgVoid.php';

        return $json;
    }

    public function handleResponse($authResult)
    {
        switch(CoreLocal::get("paycard_mode")){
            case PaycardLib::PAYCARD_MODE_AUTH:
                return $this->handleResponseAuth($authResult);
            case PaycardLib::PAYCARD_MODE_VOID:
                return $this->handleResponseVoid($authResult);
        }
    }

    /**
      Updated for E2E
    */
    private function handleResponseAuth($authResult)
    {
        $resp = $this->desoapify("CreditTransactionResult",
            $authResult["response"]);
        $xml = new xmlData($resp);

        $dbTrans = PaycardLib::paycard_db();

        // prepare some fields to store the parsed response; we'll add more as we verify it
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        // mercury truncates the reference number in the response; recalculate so it
        // actually matches
        $refNum = $this->refnum($transID);

        $sqlColumns =
            $dbTrans->identifier_escape('date').",cashierNo,laneNo,transNo,transID," .
            $dbTrans->identifier_escape('datetime').",refNum," .
            "seconds,commErr,httpCode";
        $sqlValues =
            sprintf("%d,%d,%d,%d,%d,",  $today, $cashierNo, $laneNo, $transNo, $transID) .
            sprintf("'%s','%s',",            $now, $refNum ) .
            sprintf("%f,%d,%d",         $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);
        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("CMDSTATUS");
        if ($responseCode) {
            // map response status to 0/1/2 for compatibility
            if ($responseCode == "Approved") $responseCode=1;
            elseif ($responseCode == "Declined") $responseCode=2;
            elseif ($responseCode == "Error") $responseCode=0;
            $sqlColumns .= ",xResponseCode";
            $sqlValues .= sprintf(",%d",$responseCode);
        } else {
            $validResponse = -3;
        }
        $resultCode = $xml->get("DSIXRETURNCODE");
        if ($resultCode) {
            $sqlColumns .= ",xResultCode";
            $sqlValues .= sprintf(",%d",$resultCode);
        }
        $resultMsg = $xml->get_first("CMDSTATUS");
        $rMsg = $resultMsg;
        if ($resultMsg) {
            $sqlColumns .= ",xResultMessage";
            $rMsg = $resultMsg;
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }
            $aNum = $xml->get("AUTHCODE");
            if ($aNum) {
                $rMsg .= ' '.$aNum;
            }
            $sqlValues .= sprintf(",'%s'",$rMsg);
        }
        $xTransID = $xml->get("REFNO");
        if ($xTransID) {
            $sqlColumns .= ",xTransactionID";
            $xTransID = substr($xTransID, 0, 12);
            $sqlValues .= sprintf(",'%s'", $xTransID);
        } else {
            $validResponse = -3;
        }
        $apprNumber = $xml->get("AUTHCODE");
        if ($apprNumber) {
            $sqlColumns .= ",xApprovalNumber";
            $sqlValues .= sprintf(",'%s'",$apprNumber);
        }
        $sqlColumns .= ",validResponse";
        $sqlValues .= sprintf(",%d",$validResponse);

        $cardtype = CoreLocal::get("CacheCardType");
        $ebtbalance = 0;
        if ($xml->get_first("Balance")) {
            switch($cardtype) {
                case 'EBTFOOD':
                    CoreLocal::set('EbtFsBalance', $xml->get_first('Balance'));
                    $ebtbalance = $xml->get_first('Balance');
                    break;
                case 'EBTCASH':
                    CoreLocal::set('EbtCaBalance', $xml->get_first('Balance'));
                    $ebtbalance = $xml->get_first('Balance');
                    break;
            }
        }

        $table_def = $dbTrans->table_definition('efsnetResponse');
        if (isset($table_def['efsnetRequestID'])) {
            $sqlColumns .= ', efsnetRequestID';
            $sqlValues .= sprintf(', %d', $this->last_req_id);
        }

        $sql = "INSERT INTO efsnetResponse (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
        if ($dbTrans->table_exists('efsnetResponse')) {
            PaycardLib::paycard_db_query($sql, $dbTrans);
        }

        /**
          Log transaction in newer table
        */
        // put normalized value in validResponse column
        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($responseCode == 1) {
            $normalized = 1;
        } else if ($responseCode == 2) {
            $normalized = 2;
        } else if ($responseCode == 0) {
            $normalized = 3;
        }
        $finishQ = sprintf("UPDATE PaycardTransactions
                            SET responseDatetime='%s',
                                seconds=%f,
                                commErr=%d,
                                httpCode=%d,
                                validResponse=%d,
                                xResultCode=%d,
                                xApprovalNumber='%s',
                                xResponseCode=%d,
                                xResultMessage='%s',
                                xTransactionID='%s',
                                xBalance='%s',
                                xToken='%s',
                                xProcessorRef='%s',
                                xAcquirerRef='%s'
                            WHERE paycardTransactionID=%d",
                            $now,
                            $authResult['curlTime'],
                            $authResult['curlErr'],
                            $authResult['curlHTTP'],
                            $normalized,
                            $responseCode,
                            $apprNumber,
                            $resultCode,
                            $rMsg,
                            $xTransID,
                            $ebtbalance,
                            $xml->get_first('RECORDNO'),
                            $xml->get_first('PROCESSDATA'),
                            $xml->get_first('ACQREFDATA'),
                            $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        if ($responseCode == 1) {
            $amt = $xml->get_first("AUTHORIZE");
            if ($amt != abs(CoreLocal::get("paycard_amount"))) {
                $sql = sprintf("UPDATE efsnetRequest SET amount=%f WHERE "
                    .$dbTrans->identifier_escape('date')."=%d 
                    AND cashierNo=%d AND laneNo=%d AND transNo=%d
                    AND transID=%d",
                    $amt,$today, $cashierNo, $laneNo, $transNo, $transID);
                if ($dbTrans->table_exists('efsnetRequest')) {
                    PaycardLib::paycard_db_query($sql, $dbTrans);
                }

                $upQ = sprintf('UPDATE PaycardTransactions
                                SET amount=%.2f
                                WHERE paycardTransactionID=%d',
                                $amt, $this->last_paycard_transaction_id);
                $dbTrans->query($upQ);

                CoreLocal::set("paycard_amount",$amt);
                CoreLocal::set("paycard_partial",True);
                UdpComm::udpSend('goodBeep');
            }
        }

        $tokenSql = sprintf("INSERT INTO efsnetTokens (expireDay, refNum, token, processData, acqRefData) 
                VALUES (%s,'%s','%s','%s','%s')",
            $dbTrans->now(),
            $refNum, $xml->get_first("RECORDNO"),
            $xml->get_first("PROCESSDATA"),
            $xml->get_first("ACQREFDATA")
        );
        $token = $xml->get_first('RECORDNO');
        if (!empty($token)) {
            PaycardLib::paycard_db_query($tokenSql, $dbTrans);
        }

        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200) {
            if (!$this->second_try){
                $this->second_try = true;

                return $this->send_auth("w2.backuppay.com");
            } else if ($authResult['curlHTTP'] == '0') {
                CoreLocal::set("boxMsg","No response from processor<br />
                            The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            } else {
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
            }
        } else if ($this->second_try && $authResult['curlTime'] < 10 && CoreLocal::get('MercurySwitchUrls') <= 0) {
            CoreLocal::set('MercurySwitchUrls', 5);
        }

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                if (substr(CoreLocal::get('CacheCardType'), 0, 3) == 'EBT' && $ebtbalance > 0 && $ebtbalance < $xml->get_first('AUTHORIZE')) {
                    // if EBT is declined but lists a balance less than the
                    // requested authorization, it may be possible to
                    // charge the card for a less amount. different return
                    // it to try a less amount immediatley without making
                    // the customer re-enter information
                    CoreLocal::set('PaycardRetryBalanceLimit', sprintf('%.2f', $ebtbalance));    
                    CoreLocal::set('paycard_amount', $ebtbalance);
                    TransRecord::addcomment("");
                    CoreLocal::set('paycard_id', CoreLocal::get('paycard_id') + 1);

                    return PaycardLib::PAYCARD_ERR_NSF_RETRY;
                }
                UdpComm::udpSend('termReset');
                CoreLocal::set('ccTermState','swipe');
                // intentional fallthrough
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                CoreLocal::set("boxMsg","Error: $texts");
                TransRecord::addcomment("");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
                TransRecord::addcomment("");    
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
      Updated for E2E
    */
    private function handleResponseVoid($authResult)
    {
        $resp = $this->desoapify("CreditTransactionResult",
            $authResult["response"]);
        $xml = new xmlData($resp);
        $dbTrans = PaycardLib::paycard_db();

        // prepare some fields to store the parsed response; we'll add more as we verify it
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $amount = CoreLocal::get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $refNum = $this->refnum($transID);

        // prepare some fields to store the request and the parsed response; we'll add more as we verify it
        $sqlColumns =
            $dbTrans->identifier_escape('date').",cashierNo,laneNo,transNo,transID,".
            $dbTrans->identifier_escape('datetime').
            ",origAmount,mode,altRoute," .
            "seconds,commErr,httpCode";
        $sqlValues =
            sprintf("%d,%d,%d,%d,%d,'%s',",  $today, $cashierNo, $laneNo, $transNo, $transID, $now) .
            sprintf("%s,'%s',%d,",  $amountText, "VOID", 0) .
            sprintf("%f,%d,%d", $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);

        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("CMDSTATUS");
        if ($responseCode) {
            // map response status to 0/1/2 for compatibility
            if ($responseCode == "Approved") $responseCode=1;
            elseif ($responseCode == "Declined") $responseCode=2;
            elseif ($responseCode == "Error") $responseCode=0;
            $sqlColumns .= ",xResponseCode";
            $sqlValues .= sprintf(",%d",$responseCode);
        } else {
            $validResponse = -3;
        }
        $resultCode = $xml->get_first("DSIXRETURNCODE");
        if ($resultCode) {
            $sqlColumns .= ",xResultCode";
            $sqlValues .= sprintf(",%d",$resultCode);
        }
        $resultMsg = $xml->get_first("CMDSTATUS");
        if ($resultMsg) {
            $sqlColumns .= ",xResultMessage";
            $rMsg = $resultMsg;
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }
            $sqlValues .= sprintf(",'%s'",$rMsg);
        }
        $sqlColumns .= ",origTransactionID";
        $sqlValues .= sprintf(",'%s'",$this->voidTrans);
        $sqlColumns .= ",origRefNum";
        $sqlValues .= sprintf(",'%s'",$this->voidRef);

        $sqlColumns .= ",validResponse";
        $sqlValues .= sprintf(",%d",$validResponse);

        $sql = "INSERT INTO efsnetRequestMod (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
        if ($dbTrans->table_exists('efsnetRequestMod')) {
            PaycardLib::paycard_db_query($sql, $dbTrans);
        }

        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($responseCode == 1) {
            $normalized = 1;
        } else if ($responseCode == 2) {
            $normalized = 2;
        } else if ($responseCode == 0) {
            $normalized = 3;
        }
        $finishQ = sprintf("UPDATE PaycardTransactions
                            SET responseDatetime='%s',
                                seconds=%f,
                                commErr=%d,
                                httpCode=%d,
                                validResponse=%d,
                                xResultCode=%d,
                                xResponseCode=%d,
                                xResultMessage='%s',
                                xToken='%s',
                                xProcessorRef='%s',
                                xAcquirerRef='%s'
                            WHERE paycardTransactionID=%d",
                            $now,
                            $authResult['curlTime'],
                            $authResult['curlErr'],
                            $authResult['curlHTTP'],
                            $normalized,
                            $responseCode,
                            $resultCode,
                            $rMsg,
                            $xml->get_first('RECORDNO'),
                            $xml->get_first('PROCESSDATA'),
                            $xml->get_first('ACQREFDATA'),
                            $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        $tokenRef = $xml->get_first("INVOICENO");
        $sql = sprintF("DELETE FROM efsnetTokens WHERE refNum='%s'",$tokenRef);
        if ($dbTrans->table_exists('efsnetTokens')) {
            PaycardLib::paycard_db_query($sql, $dbTrans);
        }

        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0') {
                CoreLocal::set("boxMsg","No response from processor<br />
                            The transaction did not go through");

                return PaycardLib::PAYCARD_ERR_PROC;
            } 

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

        switch (strtoupper($xml->get("CMDSTATUS"))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // if declined, try again with a regular Void op
                // and no reversal information
                $skipReversal = CoreLocal::get("MercuryE2ESkipReversal");
                if ($skipReversal == true) {
                    CoreLocal::set("MercuryE2ESkipReversal", false);
                } else {
                    return $this->send_void(true);
                }
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                CoreLocal::set("boxMsg","Error: $texts");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    public function cleanup($json)
    {
        switch (CoreLocal::get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                CoreLocal::set("autoReprint",1);
                $ttl = CoreLocal::get("paycard_amount");
                PrehLib::deptkey($ttl*100,9020);
                $bal = CoreLocal::get('GiftBalance');
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $bal . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $bal = CoreLocal::get('DatacapBalanceCheck');
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>Card balance: $" . $bal . "
                                           <p>\"rp\" to print
                                           <br>[enter] to continue</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                // cast to string. tender function expects string input
                // numeric input screws up parsing on negative values > $0.99
                $amt = "".(-1*(CoreLocal::get("paycard_amount")));
                $type = CoreLocal::get("CacheCardType");
                $t_type = 'CC';
                if (CoreLocal::get('paycard_issuer') == 'American Express') {
                    $t_type = 'AX';
                }

                $tender_code = 'CC';
                $tender_description = 'Credit Card';
                $db = Database::pDataConnect();
                $lookup = $db->prepare('SELECT TenderName,
                                            TenderCode
                                        FROM tenders
                                        WHERE TenderCode = ?');
                /**
                  Lookup user-configured tender
                  Failover to defaults if tender does not exist
                  Since we already have an authorization at this point,
                  adding a default tender record to the transaction
                  is better than issuing an error message
                */
                switch ($type) {
                    case 'DEBIT':
                        $args = array(CoreLocal::get('PaycardsTenderCodeDebit'));
                        $found = $db->execute($lookup, $args);
                        if ($found == false || $db->num_rows($found) == 0) {
                            $tender_code = 'DC';
                            $tender_description = 'Debit Card';
                        } else {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                        break;
                    case 'EBTCASH':
                        $args = array(CoreLocal::get('PaycardsTenderCodeEbtCash'));
                        $found = $db->execute($lookup, $args);
                        if ($found == false || $db->num_rows($found) == 0) {
                            $tender_code = 'EC';
                            $tender_description = 'EBT Cash';
                        } else {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                        break;
                    case 'EBTFOOD':
                        $args = array(CoreLocal::get('PaycardsTenderCodeEbtFood'));
                        $found = $db->execute($lookup, $args);
                        if ($found == false || $db->num_rows($found) == 0) {
                            $tender_code = 'EF';
                            $tender_description = 'EBT Food';
                        } else {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                        // extra tax exemption steps
                        TransRecord::addfsTaxExempt();
                        CoreLocal::set("fntlflag",0);
                        Database::setglobalvalue("FntlFlag", 0);
                        break;
                    case 'EMV':
                        $args = array(CoreLocal::get('PaycardsTenderCodeEmv'));
                        $found = $db->execute($lookup, $args);
                        if ($found == false || $db->num_rows($found) == 0) {
                            $tender_code = 'CC';
                            $tender_description = 'Credit';
                        } else {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                        break;
                    case 'CREDIT':
                    default:
                        $args = array(CoreLocal::get('PaycardsTenderCodeCredit'));
                        $found = $db->execute($lookup, $args);
                        if ($found == false || $db->num_rows($found) == 0) {
                            $tender_code = 'CC';
                            $tender_description = 'Credit Card';
                        } else {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                        break;
                }

                /**
                  Now look up card-issuer specific overrides, if any
                */
                if (CoreLocal::get('PaycardsTenderCodeVisa') && CoreLocal::get('paycard_issuer') == 'Visa') {
                        $args = array(CoreLocal::get('PaycardsTenderCodeVisa'));
                        $found = $db->execute($lookup, $args);
                        if ($found && $db->num_rows($found) > 0) {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                } elseif (CoreLocal::get('PaycardsTenderCodeMC') && CoreLocal::get('paycard_issuer') == 'MasterCard') {
                        $args = array(CoreLocal::get('PaycardsTenderCodeMC'));
                        $found = $db->execute($lookup, $args);
                        if ($found && $db->num_rows($found) > 0) {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                } elseif (CoreLocal::get('PaycardsTenderCodeDiscover') && CoreLocal::get('paycard_issuer') == 'Discover') {
                        $args = array(CoreLocal::get('PaycardsTenderCodeDiscover'));
                        $found = $db->execute($lookup, $args);
                        if ($found && $db->num_rows($found) > 0) {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                } elseif (CoreLocal::get('PaycardsTenderCodeAmex') && CoreLocal::get('paycard_issuer') == 'American Express') {
                        $args = array(CoreLocal::get('PaycardsTenderCodeAmex'));
                        $found = $db->execute($lookup, $args);
                        if ($found && $db->num_rows($found) > 0) {
                            $row = $db->fetch_row($found);
                            $tender_code = $row['TenderCode'];
                            $tender_description = $row['TenderName'];
                        }
                }

                // if the transaction has a non-zero paycardTransactionID,
                // include it in the tender line
                $record_id = $this->last_paycard_transaction_id;
                $charflag = ($record_id != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender($tender_description, $tender_code, $amt, $record_id, $charflag);

                $appr_type = 'Approved';
                if (CoreLocal::get('paycard_partial')){
                    $appr_type = 'Partial Approval';
                } elseif (CoreLocal::get('paycard_amount') == 0) {
                    $appr_type = 'Declined';
                    $json['receipt'] = 'ccDecline';
                }
                CoreLocal::set('paycard_partial', false);

                $isCredit = (CoreLocal::get('CacheCardType') == 'CREDIT' || CoreLocal::get('CacheCardType') == '') ? true : false;
                $needSig = (CoreLocal::get('paycard_amount') > CoreLocal::get('CCSigLimit') || CoreLocal::get('paycard_amount') < 0) ? true : false;
                if (($isCredit || CoreLocal::get('EmvSignature') === true) && $needSig) {
                    CoreLocal::set("boxMsg",
                            "<b>$appr_type</b>
                            <font size=-1>
                            <p>Please verify cardholder signature
                            <p>[enter] to continue
                            <br>\"rp\" to reprint slip
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                    if (CoreLocal::get('PaycardsSigCapture') != 1) {
                        $json['receipt'] = 'ccSlip';
                    }
                } else {
                    CoreLocal::set("boxMsg",
                            "<b>$appr_type</b>
                            <font size=-1>
                            <p>No signature required
                            <p>[enter] to continue
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                } 
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
                $v = new Void();
                $v->voidid(CoreLocal::get("paycard_id"), array());
                // advanced ID to the void line
                CoreLocal::set('paycard_id', CoreLocal::get('paycard_id')+1);
                CoreLocal::set("boxMsg","<b>Voided</b>
                                           <p><font size=-1>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
        }

        return $json;
    }

    public function doSend($type)
    {
        $this->second_try = false;
        switch ($type) {
            case PaycardLib::PAYCARD_MODE_AUTH: 
                return $this->send_auth();
            case PaycardLib::PAYCARD_MODE_VOID: 
                return $this->send_void(); 
            default:
                PaycardLib::paycard_reset();
                return $this->setErrorMsg(0);
        }
    }

    /**
      Updated for E2E
      Status: Should be functional once device is available
    */
    private function send_auth($domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans) {
            PaycardLib::paycard_reset();
            // database error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $amount = CoreLocal::get("paycard_amount");
        $type = CoreLocal::get("CacheCardType");
        if ($type == "CREDIT") $type = "Credit";
        elseif ($type == "DEBIT") $type = "Debit";
        elseif ($type == "") $type = "Credit";
        $cashback = 0;
        if (($type == "Debit" || $type == "EBTCASH") && $amount > CoreLocal::get("amtdue")) {
            $cashback = $amount - CoreLocal::get("amtdue");
            $amount = CoreLocal::get("amtdue");
        }
        $amountText = number_format(abs($amount), 2, '.', '');
        $cashbackText = number_format(abs($cashback), 2, '.', '');
        $mode = (($amount < 0) ? 'Return' : 'Sale');
        if (CoreLocal::get("paycard_voiceauthcode") != "") {
            $mode = "VoiceAuth";
        } else if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
            $mode = "Voucher";
        }
        $logged_mode = $type."_".$mode;
        $manual = (CoreLocal::get("paycard_keyed")===True ? 1 : 0);
        $refNum = $this->refnum($transID);
        $this->last_ref_num = $refNum;
        $live = 1;
        if (CoreLocal::get("training") != 0 || CoreLocal::get("CashierNo") == 9999) {
            $live = 0;
        }
        $termID = $this->getTermID();
        $password = $this->getPw();
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }
        $e2e = $this->parseEncBlock(CoreLocal::get("paycard_PAN"));
        $pin = $this->parsePinBlock(CoreLocal::get("CachePinEncBlock"));
        $cardIssuer = $e2e['Issuer'];
        CoreLocal::set('paycard_issuer',$e2e['Issuer']);
        $cardName = $e2e['Name'];
        $cardPANmasked = "XXXX";
        if (strlen($e2e['Last4']) == 4) {
            $cardPANmasked = '************' . $e2e['Last4'];
        }
        CoreLocal::set('paycard_partial',False);
        
        $sendPAN = 0;
        $sendExp = 0;
        $sendTr1 = 0;
        $sendTr2 = 1;

        $cardName = str_replace("\\",'',$cardName);
        if (strlen($cardName) > 50) {
            $cardName = 'Cardholder';
        }

        // store request in the database before sending it
        $sql = 'INSERT INTO efsnetRequest (' .
                    $dbTrans->identifier_escape('date') . ', cashierNo, laneNo, transNo, transID, ' .
                    $dbTrans->identifier_escape('datetime') . ', refNum, live, mode, amount,
                    PAN, issuer, manual, name,
                    sentPAN, sentExp, sentTr1, sentTr2)
                VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?)'; 
        $efsArgs = array(
            $today, $cashierNo, $laneNo, $transNo, $transID,
            $now, $refNum, $live, $logged_mode, ($amountText + $cashbackText),
            $cardPANmasked, $cardIssuer, $manual, $cardName,
            $sendPAN, $sendExp, $sendTr1, $sendTr2);
        $prep = $dbTrans->prepare($sql);

        $table_def = $dbTrans->table_definition('efsnetRequest');

        if ($dbTrans->table_exists('efsnetRequest') && !$dbTrans->execute($prep, $efsArgs)) {
            PaycardLib::paycard_reset();
            // internal error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        if (isset($table_def['efsnetRequestID'])) {
            $this->last_req_id = $dbTrans->insert_id();
        }

        /**
          Log transaction in newer table
        */
        $insQ = '
                INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID,
                    processor, refNum, live, cardType, transType,
                    amount, PAN, issuer, name, manual, requestDateTime)
                VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )';
        $ptArgs = array($today, $cashierNo, $laneNo, $transNo, $transID,
                    'MercuryE2E', $refNum, $live, $type, $mode,
                    ($amountText+$cashbackText), $cardPANmasked,
                    $cardIssuer, $cardName, $manual, $now);
        $insP = $dbTrans->prepare($insQ);
        $insR = $dbTrans->execute($insP, $ptArgs);
        if ($insR) {
            $this->last_paycard_transaction_id = $dbTrans->insert_id();
        } else {
            // internal error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        // weird string concat here so vim's color highlighting
        // doesn't screw up with what LOOKS LIKE a close-PHP tag
        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$cashierNo.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>';
        if (substr($type,0,3) == 'EBT') {
            $msgXml .= '<TranType>EBT</TranType>';
            if ($type == 'EBTFOOD') {
                CoreLocal::set('EbtFsBalance', 'unknown');
                $msgXml .= '<CardType>Foodstamp</CardType>';
            } else if ($type == 'EBTCASH') {
                $msgXml .= '<CardType>Cash</CardType>';
                CoreLocal::set('EbtCaBalance', 'unknown');
            }
        } else {
            $msgXml .= '<TranType>'.$type.'</TranType>';
        }
        $msgXml .= '<TranCode>'.$mode.'</TranCode>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            <Memo>CORE POS 1.0.0</Memo>';
        if ($logged_mode == "Credit_Sale") {
            $msgXml .= "<PartialAuth>Allow</PartialAuth>";
        }
        $msgXml .= '<RecordNo>RecordNumberRequested</RecordNo>';
        $msgXml .= '<Frequency>OneTime</Frequency>';    
        $msgXml .= '<Account>
                <EncryptedFormat>'.$e2e['Format'].'</EncryptedFormat>
                <AccountSource>'.($manual ? 'Keyed' : 'Swiped').'</AccountSource>
                <EncryptedBlock>'.$e2e['Block'].'</EncryptedBlock>
                <EncryptedKey>'.$e2e['Key'].'</EncryptedKey>
            </Account>
            <Amount>
                <Purchase>'.$amountText.'</Purchase>';
        if ($cashback > 0 && ($type == "Debit" || $type == "EBTCASH")) {
                $msgXml .= "<CashBack>$cashbackText</CashBack>";
        }
        $msgXml .= "</Amount>";
        if ($type == "Debit" || (substr($type,0,3) == "EBT" && $mode != "Voucher")) {
            $msgXml .= "<PIN>
                <PINBlock>".$pin['block']."</PINBlock>
                <DervdKey>".$pin['key']."</DervdKey>
                </PIN>";
        }
        if (CoreLocal::get("paycard_voiceauthcode") != "") {
            $msgXml .= "<TransInfo>";
            $msgXml .= "<AuthCode>";
            $msgXml .= CoreLocal::get("paycard_voiceauthcode");
            $msgXml .= "</AuthCode>";
            $msgXml .= "</TransInfo>";
        } else if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
            $msgXml .= "<TransInfo>";
            $msgXml .= "<AuthCode>";
            $msgXml .= CoreLocal::get("ebt_authcode");
            $msgXml .= "</AuthCode>";
            $msgXml .= "<VoucherNo>";
            $msgXml .= CoreLocal::get("ebt_vnum");
            $msgXml .= "</VoucherNo>";
            $msgXml .= "</TransInfo>";
        }
        $msgXml .= "</Transaction>
            </TStream>";

        $soaptext = $this->soapify("CreditTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        /**
          In switched mode, use the backup URL first
          then retry on the primary URL

          Switched mode is triggered when a request to
          the primary URL fails with some kind of
          cURL error and the subsequent request to the
          backup URL succeeds. The idea is to use the
          backup URL for a few transactions before trying
          the primary again. The most common error is
          a timeout and waiting 30 seconds for the primary
          to fail every single transaction isn't ideal.
        */
        if (CoreLocal::get('MercurySwitchUrls') > 0) {
            if (!$this->second_try) {
                $domain = self::BACKUP_URL;    
            } else {
                $domain = self::PRIMARY_URL;
            }
        } else {
            if (!$this->second_try) {
                $domain = self::PRIMARY_URL;
            } else {
                $domain = self::BACKUP_URL;    
            }
        }

        /**
          SwitchUrls is a counter
          Go back to normal order when it reaches zero 
        */    
        if (CoreLocal::get('MercurySwitchUrls') > 0) {
            $switch_count = CoreLocal::get('MercurySwitchUrls');
            $switch_count--;
            CoreLocal::set('MercurySwitchUrls', $switch_count);
        }

        if (CoreLocal::get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        } else {
            $this->GATEWAY = "https://$domain/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    /**
      Prepare an XML request body for an PDCX
      or EMVX transaction
      @param $type [string] card type
      @param $amount [number] authorization amount
      @return [string] XML request body
    */
    public function prepareDataCapAuth($type, $amount, $prompt=false)
    {
        $termID = $this->getTermID();
        $cashierNo = CoreLocal::get("CashierNo");
        $operatorID = $cashierNo;
        $registerNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get('paycard_id');
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }
        $refNum = $this->refnum($transID);
        $tran_code = $amount > 0 ? 'Sale' : 'Return';
        if ($type == 'EMV') {
            $tran_code = 'EMV' . $tran_code;
        } elseif ($type == 'GIFT') {
            $tran_code = $amount > 0 ? 'NoNSFSale' : 'Return';
        } else if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
            $tran_code = 'Voucher';
        }

        $host = "x1.mercurypay.com";
        $live = 1;
        if (CoreLocal::get("training") == 1) {
            $host = "x1.mercurydev.net";
            $live = 0;
            $operatorID = 'test';
        }

        $tran_type = 'Credit';
        $card_type = false;
        if ($type == 'DEBIT') {
            $tran_type = 'Debit';
        } elseif ($type == 'EBTFOOD') {
            $tran_type = 'EBT';
            $card_type = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tran_type = 'EBT';
            $card_type = 'Cash';
        } elseif ($type == 'GIFT') {
            $tran_type = 'PrePaid';
        }

        $cashback = 0;
        if (($type == 'DEBIT' || $type == 'EBTCASH') && CoreLocal::get('amtdue') > 0 && $amount > 0) {
            if ($amount > CoreLocal::get('amtdue')) {
                $cashback = $amount - CoreLocal::get('amtdue');
                $amount = CoreLocal::get('amtdue');
            }
        }
        $manual = ($prompt ? 1 : 0);

        $dbc = Database::tDataConnect();
        $insP = $dbc->prepare('
            INSERT INTO PaycardTransactions
            (dateID, empNo, registerNo, transNo, transID, processor,
            refNum, live, cardType, transType, amount, manual, requestDatetime,
            seconds, commErr, httpCode)
            VALUES
            (?, ?, ?, ?, ?, \'MercuryE2E\',
            ?, ?, ?, ?, ?, ?, ?,
            0, 0, 200)');
        $args = array(
            date('Ymd'), $cashierNo, $registerNo, $transNo, $transID,
            $refNum, $live, $tran_type, $tran_code, abs($amount), $manual, date('Y-m-d H:i:s')
        );
        $insR = $dbc->execute($insP, $args);
        if ($insR === false) {
            // TODO: cancel request on JS side
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }
        CoreLocal::set('LastEmvPcId', $dbc->insert_id());

        // start with fields common to PDCX and EMVX
        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranCode>' . $tran_code . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            <Amount>
                <Purchase>' . sprintf('%.2f', abs($amount)) . '</Purchase>';
        if ($cashback) {
            $msgXml .= '<CashBack>' . sprintf('%.2f', $cashback) . '</CashBack>';
        }
        $msgXml .= '</Amount>
            <RecordNo>RecordNumberRequested</RecordNo>
            <Frequency>OneTime</Frequency>';
        if ($type == 'EMV') { // add EMV specific fields
            $msgXml .= '
            <HostOrIP>' . '127.0.0.1' . '</HostOrIP>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <CollectData>CardholderName</CollectData>
            <Memo>CORE POS 1.0.0 EMVX</Memo>
            <PartialAuth>Allow</PartialAuth>';
            if ($prompt) {
                $msgXml .= '
                    <Account>
                        <AcctNo>Prompt</AcctNo>
                    </Account>';
            }
        } else {
            $msgXml .= '
            <Memo>CORE POS 1.0.0 PDCX</Memo>
            <Account>
                <AcctNo>' . ($prompt ? 'Prompt' : 'SecureDevice') . '</AcctNo>
            </Account>
            <TranType>' . $tran_type . '</TranType>';
            if ($card_type) {
                $msgXml .= '<CardType>' . $card_type . '</CardType>';
            }
            if ($type == 'CREDIT') {
                $msgXml .= '<PartialAuth>Allow</PartialAuth>';
            }
            if ($type == 'GIFT') {
                $msgXml .= '<IpPort>9100</IpPort>';
                $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
            }
            if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
                $msgXml .= "<TranInfo>";
                $msgXml .= "<AuthCode>";
                $msgXml .= CoreLocal::get("ebt_authcode");
                $msgXml .= "</AuthCode>";
                $msgXml .= "<VoucherNo>";
                $msgXml .= CoreLocal::get("ebt_vnum");
                $msgXml .= "</VoucherNo>";
                $msgXml .= "</TranInfo>";
            }
        }
        $msgXml .= '
            </Transaction>
            </TStream>';

        return $msgXml;
    }

    /**
      Prepare an XML request body to void an PDCX
      or EMVX transaction
      @param $pcID [int] PaycardTransactions record ID
      @return [string] XML request
    */
    public function prepareDataCapVoid($pcID)
    {
        $termID = $this->getTermID();
        $cashierNo = CoreLocal::get("CashierNo");
        $operatorID = $cashierNo;
        $registerNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get('paycard_id');
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }
        $refNum = $this->refnum($transID);

        $host = "x1.mercurypay.com";
        $live = 1;
        if (CoreLocal::get("training") == 1) {
            $host = "x1.mercurydev.net";
            $live = 0;
            $operatorID = 'test';
        }

        $dbc = Database::tDataConnect();
        $query = '
            SELECT cardType,
                transType,
                amount,
                refNum,
                xToken,
                xProcessorRef,
                xAcquirerRef,
                xTransactionID,
                xApprovalNumber,
                issuer
            FROM PaycardTransactions
            WHERE paycardTransactionID=' . ((int)$pcID);
        $result = $dbc->query($query);
        if (!$result || $dbc->numRows($result) == 0) {
            CoreLocal::set('boxMsg', 'Transaction not found');
            return 'Error';
        }
        $prev = $dbc->fetchRow($result);

        $initQ = "INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID,
                    previousPaycardTransactionID, processor, refNum,
                    live, cardType, transType, amount, PAN, issuer,
                    name, manual, requestDateTime)
                  SELECT dateID, empNo, registerNo, transNo, transID+1,
                    paycardTransactionID, processor, refNum,
                    live, cardType, 'VOID', amount, PAN, issuer,
                    name, manual, " . $dbc->now() . "
                  FROM PaycardTransactions
                  WHERE paycardTransactionID=" . ((int)$pcID);
        $initR = $dbc->query($initQ);
        if ($initR) {
            CoreLocal::set('LastEmvPcId', $dbc->insert_id());
        } else {
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }

        /* Determine reversal method based on
           original transaction.
           EMV and Credit are voided
           PIN Debit and EBT run an opposite transaction
           (e.g., Return after a Sale)
        */
        $tran_code = '';
        $tran_type = '';
        $card_type = false;
        if ($prev['transType'] == 'EMVSale') {
            $tran_code = 'EMVVoidSale';
            $tran_type = 'EMV';
        } elseif ($prev['transType'] == 'EMVReturn') {
            $tran_code = 'EMVVoidReturn';
            $tran_type = 'EMV';
        } elseif ($prev['transType'] == 'NoNSFSale') {
            $tran_type = 'PrePaid';
            $tran_code = 'VoidSale';
        } else {
            switch ($prev['cardType']) {
                case 'Credit':
                    $tran_code = ($prev['transType'] == 'Sale') ? 'VoidSaleByRecordNo' : 'VoidReturnByRecordNo';
                    $tran_type = 'Credit';
                    break;
                case 'Debit':
                    $tran_code = ($prev['transType'] == 'Sale') ? 'ReturnByRecordNo' : 'SaleByRecordNo';
                    $tran_type = 'Debit';
                    break;
                case 'EBT':
                    $tran_code = ($prev['transType'] == 'Sale') ? 'ReturnByRecordNo' : 'SaleByRecordNo';
                    $tran_type = 'EBT';
                    $card_type = $prev['issuer'];
                    break;
            }
        }

        // common fields
        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranCode>' . $tran_code . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'. $prev['xTransactionID'] .'</RefNo>
            <Amount>
                <Purchase>' . sprintf('%.2f', abs($prev['amount'])) . '</Purchase>
            </Amount>
            <RecordNo>RecordNumberRequested</RecordNo>
            <Frequency>OneTime</Frequency>';
        if ($tran_type == 'EMV') { // add EMV specific fields
            $msgXml .= '
            <HostOrIP>' . '127.0.0.1' . '</HostOrIP>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <CollectData>CardholderName</CollectData>
            <Memo>CORE POS 1.0.0 EMVX</Memo>
            <PartialAuth>Allow</PartialAuth>';
        } else { // add non-EMV fields
            $msgXml .= '
            <Memo>CORE POS 1.0.0 PDCX</Memo>
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <TranType>' . $tran_type . '</TranType>';
            if ($card_type) {
                $msgXml .= '<CardType>' . $card_type . '</CardType>';
            }
            if ($tran_type == 'Credit') {
                $msgXml .= '<PartialAuth>Allow</PartialAuth>';
            }
            if ($tran_type == 'PrePaid') {
                $msgXml .= '<IpPort>9100</IpPort>';
                $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
            }
        }
        /**
          Add token and reversal data fields if available
        */
        if ($prev['xToken']) {
            $msgXml .= '<RecordNo>' . $prev['xToken'] . '</RecordNo>';
        }
        if ($prev['xProcessorRef']) {
            $msgXml .= '<ProcessData>' . $prev['xProcessorRef'] . '</ProcessData>';
        }
        if ($prev['xAcquirerRef']) {
            $msgXml .= '<AcqRefData>' . $prev['xAcquirerRef'] . '</AcqRefData>';
        }
        $msgXml .= '
            <AuthCode>' . $prev['xApprovalNumber'] . '</AuthCode>
            </Transaction>
            </TStream>';

        return $msgXml;
    }

    /**
      Prepare an XML request body for an PDCX
      card balance inquiry
      @param $type [string] card type
      @return [string] XML request body
    */
    public function prepareDataCapBalance($type, $prompt=false)
    {
        CoreLocal::set('DatacapBalanceCheck', '??');
        $termID = $this->getTermID();
        $cashierNo = CoreLocal::get("CashierNo");
        $operatorID = $cashierNo;
        $registerNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get('paycard_id');
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }
        $refNum = $this->refnum($transID);

        $host = "x1.mercurypay.com";
        $live = 1;
        if (CoreLocal::get("training") == 1) {
            $host = "x1.mercurydev.net";
            $live = 0;
            $operatorID = 'test';
        }

        $tran_type = '';
        $card_type = '';
        if ($type == 'EBTFOOD') {
            $tran_type = 'EBT';
            $card_type = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tran_type = 'EBT';
            $card_type = 'Cash';
        } elseif ($type == 'GIFT') {
            $tran_type = 'PrePaid';
        }

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranType>' . $tran_type . '</TranType>
            <TranCode>Balance</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            <Memo>CORE POS 1.0.0 PDCX</Memo>
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <Amount>
                <Purchase>0.00</Purchase>
            </Amount>';
        if ($card_type) {
            $msgXml .= '<CardType>' . $card_type . '</CardType>';
        }
        if ($type == 'GIFT') {
            $msgXml .= '<IpPort>9100</IpPort>';
            $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
        }
        $msgXml .= '</Transaction></TStream>';

        if ($prompt) {
            $msgXml = str_replace('<AcctNo>SecureDevice</AcctNo>',
                '<AcctNo>Prompt</AcctNo>', $msgXml);
        }

        return $msgXml;
    }

    public function prepareDataCapGift($mode, $amount, $prompt)
    {
        $termID = $this->getTermID();
        $cashierNo = CoreLocal::get("CashierNo");
        $operatorID = $cashierNo;
        $registerNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get('paycard_id');
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }
        $refNum = $this->refnum($transID);

        $host = "g1.mercurypay.com";
        $live = 1;
        if (CoreLocal::get("training") == 1) {
            $host = "g1.mercurydev.net";
            $live = 0;
            $operatorID = 'test';
        }
        $tran_code = 'Issue';
        if ($mode == PaycardLib::PAYCARD_MODE_ADDVALUE) {
            $tran_code = 'Reload';
        }
        $amount = sprintf('%.2f', $amount);
        $manual = ($prompt ? 1 : 0);

        $dbc = Database::tDataConnect();
        $insP = $dbc->prepare('
            INSERT INTO PaycardTransactions
            (dateID, empNo, registerNo, transNo, transID, processor,
            refNum, live, cardType, transType, amount, manual, requestDatetime,
            seconds, commErr, httpCode)
            VALUES
            (?, ?, ?, ?, ?, \'MercuryE2E\',
            ?, ?, ?, ?, ?, ?, ?,
            0, 0, 200)');
        $args = array(
            date('Ymd'), $cashierNo, $registerNo, $transNo, $transID,
            $refNum, $live, 'PrePaid', $tran_code, abs($amount), $manual, date('Y-m-d H:i:s')
        );
        $insR = $dbc->execute($insP, $args);
        if ($insR === false) {
            // TODO: cancel request on JS side
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }
        CoreLocal::set('LastEmvPcId', $dbc->insert_id());
        CoreLocal::set('paycard_amount', $amount);
        CoreLocal::set('paycard_id', CoreLocal::get('LastID'+1));
        CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
        CoreLocal::set('CacheCardType', 'GIFT');
        CoreLocal::set('paycard_mode', $mode);

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranType>PrePaid</TranType>
            <TranCode>' . $tran_code . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            <Memo>CORE POS 1.0.0 PDCX</Memo>
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <Amount>
                <Purchase>' . $amount . '</Purchase>
            </Amount>
            <IpPort>9100</IpPort>';
        $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
        $msgXml .= '</Transaction></TStream>';

        if ($prompt) {
            $msgXml = str_replace('<AcctNo>SecureDevice</AcctNo>',
                '<AcctNo>Prompt</AcctNo>', $msgXml);
        }

        return $msgXml;
    }

    /**
      Examine XML response from Datacap transaction,
      log results, determine next step
      @return [int] PaycardLib error code
    */
    public function handleResponseDataCap($xml)
    {
        $rawXml = $xml;

        $xml = new BetterXmlData($xml);
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        // mercury truncates the reference number in the response; recalculate so it
        // actually matches
        $refNum = $this->refnum($transID);

        $validResponse = 1;
        $responseCode = $xml->query('/RStream/CmdResponse/CmdStatus');
        $resultMsg = $responseCode;
        if ($responseCode) {
            // map response status to 0/1/2 for compatibility
            if ($responseCode == "Approved") {
                $responseCode=1;
            } elseif ($responseCode == "Declined") {
                $responseCode=2;
            } elseif ($responseCode == "Error") {
                $responseCode=0;
            }
        } else {
            $validResponse = -3;
        }
        $resultCode = $xml->query('/RStream/CmdResponse/DSIXReturnCode');
        $apprNumber = $xml->query('/RStream/TranResponse/AuthCode');
        $rMsg = $resultMsg;
        if ($resultMsg) {
            $rMsg = $resultMsg;
            if ($responseCode == 1) { // approved
                if ($apprNumber) {
                    $rMsg .= ' ' . $apprNumber;
                }
            } else {
                $processor_text = $xml->query('/RStream/CmdResponse/TextResponse');
                if ($processor_text) {
                    $rMsg = $processor_text;
                }
            }
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }
        }
        $xTransID = $xml->query('/RStream/TranResponse/RefNo');
        if ($xTransID) {
            $xTransID = substr($xTransID, 0, 12);
        } else {
            $validResponse = -3;
        }
        $pan = $xml->query('/RStream/TranResponse/AcctNo');
        if (strlen($pan) > 4) {
            $pan = '************' . substr($pan, -4);
        }
        $resp_name = $xml->query('/RStream/TranResponse/CardholderName');
        $name = $resp_name ? $resp_name : 'Cardholder';

        $issuer = $xml->query('/RStream/TranResponse/CardType');
        $resp_balance = $xml->query('/RStream/TranResponse/Balance');
        $ebtbalance = 0;
        if ($issuer == 'Foodstamp' && $resp_balance !== false) {
            $issuer = 'EBT';
            CoreLocal::set('EbtFsBalance', $resp_balance);
            $ebtbalance = $resp_balance;
        } elseif ($issuer == 'Cash' && $resp_balance !== false) {
            $issuer = 'EBT';
            CoreLocal::set('EbtCaBalance', $resp_balance);
            $ebtbalance = $resp_balance;
        } elseif ($xml->query('/RStream/TranResponse/TranType') == 'PrePaid' && $resp_balance !== false) {
            $issuer = 'NCG';
            $ebtbalance = $resp_balance;
            CoreLocal::set('GiftBalance', $resp_balance);
        }

        $dbc = Database::tDataConnect();

        $tran_code = $xml->query('/RStream/TranResponse/TranCode');
        if (substr($tran_code, 0, 3) == 'EMV' && strpos($rawXml, 'x____') !== false) {
            CoreLocal::set('EmvSignature', true);
        } else {
            CoreLocal::set('EmvSignature', false);
        }
        if (substr($tran_code, 0, 3) == 'EMV') {
            $i = 1;
            $printData = $xml->query('/RStream/PrintData/*', false);
            if (strlen($printData) > 0) {
                $receiptID = $transID;
                if (CoreLocal::get('paycard_mode') == PaycardLib::PAYCARD_MODE_VOID) {
                    $receiptID++;
                }
                $printP = $dbc->prepare('
                    INSERT INTO EmvReceipt
                        (dateID, tdate, empNo, registerNo, transNo, transID, content)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?)');
                $dbc->execute($printP, array(date('Ymd'), date('Y-m-d H:i:s'), $cashierNo, $laneNo, $transNo, $receiptID, $printData));
            }
        }

        // put normalized value in validResponse column
        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($responseCode == 1) {
            $normalized = 1;
        } else if ($responseCode == 2) {
            $normalized = 2;
        } else if ($responseCode == 0) {
            $normalized = 3;
        }

        $finishQ = "UPDATE PaycardTransactions
                        SET responseDatetime=?,
                            name=?,
                            issuer=?,
                            PAN=?,
                            validResponse=?,
                            xResultCode=?,
                            xApprovalNumber=?,
                            xResponseCode=?,
                            xResultMessage=?,
                            xTransactionID=?,
                            xBalance=?,
                            xToken=?,
                            xProcessorRef=?,
                            xAcquirerRef=?
                        WHERE paycardTransactionID=?";
        $args = array(
            date('Y-m-d H:i:s'),
            $name,
            $issuer,
            $pan,
            $normalized,
            $responseCode,
            $apprNumber,
            $resultCode,
            $rMsg,
            $xTransID,
            $ebtbalance,
            $xml->query('/RStream/TranResponse/RecordNo'),
            $xml->query('/RStream/TranResponse/ProcessData'),
            $xml->query('/RStream/TranResponse/AcqRefData'),
            CoreLocal::get('LastEmvPcId')
        );
        $finishQ = $dbc->prepare_statement($finishQ);
        $dbc->exec_statement($finishQ, $args);

        /** handle partial auth **/
        if ($responseCode == 1) {
            $amt = $xml->query('/RStream/TranResponse/Amount/Authorize');
            if ($amt != abs(CoreLocal::get("paycard_amount"))) {
                $upQ = sprintf('UPDATE PaycardTransactions
                                SET amount=%.2f
                                WHERE paycardTransactionID=%d',
                                $amt, CoreLocal::get('LastEmvPcId'));
                $dbc->query($upQ);

                CoreLocal::set("paycard_amount",$amt);
                CoreLocal::set("paycard_partial",True);
                UdpComm::udpSend('goodBeep');
            }
        }

        switch (strtoupper($xml->query('/RStream/CmdResponse/CmdStatus'))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                if ($issuer == 'EBT') {
                    // if EBT is declined but lists a balance less than the
                    // requested authorization, it may be possible to
                    // charge the card for a less amount. 
                    TransRecord::addcomment("");
                    CoreLocal::set('boxMsg', sprintf('Card Balance: $%.2f', $ebtbalance));
                } elseif (substr($tran_code, 0, 3) == 'EMV') {
                    CoreLocal::set('paycard_amount', 0);
                    return PaycardLib::PAYCARD_ERR_OK;
                }
                UdpComm::udpSend('termReset');
                CoreLocal::set('ccTermState','swipe');
                // intentional fallthrough
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->query('/RStream/CmdResponse/TextResponse');
                CoreLocal::set("boxMsg","Error: $texts");
                $dsix = $xml->query('/RStream/CmdResponse/DSIXReturnCode');
                if ($dsix == '001007' || $dsix == '003007' || $dsix == '003010') {
                    /* These error codes indicate a potential connectivity
                     * error mid-transaction. Do not add a comment record to
                     * the transaction to avoid incrementing InvoiceNo
                     */
                } else {
                    TransRecord::addcomment("");
                }
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
                TransRecord::addcomment("");    
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
      Examine XML response from Datacap transaction,
      extract balance and/or error determine next step
      @return [int] PaycardLib error code
    */
    public function handleResponseDataCapBalance($xml)
    {
        $xml = new xmlData($xml);
        $validResponse = ($xml->isValid()) ? 1 : 0;
        $responseCode = $xml->get("CMDSTATUS");
        if ($responseCode) {
            // map response status to 0/1/2 for compatibility
            if ($responseCode == "Approved") {
                $responseCode=1;
            } elseif ($responseCode == "Declined") {
                $responseCode=2;
            } elseif ($responseCode == "Error") {
                $responseCode=0;
            }
        } else {
            $validResponse = -3;
        }

        $balance = $xml->get_first('BALANCE');

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                CoreLocal::set('DatacapBalanceCheck', $balance);
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // intentional fallthrough
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                CoreLocal::set("boxMsg","Error: $texts");
                TransRecord::addcomment("");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
                TransRecord::addcomment("");    
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
      Updated for E2E
    */
    private function send_void($skipReversal=False,$domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans){
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        if ($skipReversal) {
            CoreLocal::set("MercuryE2ESkipReversal", true);
        } else {
            CoreLocal::set("MercuryE2ESkipReversal", false);
        }

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // new timestamp
        $cashierNo = CoreLocal::get("CashierNo");
        $laneNo = CoreLocal::get("laneno");
        $transNo = CoreLocal::get("transno");
        $transID = CoreLocal::get("paycard_id");
        $amount = CoreLocal::get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = 'VoidSaleByRecordNo';
        $manual = (CoreLocal::get("paycard_keyed")===True ? 1 : 0);
        $cardIssuer = 'UNKNOWN';
        $cardName = 'Cardholder';
        $refNum = $this->refnum($transID);
        $live = 1;
        if (CoreLocal::get("training") != 0 || CoreLocal::get("CashierNo") == 9999) {
            $live = 0;
        }
        $termID = $this->getTermID();
        $password = $this->getPw();
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }

        $this->voidTrans = $transID;
        $this->voidRef = CoreLocal::get("paycard_trans");
        $temp = explode("-",$this->voidRef);
        $laneNo = $temp[1];
        $transNo = $temp[2];

        // look up the transaction info for voiding
        $sql = 'SELECT refNum,
                    xTransactionID,
                    amount,
                    xToken as token,
                    xProcessorRef as processData,
                    xAcquirerRef AS acqRefData,
                    xApprovalNumber,
                    transType AS mode
                FROM PaycardTransactions
                WHERE dateID=' . $today . '
                    AND empNo=' . $cashierNo . '
                    AND registerNo=' . $laneNo . '
                    AND transNo=' . $transNo . '
                    AND transID=' . $transID;
        if (!$dbTrans->table_exists('PaycardTransactions')) {
            $sql = "SELECT r.refNum,xTransactionID,q.amount,t.token,t.processData,t.acqRefData,r.xApprovalNumber,q.mode FROM efsnetResponse AS r
                LEFT JOIN efsnetRequest AS q ON q.refNum=r.refNum LEFT JOIN efsnetTokens AS t
                ON r.refNum=t.refNum
                WHERE r.".$dbTrans->identifier_escape('date')."='".$today."' 
                AND r.datetime >= " . $dbTrans->curdate() . "
                AND r.cashierNo=".$cashierNo." AND r.laneNo=".$laneNo." AND r.transNo=".$transNo." AND r.transID=".$transID;
        }
        $result = PaycardLib::paycard_db_query($sql, $dbTrans);
        if (!$result || PaycardLib::paycard_db_num_rows($result) != 1) {
            PaycardLib::paycard_reset();

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }
        $res = PaycardLib::paycard_db_fetch_row($result);

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
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $type = 'Credit';
        if (substr($res['mode'],0,6)=='Debit_') {
            $type = 'Debit';
            if (substr($res['mode'],-5)=="_Sale") {
                $mode = 'ReturnByRecordNo';
            } else if (substr($res['mode'],-7)=="_Return") {
                $mode = 'SaleByRecordNo';
            }
            CoreLocal::set("MercuryE2ESkipReversal", true);
        } else if (substr($res['mode'],-7)=="_Return") {
            $mode = 'VoidReturnByRecordNo';
        }

        $msgXml = "<?xml version=\"1.0\""."?".">
            <TStream>
            <Transaction>
            <MerchantID>$termID</MerchantID>
            <OperatorID>$cashierNo</OperatorID>
            <LaneID>$mcTerminalID</LaneID>
            <TranType>$type</TranType>
            <TranCode>$mode</TranCode>
            <InvoiceNo>$refNum</InvoiceNo>
            <RefNo>".$res['xTransactionID']."</RefNo>
            <Memo>CORE POS 1.0.0</Memo>
            <RecordNo>".$res['token']."</RecordNo>
            <Frequency>OneTime</Frequency>
            <Amount>
                <Purchase>$amountText</Purchase>
            </Amount>
            <TransInfo>";
        if (!$skipReversal) {
            $msgXml .= "<AcqRefData>".$res['acqRefData']."</AcqRefData>
                <ProcessData>".$res['processData']."</ProcessData>";
        }
        $msgXml .= "<AuthCode>".$res['xApprovalNumber']."</AuthCode>
            </TransInfo>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("CreditTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");
                
        if (CoreLocal::get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        } else {
            $this->GATEWAY = "https://$domain/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    // tack time onto reference number for goemerchant order_id
    // field. requires uniqueness, doesn't seem to cycle daily
    public function refnum($transID)
    {
        $transNo   = (int)CoreLocal::get("transno");
        $cashierNo = (int)CoreLocal::get("CashierNo");
        $laneNo    = (int)CoreLocal::get("laneno");    

        // assemble string
        $ref = "";
        $ref .= date("md");
        $ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
        $ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
        $ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
        $ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);

        return $ref;
    }

    /**
      Return real or testing ID depending on
      whether training mode is on
    */
    private function getTermID()
    {
        if (CoreLocal::get("training") == 1) {
            if (CoreLocal::get('CacheCardType') == 'EMV') {
                return '337234005'; // emv
            } else {
                return '118725340908147'; // newer
            }
            //return "395347308=E2ETKN"; // old test ID
        } else {
            return CoreLocal::get('MercuryE2ETerminalID');
        }
    }

    /**
      Return real or testing password depending on
      whether training mode is on
    */
    private function getPw()
    {
        if (CoreLocal::get("training") == 1) {
            return 'xyz';
            return "123E2ETKN";
        } else {
            return CoreLocal::get('MercuryE2EPassword');
        }
    }

    /**
      In theory parses output produced by MagTek and ID tech
      devices (based on spec / examples)
      @return array with keys
       - Format is encrypt format
       - Block is encryped PAN block
       - Key is encrypted key
       - Issuer is card issuer (Visa, MasterCard, etc)
       - Last4 is last four PAN digits
       - Name is cardholder name (if available)
    */
    private function parseEncBlock($str)
    {
        $ret = array(
            'Format'=>'MagneSafe',
            'Block'=>'',
            'Key'=>'',
            'Issuer'=>'Unknown',
            'Last4'=>'XXXX',
            'Name'=>'Cardholder'
        );
        if (strstr($str,"|")) {
            /* magtek style block */
            $parts = explode("|",$str);
            $tr1 = False;
            $tr2 = False;
            if ($str[0] == "%") {
                /* non-numbered format */
                $ret['Block'] = $parts[3];
                $ret['Key'] = $parts[9];
                $tr1 = $parts[0];
                $tr2 = $parts[1];
            } else if ($str[0] == "1") {
                /* numbered format */
                foreach($parts as $p) {
                    if (strlen($p) > 2 && substr($p,0,2)=="3~") {
                        $ret['Block'] = substr($p,2);    
                    } else if (strlen($p) > 3 && substr($p,0,3)=="11~") {
                        $ret['Key'] = substr($p,3);    
                    } else if (strlen($p) > 2 && substr($p,0,3)=="6~") {
                        $tr1 = substr($p,2);
                    } else if (strlen($p) > 2 && substr($p,0,3)=="7~") {
                        $tr2 = substr($p,2);
                    }
                }
            }

            // extract info from masked tracks
            if ($tr1 && $tr1[0] == "%") {
                $split = explode("^",$tr1);
                $pan = substr($split[0],1);
                if (strlen($split[1]) <= 26) {
                    $ret['Name'] = $split[1];
                }
                $ret['Last4'] = substr($pan,-4);
                $ret['Issuer'] = PaycardLib::paycard_issuer($pan);
            } else if($tr2 && $tr2[0] == ";") {
                $tr2 = substr($tr2,1);
                $pan = substr($tr2,0,strpos("="));
                $ret['Last4'] = substr($pan,-4);
                $ret['Issuer'] = PaycardLib::paycard_issuer($pan);
            }
        } else if (strlen($str)>2 && substr($str,0,2)=="02") {
            /* IDtech style block */

            // read track length from block
            $track_length = array(
                1 => hexdec(substr($str,10,2)),
                2 => hexdec(substr($str,12,2)),
                3 => hexdec(substr($str,14,2))
            );

            // skip to track data start point
            $pos = 20;
            // move through masked track data
            foreach ($track_length as $num=>$kl) {
                if ($num == 1 && $kl > 0) {
                    // read name and masked PAN from track 1
                    $caret = strpos($str,"5E",$pos);
                    $pan = substr($str,$pos,$caret-$pos);
                    $pan = substr($pan,4); // remove leading %*
                    $caret2 = strpos($str,"5E",$caret+2);
                    if ($caret2 < ($pos + ($kl*2))) { // still in track 1
                        $name = substr($str,$caret+2,$caret2-$caret-2);
                        $ret['Name'] = $this->dehexify($name);    
                    }
                    $pan = $this->dehexify($pan);
                    $ret['Last4'] = substr($pan,-4);
                    $ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*","0",$pan));
                } else if ($num == 2 && $kl > 0) {
                    $equal = strpos($str,"3D",$pos);
                    $pan = substr($str,$pos,$equal-$pos);
                    $pan = substr($pan,2); // remove leading ;
                    $pan = $this->dehexify($pan);
                    $ret['Last4'] = substr($pan,-4);
                    $ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*",0,$pan));
                }
                $pos += $kl*2;
            }

            // mercury rejects track 1
            if ($track_length[1] > 0) {
                while($track_length[1] % 8 != 0) $track_length[1]++;
                // cannot send back track 1
                //$ret['Block'] = substr($str,$pos,$track_length[1]*2);
                $pos += ($track_length[1]*2);
            }

            // read encrypted track 2
            if ($track_length[2] > 0) {
                while($track_length[2] % 8 != 0) $track_length[2]++;
                $ret['Block'] = substr($str,$pos,$track_length[2]*2);
                $pos += ($track_length[2]*2);
            }

            // move past hash 1 if present, hash 2 if present
            if ($track_length[1] > 0) {
                $pos += (20*2);
            }
            if ($track_length[2] > 0) {
                $pos += (20*2);
            }

            // read key segment
            $ret['Key'] = substr($str,$pos,20);
        } elseif (strlen($str) > 4 && substr($str, 0, 4) == "23.0") {
            // Ingenico style
            $data = substr($str, 4);
            $tracks = explode('@@', $data);
            $track1 = false;
            $track2 = false;
            $track3 = $tracks[count($tracks)-1];
            if ($tracks[0][0] == '%') {
                $track1 = $tracks[0];
            } elseif ($tracks[0][0] == ';') {
                $track2 = $tracks[0];
            }
            if ($track2 === false && $tracks[1][0] == ';') {
                $track2 = $tracks[1];
            }

            if ($track1 !== false) {
                $pieces = explode('^', $track1);
                $masked = ltrim($pieces[0], '%');
                $ret['Issuer'] = PaycardLib::paycard_issuer($masked);
                $ret['Last4'] = substr($masked, -4);
                if (count($pieces) >= 3) {
                    $ret['Name'] = $pieces[1];
                }
            } elseif ($track2 !== false) {
                list($start, $end) = explode('=', $track2, 2);
                $masked = ltrim($start, ';');
                $ret['Issuer'] = PaycardLib::paycard_issuer($masked);
                $ret['Last4'] = substr($masked, -4);
            }

            if (strstr($track3, ';')) {
                list($e2e, $actual_track3) = explode(';', $track3, 2);
                $track3 = $e2e;
            }

            $pieces = explode(':', $track3);
            if (count($pieces) == 4) {
                $ret['Block'] = $pieces[2];
                $ret['Key'] = $pieces[3];
            } elseif (count($pieces) == 2 && $track1 === false) {
                $ret['Block'] = $pieces[0];
                $ret['Key'] = $pieces[1];
            }
        }

        return $ret;
    }

    private function parsePinBlock($str){
        $ret = array('block'=>'', 'key'=>'');
        if (strlen($str) == 36 && substr($str,0,2) == "FF") {
            // idtech
            $ret['key'] = substr($str,4,16);
            $ret['block'] = substr($str,-16);
        } else {
            // ingenico
            $ret['key'] = substr($str, -20);
            $ret['block'] = substr($str, 0, 16);
        }

        return $ret;
    }

    /*
      Utility. Convert hex string to ascii characters
    */
    private function dehexify($in){
        // must be two characters per digit
        if (strlen($in) % 2 != 0) {
            return false;
        }
        $ret = "";
        for ($i=0;$i<strlen($in);$i+=2) {
            $ret .= chr(hexdec(substr($in,$i,2)));
        }

        return $ret;
    }

    public function myRefNum($ref)
    {
        if (strlen($ref) == 16 && preg_match('/^[0-9]+$/', $ref)) {
            return true;
        } else {
            return false;
        }
    }

    public function lookupTransaction($ref, $local, $mode)
    {
        $ws_params = array(
            'merchant' => CoreLocal::get('MercuryE2ETerminalID'),
            'pw' => CoreLocal::get('MercuryE2EPassword'),
            'invoice' => $ref,
        );

        // emp_no 9999 => test transaction
        if (substr($ref, 4, 4) == "9999") {
            $ws_params['merchant'] = '395347308=E2ETKN';
            $ws_params['pw'] = '123E2ETKN';
        }

        $this->SOAPACTION = 'http://www.mercurypay.com/CTranDetail';
        $soaptext = $this->soapify('CTranDetail', $ws_params, 'http://www.mercurypay.com');
        $this->GATEWAY = 'https://' . self::PRIMARY_URL . '/ws/ws.asmx';

        $curl_result = $this->curlSend($soaptext, 'SOAP', false, array(), false);

        if ($curl_result['curlErr'] != CURLE_OK || $curl_result['curlHTTP'] != 200) {
            $this->GATEWAY = 'https://' . self::BACKUP_URL . '/ws/ws.asmx';
            $curl_result = $this->curlSend($soaptext, 'SOAP', false, array(), false);
            if ($curl_result['curlErr'] != CURLE_OK || $curl_result['curlHTTP'] != 200) {
                return array(
                    'output' => DisplayLib::boxMsg('No response from processor', '', true),
                    'confirm_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
                    'cancel_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
                );
            }
        }

        $directions = 'Press [enter] or [clear] to continue';
        $resp = array(
            'confirm_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
            'cancel_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
        );
        $info = new Paycards();
        $url_stem = $info->plugin_url();

        $xml_resp = $this->desoapify('CTranDetailResponse', $curl_result['response']);
        $xml = new xmlData($xml_resp);

        $status = trim($xml->get_first('STATUS'));
        if ($status === '') {
            $status = 'NOTFOUND';
            $directions = 'Press [enter] to try again, [clear] to stop';
            $query_string = 'id=' . ($local ? '_l' : '') . $ref . '&mode=' . $mode;
            $resp['confirm_dest'] = $url_stem . '/gui/PaycardTransLookupPage.php?' . $query_string;
        } else if ($local == 1 && $mode == 'verify') {
            // Update efsnetResponse record to contain
            // actual processor result and finish
            // the transaction correctly
            $responseCode = -3;
            $resultCode = 0;
            $normalized = 0;
            if ($status == 'Approved') {
                $responseCode = 1;
                $normalized = 1;
                PaycardLib::paycard_wipe_pan();
                $this->cleanup(array());
                $resp['confirm_dest'] = $url_stem . '/gui/paycardSuccess.php';
                $resp['cancel_dest'] = $url_stem . '/gui/paycardSuccess.php';
                $directions = 'Press [enter] to continue';
            } else if ($status == 'Declined') {
                PaycardLib::paycard_reset();
                $responseCode = 2;
                $normalized = 2;
            } else if ($status == 'Error') {
                PaycardLib::paycard_reset();
                $responseCode = 0;
                $resultCode = -1; // CTranDetail does not provide this value
                $normalized = 3;
            } else {
                // Unknown status; clear any data
                PaycardLib::paycard_reset();
            }

            $apprNumber = $xml->get_first('authcode');
            $xTransID = $xml->get_first('reference');
            $rMsg = $status;
            if ($apprNumber) {
                $rMsg .= ' ' . $apprNumber;
            }
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }

            $db = Database::tDataConnect(); 
            $upP = $db->prepare("
                UPDATE PaycardTransactions 
                SET xResponseCode=?,
                    xResultCode=?,
                    xResultMessage=?,
                    xTransactionID=?,
                    xApprovalNumber=?,
                    commErr=0,
                    httpCode=200,
                    validResponse=?
                WHERE refNum=?
                    AND transID=?");
            $args = array(
                $responseCode,
                $resultCode,
                $rMsg,
                $xTransID,
                $apprNumber,
                $normalized,
                $ref,
                CoreLocal::get('paycard_id'),
            );
            $upR = $db->execute($upP, $args);

            $upP = $db->prepare("
                UPDATE efsnetResponse SET
                    xResponseCode=?,
                    xResultCode=?, 
                    xResultMessage=?,
                    xTransactionID=?,
                    xApprovalNumber=?,
                    commErr=0,
                    httpCode=200
                WHERE refNum=?
                    AND transID=?");
            $args = array(
                $responseCode,
                $resultCode,
                $rMsg,
                $xTransID,
                $apprNumber,
                $ref,
                CoreLocal::get('paycard_id')
            );
            if ($db->table_exists('efsnetResponse')) {
                $upR = $db->execute($upP, $args);
            }
        }

        switch(strtoupper($status)) {
            case 'APPROVED':
                $line1 = $status . ' ' . $xml->get_first('authcode');
                $line2 = 'Amount: ' . sprintf('%.2f', $xml->get_first('total'));
                $trans_type = $xml->get_first('trantype');
                $line3 = 'Type: ' . $trans_type;
                $voided = $xml->get_first('voided');
                $line4 = 'Voided: ' . ($voided == 'true' ? 'Yes' : 'No');
                $resp['output'] = DisplayLib::boxMsg($line1 
                                                     . '<br />' . $line2
                                                     . '<br />' . $line3
                                                     . '<br />' . $line4
                                                     . '<br />' . $directions, 
                                                     '', 
                                                     true
                );
                break;
            case 'DECLINED':
                $resp['output'] = DisplayLib::boxMsg('The original transaction was declined
                                                      <br />' . $directions, 
                                                      '', 
                                                      true
                );
                break;
            case 'ERROR':
                $resp['output'] = DisplayLib::boxMsg('The original transaction resulted in an error
                                                      <br />' . $directions,
                                                      '',
                                                      true
                );
                break;
            case 'NOTFOUND':
                $resp['output'] = DisplayLib::boxMsg('Processor has no record of the transaction
                                                      <br />' . $directions,
                                                      '',
                                                      true
                );
                break;
        }

        return $resp;
    }

    private function giftServerIP()
    {
        $host = 'g1.mercurypay.com';
        if (CoreLocal::get('training') == 1) {
            $host = 'g1.mercurydev.net';
        }
        $host_cache = CoreLocal::get('DnsCache');
        if (!is_array($host_cache)) {
            $host_cache = array();
        }
        if (isset($host_cache[$host])) {
            return $host_cache[$host];
        } else {
            $ip = gethostbyname($host);
            if ($ip === $host) { // name did not resolve
                return $host;
            } else { // cache IP for next time
                $host_cache[$host] = $ip;
                CoreLocal::set('DnsCache', $host_cache);
                return $ip;
            }
        }
    }
}

