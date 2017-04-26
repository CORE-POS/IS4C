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

use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\plugins\Paycards\sql\PaycardRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardVoidRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardResponse;
use COREPOS\pos\plugins\Paycards\xml\XmlData;

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

define('AUTHDOTNET_LOGIN','6Jc5c8QcB');
define('AUTHDOTNET_TRANS_KEY','68j46u5S3RL4CCbX');

class AuthorizeDotNet extends BasicCCModule 
{

    private $pmod;
    public function __construct()
    {
        $this->pmod = new PaycardModule();
        $this->pmod->setDialogs(new PaycardDialogs());
        $this->conf = new PaycardConf();
    }

    public function handlesType($type){
        if ($type == PaycardLib::PAYCARD_TYPE_CREDIT) return True;
        else return False;
    }

    public function entered($validate,$json)
    {
        $this->trans_pan['pan'] = $this->conf->get("paycard_PAN");
        return $this->pmod->ccEntered($this->trans_pan['pan'], $validate, $json);
    }

    public function paycardVoid($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        $this->voidTrans = "";
        $this->voidRef = "";
        return $this->pmod->ccVoid($transID, $laneNo, $transNo, $json);
    }

    public function handleResponse($authResult)
    {
        switch($this->conf->get("paycard_mode")){
        case PaycardLib::PAYCARD_MODE_AUTH:
            return $this->handleResponseAuth($authResult);
        case PaycardLib::PAYCARD_MODE_VOID:
            return $this->handleResponseVoid($authResult);
        }
    }

    protected function handleResponseAuth($authResult)
    {
        $xml = new XmlData($authResult['response']);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult, PaycardLib::paycard_db());
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

        $comm = $this->pmod->commError($authResult);
        if ($comm !== false) {
            TransRecord::addcomment('');
            return $comm;
        }

        return $this->responseReturn($xml);
    }

    protected function handleResponseVoid($authResult)
    {
        $xml = new XmlData($authResult['response']);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult, PaycardLib::paycard_db());
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

        return $this->responseReturn($xml);
    }

    private function responseReturn($xml)
    {
        switch ($xml->get("RESPONSECODE")) {
            case 1: // APPROVED
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                $this->conf->set("boxMsg","Transaction declined");
                if ($xml->get_first("ERRORCODE") == 4)
                    $this->conf->set("boxMsg",$this->conf->get("boxMsg")."<br />Pick up card");
                break;
            case 3: // ERROR
                $this->conf->set("boxMsg","");
                $codes = $xml->get("ERRORCODE");
                $texts = $xml->get("ERRORTEXT");
                if (!is_array($codes))
                    $this->conf->set("boxMsg","EC$codes: $texts");
                else{
                    for($i=0; $i<count($codes);$i++){
                        $this->conf->set("boxMsg",$this->conf->get("boxMsg")."EC".$codes[$i].": ".$texts[$i]);
                        if ($i != count($codes)-1) 
                            $this->conf->set("boxMsg",$this->conf->get("boxMsg")."<br />");
                    }
                }
                break;
            default:
                $this->conf->set("boxMsg","An unknown error occurred<br />at the gateway");
        }
        return PaycardLib::PAYCARD_ERR_PROC;
    }

    public function cleanup($json)
    {
        switch($this->conf->get("paycard_mode")){
        case PaycardLib::PAYCARD_MODE_AUTH:
            // cast to string. tender function expects string input
            // numeric input screws up parsing on negative values > $0.99
            $amt = "".($this->conf->get("paycard_amount")*100);
            $tType = 'CC';
            if ($this->conf->get('paycard_issuer') == 'American Express')
                $tType = 'AX';
            // if the transaction has a non-zero PaycardTransactionID,
            // include it in the tender line
            $recordID = $this->last_paycard_transaction_id;
            $charflag = ($recordID != 0) ? 'PT' : '';
            TransRecord::addFlaggedTender("Credit Card", $tType, $amt, $recordID, $charflag);
            $this->conf->set("boxMsg","<b>Approved</b><font size=-1><p>Please verify cardholder signature<p>[enter] to continue<br>\"rp\" to reprint slip<br>[clear] to cancel and void</font>");
            if ($this->conf->get("paycard_amount") <= $this->conf->get("CCSigLimit") && $this->conf->get("paycard_amount") >= 0) {
                $this->conf->set("boxMsg","<b>Approved</b><font size=-1><p>No signature required<p>[enter] to continue<br>[void] to cancel and void</font>");
            } else if ($this->conf->get('PaycardsSigCapture') != 1) {
                $json['receipt'] = 'ccSlip';
            }
            break;
        case PaycardLib::PAYCARD_MODE_VOID:
            $void = new COREPOS\pos\parser\parse\VoidCmd($this->conf);
            $void->voidid($this->conf->get("paycard_id"), array());
            $this->conf->set("boxMsg","<b>Voided</b><p><font size=-1>[enter] to continue<br>\"rp\" to reprint slip</font>");
            break;    
        }

        return $json;
    }

    public function doSend($type){
        switch($type){
        case PaycardLib::PAYCARD_MODE_AUTH: return $this->sendAuth();
        case PaycardLib::PAYCARD_MODE_VOID: return $this->sendVoid(); 
        default:
            $this->conf->reset();
            return $this->setErrorMsg(0);
        }
    }    

    protected function sendAuth()
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if( !$dbTrans){
            $this->conf->reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }

        $request = new PaycardRequest($this->refnum($this->conf->get('paycard_id')), $dbTrans);
        $request->setProcessor('AuthDotNot');
        $mode = (($request->amount < 0) ? 'refund' : 'tender');
        $cardPAN = $this->trans_pan['pan'];
        $request->setPAN($cardPAN);
        $request->setIssuer($this->conf->get("paycard_issuer"));
        $cardExM = substr($this->conf->get("paycard_exp"),0,2);
        $cardExY = substr($this->conf->get("paycard_exp"),2,2);
        $cardTr1 = $this->trans_pan['tr1'];
        $cardTr2 = $this->trans_pan['tr2'];
        $request->setCardholder($this->conf->get("paycard_name"));

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
        if ($this->conf->get("training") == 1) {
            $postValues["x_test_request"] = "1";
        }
        $postValues["x_type"] = $mode === 'refund' ? "CREDIT" : 'AUTH_CAPTURE';

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

    protected function sendVoid()
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if( !$dbTrans){
            $this->conf->reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        $request = new PaycardVoidRequest($this->refnum($this->conf->get('paycard_id')), $dbTrans);
        $request->setProcessor('AuthDotNot');

        $cardPAN = $this->trans_pan['pan'];
        $request->setPAN($cardPAN);
        $request->setIssuer($this->conf->get("paycard_issuer"));
        $cardExM = substr($this->conf->get("paycard_exp"),0,2);
        $cardExY = substr($this->conf->get("paycard_exp"),2,2);
        $request->setCardholder($this->conf->get("paycard_name"));

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

        $postValues["x_ref_trans_id"] = $res['xTransactionID'];

        $this->last_request = $request;

        $postData = $this->array2post($postValues);
        $this->GATEWAY = "https://test.authorize.net/gateway/transact.dll";
        return $this->curlSend($postData,'POST',False);
    }
}

