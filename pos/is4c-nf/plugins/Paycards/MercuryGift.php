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
        } elseif ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED_GIFT) {
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
        if (substr($cardPAN, 0, 8) == "02AA0080") {
            $encBlock = new COREPOS\pos\plugins\Paycards\card\EncBlock();
            $e2e = $encBlock->parseEncBlock($cardPAN);
            $cardPAN = str_repeat('*', 12) . $e2e['Last4'];
        }
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
        $msgXml .= $this->getAccount();
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
            $this->GATEWAY = "https://w1.mercurycert.net/ws/ws.asmx?WSDL";
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
        $msgXml .= $this->getAccount();
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
            $this->GATEWAY = "https://w1.mercurycert.net/ws/ws.asmx?WSDL";
        }

        return $this->curlSend($soaptext,'SOAP');
    }

    private function sendBalance($domain="w1.mercurypay.com")
    {
        // prepare data for the request
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
        $msgXml .= $this->getAccount();
        $msgXml .= "</Account>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("GiftTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        $this->GATEWAY = "https://$domain/ws/ws.asmx";
        if ($this->conf->get("training") == 1) {
            $this->GATEWAY = "https://w1.mercurycert.net/ws/ws.asmx?WSDL";
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
        $xml = new BetterXmlData($resp);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the request
        $now = date('Y-m-d H:i:s'); // full timestamp
        $transID = $this->conf->get("paycard_id");
        $identifier = $this->refnum($transID); 

        $validResponse = 1;
        $errorMsg = $xml->query('/RStream/CmdResponse/TextResponse');
        $balance = $xml->query('/RStream/TranResponse/Amount/Balance');
        $tranType = $xml->query('/RStream/TranResponse/TranType');
        $status = $xml->query('/RStream/CmdResponse/CmdStatus');
        $invoice = $xml->query('/RStream/TranResponse/InvoiceNo');
        $normalized = $this->getNormalized($status);
        $resultCode = ($normalized >= 3) ? 0 : $normalized;
        $rMsg = $normalized === 3 ? substr($errorMsg, 0, 100) : $status;
        $apprNumber = $xml->query('/RStream/TranResponse/RefNo');

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
            $amt = $xml->query('/RStream/TranResponse/Amount/Authorize');
            $tranCode = $xml->query('/RStream/TranResponse/TranCode');
            $realAmt = $tranCode == 'Return' ? -1*$amt : $amt;
            if ($realAmt != $this->conf->get('paycard_amount')) {
                $correctionQ = sprintf("UPDATE PaycardTransactions SET amount=%f WHERE
                    dateID=%s AND refNum='%s'",
                    $amt,date("Ymd"),$identifier);
                $dbTrans->query($correctionQ);
                $this->conf->set('paycard_amount', $realAmt);
            }
        }

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($status == 'Approved' && $apprNumber != '') {
            return PaycardLib::PAYCARD_ERR_OK; // authorization approved, no error
        }

        /**
         * For strange reasons the gateway sometimes declines encrypted prepaid
         * transactions but includes the full card number in the response. Until
         * this gets correct, we can just re-submit the transaction using the
         * decrypted card number. This only applies to gift (i.e., non-PCI) cards.
         */
        if ($status == 'Declined' && $this->conf->get('paycard_type') == PaycardLib::PAYCARD_TYPE_ENCRYPTED_GIFT) {
            $realPAN = $xml->query('/RStream/TranResponse/AcctNo');
            if (strlen($realPAN) == 19) {
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $this->conf->set('paycard_PAN', $realPAN);
                return $this->sendAuth();
            }
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
        $xml = new BetterXmlData($resp);

        // initialize
        $dbTrans = Database::tDataConnect();

        // prepare data for the void request
        $now = date('Y-m-d H:i:s'); // full timestamp

        $validResponse = 1;
        $errorMsg = $xml->query('/RStream/CmdResponse/TextResponse');
        $balance = $xml->query('/RStream/TranResponse/Amount/Balance');
        $tranType = $xml->query('/RStream/TranResponse/TranType');
        $status = $xml->query('/RStream/CmdResponse/CmdStatus');
        $invoice = $xml->query('/RStream/TranResponse/InvoiceNo');
        $normalized = $this->getNormalized($status);
        $resultCode = ($normalized >= 3) ? 0 : $normalized;
        $rMsg = $normalized === 3 ? substr($errorMsg, 0, 100) : $status;
        $apprNumber = $xml->query('/RStream/TranResponse/RefNo');

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
        $this->conf->set("paycard_response",array(
            'Balance' => strlen($balance) > 0 ? $balance : 0,
        ));

        // comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
        if ($status == 'Approved' && $apprNumber != '') {
            return PaycardLib::PAYCARD_ERR_OK; // void successful, no error
        }

        /**
         * For strange reasons the gateway sometimes declines encrypted prepaid
         * transactions but includes the full card number in the response. Until
         * this gets correct, we can just re-submit the transaction using the
         * decrypted card number. This only applies to gift (i.e., non-PCI) cards.
         */
        if ($status == 'Declined' && $this->conf->get('paycard_type') == PaycardLib::PAYCARD_TYPE_ENCRYPTED_GIFT) {
            $realPAN = $xml->query('/RStream/TranResponse/AcctNo');
            if (strlen($realPAN) == 19) {
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $this->conf->set('paycard_PAN', $realPAN);
                return $this->sendVoid();
            }
        }

        // the authorizor gave us some failure code
        $this->conf->set("boxMsg","PROCESSOR ERROR: " . $errorMsg);

        return PaycardLib::PAYCARD_ERR_PROC; 
    }

    private function handleResponseBalance($balResult)
    {
        $resp = $this->desoapify("GiftTransactionResult",
            $balResult["response"]);
        $xml = new BetterXmlData($resp);

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
        $resp = array();
        $balance = $xml->query('/RStream/TranResponse/Amount/Balance');
        if (strlen($balance) > 0) {
            $resp["Balance"] = $balance;
            $this->conf->set("paycard_response",$resp);
        }

        $tranType = $xml->query('/RStream/TranResponse/TranType');
        $cmdStatus = $xml->query('/RStream/CmdResponse/CmdStatus');
        if ($tranType == 'PrePaid' && $cmdStatus == 'Approved') {
            return PaycardLib::PAYCARD_ERR_OK; // balance checked, no error
        }

        /**
         * For strange reasons the gateway sometimes declines encrypted prepaid
         * transactions but includes the full card number in the response. Until
         * this gets correct, we can just re-submit the transaction using the
         * decrypted card number. This only applies to gift (i.e., non-PCI) cards.
         */
        if ($cmdStatus == 'Declined' && $this->conf->get('paycard_type') == PaycardLib::PAYCARD_TYPE_ENCRYPTED_GIFT) {
            $realPAN = $xml->query('/RStream/TranResponse/AcctNo');
            if (strlen($realPAN) == 19) {
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $this->conf->set('paycard_PAN', $realPAN);
                return $this->sendBalance();
            }
        }

        // the authorizor gave us some failure code
        $textResponse = $xml->query('/RStream/CmdResponse/TextResponse');
        $this->conf->set("boxMsg","Processor error: ". $textResponse);

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    private function getTermID()
    {
        if ($this->conf->get("training") == 1) {
            return '019588466313922';
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

    /**
     * @return string XML
     *   - Encryption fields if PAN is P2PE block
     *   - Otherwise Track 2 if present
     *   - Otherwise PAN as account number
     */
    private function getAccount()
    {
        $pan = $this->getPAN();
        $tr2 = $this->getTrack2();
        if (substr($pan, 0, 8) == "02AA0080") {
            $encBlock = new COREPOS\pos\plugins\Paycards\card\EncBlock();
            $e2e = $encBlock->parseEncBlock($this->conf->get("paycard_PAN"));
            return <<<XML
<EncryptedFormat>{$e2e['Format']}</EncryptedFormat>
<AccountSource>Swiped</AccountSource>
<EncryptedBlock>{$e2e['Block']}</EncryptedBlock>
<EncryptedKey>{$e2e['Key']}</EncryptedKey>
XML;
        } elseif ($tr2) {
            return "<Track2>{$tr2}</Track2>";
        }

        return "<AcctNo>{$pan}</AcctNo>";
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

