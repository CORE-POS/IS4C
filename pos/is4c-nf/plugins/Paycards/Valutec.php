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
        try {
            $enabled = PaycardDialogs::enabledCheck();
            // error checks based on processing mode
            if (CoreLocal::get("paycard_mode") == PaycardLib::PAYCARD_MODE_VOID) {
                // use the card number to find the trans_id
                $pan4 = substr($this->getPAN(), -4);
                $trans = array(CoreLocal::get('CashierNo'), CoreLocal::get('laneno'), CoreLocal::get('transno'));
                $result = PaycardDialogs::voidableCheck($pan4, $trans);
                return $this->paycard_void($result,-1,-1,$json);
            }

            // check card data for anything else
            if ($validate) {
                $valid = PaycardDialogs::validateCard(CoreLocal::get('paycard_PAN'), false);
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
                $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgBalance.php';
                return $json;
        } // switch mode
    
        // if we're still here, it's an error
        $json['output'] = PaycardDialogs::invalidMode();
        return $json;
    }

    protected $sendByType = array(
        PaycardLib::PAYCARD_MODE_ACTIVATE => 'send_auth',
        PaycardLib::PAYCARD_MODE_ADDVALUE => 'send_auth',
        PaycardLib::PAYCARD_MODE_AUTH => 'send_auth',
        PaycardLib::PAYCARD_MODE_VOID => 'send_void',
        PaycardLib::PAYCARD_MODE_VOIDITEM => 'send_void',
        PaycardLib::PAYCARD_MODE_BALANCE => 'send_balance',
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
                CoreLocal::set("autoReprint",1);
                $ttl = CoreLocal::get("paycard_amount");
                PrehLib::deptkey($ttl*100,9020);
                $resp = CoreLocal::get("paycard_response");    
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                CoreLocal::set("autoReprint",1);
                $record_id = $this->last_paycard_transaction_id;
                $charflag = ($record_id != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender("Gift Card", "GD", $amt, $record_id, $charflag);
                $resp = CoreLocal::get("paycard_response");
                CoreLocal::set("boxMsg","<b>Approved</b><font size=-1>
                                           <p>Used: $" . CoreLocal::get("paycard_amount") . "
                                           <br />New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip
                                           <br>[void] to cancel and void</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
            case PaycardLib::PAYCARD_MODE_VOIDITEM:
                CoreLocal::set("autoReprint",1);
                $void = new Void();
                $void->voidid(CoreLocal::get("paycard_id"), array());
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
    
    private function send_auth()
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
        $request = new PaycardGiftRequest($this->valutecIdentifier(CoreLocal::get('paycard_id')));
        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $mode = "";
        $logged_mode = $mode;
        $authMethod = "";
        switch (CoreLocal::get("paycard_mode")) {
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
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $request->setPAN($cardPAN);
        $request->setIssuer('Valutec');
        $request->setProcessor('Valutec');
        $request->setMode($logged_mode);
        if ($cardTr2) {
            $request->setSent(0, 0, 0, 1);
        } else {
            $request->setSent(1, 0, 0, 0);
        }
        
        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
                
        $authFields = array(
            'ProgramType'       => $program,
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

    private function send_void()
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }
        $request = new PaycardVoidRequest($this->valutecIdentifier(CoreLocal::get('paycard_id')));

        $program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
        $mode = 'void';
        $cardPAN = $this->getPAN();
        $identifier = date('mdHis'); // the void itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
        $termID = $this->getTermID();

        try {
            $log = $request->findOriginal();
            $request->saveRequest();
        } catch (Exception $ex) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $log = $dbTrans->fetch_array($search);
        $authcode = $log['xAuthorizationCode'];
        $this->temp = $authcode;

        // assemble and send void request
        $vdMethod = 'Void';
        $vdFields = array(
            'ProgramType'       => $program,
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

    private function send_balance()
    {
        // prepare data for the request
        $cashierNo = CoreLocal::get("CashierNo");
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

    protected $respondByType = array(
        PaycardLib::PAYCARD_MODE_ACTIVATE => 'handleResponseAuth',
        PaycardLib::PAYCARD_MODE_ADDVALUE => 'handleResponseAuth',
        PaycardLib::PAYCARD_MODE_AUTH => 'handleResponseAuth',
        PaycardLib::PAYCARD_MODE_VOID => 'handleResponseVoid',
        PaycardLib::PAYCARD_MODE_VOIDITEM => 'handleResponseVoid',
        PaycardLib::PAYCARD_MODE_BALANCE => 'handleResponseBalance',
    );

    private function handleResponseAuth($authResult)
    {
        $xml = new xmlData($authResult["response"]);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);

        // initialize
        $dbTrans = Database::tDataConnect();

        $program = 'Gift';

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

        $response->setBalance($balance);

        $resultCode = 0;
        $apprNumber = $xml->get('AUTHORIZATIONCODE');
        $response->setApprovalNum($apprNumber);
        $rMsg = '';
        if ($apprNumber != '' && $xml->get('AUTHORIZED') == 'true') {
            $validResponse = 1;
            $resultCode = 1;
            $rMsg = 'Approved';
        } else {
            $rMsg = substr($xml->get_first('ERRORMSG'), 0, 100);
        }
        $response->setResultMsg($rMsg);
        $response->setResultCode($resultCode);
        $response->setResponseCode($resultCode);
        $response->setNormalizedCode($resultCode);
        $response->setValid($validResponse);

        try {
            $response->saveResponse();
        } catch (Exception $ex) {}

        // check for communication errors (any cURL error or any HTTP code besides 200)
        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0') {
                    CoreLocal::set("boxMsg","No response from processor<br />
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
            $request->changeAmount($amtUsed);
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        CoreLocal::set("paycard_response",array());
        CoreLocal::set("paycard_response",$xml->array_dump());
        $temp = CoreLocal::get("paycard_response");
        $temp["Balance"] = $temp["BALANCE"];
        CoreLocal::set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('AUTHORIZED') == 'true' && $xml->get('AUTHORIZATIONCODE') != '' 
            && $xml->get_first('ERRORMSG') == ''
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // authorization approved, no error
        }

        // the authorizor gave us some failure code
        // authorization failed, response fields in $_SESSION["paycard_response"]
        CoreLocal::set("boxMsg","Processor error: ".$errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    private function handleResponseVoid($vdResult)
    {
        $xml = new xmlData($vdResult["response"]);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);

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
        $response->setApprovalNum($apprNumber);
        $rMsg = '';
        if ($apprNumber != '' && $xml->get('AUTHORIZED') == 'true') {
            $validResponse = 1;
            $resultCode = 1;
            $rMsg = 'Voided';
        } else {
            $rMsg = substr($xml->get_first('ERRORMSG'), 0, 100);
        }
        $response->setResultMsg($rMsg);
        $response->setResultCode($resultCode);
        $response->setResponseCode($resultCode);
        $response->setNormalizedCode($resultCode);
        $response->setValid($validResponse);

        try {
            $response->saveResponse();
        } catch (Exception $ex) {}

        if ($vdResult['curlErr'] != CURLE_OK || $vdResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0') {
                CoreLocal::set("boxMsg","No response from processor<br />
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

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        CoreLocal::set("paycard_response",array());
        CoreLocal::set("paycard_response",$xml->array_dump());
        $temp = CoreLocal::get("paycard_response");
        $temp["Balance"] = $temp["BALANCE"];
        CoreLocal::set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('AUTHORIZED') == 'true' && $xml->get('AUTHORIZATIONCODE') != '' 
            && $xml->get_first('ERRORMSG') == '') {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        // the authorizor gave us some failure code
        CoreLocal::set("boxMsg","PROCESSOR ERROR: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseBalance($balResult)
    {
        $xml = new xmlData($balResult["response"]);
        $program = 'Gift';

        if ($balResult['curlErr'] != CURLE_OK || $balResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0'){
                CoreLocal::set("boxMsg","No response from processor<br />
                                          The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
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
        if ($xml->isValid()
            && $xml->get('TRANSACTIONTYPE') && $xml->get('TRANSACTIONTYPE') == $program
            && $xml->get('AUTHORIZED') && $xml->get('AUTHORIZED') == 'true'
            && (!$xml->get('ERRORMSG') || $xml->get_first('ERRORMSG') == '')
            && $xml->get('BALANCE')
        ) {
            return PaycardLib::PAYCARD_ERR_OK; // balance checked, no error
        }

        // the authorizor gave us some failure code
        CoreLocal::set("boxMsg","Processor error: ".$xml->get_first("ERRORMSG"));

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
            return "45095";
        } else {
            return CoreLocal::get("gcTermID");
        }
    }

    private function getPAN()
    {
        if (CoreLocal::get("training") == 1) {
            return "7018525936200000012";
        } else {
            return CoreLocal::get("paycard_PAN");
        }
    }

    private function isLive()
    {
        if (CoreLocal::get("training") == 1) {
            return 0;
        } else {
            return 1;
        }
    }

    private function getTrack2()
    {
        if (CoreLocal::get("training") == 1) {
            return "7018525936200000012=68893620";
        } else {
            return CoreLocal::get("paycard_tr2");
        }
    }
}

