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
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\plugins\Paycards\card\CardReader;
use COREPOS\pos\plugins\Paycards\xml\XmlData;

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

class GoEMerchant extends BasicCCModule 
{

    private $voidTrans;
    private $voidRef;
    private $pmod;
    public function __construct()
    {
        $this->pmod = new PaycardModule();
        $this->pmod->setDialogs(new PaycardDialogs());
        $this->conf = new PaycardConf();
    }

    public function handlesType($type)
    {
        if ($type == PaycardLib::PAYCARD_TYPE_CREDIT) {
            return true;
        }
        return false;
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
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                return $this->handleResponseAuth($authResult);
            case PaycardLib::PAYCARD_MODE_VOID:
                return $this->handleResponseVoid($authResult);
        }
    }

    private function handleResponseAuth($authResult)
    {
        $xml = new XmlData($authResult['response']);
        $dbTrans = PaycardLib::paycard_db();

        // prepare some fields to store the parsed response; we'll add more as we verify it
        $now = date('Y-m-d H:i:s'); // full timestamp
        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("STATUS");
        if (!$responseCode) {
            $validResponse = -3;
        }
        // aren't two separate codes from goemerchant
        $resultCode = $xml->get_first("STATUS");
        $resultMsg = $xml->get_first("AUTH_RESPONSE");
        $rMsg = $resultMsg;
        if ($resultMsg) {
            $rMsg = $resultMsg;
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }
        }
        $xTransID = $xml->get("REFERENCE_NUMBER");
        if (!$xTransID) {
            $validResponse = -3;
        }
        $apprNumber = $xml->get_first("AUTH_CODE");
        // valid credit (return) transactions don't have an approval number

        /**
          Log transaction in newer table
        */
        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($resultCode == 1) {
            $normalized = 1;
        } else if ($resultCode == 2) {
            $normalized = 2;
        } else if ($resultCode == 0) {
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
                                xTransactionID='%s'
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
                            $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        $comm = $this->pmod->commError($authResult);
        if ($comm !== false) {
            TransRecord::addcomment('');
            return $comm === true ? $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM) : $comm;
        }

        switch ($xml->get("STATUS")) {
            case 1: // APPROVED
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                $this->conf->set("boxMsg",$resultMsg);
                TransRecord::addcomment("");    
                break;
            case 0: // ERROR
                $this->conf->set("boxMsg","");
                $texts = $xml->get_first("ERROR");
                $this->conf->set("boxMsg","Error: $texts");
                TransRecord::addcomment("");    
                break;
            default:
                TransRecord::addcomment("");    
                $this->conf->set("boxMsg","An unknown error occurred<br />at the gateway");
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    private function handleResponseVoid($authResult)
    {
        $xml = new XmlData($authResult['response']);
        // prepare some fields to store the parsed response; we'll add more as we verify it
        $now = date('Y-m-d H:i:s'); // full timestamp

        $dbTrans = PaycardLib::paycard_db();
        // prepare some fields to store the request and the parsed response; we'll add more as we verify it

        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("STATUS1");
        if (!$responseCode) {
            $validResponse = -3;
        }
        $resultCode = $xml->get_first("STATUS1");
        $resultMsg = $xml->get_first("RESPONSE1");
        $rMsg = $resultMsg;
        if ($resultMsg) {
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }
        }

        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($resultCode == 1) {
            $normalized = 1;
        } else if ($resultCode == 2) {
            $normalized = 2;
        } else if ($resultCode == 0) {
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
                                xResultMessage='%s'
                            WHERE paycardTransactionID=%d",
                            $now,
                            $authResult['curlTime'],
                            $authResult['curlErr'],
                            $authResult['curlHTTP'],
                            $normalized,
                            $responseCode,
                            $resultCode,
                            $rMsg,
                            $this->last_paycard_transaction_id
        );
        $dbTrans->query($finishQ);

        $comm = $this->pmod->commError($authResult);
        if ($comm !== false) {
            return $comm === true ? $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM) : $comm;
        }

        switch ($xml->get("STATUS1")) {
            case 1: // APPROVED
                return PaycardLib::PAYCARD_ERR_OK;
            case 2: // DECLINED
                $this->conf->set("boxMsg", $resultMsg);
                break;
            case 0: // ERROR
                $this->conf->set("boxMsg","");
                $texts = $xml->get_first("ERROR1");
                $this->conf->set("boxMsg","Error: $texts");
                break;
            default:
                $this->conf->set("boxMsg","An unknown error occurred<br />at the gateway");
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    public function cleanup($json)
    {
        switch ($this->conf->get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_AUTH:
                // cast to string. tender function expects string input
                // numeric input screws up parsing on negative values > $0.99
                $amt = "".(-1*($this->conf->get("paycard_amount")));
                $tType = 'CC';
                if ($this->conf->get('paycard_issuer') == 'American Express') {
                    $tType = 'AX';
                }
                // if the transaction has a non-zero paycardTransactionID,
                // include it in the tender line
                $recordID = $this->last_paycard_transaction_id;
                $charflag = ($recordID != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender("Credit Card", $tType, $amt, $recordID, $charflag);
                $this->conf->set("boxMsg",
                        "<b>Approved</b>
                        <font size=-1>
                        <p>Please verify cardholder signature
                        <p>[enter] to continue
                        <br>\"rp\" to reprint slip
                        <br>[void] " . _('to reverse the charge') . "
                        </font>");
                if ($this->conf->get("paycard_amount") <= $this->conf->get("CCSigLimit") && $this->conf->get("paycard_amount") >= 0) {
                    $this->conf->set("boxMsg",
                            "<b>Approved</b>
                            <font size=-1>
                            <p>No signature required
                            <p>[enter] to continue
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                } else if ($this->conf->get('PaycardsSigCapture') != 1) {
                    $json['receipt'] = 'ccSlip';
                }
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
                $void = new COREPOS\pos\parser\parse\VoidCmd($this->conf);
                $void->voidid($this->conf->get("paycard_id"), array());
                $this->conf->set("boxMsg","<b>Voided</b>
                                           <p><font size=-1>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>");
                break;    
        }

        return $json;
    }

    public function doSend($type)
    {
        switch ($type) {
            case PaycardLib::PAYCARD_MODE_AUTH: 
                return $this->sendAuth();
            case PaycardLib::PAYCARD_MODE_VOID: 
                return $this->sendVoid(); 
            default:
                $this->conf->reset();
                return $this->setErrorMsg(0);
        }
    }    

    private function sendAuth()
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans) {
            $this->conf->reset();
            // database error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $now = date('Y-m-d H:i:s'); // full timestamp
        $cashierNo = $this->conf->get("CashierNo");
        $laneNo = $this->conf->get("laneno");
        $transNo = $this->conf->get("transno");
        $transID = $this->conf->get("paycard_id");
        $amount = $this->conf->get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = (($amount < 0) ? 'retail_alone_credit' : 'retail_sale');
        // standardize transaction type for PaycardTransactions
        $loggedMode = ($mode == 'retail_sale') ? 'Sale' : 'Return';
        $manual = ($this->conf->get("paycard_manual") ? 1 : 0);
        $this->trans_pan['pan'] = $this->conf->get("paycard_PAN");
        $cardPAN = $this->trans_pan['pan'];
        $reader = new CardReader();
        $cardPANmasked = $reader->maskPAN($cardPAN,0,4);
        $cardIssuer = $this->conf->get("paycard_issuer");
        $cardExM = substr($this->conf->get("paycard_exp"),0,2);
        $cardExY = substr($this->conf->get("paycard_exp"),2,2);
        $cardTr1 = $this->conf->get("paycard_tr1");
        $cardTr2 = $this->conf->get("paycard_tr2");
        $cardTr3 = $this->conf->get("paycard_tr3");
        $cardName = $this->conf->get("paycard_name");
        $refNum = $this->refnum($transID);
        $this->last_ref_num = $refNum;
        $live = 1;

        $merchantID = $this->conf->get('GoEMerchID');
        $password = $this->conf->get('GoEMerchPassword');
        $gatewayID = $this->conf->get('GoEmerchGatewayID');
        if ($this->conf->get("training") == 1) {
            $merchantID = "1264";
            $password = "password";
            $gatewayID = "a91c38c3-7d7f-4d29-acc7-927b4dca0dbe";
            $cardPAN = "4111111111111111";
            $cardPANmasked = "xxxxxxxxxxxxTEST";
            $cardIssuer = "Visa";
            $cardTr1 = False;
            $cardTr2 = False;
            $cardName = "Just Testing";
            $nextyear = mktime(0,0,0,date("m"),date("d"),date("Y")+1);
            $cardExM = date("m",$nextyear);
            $cardExY = date("y",$nextyear);
            $live = 0;
        }
        
        $sendPAN = 0;
        $sendExp = 0;
        $sendTr1 = 0;
        $sendTr2 = 0;
        $magstripe = "";
        if (!$cardTr1 && !$cardTr2) {
            $sendPAN = 1;
            $sendExp = 1;
        }
        if ($cardTr1) {
            $sendTr1 = 1;
            $magstripe .= "%".$cardTr1."?";
        }
        if ($cardTr2) {
            $sendTr2 = 1;
            $magstripe .= ";".$cardTr2."?";
        }
        if ($cardTr2 && $cardTr3) {
            $sendPAN = 1;
            $magstripe .= ";".$cardTr3."?";
        }

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
                    'GoEMerchant', $refNum, $live, 'Credit', $loggedMode,
                    $amountText, $cardPANmasked,
                    $cardIssuer, $cardName, $manual, $now);
        $insP = $dbTrans->prepare($insQ);
        $insR = $dbTrans->execute($insP, $ptArgs);
        if ($insR === false) {
            $this->conf->reset();
            // internal error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }
        $this->last_paycard_transaction_id = $dbTrans->insertID();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xml .= "<TRANSACTION>";
        $xml .= "<FIELDS>";
        $xml .= "<FIELD KEY=\"merchant\">$merchantID</FIELD>";
        if ($password != "") {
            $xml .= "<FIELD KEY=\"password\">$password</FIELD>";
        }
        $xml .= "<FIELD KEY=\"gateway_id\">$gatewayID</FIELD>";
        $xml .= "<FIELD KEY=\"operation_type\">$mode</FIELD>";
        $xml .= "<FIELD KEY=\"order_id\">$refNum</FIELD>";
        $xml .= "<FIELD KEY=\"total\">$amountText</FIELD>";
        if ($magstripe == "") {
            $xml .= "<FIELD KEY=\"card_name\">$cardIssuer</FIELD>";
            $xml .= "<FIELD KEY=\"card_number\">$cardPAN</FIELD>";
            $xml .= "<FIELD KEY=\"card_exp\">".$cardExM.$cardExY."</FIELD>";
        } else {
            $xml .= "<FIELD KEY=\"mag_data\">$magstripe</FIELD>";
        }
        if ($cardName != "Customer") {
            $xml .= "<FIELD KEY=\"owner_name\">$cardName</FIELD>";
        }
        $xml .= "<FIELD KEY=\"recurring\">0</FIELD>";
        $xml .= "<FIELD KEY=\"recurring_type\"></FIELD>";
        $xml .= "</FIELDS>";
        $xml .= "</TRANSACTION>";

        $this->GATEWAY = "https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx";

        return $this->curlSend($xml,'POST',True);
    }

    private function sendVoid()
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans) {
            $this->conf->reset();

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        // prepare data for the void request
        $today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
        $cashierNo = $this->conf->get("CashierNo");
        $laneNo = $this->conf->get("laneno");
        $transNo = $this->conf->get("transno");
        $transID = $this->conf->get("paycard_id");
        $amount = $this->conf->get("paycard_amount");
        $amountText = number_format(abs($amount), 2, '.', '');
        $mode = 'void';
        $this->trans_pan['pan'] = $this->conf->get("paycard_PAN");
        $cardPAN = $this->trans_pan['pan'];
        $reader = new CardReader();
        $cardPANmasked = $reader->maskPAN($cardPAN,0,4);
        $cardIssuer = $this->conf->get("paycard_issuer");
        $cardExM = substr($this->conf->get("paycard_exp"),0,2);
        $cardExY = substr($this->conf->get("paycard_exp"),2,2);
        $cardName = $this->conf->get("paycard_name");
        $live = 1;

        $this->voidTrans = $transID;
        $this->voidRef = $this->conf->get("paycard_trans");
        $temp = explode("-",$this->voidRef);
        $laneNo = $temp[1];
        $transNo = $temp[2];

        $merchantID = $this->conf->get('GoEMerchID');
        $password = $this->conf->get('GoEMerchPassword');
        $gatewayID = $this->conf->get('GoEmerchGatewayID');
        if ($this->conf->get("training") == 1) {
            $merchantID = "1264";
            $password = "password";
            $cardPAN = "4111111111111111";
            $gatewayID = "a91c38c3-7d7f-4d29-acc7-927b4dca0dbe";
            $cardPANmasked = "xxxxxxxxxxxxTEST";
            $cardIssuer = "Visa";
            $cardName = "Just Testing";
            $nextyear = mktime(0,0,0,date("m"),date("d"),date("Y")+1);
            $cardExM = date("m",$nextyear);
            $cardExY = date("y",$nextyear);
            $live = 0;
        }

        // look up the TransactionID from the original response (card number and amount should already be in session vars)
        $sql = 'SELECT refNum,
                    xTransactionID
                FROM PaycardTransactions
                WHERE dateID=' . $today . '
                    AND empNo=' . $cashierNo . '
                    AND registerNo=' . $laneNo . '
                    AND transNo=' . $transNo . '
                    AND transID=' . $transID;
        $result = $dbTrans->query($sql);
        if (!$result || $dbTrans->numRows($result) != 1) {
            $this->conf->reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }
        $res = $dbTrans->fetchRow($result);
        $transactionID = $res['xTransactionID'];

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
            $this->last_paycard_transaction_id = $dbTrans->insertID();
        } else {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xml .= "<TRANSACTION>";
        $xml .= "<FIELDS>";
        $xml .= "<FIELD KEY=\"merchant\">$merchantID</FIELD>";
        if ($password != "") {
            $xml .= "<FIELD KEY=\"password\">$password</FIELD>";
        }
        $xml .= "<FIELD KEY=\"gateway_id\">$gatewayID</FIELD>";
        $xml .= "<FIELD KEY=\"operation_type\">$mode</FIELD>";
        $xml .= "<FIELD KEY=\"total_number_transactions\">1</FIELD>";
        $xml .= "<FIELD KEY=\"reference_number1\">$transactionID</FIELD>";
        $xml .= "<FIELD KEY=\"credit_amount1\">$amountText</FIELD>";
        $xml .= "</FIELDS>";
        $xml .= "</TRANSACTION>";

        $this->GATEWAY = "https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx";

        return $this->curlSend($xml,'POST',True);
    }

    // tack time onto reference number for goemerchant order_id
    // field. requires uniqueness, doesn't seem to cycle daily
    public function refnum($transID)
    {
        $transNo   = (int)$this->conf->get("transno");
        $cashierNo = (int)$this->conf->get("CashierNo");
        $laneNo    = (int)$this->conf->get("laneno");    

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

    public function myRefNum($ref)
    {
        if (strlen($ref) == 25 && preg_match('/^[0-9]{12}-[0-9]{12}$/', $ref)) {
            return true;
        }
        return false;
    }

    public function lookupTransaction($ref, $local, $mode)
    {
        $merchantID = $this->conf->get('GoEMerchID');
        $password = $this->conf->get('GoEMerchPassword');
        $gatewayID = $this->conf->get('GoEmerchGatewayID');
        if (substr($ref, 13, 4) == "9999") {
            $merchantID = "1264";
            $password = "password";
            $gatewayID = "a91c38c3-7d7f-4d29-acc7-927b4dca0dbe";
        }
        $dateStr = date('mdy');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xml .= "<TRANSACTION>";
        $xml .= "<FIELDS>";
        $xml .= "<FIELD KEY=\"merchant\">$merchantID</FIELD>";
        if ($password != "" ) {
            $xml .= "<FIELD KEY=\"password\">$password</FIELD>";
        }
        $xml .= "<FIELD KEY=\"gateway_id\">$gatewayID</FIELD>";
        $xml .= "<FIELD KEY=\"operation_type\">query</FIELD>";
        $xml .= "<FIELD KEY=\"trans_type\">SALE</FIELD>";
        $xml .= "<FIELD KEY=\"begin_date\">$dateStr</FIELD>";
        $xml .= "<FIELD KEY=\"begin_time\">0001AM</FIELD>";
        $xml .= "<FIELD KEY=\"end_date\">$dateStr</FIELD>";
        $xml .= "<FIELD KEY=\"end_time\">1159PM</FIELD>";
        $xml .= "<FIELD KEY=\"order_id\">$ref</FIELD>";
        $xml .= "</FIELDS>";
        $xml .= "</TRANSACTION>";

        $this->GATEWAY = "https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx";
        $curlResult = $this->curlSend($xml, 'POST', true, array(), false);
        if ($curlResult['curlErr'] != CURLE_OK || $curlResult['curlHTTP'] != 200) {
            return array(
                'output' => DisplayLib::boxMsg('No response from processor', '', true),
                'confirm_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
                'cancel_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
            );
        }

        $directions = 'Press [enter] or [clear] to continue';
        $resp = array(
            'confirm_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
            'cancel_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
        );
        $info = new Paycards();
        $urlStem = $info->pluginUrl();

        $xmlResp = new XmlData($curlResult['response']);
        $status = 'UNKNOWN';
        if ($xmlResp->get_first('RECORDS_FOUND') == 0) {
            $status = 'NOTFOUND';
            $directions = 'Press [enter] to try again, [clear] to stop';
            $queryString = 'id=' . ($local ? '_l' : '') . $ref . '&mode=' . $mode;
            $resp['confirm_dest'] = $urlStem . '/gui/PaycardTransLookupPage.php?' . $queryString;
        } else {
            $responseCode = $xmlResp->get_first('TRANS_STATUS1');;
            $resultCode = $responseCode;
            $normalized = $resultCode;
            $xTransID = $xmlResp->get_first('REFERENCE_NUMBER1');
            $rMsg = '';
            if ($responseCode == 1) {
                $status = 'APPROVED';
                $rMsg = 'APPROVED';
                $normalized = 1;
            } else if ($responseCode == 2) {
                $status == 'DECLINED';
                $rMsg = 'DECLINED';
                $normalized = 2;
            } else if ($responseCode == 0) {
                $status == 'ERROR';
                $eMsg = $xmlResp->get_first('ERROR1');
                $normalized = 3;
                $rMsg = $eMsg ? substr($eMsg, 0, 100) : 'ERROR';
            } else {
                $responseCode = -3;
                $normalized = 0;
                $status = 'UNKNOWN';
            }

            $apprNumber = ''; // not returned by query op

            if ($local == 1 && $mode == 'verify') {
                // Update PaycardTransactions record to contain
                // actual processor result and finish
                // the transaction correctly
                $dbc = Database::tDataConnect(); 
                $upP = $dbc->prepare("
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
                    $this->conf->get('paycard_id'),
                );
                $dbc->execute($upP, $args);

                if ($status == 'APPROVED') {
                    $this->conf->wipePAN();
                    $this->cleanup(array());
                    $resp['confirm_dest'] = $urlStem . '/gui/paycardSuccess.php';
                    $resp['cancel_dest'] = $urlStem . '/gui/paycardSuccess.php';
                    $directions = 'Press [enter] to continue';
                } else {
                    $this->conf->reset();
                }
            } // end verification record update
        } // end found result

        switch (strtoupper($status)) {
            case 'APPROVED':
                $line1 = $status;
                $line2 = 'Amount: ' . sprintf('%.2f', $xmlResp->get_first('AMOUNT1'));
                $line3 = 'Type: CREDIT';
                $voided = $xmlResp->get_first('CREDIT_VOID1');
                $line4 = 'Voided: ' . (strtoupper($voided) == 'VOID' ? 'Yes' : 'No');
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
            case 'UNKNOWN':
                $resp['output'] = DisplayLib::boxMsg('Processor responded but made no sense
                                                      <br />' . $directions,
                                                      '',
                                                      true
                );
                break;
        }

        return $resp;
    }
}

