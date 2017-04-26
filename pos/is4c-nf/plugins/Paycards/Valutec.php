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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\plugins\Paycards\sql\PaycardGiftRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardVoidRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardResponse;
use COREPOS\pos\plugins\Paycards\xml\XmlData;

/*
 * Valutec processing module
 *
 */
if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

class Valutec extends BasicCCModule 
{
    private $pmod;
    private $dialogs;
    public function __construct()
    {
        $this->pmod = new PaycardModule();
        $this->dialogs = new PaycardDialogs();
        $this->pmod->setDialogs($this->dialogs);
        $this->conf = new PaycardConf();
    }
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
        }
        return false;
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
            $this->dialogs->enabledCheck();
            // error checks based on processing mode
            if ($this->conf->get("paycard_mode") == PaycardLib::PAYCARD_MODE_VOID) {
                // use the card number to find the trans_id
                $pan4 = substr($this->getPAN(), -4);
                $trans = array($this->conf->get('CashierNo'), $this->conf->get('laneno'), $this->conf->get('transno'));
                $result = $this->dialogs->voidableCheck($pan4, $trans);
                return $this->paycardVoid($result,-1,-1,$json);
            }

            // check card data for anything else
            if ($validate) {
                $this->dialogs->validateCard($this->conf->get('paycard_PAN'), false);
            }
        } catch (Exception $ex) {
            $json['output'] = $ex->getMessage();
            return $json;
        }

        // other modes
        $pluginInfo = new Paycards();
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                return PaycardLib::setupAuthJson($json);
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $this->conf->set("paycard_amount",0);
                $this->conf->set("paycard_id",$this->conf->get("LastID")+1); // kind of a hack to anticipate it this way..
                $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/paycardboxMsgGift.php';
                return $json;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/paycardboxMsgBalance.php';
                return $json;
        } // switch mode
    
        // if we're still here, it's an error
        $json['output'] = $this->dialogs->invalidMode();
        return $json;
    }

    protected $sendByType = array(
        PaycardLib::PAYCARD_MODE_ACTIVATE => 'sendAuth',
        PaycardLib::PAYCARD_MODE_ADDVALUE => 'sendAuth',
        PaycardLib::PAYCARD_MODE_AUTH => 'sendAuth',
        PaycardLib::PAYCARD_MODE_VOID => 'sendVoid',
        PaycardLib::PAYCARD_MODE_BALANCE => 'sendBalance',
    );

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
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $resp = $this->conf->get("paycard_response");
                $this->conf->set("boxMsg","<b>Success</b><font size=-1>
                                           <p>Gift card balance: $" . $resp["Balance"] . "
                                           <p>\"rp\" to print
                                           <br>[enter] to continue</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $this->conf->set("autoReprint",1);
                $ttl = $this->conf->get("paycard_amount");
                $deptObj = new COREPOS\pos\lib\DeptLib($this->conf);
                $deptObj->deptkey($ttl*100, $dept . '0');
                $resp = $this->conf->get("paycard_response");    
                $this->conf->set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                $amt = "".(-1*($this->conf->get("paycard_amount")));
                $this->conf->set("autoReprint",1);
                $recordID = $this->last_paycard_transaction_id;
                $charflag = ($recordID != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender("Gift Card", "GD", $amt, $recordID, $charflag);
                $resp = $this->conf->get("paycard_response");
                $this->conf->set("boxMsg","<b>Approved</b><font size=-1>
                                           <p>Used: $" . $this->conf->get("paycard_amount") . "
                                           <br />New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip
                                           <br>[void] to cancel and void</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
                $this->conf->set("autoReprint",1);
                $void = new COREPOS\pos\parser\parse\VoidCmd($this->conf);
                $void->voidid($this->conf->get("paycard_id"), array());
                $resp = $this->conf->get("paycard_response");
                $this->conf->set("boxMsg","<b>Voided</b><font size=-1>
                                           <p>New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
        }

        return $json;
    }

    /* paycardVoid($transID)
     * Argument is trans_id to be voided
     * Again, this is for removing type-specific
     * code from paycard*.php files.
     */
    public function paycardVoid($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        $this->voidTrans = "";
        $this->voidRef = "";
        $ret = $this->pmod->ccVoid($transID, $laneNo, $transNo, $json);

        // save the details
        $this->conf->set("paycard_type",PaycardLib::PAYCARD_TYPE_GIFT);
        $this->conf->set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
    
        return $ret;
    }

    // END INTERFACE METHODS
    
    protected function sendAuth()
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
        $request = new PaycardGiftRequest($this->valutecIdentifier($this->conf->get('paycard_id')), $dbTrans);
        $mode = "";
        $loggedMode = $mode;
        $authMethod = "";
        $amount = $this->conf->get('paycard_amount');
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                $mode = (($amount < 0) ? 'refund' : 'tender');
                $loggedMode = (($amount < 0) ? 'Return' : 'Sale');
                $authMethod = (($amount < 0) ? 'AddValue' : 'Sale');
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $mode = 'addvalue';
                $loggedMode = 'Reload';
                $authMethod = 'AddValue';
                break;
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $mode = 'activate';
                $loggedMode = 'Issue';
                $authMethod = 'ActivateCard';
                break;
            default:
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }
        $termID = $this->getTermID();
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $request->setPAN($cardPAN);
        $request->setIssuer('Valutec');
        $request->setProcessor('Valutec');
        $request->setMode($loggedMode);
        $request->setSent(1, 0, 0, 0);
        if ($cardTr2) {
            $request->setSent(0, 0, 0, 1);
        }
        
        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
                
        $authFields = array(
            'ProgramType'       => 'Gift',
            'CardNumber'        => (($cardTr2) ? $cardTr2 : $cardPAN),
            'Amount'            => $request->formattedAmount(),
            'ServerID'          => $request->cashierNo,
            'Identifier'        => $request->refNum,
        );

        $this->GATEWAY = "https://www.valutec.net/customers/transactions/valutec.asmx/";

        $getData = urlencode($authMethod)."?";
        $getData .= "TerminalID=".urlencode($termID);
        foreach ($authFields as $field => $value) {
            $getData .= "&".urlencode($field)."=".urlencode($value);
        }

        $this->last_request = $request;

        return $this->curlSend($getData,'GET');
    }

    protected function sendVoid()
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }
        $request = new PaycardVoidRequest($this->valutecIdentifier($this->conf->get('paycard_id')), $dbTrans);

        $cardPAN = $this->getPAN();
        $termID = $this->getTermID();

        try {
            $search = $request->findOriginal();
            $request->saveRequest();
        } catch (Exception $ex) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $log = $dbTrans->fetchRow($search);
        $authcode = $log['xAuthorizationCode'];

        // assemble and send void request
        $vdMethod = 'Void';
        $vdFields = array(
            'ProgramType'       => 'Gift',
            'CardNumber'        => $cardPAN,
            'RequestAuthCode'   => $authcode,
            'ServerID'          => $request->cashierNo,
            'Identifier'        => $request->refNum,
        );

        $this->GATEWAY = "https://www.valutec.net/customers/transactions/valutec.asmx/";

        $getData = urlencode($vdMethod)."?";
        $getData .= "TerminalID=".urlencode($termID);
        foreach ($vdFields as $field=>$value) {
            $getData .= "&".urlencode($field)."=".urlencode($value);
        }

        $this->last_request = $request;

        return $this->curlSend($getData,'GET');
    }

    protected function sendBalance()
    {
        // prepare data for the request
        $cashierNo = $this->conf->get("CashierNo");
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $identifier = date('mdHis'); // the balance check itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
        $termID = $this->getTermID();

        // assemble and send balance check
        $balMethod = 'CardBalance';
        $balFields = array(
            'ProgramType'       => 'Gift',
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

    protected $respondByType = array(
        PaycardLib::PAYCARD_MODE_ACTIVATE => 'handleResponseAuth',
        PaycardLib::PAYCARD_MODE_ADDVALUE => 'handleResponseAuth',
        PaycardLib::PAYCARD_MODE_AUTH => 'handleResponseAuth',
        PaycardLib::PAYCARD_MODE_VOID => 'handleResponseVoid',
        PaycardLib::PAYCARD_MODE_BALANCE => 'handleResponseBalance',
    );

    protected function handleResponseAuth($authResult)
    {
        $xml = new XmlData($authResult["response"]);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult, PaycardLib::paycard_db());
        $identifier = $this->valutecIdentifier($this->conf->get('paycard_id'));

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
                } elseif ($balance && $balance !== "") {
                    $errorMsg = "NSF, BAL: ".PaycardLib::moneyFormat($balance);    
                }
            }

            // verify that echo'd fields match our request
            $validResponse = 4; // response was parsed as XML but fields didn't match
            if ($xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == 'Gift'
                && $xml->get('IDENTIFIER') && $xml->get('IDENTIFIER') == $identifier
                && $xml->get('AUTHORIZED')
            ) {
                $validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
            }
        }

        $response->setBalance($balance);

        $resultCode = 0;
        $apprNumber = $xml->get('AUTHORIZATIONCODE');
        $response->setApprovalNum($apprNumber);
        $rMsg = substr($xml->get_first('ERRORMSG'), 0, 100);
        if ($apprNumber != '' && $xml->get('AUTHORIZED') == 'true') {
            $validResponse = 1;
            $resultCode = 1;
            $rMsg = 'Approved';
        }
        $response->setResultMsg($rMsg);
        $response->setResultCode($resultCode);
        $response->setResponseCode($resultCode);
        $response->setNormalizedCode($resultCode);
        $response->setValid($validResponse);

        try {
            $response->saveResponse();
        } catch (Exception $ex) {}

        $comm = $this->pmod->commError($authResult);
        if ($comm !== false) {
            return $comm === true ? $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM) : $comm;
        }

         // check for data errors (any failure to parse response XML or echo'd field mismatch
        if ($validResponse != 1) {
            // invalid server response, we don't know if the transaction was processed (use carbon)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA);
        }

        $amtUsed = $xml->get('CARDAMOUNTUSED');
        if ($amtUsed) {
            $request->changeAmount($amtUsed);
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        $this->conf->set("paycard_response",array());
        $this->conf->set("paycard_response",$xml->arrayDump());
        $temp = $this->conf->get("paycard_response");
        $temp["Balance"] = $temp["BALANCE"];
        $this->conf->set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('AUTHORIZED') == 'true' && $xml->get('AUTHORIZATIONCODE') != '' 
            && $xml->get_first('ERRORMSG') == ''
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // authorization approved, no error
        }

        // the authorizor gave us some failure code
        // authorization failed, response fields in $_SESSION["paycard_response"]
        $this->conf->set("boxMsg","Processor error: ".$errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    protected function handleResponseVoid($vdResult)
    {
        $xml = new XmlData($vdResult["response"]);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $vdResult, PaycardLib::paycard_db());

        $validResponse = 4; // response was parsed as XML but fields didn't match
        // verify that echo'd fields match our request
        if ($xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == 'Gift'
                && $xml->get('AUTHORIZED')
                && $xml->get('AUTHORIZATIONCODE')
                && $xml->get('BALANCE')
        ) {
            $validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
        }

        $resultCode = 0;
        $apprNumber = $xml->get('AUTHORIZATIONCODE');
        $response->setApprovalNum($apprNumber);
        $rMsg = substr($xml->get_first('ERRORMSG'), 0, 100);
        if ($apprNumber != '' && $xml->get('AUTHORIZED') == 'true') {
            $validResponse = 1;
            $resultCode = 1;
            $rMsg = 'Voided';
        }
        $response->setResultMsg($rMsg);
        $response->setResultCode($resultCode);
        $response->setResponseCode($resultCode);
        $response->setNormalizedCode($resultCode);
        $response->setValid($validResponse);

        try {
            $response->saveResponse();
        } catch (Exception $ex) {}

        $comm = $this->pmod->commError($vdResult);
        if ($comm !== false) {
            return $comm === true ? $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM) : $comm;
        }

        // check for data errors (any failure to parse response XML or echo'd field mismatch)
        // invalid server response, we don't know if the transaction was voided (use carbon)
        if ($validResponse != 1) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA);
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        $this->conf->set("paycard_response",array());
        $this->conf->set("paycard_response",$xml->arrayDump());
        $temp = $this->conf->get("paycard_response");
        $temp["Balance"] = $temp["BALANCE"];
        $this->conf->set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('AUTHORIZED') == 'true' && $xml->get('AUTHORIZATIONCODE') != '' 
            && $xml->get_first('ERRORMSG') == '') {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        // the authorizor gave us some failure code
        $this->conf->set("boxMsg","PROCESSOR ERROR: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    protected function handleResponseBalance($balResult)
    {
        $xml = new XmlData($balResult["response"]);

        $comm = $this->pmod->commError($balResult);
        if ($comm !== false) {
            return $comm === true ? $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM) : $comm;
        }

        $this->conf->set("paycard_response",array());
        $this->conf->set("paycard_response",$xml->arrayDump());
        $resp = $this->conf->get("paycard_response");
        if (isset($resp["BALANCE"])) {
            $resp["Balance"] = $resp["BALANCE"];
            $this->conf->set("paycard_response",$resp);
        }

        // there's less to verify for balance checks, just make sure all the fields are there
        if ($xml->isValid()
            && $xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == 'Gift'
            && $xml->get('AUTHORIZED') && $xml->get('AUTHORIZED') == 'true'
            && (!$xml->get('ERRORMSG') || $xml->get_first('ERRORMSG') == '')
            && $xml->get('BALANCE')
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // balance checked, no error
        }

        // the authorizor gave us some failure code
        $this->conf->set("boxMsg","Processor error: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    // generate a partially-daily-unique identifier number according to the gift card processor's limitations
    // along with their CashierID field, it will be a daily-unique identifier on the transaction
    private function valutecIdentifier($transID) 
    {
        $transNo   = (int)$this->conf->get("transno");
        $laneNo    = (int)$this->conf->get("laneno");
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
        if ($this->conf->get("training") == 1) {
            return "45095";
        }
        return $this->conf->get("gcTermID");
    }

    private function getPAN()
    {
        if ($this->conf->get("training") == 1) {
            return "7018525936200000012";
        }
        return $this->conf->get("paycard_PAN");
    }

    private function getTrack2()
    {
        if ($this->conf->get("training") == 1) {
            return "7018525936200000012=68893620";
        }
        return $this->conf->get("paycard_tr2");
    }
}

