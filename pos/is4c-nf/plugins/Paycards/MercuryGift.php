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
use COREPOS\pos\plugins\Paycards\xml\XmlData;

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
    protected $SOAPACTION = "http://www.mercurypay.com/GiftTransaction";
    private $secondTry;
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
                $pan4 = substr($this->getPAN(), -4);
                $trans = array($this->conf->get('CashierNo'), $this->conf->get('laneno'), $this->conf->get('transno'));
                $result = $this->dialogs->voidableCheck($pan4, $trans);
                return $this->paycardVoid($result,-1,-1,$json);
            }

            // check card data for anything else
            if ($validate) {
                $this->dialogs->validateCard($this->conf->get('paycard_PAN'), false, false);
            }
        } catch (Exception $ex) {
            $json['output'] = $ex->getMessage();
            return $json;
        }

        // other modes
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
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
        $this->conf->reset();
        $json['output'] = $this->dialogs->invalidMode();

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
    
    private function sendAuth($domain="w1.mercurypay.com")
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
        $amountText = number_format(abs($amount), 2, '.', '');
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                $authMethod = $amount < 0 ? 'Return' : 'NoNSFSale';  
                break;
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
                $authMethod = 'Reload';
                break;
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $authMethod = 'Issue';
                break;
            default:
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }
        $loggedMode = $authMethod === 'NoNSFSale' ? 'Sale' : $authMethod;
        $termID = $this->getTermID();
        $password = $this->getPw();
        $live = 0;
        $manual = ($this->conf->get("paycard_manual") ? 1 : 0);
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
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
                    'MercuryGift', $identifier, $live, 'Gift', $loggedMode,
                    $amountText, $cardPAN,
                    'Mercury', 'Cardholder', $manual, $now);
        $insR = $dbTrans->query($insQ);
        if ($insR === false) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
        }
        $this->last_paycard_transaction_id = $dbTrans->insertID();

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
        $msgXml .= $cardTr2 ? "<Track2>$cardTr2</Track2>" : "<AcctNo>$cardPAN</AcctNo>";
        $msgXml .= "</Account>
            <Amount>
            <Purchase>$amountText</Purchase>
            </Amount>
            </Transaction>
            </TStream>";
        

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        $this->GATEWAY = "https://$domain/ws/ws.asmx";
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    private function sendVoid($domain="w1.mercurypay.com")
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
        $amountText = number_format(abs($amount), 2, '.', '');
        $cardPAN = $this->getPAN();
        $cardTr2 = $this->getTrack2();
        $identifier = date('mdHis'); // the void itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
        $termID = $this->getTermID();
        $password = $this->getPw();

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
        if ($initR === false) {
            return PaycardLib::PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
        }
        $this->last_paycard_transaction_id = $dbTrans->insertID();

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
        $msgXml .= $cardTr2 ? "<Track2>$cardTr2</Track2>" : "<AcctNo>$cardPAN</AcctNo>";
        $msgXml .= "</Account>
            <Amount>
            <Purchase>$amountText</Purchase>
            </Amount>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        $this->GATEWAY = "https://$domain/ws/ws.asmx";
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    private function sendBalance($domain="w1.mercurypay.com")
    {
        // prepare data for the request
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
        $msgXml .= $cardTr2 ? "<Track2>$cardTr2</Track2>" : "<AcctNo>$cardPAN</AcctNo>";
        $msgXml .= "</Account>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        $this->GATEWAY = "https://$domain/ws/ws.asmx";
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
        }

        return $this->curlSend($soaptext,'SOAP');
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
        $resp = $this->desoapify("GiftTransactionResult",
            $authResult["response"]);
        $xml = new XmlData($resp);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the request
        $now = date('Y-m-d H:i:s'); // full timestamp
        $transID = $this->conf->get("paycard_id");
        $identifier = $this->refnum($transID); 

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
            } elseif (!$xml->get('CMDSTATUS')) {
                $validResponse = -2; // response was parsed as XML but fields didn't match
            } elseif (!$xml->get('TRANTYPE')) {
                $validResponse = -3; // response was parsed as XML but fields didn't match
            } elseif (!$xml->get('INVOICENO')) {
                $validResponse = -4; // response was parsed as XML but fields didn't match
            }
        }

        $status = $xml->get_first('CMDSTATUS');
        $normalized = $this->getNormalized($status);
        $resultCode = ($normalized >= 3) ? 0 : $normalized;
        $rMsg = $normalized === 3 ? substr($errorMsg, 0, 100) : $status;
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
                if (!$this->secondTry) {
                    $this->secondTry = true;
                    return $this->sendAuth("w2.backuppay.com");
                }
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
        $this->conf->set("paycard_response",array());
        $this->conf->set("paycard_response",$xml->arrayDump());
        $temp = $this->conf->get("paycard_response");
        $temp["Balance"] = isset($temp['BALANCE']) ? $temp["BALANCE"] : 0;
        $this->conf->set("paycard_response",$temp);
        /**
          Update authorized amount based on response. If
          the transaction was a refund ("Return") then the
          amount needs to be negative for POS to handle
          it correctly.
        */
        if ($xml->get_first("AUTHORIZE")) {
            $this->conf->set("paycard_amount",$xml->get_first("AUTHORIZE"));
            if ($xml->get_first('TRANCODE') && $xml->get_first('TRANCODE') == 'Return') {
                $this->conf->set("paycard_amount",-1*$xml->get_first("AUTHORIZE"));
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
        $this->conf->set("boxMsg","Processor error: ".$errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseVoid($vdResult)
    {
        $resp = $this->desoapify("GiftTransactionResult",
            $vdResult["response"]);
        $xml = new XmlData($resp);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the void request
        $now = date('Y-m-d H:i:s'); // full timestamp

        $validResponse = -2;
        // verify that echo'd fields match our request
        if ($xml->get('TRANTYPE') && $xml->get('CMDSTATUS') && $xml->get('BALANCE')) {
            // response was parsed normally, echo'd fields match, and other required fields are present
            $validResponse = 1;
        }

        $status = $xml->get_first('CMDSTATUS');
        $errorMsg = $xml->get_first("TEXTRESPONSE");
        $normalized = $this->getNormalized($status);
        $resultCode = ($normalized >= 3) ? 0 : $normalized;
        $rMsg = $normalized === 3 ? substr($errorMsg, 0, 100) : $status;
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
                                $vdResult['curlTime'],
                                $vdResult['curlErr'],
                                $vdResult['curlHTTP'],
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
            if ($vdResult['curlHTTP'] == '0'){
                if (!$this->secondTry){
                    $this->secondTry = true;
                    return $this->sendVoid("w2.backuppay.com");
                }
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
        $this->conf->set("paycard_response",array());
        $this->conf->set("paycard_response",$xml->arrayDump());
        $temp = $this->conf->get("paycard_response");
        $temp["Balance"] = isset($temp['BALANCE']) ? $temp["BALANCE"] : 0;
        $this->conf->set("paycard_response",$temp);

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($xml->get('CMDSTATUS') == 'Approved' && $xml->get('REFNO') != '' ) {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        // the authorizor gave us some failure code
        $this->conf->set("boxMsg","PROCESSOR ERROR: ".$xml->get_first("ERRORMSG"));

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseBalance($balResult)
    {
        $resp = $this->desoapify("GiftTransactionResult",
            $balResult["response"]);
        $xml = new XmlData($resp);

        if ($balResult['curlErr'] != CURLE_OK || $balResult['curlHTTP'] != 200) {
            if ($balResult['curlHTTP'] == '0'){
                if (!$this->secondTry) {
                    $this->secondTry = true;
                    return $this->sendBalance("w2.backuppay.com");
                }
                $this->conf->set("boxMsg","No response from processor<br />The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM); // comm error, try again
        }

        $this->conf->set("paycard_response",array());
        $this->conf->set("paycard_response",$xml->arrayDump());
        $resp = $this->conf->get("paycard_response");
        if (isset($resp["BALANCE"])) {
            $resp["Balance"] = $resp["BALANCE"];
            $this->conf->set("paycard_response",$resp);
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
        $this->conf->set("boxMsg","Processor error: ".$xml->get_first("TEXTRESPONSE"));

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    private function getTermID()
    {
        if ($this->conf->get("training") == 1) {
            return "595901";
        }
        return $this->conf->get('MercuryGiftID');
    }

    private function getPw()
    {
        if ($this->conf->get("training") == 1) {
            return "xyz";
        }
        return $this->conf->get('MercuryGiftPassword');
    }

    private function getPAN()
    {
        if ($this->conf->get("training") == 1) {
            return "6050110000000296951";
        }
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
        if ($status === 'Approved') {
            return 1;
        } elseif ($status === 'Declined') {
            return 2;
        } elseif ($status === 'Error') {
            return 3;
        }
        return 4;
    }
}

