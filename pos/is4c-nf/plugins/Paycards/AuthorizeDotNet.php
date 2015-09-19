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

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("xmlData")) include_once(realpath(dirname(__FILE__)."/lib/xmlData.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

define('AUTHDOTNET_LOGIN','6Jc5c8QcB');
define('AUTHDOTNET_TRANS_KEY','68j46u5S3RL4CCbX');

class AuthorizeDotNet extends BasicCCModule {

    function handlesType($type){
        if ($type == PaycardLib::PAYCARD_TYPE_CREDIT) return True;
        else return False;
    }

    function entered($validate,$json)
    {
        // error checks based on card type
        if( CoreLocal::get("CCintegrate") != 1) { // credit card integration must be enabled
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
                "Card Integration Disabled",
                "Please process credit cards in standalone",
                "[clear] to cancel");
            return $json;
        }

        // error checks based on processing mode
        switch( CoreLocal::get("paycard_mode")) {
        case PaycardLib::PAYCARD_MODE_VOID:
            // use the card number to find the trans_id
            $dbTrans = PaycardLib::paycard_db();
            $today = date('Ymd');
            $pan4 = substr($this->trans_pan['pan'],-4);
            $cashier = CoreLocal::get("CashierNo");
            $lane = CoreLocal::get("laneno");
            $trans = CoreLocal::get("transno");
            $sql = "SELECT transID FROM efsnetRequest WHERE ".$dbTrans->identifier_escape('date')."='".$today."' AND (PAN LIKE '%".$pan4."') " .
                "AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans;
            $search = PaycardLib::paycard_db_query($sql, $dbTrans);
            $num = PaycardLib::paycard_db_num_rows($search);
            if( $num < 1) {
                PaycardLib::paycard_reset();
                $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Card Not Used",
                    "That card number was not used in this transaction","[clear] to cancel");
                return $json;
            } else if( $num > 1) {
                PaycardLib::paycard_reset();
                $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Multiple Uses",
                    "That card number was used more than once in this transaction; select the payment and press VOID","[clear] to cancel");
                return $json;
            }
            $payment = PaycardLib::paycard_db_fetch_row($search);
            return $this->paycard_void($payment['transID'],$lane,$trans,$json);

        case PaycardLib::PAYCARD_MODE_AUTH:
            if( $validate) {
                if( PaycardLib::paycard_validNumber($this->trans_pan['pan']) != 1) {
                    PaycardLib::paycard_reset();
                    $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                        "Invalid Card Number",
                        "Swipe again or type in manually",
                        "[clear] to cancel");
                    return $json;
                } else if( !PaycardLib::paycard_accepted($this->trans_pan['pan'])) {
                    PaycardLib::paycard_reset();
                    $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                        "Unsupported Card Type",
                        "We cannot process " . CoreLocal::get("paycard_issuer") . " cards",
                        "[clear] to cancel");
                    return $json;
                } else if( PaycardLib::paycard_validExpiration(CoreLocal::get("paycard_exp")) != 1) {
                    PaycardLib::paycard_reset();
                    $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                        "Invalid Expiration Date",
                        "The expiration date has passed or was not recognized",
                        "[clear] to cancel");
                    return $json;
                }
            }
            // set initial variables
            //getsubtotals();
            CoreLocal::set("paycard_amount",CoreLocal::get("amtdue"));
            CoreLocal::set("paycard_id",CoreLocal::get("LastID")+1); // kind of a hack to anticipate it this way..
            $plugin_info = new Paycards();
            $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgAuth.php';
            return $json;
            break;
        } // switch mode
    
        // if we're still here, it's an error
        PaycardLib::paycard_reset();
        $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Invalid Mode",
            "This card type does not support that processing mode","[clear] to cancel");
        return $json;
    }

    function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        // situation checking
        if( CoreLocal::get("CCintegrate") != 1) { // credit card integration must be enabled
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                "Card Integration Disabled",
                "Please process credit cards in standalone",
                "[clear] to cancel");
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
        $sql = "SELECT live,PAN,mode,amount,name FROM efsnetRequest 
            WHERE ".$dbTrans->identifier_escape('date')."='".$today."' AND cashierNo=".$cashier." 
            AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $num = PaycardLib::paycard_db_num_rows($search);
        if( $num < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Card request not found, unable to void","[clear] to cancel");
            return $json;
        } else if( $num > 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Card request not distinct, unable to void","[clear] to cancel");
            return $json;
        }
        $request = PaycardLib::paycard_db_fetch_row($search);

        // look up the response
        $sql = "SELECT commErr,httpCode,validResponse,xResponseCode,
            xTransactionID FROM efsnetResponse WHERE ".$dbTrans->identifier_escape('date')."='".$today."' 
            AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $num = PaycardLib::paycard_db_num_rows($search);
        if( $num < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Card response not found, unable to void","[clear] to cancel");
            return $json;
        } else if( $num > 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Card response not distinct, unable to void","[clear] to cancel");
            return $json;
        }
        $response = $dbTrans->fetch_array($search);

        // look up any previous successful voids
        $sql = "SELECT transID FROM efsnetRequestMod WHERE "
                .$dbTrans->identifier_escape('date')."=".$today
                ." AND cashierNo=".$cashier." AND laneNo=".$lane
                ." AND transNo=".$trans." AND transID=".$transID
                ." AND mode='void' AND xResponseCode=0";
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $voided = PaycardLib::paycard_db_num_rows($search);
        // look up the transaction tender line-item
        $sql = "SELECT trans_type,trans_subtype,trans_status,voided
                   FROM localtemptrans WHERE trans_id=" . $transID;
        $search = PaycardLib::paycard_db_query($sql, $dbTrans);
        $num = PaycardLib::paycard_db_num_rows($search);
        if( $num < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Transaction item not found, unable to void","[clear] to cancel");
            return $json;
        } else if( $num > 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Transaction item not distinct, unable to void","[clear] to cancel");
            return $json;
        }
        $lineitem = PaycardLib::paycard_db_fetch_row($search);

        // make sure the payment is applicable to void
        if( $response['commErr'] != 0 || $response['httpCode'] != 200 || $response['validResponse'] != 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
                "Card transaction not successful","[clear] to cancel");
            return $json;
        } else if( $voided > 0) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
                "Card transaction already voided","[clear] to cancel");
            return $json;
        } else if( $request['live'] != PaycardLib::paycard_live(PaycardLib::PAYCARD_TYPE_CREDIT)) {
            // this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
            // I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
                "Processor platform mismatch","[clear] to cancel");
            return $json;
        } else if( $response['xResponseCode'] != 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
                "Credit card transaction not approved<br>The result code was " . $response['xResponseCode'],"[clear] to cancel");
            return $json;
        } else if( $response['xTransactionID'] < 1) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Invalid reference number","[clear] to cancel");
            return $json;
        }

        // make sure the tender line-item is applicable to void
        if( $lineitem['trans_type'] != "T" || $lineitem['trans_subtype'] != "CC" ){
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Authorization and tender records do not match $transID","[clear] to cancel");
            return $json;
        } else if( $lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
            PaycardLib::paycard_reset();
            $json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
                "Void records do not match","[clear] to cancel");
            return $json;
        }
    
        // save the details
        CoreLocal::set("paycard_amount",(($request['mode']=='refund') ? -1 : 1) * $request['amount']);
        CoreLocal::set("paycard_id",$transID);
        CoreLocal::set("paycard_type",PaycardLib::PAYCARD_TYPE_CREDIT);
        CoreLocal::set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
        CoreLocal::set("paycard_name",$request["name"]);
    
        // display FEC code box
        $plugin_info = new Paycards();
        $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgVoid.php';
        return $json;
    }

    function handleResponse($authResult)
    {
        switch(CoreLocal::get("paycard_mode")){
        case PaycardLib::PAYCARD_MODE_AUTH:
            return $this->handleResponseAuth($authResult);
        case PaycardLib::PAYCARD_MODE_VOID:
            return $this->handleResponseVoid($authResult);
        }
    }

    function handleResponseAuth($authResult)
    {
        $xml = new xmlData($authResult['response']);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);
        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("RESPONSECODE");
        if ($responseCode === false){
            $validResponse = -3;
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->get_first("CODE");
        $response->setResultCode($resultCode);
        $resultMsg = $xml->get_first("DESCRIPTION");
        $response->setResultMsg($resultMsg);
        $xTransID = $xml->get("TRANSID");
        $response->setTransactionID($xTransID);
        if ($xTransID === false){
            $validResponse = -3;
        }
        $apprNumber = $xml->get("AUTHCODE");
        $response->setApprovalNum($apprNumber);
        if ($apprNumber === false) {
            $validResponse = -3;
        }
        $response->setValid($validResponse);

        try {
            $response->saveResponse();
        } catch (Exception $ex) { }

        if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
            if ($authResult['curlHTTP'] == '0'){
                CoreLocal::set("boxMsg","No response from processor<br />
                            The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }    
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

        switch ($xml->get("RESPONSECODE")){
            case 1: // APPROVED
                CoreLocal::set("ccTermOut","approval:".str_pad($xml->get("AUTH_CODE"),6,'0',STR_PAD_RIGHT));
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                CoreLocal::set("ccTermOut","approval:denied");
                CoreLocal::set("boxMsg","Transaction declined");
                if ($xml->get_first("ERRORCODE") == 4)
                    CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."<br />Pick up card)");
                break;
            case 3: // ERROR
                CoreLocal::set("ccTermOut","resettotal");
                CoreLocal::set("boxMsg","");
                $codes = $xml->get("ERRORCODE");
                $texts = $xml->get("ERRORTEXT");
                if (!is_array($codes))
                    CoreLocal::set("boxMsg","EC$codes: $texts");
                else{
                    for($i=0; $i<count($codes);$i++){
                        CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."EC".$codes[$i].": ".$texts[$i]);
                        if ($i != count($codes)-1) 
                            CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."<br />");
                    }
                }
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
        }
        return PaycardLib::PAYCARD_ERROR_PROC;
    }

    function handleResponseVoid($authResult)
    {
        $xml = new xmlData($authResult['response']);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);
        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("RESPONSECODE");
        if ($responseCode === false){
            $validResponse = -3;
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->get_first("CODE");
        $response->setResultCode($resultCode);
        $resultMsg = $xml->get_first("DESCRIPTION");
        $response->setResultMsg($resultMsg);
        $response->setValid($validResponse);

        try {
            $response->saveResponse();
        } catch (Exception $ex) { }

        if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

        switch ($xml->get("RESPONSECODE")){
            case 1: // APPROVED
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                CoreLocal::set("boxMsg","Transaction declined");
                if ($xml->get_first("ERRORCODE") == 4)
                    CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."<br />Pick up card");
                break;
            case 3: // ERROR
                CoreLocal::set("boxMsg","");
                $codes = $xml->get("ERRORCODE");
                $texts = $xml->get("ERRORTEXT");
                if (!is_array($codes))
                    CoreLocal::set("boxMsg","EC$codes: $texts");
                else{
                    for($i=0; $i<count($codes);$i++){
                        CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."EC".$codes[$i].": ".$texts[$i]);
                        if ($i != count($codes)-1) 
                            CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."<br />");
                    }
                }
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
        }
        return PaycardLib::PAYCARD_ERROR_PROC;
    }

    function cleanup($json)
    {
        switch(CoreLocal::get("paycard_mode")){
        case PaycardLib::PAYCARD_MODE_AUTH:
            // cast to string. tender function expects string input
            // numeric input screws up parsing on negative values > $0.99
            $amt = "".(CoreLocal::get("paycard_amount")*100);
            $t_type = 'CC';
            if (CoreLocal::get('paycard_issuer') == 'American Express')
                $t_type = 'AX';
            // if the transaction has a non-zero efsnetRequestID,
            // include it in the tender line
            $record_id = $this->last_paycard_transaction_id;
            $charflag = ($record_id != 0) ? 'PT' : '';
            TransRecord::addFlaggedTender("Credit Card", $t_type, $amt, $record_id, $charflag);
            CoreLocal::set("boxMsg","<b>Approved</b><font size=-1><p>Please verify cardholder signature<p>[enter] to continue<br>\"rp\" to reprint slip<br>[clear] to cancel and void</font>");
            if (CoreLocal::get("paycard_amount") <= CoreLocal::get("CCSigLimit") && CoreLocal::get("paycard_amount") >= 0) {
                CoreLocal::set("boxMsg","<b>Approved</b><font size=-1><p>No signature required<p>[enter] to continue<br>[void] to cancel and void</font>");
            } else if (CoreLocal::get('PaycardsSigCapture') != 1) {
                $json['receipt'] = 'ccSlip';
            }
            break;
        case PaycardLib::PAYCARD_MODE_VOID:
            $v = new Void();
            $v->voidid(CoreLocal::get("paycard_id"), array());
            CoreLocal::set("boxMsg","<b>Voided</b><p><font size=-1>[enter] to continue<br>\"rp\" to reprint slip</font>");
            break;    
        }

        return $json;
    }

    function doSend($type){
        switch($type){
        case PaycardLib::PAYCARD_MODE_AUTH: return $this->send_auth();
        case PaycardLib::PAYCARD_MODE_VOID: return $this->send_void(); 
        default:
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(0);
        }
    }    

    function send_auth()
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if( !$dbTrans){
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }

        $request = new PaycardRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('AuthDotNot');
        $mode = (($request->amount < 0) ? 'refund' : 'tender');
        $cardPAN = $this->trans_pan['pan'];
        $request->setPAN($cardPAN);
        $request->setIssuer(CoreLocal::get("paycard_issuer"));
        $cardExM = substr(CoreLocal::get("paycard_exp"),0,2);
        $cardExY = substr(CoreLocal::get("paycard_exp"),2,2);
        $cardTr1 = $this->trans_pan['tr1'];
        $cardTr2 = $this->trans_pan['tr2'];
        $request->setCardholder(CoreLocal::get("paycard_name"));

        // x_login & x_tran_key need to be
        // filled in to work
        $postValues = array(
        "x_login"    => AUTHDOTNET_LOGIN,
        "x_tran_key"    => AUTHDOTNET_TRANS_KEY,
        "x_market_type"    => "2",
        "x_device_type"    => "5",
        "cp_version"    => "1.0",
        "x_test_request"=> "0",
        "x_amount"    => $request->formattedAmount(),
        "x_user_ref"    => $request->refNum
        );
        if (CoreLocal::get("training") == 1)
            $postValues["x_test_request"] = "1";

        if ($mode == "refund")
            $postValues["x_type"] = "CREDIT";
        else
            $postValues["x_type"] = "AUTH_CAPTURE";

        if ((!$cardTr1 && !$cardTr2) || $mode == "refund"){
            $postValues["x_card_num"] = $cardPAN;
            $postValues["x_exp_date"] = $cardExM.$cardExY;
            $request->setSent(1, 1, 0, 0);
        } elseif ($cardTr1){
            $postValues["x_track1"] = $cardTr1;
            $request->setSent(0, 0, 1, 0);
        } elseif ($cardTr2){
            $postValues["x_track2"] = $cardTr2;
            $request->setSent(0, 0, 0, 1);
        }

        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }

        $this->last_request = $request;

        $postData = $this->array2post($postValues);
        $this->GATEWAY = "https://test.authorize.net/gateway/transact.dll";
        return $this->curlSend($postData,'POST',False);
    }

    function send_void()
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if( !$dbTrans){
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        $request = new PaycardVoidRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('AuthDotNot');

        $mode = 'void';
        $cardPAN = $this->trans_pan['pan'];
        $request->setPAN($cardPAN);
        $request->setIssuer(CoreLocal::get("paycard_issuer"));
        $cardExM = substr(CoreLocal::get("paycard_exp"),0,2);
        $cardExY = substr(CoreLocal::get("paycard_exp"),2,2);
        $cardTr1 = $this->trans_pan['tr1'];
        $cardTr2 = $this->trans_pan['tr2'];
        $request->setCardholder(CoreLocal::get("paycard_name"));

        // x_login and x_tran_key need to
        // be filled in to work
        $postValues = array(
        "x_login"    => AUTHDOTNET_LOGIN,
        "x_tran_key"    => AUTHDOTNET_TRANS_KEY,
        "x_market_type"    => "2",
        "x_device_type"    => "5",
        "cp_version"    => "1.0",
        "x_text_request"=> "1",
        "x_amount"    => $request->formattedAmount(),
        "x_user_ref"    => $request->refNum,
        "x_type"    => "VOID",
        "x_card_num"    => $cardPAN,
        "x_exp_date"    => $cardExM.$cardExY
        );

        try {
            $res = $request->findOriginal();
            $request->saveRequest();
        } catch (Exception $ex) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }
        $TransactionID = $res['xTransactionID'];

        $postValues["x_ref_trans_id"] = $TransactionID;

        $this->last_request = $request;

        $postData = $this->array2post($postValues);
        $this->GATEWAY = "https://test.authorize.net/gateway/transact.dll";
        return $this->curlSend($postData,'POST',False);
    }
}

