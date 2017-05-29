<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\plugins\Paycards\sql\PaycardRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardVoidRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardGiftRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardResponse;
use COREPOS\pos\plugins\Paycards\card\EncBlock;
use COREPOS\pos\plugins\Paycards\xml\XmlData;

/**
2014-04-01:
 Mercury has a public webservices implementation on github:
 https://github.com/MercuryPay

 If they can post one, I think we can too. No new information about
 the service is being exposed.
*/

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

class MercuryE2E extends BasicCCModule 
{
    private $voidTrans;
    private $voidRef;
    protected $SOAPACTION = "http://www.mercurypay.com/CreditTransaction";
    private $secondTry;

    private $encBlock;
    private $pmod;

    public function __construct()
    {
        $this->encBlock = new EncBlock();
        $this->pmod = new PaycardModule();
        $this->pmod->setDialogs(new PaycardDialogs());
        $this->conf = new PaycardConf();
    }

    const PRIMARY_URL = 'w1.mercurypay.com';
    const BACKUP_URL = 'w2.backuppay.com';

    public function handlesType($type)
    {
        if ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
            return true;
        }
        return false;
    }

    /**
      Updated for E2E
      Status: done
    */
    public function entered($validate,$json)
    {
        $pan = $this->conf->get('paycard_PAN');
        if ($this->conf->get('paycard_mode') == PaycardLib::PAYCARD_MODE_AUTH) {
            $e2e = $this->encBlock->parseEncBlock($this->conf->get('paycard_PAN'));
            if (empty($e2e['Block']) || empty($e2e['Key'])){
                $this->conf->reset();
                $json['output'] = PaycardLib::paycardMsgBox("Swipe Error",
                                                            "Error reading card. Swipe again.",
                                                            "[clear] to cancel"
                );
                UdpComm::udpSend('termReset');

                return $json;
            }
            $pan = str_repeat('*', 12) . $e2e['Last4'];
        }

        return $this->pmod->ccEntered($pan, false, $json);
    }

    /**
      Updated for E2E
    */
    public function paycardVoid($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        $this->voidTrans = "";
        $this->voidRef = "";
        $json = $this->pmod->ccVoid($transID, $laneNo, $transNo, $json);
        $this->conf->set("paycard_type",PaycardLib::PAYCARD_TYPE_ENCRYPTED);
    
        return $json;
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

    /**
      Updated for E2E
    */
    private function handleResponseAuth($authResult)
    {
        $resp = $this->desoapify("CreditTransactionResult",
            $authResult["response"]);
        $xml = new XmlData($resp);

        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult, PaycardLib::paycard_db());

        $responseCode = $xml->get("CMDSTATUS");
        $validResponse = -3;
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->get("DSIXRETURNCODE");
        if ($resultCode) {
            $response->setResultCode($resultCode);
        }
        $resultMsg = $xml->get_first("CMDSTATUS");
        $rMsg = $resultMsg;
        $aNum = $xml->get("AUTHCODE");
        if ($aNum) {
            $rMsg .= ' '.$aNum;
        }
        $response->setResultMsg($rMsg);
        $response->setApprovalNum($aNum);
        $xTransID = $xml->get("REFNO");
        if ($xTransID === false) {
            $validResponse = -3;
        }
        $response->setTransactionID($xTransID);
        $response->setValid($validResponse);

        $cardtype = $this->conf->get("CacheCardType");
        $ebtbalance = 0;
        if ($xml->get_first("Balance")) {
            switch($cardtype) {
                case 'EBTFOOD':
                    $this->conf->set('EbtFsBalance', $xml->get_first('Balance'));
                    $ebtbalance = $xml->get_first('Balance');
                    break;
                case 'EBTCASH':
                    $this->conf->set('EbtCaBalance', $xml->get_first('Balance'));
                    $ebtbalance = $xml->get_first('Balance');
                    break;
            }
        }
        $response->setBalance($ebtbalance);

        /**
          Log transaction in newer table
        */
        // put normalized value in validResponse column
        $normalized = $this->normalizeResponseCode($responseCode, $validResponse);
        $response->setNormalizedCode($normalized);
        $response->setToken(
            $xml->get_first('RECORDNO'),
            $xml->get_first('PROCESSDATA'),
            $xml->get_first('ACQREFDATA')
        );
        try {
            $response->saveResponse();
        } catch (Exception $ex) { 
            echo $ex->getMessage() . "\n";
        }
        // auth still happened even if logging the result failed

        if ($responseCode == 1) {
            $amt = $xml->get_first("AUTHORIZE");
            $this->handlePartial($amt, $request);
        }

        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200) {
            if (!$this->secondTry){
                $this->secondTry = true;

                return $this->sendAuth("w2.backuppay.com");
            } elseif ($authResult['curlHTTP'] == '0') {
                $this->conf->set("boxMsg","No response from processor<br />
                            The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        } elseif ($this->secondTry && $authResult['curlTime'] < 10 && $this->conf->get('MercurySwitchUrls') <= 0) {
            $this->conf->set('MercurySwitchUrls', 5);
        }

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                if (substr($this->conf->get('CacheCardType'), 0, 3) == 'EBT' && $ebtbalance > 0 && $ebtbalance < $xml->get_first('AUTHORIZE')) {
                    // if EBT is declined but lists a balance less than the
                    // requested authorization, it may be possible to
                    // charge the card for a less amount. different return
                    // it to try a less amount immediatley without making
                    // the customer re-enter information
                    $this->conf->set('PaycardRetryBalanceLimit', sprintf('%.2f', $ebtbalance));    
                    $this->conf->set('paycard_amount', $ebtbalance);
                    TransRecord::addcomment("");
                    $this->conf->set('paycard_id', $this->conf->get('paycard_id') + 1);

                    return PaycardLib::PAYCARD_ERR_NSF_RETRY;
                }
                UdpComm::udpSend('termReset');
                $this->conf->set('ccTermState','swipe');
                // intentional fallthrough
            case 'ERROR':
                $this->conf->set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                $this->conf->set("boxMsg","Error: $texts");
                TransRecord::addcomment("");
                break;
            default:
                $this->conf->set("boxMsg","An unknown error occurred<br />at the gateway");
                TransRecord::addcomment("");    
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
      Updated for E2E
    */
    private function handleResponseVoid($authResult)
    {
        $resp = $this->desoapify("CreditTransactionResult",
            $authResult["response"]);
        $xml = new XmlData($resp);
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult, PaycardLib::paycard_db());

        $responseCode = $xml->get("CMDSTATUS");
        $validResponse = -3;
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->get_first("DSIXRETURNCODE");
        $response->setResultCode($resultCode);
        $resultMsg = $xml->get_first("CMDSTATUS");
        $response->setResultMsg($resultMsg);
        $response->setValid($validResponse);

        $normalized = $this->normalizeResponseCode($responseCode, $validResponse);
        $response->setNormalizedCode($normalized);
        $response->setToken(
            $xml->get_first('RECORDNO'),
            $xml->get_first('PROCESSDATA'),
            $xml->get_first('ACQREFDATA')
        );
        try {
            $response->saveResponse();
        } catch (Exception $ex) { }
        // void still happened even if logging the result failed

        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200) {
            if ($authResult['curlHTTP'] == '0') {
                $this->conf->set("boxMsg","No response from processor<br />
                            The transaction did not go through");

                return PaycardLib::PAYCARD_ERR_PROC;
            } 

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // if declined, try again with a regular Void op
                // and no reversal information
                $skipReversal = $this->conf->get("MercuryE2ESkipReversal");
                if ($skipReversal == false) {
                    return $this->sendVoid(true);
                }
                $this->conf->set("MercuryE2ESkipReversal", false);
            case 'ERROR':
                $this->conf->set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
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
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                $this->conf->set("autoReprint",1);
                $ttl = $this->conf->get("paycard_amount");
                $dept = $this->conf->get('PaycardDepartmentGift');
                $dept = $dept == '' ? 902 : $dept;
                $deptObj = new COREPOS\pos\lib\DeptLib($this->conf);
                $deptObj->deptkey($ttl*100, $dept . '0');
                $bal = $this->conf->get('GiftBalance');
                $this->conf->set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $bal . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $bal = $this->conf->get('DatacapBalanceCheck');
                $this->conf->set("boxMsg","<b>Success</b><font size=-1>
                                           <p>Card balance: $" . $bal . "
                                           <p>\"rp\" to print
                                           <br>[enter] to continue</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                // cast to string. tender function expects string input
                // numeric input screws up parsing on negative values > $0.99
                $amt = "".(-1*($this->conf->get("paycard_amount")));
                $type = $this->conf->get("CacheCardType");
                if ($type == 'EBTFOOD') {
                    // extra tax exemption steps
                    TransRecord::addfsTaxExempt();
                    $this->conf->set("fntlflag",0);
                    Database::setglobalvalue("FntlFlag", 0);
                }
                $tInfo = new PaycardTenders($this->conf);
                list($tenderCode, $tenderDescription) = $tInfo->getTenderInfo($type, $this->conf->get('paycard_issuer'));

                // if the transaction has a non-zero paycardTransactionID,
                // include it in the tender line
                $recordID = $this->last_paycard_transaction_id;
                $charflag = ($recordID != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender($tenderDescription, $tenderCode, $amt, $recordID, $charflag);

                $apprType = 'Approved';
                if ($this->conf->get('paycard_partial')){
                    $apprType = 'Partial Approval';
                } elseif ($this->conf->get('paycard_amount') == 0) {
                    $apprType = 'Declined';
                    $json['receipt'] = 'ccDecline';
                }
                $this->conf->set('paycard_partial', false);

                $isCredit = ($this->conf->get('CacheCardType') == 'CREDIT' || $this->conf->get('CacheCardType') == '') ? true : false;
                $needSig = ($this->conf->get('paycard_amount') > $this->conf->get('CCSigLimit') || $this->conf->get('paycard_amount') < 0) ? true : false;
                if ($this->conf->get('paycard_recurring')) {
                    $this->conf->set("boxMsg",
                            "<b>$apprType</b>
                            <font size=-1>
                            <p>Please verify cardholder signature
                            <p>[enter] to continue
                            <br>\"rp\" to reprint slip
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                    $json['receipt'] = 'ccSlip';
                } elseif (($isCredit || $this->conf->get('EmvSignature') === true) && $needSig) {
                    $this->conf->set("boxMsg",
                            "<b>$apprType</b>
                            <font size=-1>
                            <p>Please verify cardholder signature
                            <p>[enter] to continue
                            <br>\"rp\" to reprint slip
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                    if ($this->conf->get('PaycardsSigCapture') != 1) {
                        $json['receipt'] = 'ccSlip';
                    }
                } else {
                    $this->conf->set("boxMsg",
                            "<b>$apprType</b>
                            <font size=-1>
                            <p>No signature required
                            <p>[enter] to continue
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                } 
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
                $void = new COREPOS\pos\parser\parse\VoidCmd($this->conf);
                $void->voidid($this->conf->get("paycard_id"), array());
                // advanced ID to the void line
                $this->conf->set('paycard_id', $this->conf->get('paycard_id')+1);
                $this->conf->set("boxMsg","<b>Voided</b>
                                           <p><font size=-1>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
        }

        return $json;
    }

    public function doSend($type)
    {
        $this->secondTry = false;
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

    /**
      Updated for E2E
      Status: Should be functional once device is available
    */
    private function sendAuth($domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans) {
            $this->conf->reset();
            // database error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $request = new PaycardRequest($this->refnum($this->conf->get('paycard_id')), $dbTrans);
        $request->setProcessor('MercuryE2E');

        if ($this->conf->get("paycard_voiceauthcode") != "") {
            $request->setMode("VoiceAuth");
        } elseif ($this->conf->get("ebt_authcode") != "" && $this->conf->get("ebt_vnum") != "") {
            $request->setMode("Voucher");
        }
        $password = $this->getPw();
        $e2e = $this->encBlock->parseEncBlock($this->conf->get("paycard_PAN"));
        $pin = $this->encBlock->parsePinBlock($this->conf->get("CachePinEncBlock"));
        $request->setIssuer($e2e['Issuer']);
        $this->conf->set('paycard_issuer',$e2e['Issuer']);
        $request->setCardholder($e2e['Name']);
        $request->setPAN($e2e['Last4']);
        $this->conf->set('paycard_partial',false);
        $request->setSent(0, 0, 0, 1);
        
        // store request in the database before sending it
        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            $this->conf->reset();
            // internal error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        $msgXml = $this->beginXmlRequest($request);
        if (substr($request->type,0,3) == 'EBT') {
            $msgXml .= '<TranType>EBT</TranType>';
            if ($request->type == 'EBTFOOD') {
                $this->conf->set('EbtFsBalance', 'unknown');
                $msgXml .= '<CardType>Foodstamp</CardType>';
            } elseif ($request->type == 'EBTCASH') {
                $msgXml .= '<CardType>Cash</CardType>';
                $this->conf->set('EbtCaBalance', 'unknown');
            }
        } else {
            $msgXml .= '<TranType>'.$request->type.'</TranType>';
        }
        $msgXml .= '<TranCode>'.$request->mode.'</TranCode>';
        $msgXml .= '<Account>
                <EncryptedFormat>'.$e2e['Format'].'</EncryptedFormat>
                <AccountSource>'.($request->manual ? 'Keyed' : 'Swiped').'</AccountSource>
                <EncryptedBlock>'.$e2e['Block'].'</EncryptedBlock>
                <EncryptedKey>'.$e2e['Key'].'</EncryptedKey>
            </Account>';
        if ($request->type == "Debit" || (substr($request->type,0,3) == "EBT" && $request->mode != "Voucher")) {
            $msgXml .= "<PIN>
                <PINBlock>".$pin['block']."</PINBlock>
                <DervdKey>".$pin['key']."</DervdKey>
                </PIN>";
        }
        if ($this->conf->get("paycard_voiceauthcode") != "") {
            $msgXml .= "<TransInfo>";
            $msgXml .= "<AuthCode>";
            $msgXml .= $this->conf->get("paycard_voiceauthcode");
            $msgXml .= "</AuthCode>";
            $msgXml .= "</TransInfo>";
        } elseif ($this->conf->get("ebt_authcode") != "" && $this->conf->get("ebt_vnum") != "") {
            $msgXml .= $this->ebtVoucherXml();
        }
        $msgXml .= "</Transaction>
            </TStream>";

        $soaptext = $this->soapify("CreditTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");

        $domain = $this->getWsDomain($domain);
        $this->GATEWAY = $this->getWsUrl($domain);

        $this->last_request = $request;

        return $this->curlSend($soaptext,'SOAP');
    }

    /**
      Updated for E2E
    */
    private function sendVoid($skipReversal=False,$domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans){
            $this->conf->reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        $this->conf->set("MercuryE2ESkipReversal", $skipReversal ? true : false);

        $request = new PaycardVoidRequest($this->refnum($this->conf->get('paycard_id')), $dbTrans);
        $request->setProcessor('MercuryE2E');
        $request->setMode('VoidSaleByRecordNo');

        $password = $this->getPw();
        $transID = $this->conf->get("paycard_id");
        $this->voidTrans = $transID;
        $this->voidRef = $this->conf->get("paycard_trans");

        try {
            $res = $request->findOriginal();
        } catch (Exception $ex) {
            $this->conf->reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $type = 'Credit';
        $mode = 'VoidSaleByRecordNo';
        if (substr($res['mode'],0,6)=='Debit_') {
            $type = 'Debit';
            if (substr($res['mode'],-5)=="_Sale") {
                $mode = 'ReturnByRecordNo';
            } elseif (substr($res['mode'],-7)=="_Return") {
                $mode = 'SaleByRecordNo';
            }
            $this->conf->set("MercuryE2ESkipReversal", true);
        } elseif (substr($res['mode'],-7)=="_Return") {
            $mode = 'VoidReturnByRecordNo';
        }

        $msgXml = $this->beginXmlRequest($request, $res['xTransactionID'], $res['token']);
        $msgXml .= "<TranType>$type</TranType>
            <TranCode>$mode</TranCode>
            <TransInfo>";
        if (!$skipReversal) {
            $msgXml .= "<AcqRefData>".$res['acqRefData']."</AcqRefData>
                <ProcessData>".$res['processData']."</ProcessData>";
        }
        $msgXml .= "<AuthCode>".$res['xApprovalNumber']."</AuthCode>
            </TransInfo>
            </Transaction>
            </TStream>";

        $soaptext = $this->soapify("CreditTransaction",
            array("tran"=>$msgXml,"pw"=>$password),
            "http://www.mercurypay.com");
                
        $this->GATEWAY = $this->getWsUrl($domain);

        $this->last_request = $request;

        return $this->curlSend($soaptext,'SOAP');
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
        $ref .= date("md");
        $ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
        $ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
        $ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
        $ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);

        return $ref;
    }

    /**
      Return real or testing ID depending on
      whether training mode is on
    */
    public function getTermID()
    {
        if ($this->conf->get("training") == 1) {
            if ($this->conf->get('CacheCardType') == 'EMV') {
                return '337234005'; // emv
            }
            return '019588466313922';
            //return '118725340908147'; // newer
            //return "395347308=E2ETKN"; // old test ID
        }
        return $this->conf->get('MercuryE2ETerminalID');
    }

    /**
      Return real or testing password depending on
      whether training mode is on
    */
    protected function getPw()
    {
        if ($this->conf->get("training") == 1) {
            return 'xyz';
            //return "123E2ETKN";
        }
        return $this->conf->get('MercuryE2EPassword');
    }

    public function myRefNum($ref)
    {
        if (strlen($ref) == 16 && preg_match('/^[0-9]+$/', $ref)) {
            return true;
        }
        return false;
    }

    public function lookupTransaction($ref, $local, $mode)
    {
        $wsParams = array(
            'merchant' => $this->conf->get('MercuryE2ETerminalID'),
            'pw' => $this->conf->get('MercuryE2EPassword'),
            'invoice' => $ref,
        );

        // emp_no 9999 => test transaction
        if (substr($ref, 4, 4) == "9999") {
            $wsParams['merchant'] = '395347308=E2ETKN';
            $wsParams['pw'] = '123E2ETKN';
        }

        $this->SOAPACTION = 'http://www.mercurypay.com/CTranDetail';
        $soaptext = $this->soapify('CTranDetail', $wsParams, 'http://www.mercurypay.com');
        $this->GATEWAY = 'https://' . self::PRIMARY_URL . '/ws/ws.asmx';

        $curlResult = $this->curlSend($soaptext, 'SOAP', false, array(), false);

        if ($curlResult['curlErr'] != CURLE_OK || $curlResult['curlHTTP'] != 200) {
            $this->GATEWAY = 'https://' . self::BACKUP_URL . '/ws/ws.asmx';
            $curlResult = $this->curlSend($soaptext, 'SOAP', false, array(), false);
            if ($curlResult['curlErr'] != CURLE_OK || $curlResult['curlHTTP'] != 200) {
                return array(
                    'output' => DisplayLib::boxMsg('No response from processor', '', true),
                    'confirm_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
                    'cancel_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
                );
            }
        }

        $directions = 'Press [enter] or [clear] to continue';
        $resp = array(
            'confirm_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
            'cancel_dest' => MiscLib::baseURL() . 'gui-modules/pos2.php',
        );
        $info = new Paycards();
        $urlStem = $info->pluginUrl();

        $xmlResp = $this->desoapify('CTranDetailResponse', $curlResult['response']);
        $xml = new XmlData($xmlResp);

        $status = trim($xml->get_first('STATUS'));
        if ($status === '') {
            $status = 'NOTFOUND';
            $directions = 'Press [enter] to try again, [clear] to stop';
            $queryString = 'id=' . ($local ? '_l' : '') . $ref . '&mode=' . $mode;
            $resp['confirm_dest'] = $urlStem . '/gui/PaycardTransLookupPage.php?' . $queryString;
        } elseif ($local == 1 && $mode == 'verify') {
            // Update PaycardTransactions record to contain
            // actual processor result and finish
            // the transaction correctly
            $responseCode = -3;
            $resultCode = 0;
            $normalized = 0;
            if ($status == 'Approved') {
                $responseCode = 1;
                $normalized = 1;
                $this->conf->wipePAN();
                $this->cleanup(array());
                $resp['confirm_dest'] = $urlStem . '/gui/paycardSuccess.php';
                $resp['cancel_dest'] = $urlStem . '/gui/paycardSuccess.php';
                $directions = 'Press [enter] to continue';
            } elseif ($status == 'Declined') {
                $this->conf->reset();
                $responseCode = 2;
                $normalized = 2;
            } elseif ($status == 'Error') {
                $this->conf->reset();
                $responseCode = 0;
                $resultCode = -1; // CTranDetail does not provide this value
                $normalized = 3;
            } else {
                // Unknown status; clear any data
                $this->conf->reset();
            }

            $apprNumber = $xml->get_first('authcode');
            $xTransID = $xml->get_first('reference');
            $rMsg = $status;
            if ($apprNumber) {
                $rMsg .= ' ' . $apprNumber;
            }
            if (strlen($rMsg) > 100) {
                $rMsg = substr($rMsg,0,100);
            }

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
        }

        switch(strtoupper($status)) {
            case 'APPROVED':
                $line1 = $status . ' ' . $xml->get_first('authcode');
                $line2 = 'Amount: ' . sprintf('%.2f', $xml->get_first('total'));
                $transType = $xml->get_first('trantype');
                $line3 = 'Type: ' . $transType;
                $voided = $xml->get_first('voided');
                $line4 = 'Voided: ' . ($voided == 'true' ? 'Yes' : 'No');
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
        }

        return $resp;
    }

    protected function giftServerIP()
    {
        $host = 'g1.mercurypay.com';
        if ($this->conf->get('training') == 1) {
            $host = 'g1.mercurydev.net';
        }
        $hostCache = $this->conf->get('DnsCache');
        if (!is_array($hostCache)) {
            $hostCache = array();
        }
        if (isset($hostCache[$host])) {
            return $hostCache[$host];
        }
        $addr = gethostbyname($host);
        if ($addr === $host) { // name did not resolve
            return $host;
        }
        $hostCache[$host] = $addr;
        $this->conf->set('DnsCache', $hostCache);
        return $addr;
    }

    protected function responseToNumber($responseCode)
    {
        // map response status to 0/1/2 for compatibility
        if ($responseCode == "Approved") {
            return 1;
        } elseif ($responseCode == "Declined") {
            return 2;
        } elseif ($responseCode == "Error") {
            return 0;
        }
        return -1;
    }

    protected function ebtVoucherXml()
    {
        return "<TranInfo>
            <AuthCode>
            " . $this->conf->get("ebt_authcode") . "
            </AuthCode>
            <VoucherNo>
            " . $this->conf->get("ebt_vnum") . "
            </VoucherNo>
            </TranInfo>";
    }
    
    protected function normalizeResponseCode($responseCode, $validResponse)
    {
        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($responseCode == 1) {
            $normalized = 1;
        } elseif ($responseCode == 2) {
            $normalized = 2;
        } elseif ($responseCode == 0) {
            $normalized = 3;
        }

        return $normalized;
    }

    protected function beginXmlRequest($request, $refNo=false, $recordNo=false)
    {
        $termID = $this->getTermID();
        $mcTerminalID = $this->conf->get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = $this->conf->get('laneno');
        }

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$request->cashierNo.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <InvoiceNo>'.$request->refNum.'</InvoiceNo>
            <RefNo>'. ($refNo ? $refNo : $request->refNum) .'</RefNo>
            <Memo>CORE POS 1.0.0</Memo>
            <RecordNo>' . ($recordNo ? $recordNo : 'RecordNumberRequested') . '</RecordNo>
            <Frequency>OneTime</Frequency>
            <Amount>
                <Purchase>'.$request->formattedAmount().'</Purchase>';
        if ($request->cashback > 0 && ($request->type == "Debit" || $request->type == "EBTCASH")) {
                $msgXml .= "<CashBack>" . $request->formattedCashBack() . "</CashBack>";
        }
        $msgXml .= "</Amount>";
        if ($request->type == 'Credit' && $request->mode == 'Sale') {
            $msgXml .= "<PartialAuth>Allow</PartialAuth>";
        }

        return $msgXml;
    }

    private function getWsDomain($domain)
    {
        /**
          In switched mode, use the backup URL first
          then retry on the primary URL

          Switched mode is triggered when a request to
          the primary URL fails with some kind of
          cURL error and the subsequent request to the
          backup URL succeeds. The idea is to use the
          backup URL for a few transactions before trying
          the primary again. The most common error is
          a timeout and waiting 30 seconds for the primary
          to fail every single transaction isn't ideal.
        */
        $domain = self::BACKUP_URL;    
        if (!$this->secondTry) {
            $domain = self::PRIMARY_URL;
        }
        if ($this->conf->get('MercurySwitchUrls') > 0) {
            $domain = self::PRIMARY_URL;
            if (!$this->secondTry) {
                $domain = self::BACKUP_URL;    
            }
        }

        /**
          SwitchUrls is a counter
          Go back to normal order when it reaches zero 
        */    
        if ($this->conf->get('MercurySwitchUrls') > 0) {
            $switchCount = $this->conf->get('MercurySwitchUrls');
            $switchCount--;
            $this->conf->set('MercurySwitchUrls', $switchCount);
        }

        return $domain;
    }

    private function getWsUrl($domain)
    {
        if ($this->conf->get("training") == 1) {
            return "https://w1.mercurydev.net/ws/ws.asmx";
        }
        return "https://$domain/ws/ws.asmx";
    }

    protected function handlePartial($amt, $request)
    {
        if ($amt != abs($this->conf->get("paycard_amount"))) {
            $request->changeAmount($amt);

            $this->conf->set("paycard_amount",$amt);
            $this->conf->set("paycard_partial",True);
            UdpComm::udpSend('goodBeep');
        }
    }

    protected function getRequestObj($ref)
    {
        if ($this->conf->get('LastEmvReqType') == 'void') {
            return new PaycardVoidRequest($ref, PaycardLib::paycard_db());
        } elseif ($this->conf->get('LastEmvReqType') == 'gift') {
            return new PaycardGiftRequest($ref, PaycardLib::paycard_db());
        }
        return new PaycardRequest($this->refnum($this->conf->get('paycard_id')), PaycardLib::paycard_db());
    }
}

