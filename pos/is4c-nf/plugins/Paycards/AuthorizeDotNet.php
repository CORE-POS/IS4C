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
        $this->trans_pan['pan'] = CoreLocal::get("paycard_PAN");
        return PaycardModule::ccEntered($this->trans_pan['pan'], $validate, $json);
    }

    function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        $this->voidTrans = "";
        $this->voidRef = "";
        return PaycardModule::ccVoid($transID, $laneNo, $transNo, $json);
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

        $comm = PaycardModule::commError($authResult);
        if ($comm !== false) {
            TransRecord::addcomment('');
            return $comm;
        }

        switch ($xml->get("RESPONSECODE")){
            case 1: // APPROVED
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                CoreLocal::set("boxMsg","Transaction declined");
                if ($xml->get_first("ERRORCODE") == 4)
                    CoreLocal::set("boxMsg",CoreLocal::get("boxMsg")."<br />Pick up card)");
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

