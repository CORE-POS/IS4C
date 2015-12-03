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

define('FD_STORE_ID','');
define('FD_PASSWD','');
define('FD_CERT_PATH',realpath(dirname(__FILE__).'/lib').'/fd');
define('FD_CERT_PASSWD','');
define('FD_KEY_PASSWD','');

/* test credentials  */
/*

*/

class FirstData extends BasicCCModule {

    function handlesType($type){
        if ($type == PaycardLib::PAYCARD_TYPE_CREDIT) return True;
        else return False;
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

    function handleResponseAuth($authResult)
    {
        $inner_xml = $this->desoapify("SOAP-ENV:Body",$authResult['response']);
        $xml = new xmlData($inner_xml);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);
        $dbTrans = PaycardLib::paycard_db();

        $cvv2 = CoreLocal::get("paycard_cvv2");

        $validResponse = ($xml->isValid()) ? 1 : 0;
        $statusMsg = $xml->get("fdggwsapi:TransactionResult");
        $responseCode = 4;
        switch(strtoupper($statusMsg)){
        case 'APPROVED':
            $responseCode=1; break;
        case 'DECLINED':
        case 'FRAUD':
            $responseCode=2; break;
        case 'FAILED':
        case 'DUPLICATE':
            $responseCode=0; break;
        }
        $response->setResponseCode($responseCode);
        // aren't two separate codes from goemerchant
        $resultCode = $responseCode;
        $response->setResultCode($resultCode);
        $resultMsg = $statusMsg; // already gathered above
        $response->setResultMsg($resultMsg);
        $xTransID = $xml->get("fdggwsapi:ProcessorReferenceNumber");
        $response->setTransactionID($xTransID);
        $apprNumber = $xml->get("fdggwsapi:ApprovalCode");
        $response->setApprovalNum($apprNumber);
        // valid credit transactions don't have an approval number
        $response->setValid(0);

        try {
            $response->saveResponse();
        } catch (Exception $ex) { }

        $comm = PaycardModule::commError($authResult);
        if ($comm !== false) {
            TransRecord::addcomment('');
            return $comm;
        }

        switch ($responseCode){
            case 1: // APPROVED
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                CoreLocal::set("boxMsg",'Card Declined');
                break;
            case 0: // ERROR
                $texts = $xml->get_first("fdggwsapi:ProcessorResponseMessage");
                CoreLocal::set("boxMsg","Error: $texts");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
        }
        return PaycardLib::PAYCARD_ERR_PROC;
    }

    function handleResponseVoid($authResult){
        throw new Exception('Void not implemented');
    }

    function cleanup($json=array())
    {
        switch(CoreLocal::get("paycard_mode")){
        case PaycardLib::PAYCARD_MODE_AUTH:
            // cast to string. tender function expects string input
            // numeric input screws up parsing on negative values > $0.99
            $amt = "".(-1*(CoreLocal::get("paycard_amount")));
            $t_type = 'CC';
            if (CoreLocal::get('paycard_issuer') == 'American Express')
                $t_type = 'AX';
            // if the transaction has a non-zero efsnetRequestID,
            // include it in the tender line
            $record_id = $this->last_paycard_transaction_id;
            $charflag = ($record_id != 0) ? 'PT' : '';
            TransRecord::addFlaggedTender("Credit Card", $t_type, $amt, $record_id, $charflag);
            CoreLocal::set("boxMsg","<b>Approved</b><font size=-1><p>Please verify cardholder signature<p>[enter] to continue<br>\"rp\" to reprint slip<br>[void] to cancel and void</font>");
            if (CoreLocal::get("paycard_amount") <= CoreLocal::get("CCSigLimit") && CoreLocal::get("paycard_amount") >= 0){
                CoreLocal::set("boxMsg","<b>Approved</b><font size=-1><p>No signature required<p>[enter] to continue<br>[void] to cancel and void</font>");
            }    
            break;
        case PaycardLib::PAYCARD_MODE_VOID:
            $void = new Void();
            $void->voidid(CoreLocal::get("paycard_id"), array());
            CoreLocal::set("boxMsg","<b>Voided</b><p><font size=-1>[enter] to continue<br>\"rp\" to reprint slip</font>");
            break;    
        }
        if (CoreLocal::get("paycard_amount") > CoreLocal::get("CCSigLimit") || CoreLocal::get("paycard_amount") < 0)
            $json['receipt'] = "ccSlip";
        return $json;
    }

    function doSend($type)
    {
        switch($type){
        case PaycardLib::PAYCARD_MODE_AUTH: 
            return $this->send_auth();
        case PaycardLib::PAYCARD_MODE_VOID: 
            return $this->send_void(); 
        default:
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(0);
        }
    }    

    function send_auth()
    {
        $dbTrans = PaycardLib::paycard_db();
        if( !$dbTrans){
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }

        $request = new PaycardRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('FirstData');
        $mode = 'sale';
        $this->trans_pan['pan'] = CoreLocal::get("paycard_PAN");
        $cardPAN = $this->trans_pan['pan'];
        $cardExM = substr(CoreLocal::get("paycard_exp"),0,2);
        $cardExY = substr(CoreLocal::get("paycard_exp"),2,2);
        $cardTr1 = CoreLocal::get("paycard_tr1");
        $cardTr2 = CoreLocal::get("paycard_tr2");
        $cardTr3 = CoreLocal::get("paycard_tr3");
        $request->setCardholder(CoreLocal::get("paycard_name"));
        $cvv2 = CoreLocal::get("paycard_cvv2");

        if (CoreLocal::get("training") == 1){
            $cardPAN = "4111111111111111";
            $cardPANmasked = "xxxxxxxxxxxxTEST";
            $cardIssuer = "Visa";
            $cardTr1 = False;
            $cardTr2 = False;
            $request->setCardholder("Just Testing");
            $nextyear = mktime(0,0,0,date("m"),date("d"),date("Y")+1);
            $cardExM = date("m",$nextyear);
            $cardExY = date("y",$nextyear);
        }
        $request->setPAN($cardPAN);
        $request->setIssuer(CoreLocal::get("paycard_issuer"));

        $sendPAN = 0;
        $sendExp = 0;
        $sendTr1 = 0;
        $sendTr2 = 0;
        $magstripe = "";
        if (!$cardTr1 && !$cardTr2){
            $sendPAN = 1;
            $sendExp = 1;
        }
        if ($cardTr1) {
            $sendTr1 = 1;
            $magstripe .= "%".$cardTr1."?";
        }
        if ($cardTr2){
            $sendTr2 = 1;
            $magstripe .= ";".$cardTr2."?";
        }
        if ($cardTr2 && $cardTr3){
            $sendPAN = 1;
            $magstripe .= ";".$cardTr3."?";
        }
        $request->setSent($sendPAN, $sendExp, $sendTr1, $sendTr2);

        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }

        $this->last_request = $request;

        $xml = '<fdggwsapi:FDGGWSApiOrderRequest  
             xmlns:v1="http://secure.linkpt.net/fdggwsapi/schemas_us/v1" 
              xmlns:fdggwsapi="http://secure.linkpt.net/fdggwsapi/schemas_us/fdggwsapi"> 
             <v1:Transaction>';

        $xml .= "<v1:CreditCardTxType> 
               <v1:Type>$mode</v1:Type> 
              </v1:CreditCardTxType>";
          $xml .= "<v1:CreditCardData> 
               <v1:CardNumber>$pan</v1:CardNumber> 
               <v1:ExpMonth>$cardExM</v1:ExpMonth> 
               <v1:ExpYear>$cardExY</v1:ExpYear> 
               <v1:CardCodeValue>$cvv2</v1:CardCodeValue>
              </v1:CreditCardData>";
        $xml .= "<v1:Payment>
            <v1:ChargeTotal>" . $request->formattedAmount() . "</v1:ChargeTotal> 
            </v1:Payment>";
        $xml .= "<v1:TransactionDetails>
            <v1:OrderId>" . $request->refNum . "</v1:OrderId>
            <v1:Ip>" . filter_input(INPUT_SERVER, 'REMOTE_ADDR') . "</v1:Ip>
            </v1:TransactionDetails>";
        $xml .= '</v1:Transaction> 
            </fdggwsapi:FDGGWSApiOrderRequest>';

        $this->GATEWAY = "https://ws.firstdataglobalgateway.com/fdggwsapi/services/order.wsdl";
        if ($live == 0)
            $this->GATEWAY = "https://ws.merchanttest.firstdataglobalgateway.com/fdggwsapi/services/order.wsdl";

        $extraCurlSetup = array(
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "WS".FD_STORE_ID."._.1:".FD_PASSWD,
            CURLOPT_SSLCERT => FD_CERT_PATH."/WS".FD_STORE_ID."._.1.pem",
            CURLOPT_SSLKEY => FD_CERT_PATH."/WS".FD_STORE_ID."._.1.key",
            CURLOPT_SSLKEYPASSWD => FD_KEY_PASSWD
        );

        $soaptext = $this->soapify('', array('xml'=>$xml), '', False);
        return $this->curlSend($soaptext,'SOAP',True,$extraCurlSetup);
    }

    var $void_trans;
    var $void_ref;
    function send_void($amt,$pan,$exp){
        throw new Exception('Void not implemented');
    }

    function refnum($transID)
    {
        $transNo   = (int)CoreLocal::get("transno");
        $cashierNo = (int)CoreLocal::get("CashierNo");
        $laneNo    = (int)CoreLocal::get("laneno");    

        // assemble string
        $ref = "";
        $ref .= date("ymdHis");
        $ref .= "-";
        $ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
        $ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
        $ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
        $ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);
        return $ref;
    }

    protected $SOAP_ENVELOPE_ATTRS = array(
        "xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\""
        );

    /** FirstData has a signficantly different SOAP format
        so the parent implementation is overriden
      @param $action top level tag in the soap body
      @param $objs keyed array of values    
      @param $namespace include an xmlns attribute
      @return soap string
    */
    function soapify($action,$objs,$namespace="",$encode_tags=True){
        $ret = "<?xml version=\"1.0\"?>
            <SOAP-ENV:Envelope";
        foreach ($this->SOAP_ENVELOPE_ATTRS as $attr){
            $ret .= " ".$attr;
        }
        $ret .= ">
            <SOAP-ENV:Header />
            <SOAP-ENV:Body>";
        foreach($objs as $xml)
            $ret .= $xml;
        $ret .= "</SOAP-ENV:Body>
            </SOAP-ENV:Envelope>";

        return $ret;
    }
}

