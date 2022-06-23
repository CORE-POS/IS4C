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
use COREPOS\pos\plugins\Paycards\xml\BetterXmlData;

/*
 * Mercury Gift Card processing module
 *
 */

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

class ValueLink extends BasicCCModule 
{
    // BEGIN INTERFACE METHODS

    private $pmod;
    private $dialogs;
    public function __construct()
    {
        $this->pmod = new PaycardModule();
        $this->dialogs = new PaycardDialogs();
        $this->pmod->setDialogs($this->dialogs);
        $this->conf = new PaycardConf();
    }

    /* handlesType($type)
     * $type is a constant as defined in paycardLib.php.
     * If you class can handle the given type, return
     * True
     */
    public function handlesType($type)
    {
        if ($type == PaycardLib::PAYCARD_TYPE_VALUELINK) {
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
                $pan4 = substr($this->getPAN(), -4);
                $trans = array($this->conf->get('CashierNo'), $this->conf->get('laneno'), $this->conf->get('transno'));
                $result = $this->dialogs->voidableCheck($pan4, $trans);
                return $this->paycardVoid($result,-1,-1,$json);
            }

            // check card data for anything else
            if ($validate) {
                $this->dialogs->validateCard($this->conf->get('paycard_PAN'), false, false);
            }

            if ($this->conf->get("paycard_mode") == PaycardLib::PAYCARD_MODE_AUTH) {
                $dbc = Database::tDataConnect();
                $res = $dbc->query("SELECT SUM(total) AS ttl FROM localtemptrans WHERE department=902");
                $row = $dbc->fetchRow($res);
                if ($row && abs($row['ttl']) > 0.005) {
                    throw new Exception(PaycardLib::paycardErrBox("Gift Card Error",
                                                     "cannot pay for gift card with a gift card",
                                                     "[clear] to cancel"
                                                 ));
                }
            }

        } catch (Exception $ex) {
            $json['output'] = $ex->getMessage();
            return $json;
        }

        // other modes
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                $this->conf->set('CacheCardType', 'GIFT');
                return PaycardLib::setupAuthJson($json);
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $this->conf->set("paycard_amount",0);
                $this->conf->set("paycard_id",$this->conf->get("LastID")+1); // kind of a hack to anticipate it this way..
                $pluginInfo = new Paycards();
                $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/paycardboxMsgGift.php';

                return $json;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $pluginInfo = new Paycards();
                $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/paycardboxMsgBalance.php';

                return $json;
        } // switch mode
    
        // if we're still here, it's an error
        $json['output'] = $this->dialogs->invalidMode();
        $this->conf->reset();

        return $json;
    }

    /* doSend()
     * Process the paycard request and return
     * an error value as defined in paycardLib.php.
     *
     * On success, return PaycardLib::PAYCARD_ERR_OK.
     * On failure, return anything else and set any
     * error messages to be displayed in
     * $this->conf->["boxMsg"].
     */
    public function doSend($type)
    {
        $this->secondTry = false;
        switch($type) {
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_AUTH: 
                return $this->sendAuth();
            case PaycardLib::PAYCARD_MODE_VOID:
                return $this->sendVoid();
            case PaycardLib::PAYCARD_MODE_BALANCE:
                return $this->sendBalance();
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
                $ttl = $this->conf->get("paycard_amount");
                $dept = $this->conf->get('PaycardDepartmentGift');
                $dept = $dept == '' ? 902 : $dept;
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
                $recordID = $this->last_paycard_transaction_id;
                $charflag = ($recordID != 0) ? 'PT' : '';
                $tcode = $this->conf->get('PaycardsTenderCodeGift');
                $tcode = $tcode == '' ? 'GD' : $tcode;
                TransRecord::addFlaggedTender("Gift Card", $tcode, $amt, $recordID, $charflag);
                $resp = $this->conf->get("paycard_response");
                $this->conf->set("boxMsg","<b>Approved</b><font size=-1>
                                           <p>Used: $" . $this->conf->get("paycard_amount") . "
                                           <br />New balance: $" . $resp["Balance"] . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip
                                           <br>[clear] to cancel and void</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
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
    
    private function sendAuth()
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }

        // prepare data for the request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = $this->conf->get("CashierNo");
        $laneNo = $this->conf->get("laneno");
        $transNo = $this->conf->get("transno");
        $transID = $this->conf->get("paycard_id");
        $amount = $this->conf->get("paycard_amount");
        $amountText = sprintf('%d', $amount * 100);
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                $authMethod = $amount < 0 ? 'Reload' : 'Purchase';  
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $authMethod = 'Reload';
                break;
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $authMethod = 'Reload';
                break;
            default:
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }
        $live = 0;
        $manual = ($this->conf->get("paycard_manual") ? 1 : 0);
        $cardPAN = $this->getPAN();
        $identifier = $this->refnum($transID); 
        
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
                    'ValueLink', $identifier, $live, 'Gift', $authMethod,
                    $amount, $cardPAN,
                    'Mercury', 'Cardholder', $manual, $now);
        $insR = $dbTrans->query($insQ);
        if ($insR === false) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
        $this->last_paycard_transaction_id = $dbTrans->insertID();

        $msgJson = array(
            'amount' => $amountText,
            'transaction_type' => strtolower($authMethod),
            'method' => 'valuelink',
            'currency_code' => 'USD',
            'valuelink' => array(
                'cardholder_name' => 'Cardholder',
                'cc_number' => $cardPAN,
                'credit_card_type' => 'Gift',
            ), 
        );
        $msgJson = json_encode($msgJson);

        $nonce = $this->getNonce();
        $timestamp = floor(microtime(true) * 1000);
        $hmac = $this->getHMAC($msgJson, $nonce, $timestamp);
        $extraHeaders = array(
            CURLOPT_HTTPHEADER => array(
                'apiKey: ' . $this->conf->get('ValueLinkApiKey'),
                'token: ' . $this->conf->get('ValueLinkToken'),
                'Content-type: application/json' ,
                'Authorization: ' . $hmac,
                'nonce: ' . $nonce,
                'timestamp: ' . $timestamp,
            ),
        );
        
        $this->GATEWAY = 'https://api.payeezy.com/v1/transactions';
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = 'https://api-cert.payeezy.com/v1/transactions';
        }

        return $this->curlSend($msgJson, 'POST', false, $extraHeaders);
    }

    private function sendVoid()
    {
        // initialize
        $dbTrans = Database::tDataConnect();
        if (!$dbTrans) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
        }

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $cashierNo = $this->conf->get("CashierNo");
        $laneNo = $this->conf->get("laneno");
        $transNo = $this->conf->get("transno");
        $transID = $this->conf->get("paycard_id");
        $amount = $this->conf->get("paycard_amount");
        $amountText = sprintf('%d', $amount * 100);
        $cardPAN = $this->getPAN();
        $identifier = date('mdHis'); // the void itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)

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
        if (!$search || $dbTrans->numRows($search) == 0) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $log = $dbTrans->fetchRow($search);
        $authcode = $log['xAuthorizationCode'];
        
        // look up original transaction type
        $sql = "SELECT transType AS mode 
                FROM PaycardTransactions 
                WHERE dateID='" . $today . "'
                    AND empNo=" . $cashierNo . "
                    AND registerNo=" . $laneNo . "
                    AND transNo=" . $transNo . "
                    AND transID=" . $transID;
        $search = $dbTrans->query($sql);
        if (!$search || $dbTrans->numRows($search) == 0) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $row = $dbTrans->fetchRow($search);

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
        if ($initR === false) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $this->last_paycard_transaction_id = $dbTrans->insertID();

        $json = array(
            'amount' => $amountText,
            'transaction_type' => 'void',
            'method' => 'valuelink',
            'currency_code' => 'USD',
            'valuelink' => array(
                'cardholder_name' => 'Cardholder',
                'cc_number' => $cardPAN,
                'credit_card_type' => 'Gift',
            ),
        );
        $msgJson = json_encode($msgJson);

        $nonce = $this->getNonce();
        $timestamp = floor(microtime(true) * 1000);
        $hmac = $this->getHMAC($msgJson, $nonce, $timestamp);
        $extraHeaders = array(
            CURLOPT_HTTPHEADER => array(
                'apiKey: ' . $this->conf->get('ValueLinkApiKey'),
                'token: ' . $this->conf->get('ValueLinkToken'),
                'Content-type: application/json' ,
                'Authorization: ' . $hmac,
                'nonce: ' . $nonce,
                'timestamp: ' . $timestamp,
            ),
        );

        $this->GATEWAY = 'https://api.payeezy.com/v1/transactions/' . $authcode;
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = 'https://api-cert.payeezy.com/v1/transactions/'. $authcode;
        }

        return $this->curlSend($msgJson, 'POST', false, $extraHeaders);
    }

    private function sendBalance($domain="w1.mercurypay.com")
    {

        $msgJson = array(
            'transaction_type' => 'balance_inquiry',
            'method' => 'valuelink',
            'scv' => '93111484',
            'valuelink' => array(
                'cardhodlder_name' => 'Cardholder',
                'cc_number' => $this->getPAN(),
                'credit_card_type' => 'Gift',
            ),
        );
        $msgJson = json_encode($msgJson);

        $nonce = $this->getNonce();
        $timestamp = floor(microtime(true) * 1000);
        $hmac = $this->getHMAC($msgJson, $nonce, $timestamp);
        $extraHeaders = array(
            CURLOPT_HTTPHEADER => array(
                'apiKey: ' . $this->conf->get('ValueLinkApiKey'),
                'token: ' . $this->conf->get('ValueLinkToken'),
                'Content-type: application/json' ,
                'Authorization: ' . $hmac,
                'nonce: ' . $nonce,
                'timestamp: ' . $timestamp,
            ),
        );

        $this->GATEWAY = 'https://api.payeezy.com/v1/transactions';
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = 'https://api-cert.payeezy.com/v1/transactions';
        }

        return $this->curlSend($msgJson, 'POST', false, $extraHeaders);
    }

    public function handleResponse($authResult)
    {
        switch($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                return $this->handleResponseAuth($authResult);
            case PaycardLib::PAYCARD_MODE_VOID:
                return $this->handleResponseVoid($authResult);
            case PaycardLib::PAYCARD_MODE_BALANCE:
                return $this->handleResponseBalance($authResult);
        }
    }

    private function handleResponseAuth($authResult)
    {
        $json = json_decode($authResult['response'], true);

        // initialize
        $dbTrans = Database::tDataConnect();

        $now = date('Y-m-d H:i:s'); // full timestamp
        $validResponse = 1;
        $errorMsg = '';
        if (isset($json['Error'])) {
            foreach ($json['Error']['messages'] as $err) {
                $errorMsg .= $err['code'] . ' ';
            }
        }
        $balance = isset($json['valuelink']) && isset($json['valuelink']['current_balance']) ? $json['valuelink']['current_balance'] : 0;
        $tranType = $json['transaction_type'];
        $status = $json['transaction_status'];
        $normalized = $this->getNormalized($status);
        $resultCode = ($normalized >= 3) ? 0 : $normalized;
        $rMsg = $normalized === 3 ? substr($errorMsg, 0, 100) : $status;
        $apprNumber = $json['transaction_id'];

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

        // check for communication errors
        if ($authResult['curlErr'] != CURLE_OK) {
            if ($authResult['curlHTTP'] == '0') {
                $this->conf->set("boxMsg","No response from processor<br />The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

         // check for data errors (any failure to parse response XML or echo'd field mismatch
        if ($validResponse != 1) {
            // invalid server response, we don't know if the transaction was processed (use carbon)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA); 
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        $this->conf->set("paycard_response",array(
            'Balance' => strlen($balance) > 0 ? $balance : 0,
        ));
        /**
          Update authorized amount based on response. If
          the transaction was a refund ("Return") then the
          amount needs to be negative for POS to handle
          it correctly.
        */
        if ($normalized == 1) {
            $realAmt = $json['amount'] / 100;
            if ($realAmt != $this->conf->get('paycard_amount')) {
                $correctionQ = sprintf("UPDATE PaycardTransactions SET amount=%f WHERE
                    dateID=%s AND refNum='%s'",
                    $amt,date("Ymd"),$identifier);
                $dbTrans->query($correctionQ);
                $this->conf->set('paycard_amount', $realAmt);
            }
        }

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if (strtolower($status) == 'approved' && $apprNumber != '') {
            return PaycardLib::PAYCARD_ERR_OK; // authorization approved, no error
        }

        // the authorizor gave us some failure code
        // authorization failed, response fields in $_SESSION["paycard_response"]
        $this->conf->set("boxMsg","Processor error: ".$errorMsg);
        if (strlen(trim($errorMsg)) == 0 && strtolower($status) == 'Declined') {
            $this->conf-set('boxMsg', 'Transaction declined');
        }

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseVoid($vdResult)
    {
        $json = json_decode($vdResult['response'], true);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the void request
        $now = date('Y-m-d H:i:s'); // full timestamp

        $validResponse = 1;
        $errorMsg = '';
        if (isset($json['Error'])) {
            foreach ($json['Error']['messages'] as $err) {
                $errorMsg .= $err['code'] . ' ';
            }
        }
        $balance = isset($json['valuelink']) && isset($json['valuelink']['current_balance']) ? $json['valuelink']['current_balance'] : 0;
        $tranType = $json['transaction_type'];
        $status = $json['transaction_status'];
        $normalized = $this->getNormalized($status);
        $resultCode = ($normalized >= 3) ? 0 : $normalized;
        $rMsg = $normalized === 3 ? substr($errorMsg, 0, 100) : $status;
        $apprNumber = $json['transaction_id'];

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
                                $vdResult['curlTime'],
                                $vdResult['curlErr'],
                                $vdResult['curlHTTP'],
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

        if ($vdResult['curlErr'] != CURLE_OK) {
            if ($vdResult['curlHTTP'] == '0'){
                $this->conf->set("boxMsg","No response from processor<br />The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }

        // check for data errors (any failure to parse response XML or echo'd field mismatch)
        if ($validResponse != 1) {
            // invalid server response, we don't know if the transaction was voided (use carbon)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_DATA);
        }

        // put the parsed response into session so the caller, receipt printer, etc can get the data they need
        $this->conf->set("paycard_response",array(
            'Balance' => strlen($balance) > 0 ? $balance : 0,
        ));

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($status == 'Approved' && $apprNumber != '') {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        // the authorizor gave us some failure code
        $this->conf->set("boxMsg","PROCESSOR ERROR: " . $errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseBalance($balResult)
    {
        $json = json_decode($balResult['response'], true);

        if ($balResult['curlErr'] != CURLE_OK) {
            if ($balResult['curlHTTP'] == '0'){
                $this->conf->set("boxMsg","No response from processor<br />The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }

        $this->conf->set("paycard_response",array());
        $resp = array();
        $balance = isset($json['valuelink']) && isset($json['valuelink']['current_balance']) ? $json['valuelink']['current_balance'] : 0;
        if (strlen($balance) > 0) {
            $resp["Balance"] = $balance;
            $this->conf->set("paycard_response",$resp);
        }

        $tranType = $json['transaction_type'];
        $cmdStatus = $json['transaction_status'];
        if ($tranType == 'balance_inquiry' && $cmdStatus == 'approved') {
            return PaycardLib::PAYCARD_ERR_OK; // balance checked, no error
        }

        // the authorizor gave us some failure code
        $errorMsg = '';
        if (isset($json['Error'])) {
            foreach ($json['Error']['messages'] as $err) {
                $errorMsg .= $err['code'] . ' ' . $err['description'];
            }
        }
        if ($errorMsg == '' && isset($json['bank_message'])) {
            $errorMsg = $json['bank_message'];
        }
        $this->conf->set("boxMsg","Processor error: ". $errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    private function getPAN()
    {
        return $this->conf->get("paycard_PAN");
    }

    private function getTrack2()
    {
        if ($this->conf->get("training") == 1) {
            return false;
        }
        return $this->conf->get("paycard_tr2");
    }

    private function getNormalized($status)
    {
        if ($status === 'approved') {
            return 1;
        } elseif ($status === 'declined') {
            return 2;
        } elseif ($status === 'Not Processed') {
            return 3;
        }
        return 4;
    }

    private function getNonce()
    {
        $ret = '';
        for ($i=0; $i<18; $i++) {
            $ret .= random_int(0, 9);
        }

        return $ret;
    }

    private function getHMAC($payload, $nonce, $timestamp)
    {
        $apiKey = $this->conf->get('ValueLinkApiKey');
        $apiSecret = $this->conf->get('ValueLinkApiSecret');
        $token = $this->conf->get('ValueLinkToken');
        $data = $apiKey . $nonce . $timestamp . $token . $payload;
        $hashAlgorithm = "sha256";

        $hmac = hash_hmac($hashAlgorithm, $data, $apiSecret, false);
        $authorization = base64_encode($hmac);

        return $authorization;
    }
}

