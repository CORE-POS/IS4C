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

/**
2014-04-01:
 Mercury has a public webservices implementation on github:
 https://github.com/MercuryPay

 If they can post one, I think we can too. No new information about
 the service is being exposed.
*/

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("xmlData")) include_once(realpath(dirname(__FILE__)."/lib/xmlData.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

class MercuryE2E extends BasicCCModule 
{

    private $voidTrans;
    private $voidRef;
    protected $SOAPACTION = "http://www.mercurypay.com/CreditTransaction";
    private $second_try;

    const PRIMARY_URL = 'w1.mercurypay.com';
    const BACKUP_URL = 'w2.backuppay.com';

    public function handlesType($type)
    {
        if ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Updated for E2E
      Status: done
    */
    public function entered($validate,$json)
    {
        if (CoreLocal::get('paycard_mode') == PaycardLib::PAYCARD_MODE_AUTH) {
            $e2e = EncBlock::parseEncBlock(CoreLocal::get('paycard_PAN'));
            if (empty($e2e['Block']) || empty($e2e['Key'])){
                PaycardLib::paycard_reset();
                $json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
                                                             "Swipe Error",
                                                             "Error reading card. Swipe again.",
                                                             "[clear] to cancel"
                );
                UdpComm::udpSend('termReset');

                return $json;
            }
        }

        $pan = str_repeat('*', 12) . $e2e['Last4'];
        return PaycardModule::ccEntered($pan, false, $json);
    }

    /**
      Updated for E2E
    */
    public function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        $this->voidTrans = "";
        $this->voidRef = "";
        $json = PaycardModule::ccVoid($transID, $laneNo, $transNo, $json);
        CoreLocal::set("paycard_type",PaycardLib::PAYCARD_TYPE_ENCRYPTED);
    
        return $json;
    }

    public function handleResponse($authResult)
    {
        switch(CoreLocal::get("paycard_mode")){
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
        $xml = new xmlData($resp);

        $dbTrans = PaycardLib::paycard_db();
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);

        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("CMDSTATUS");
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        } else {
            $validResponse = -3;
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

        $cardtype = CoreLocal::get("CacheCardType");
        $ebtbalance = 0;
        if ($xml->get_first("Balance")) {
            switch($cardtype) {
                case 'EBTFOOD':
                    CoreLocal::set('EbtFsBalance', $xml->get_first('Balance'));
                    $ebtbalance = $xml->get_first('Balance');
                    break;
                case 'EBTCASH':
                    CoreLocal::set('EbtCaBalance', $xml->get_first('Balance'));
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
            if (!$this->second_try){
                $this->second_try = true;

                return $this->send_auth("w2.backuppay.com");
            } else if ($authResult['curlHTTP'] == '0') {
                CoreLocal::set("boxMsg","No response from processor<br />
                            The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            } else {
                return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
            }
        } else if ($this->second_try && $authResult['curlTime'] < 10 && CoreLocal::get('MercurySwitchUrls') <= 0) {
            CoreLocal::set('MercurySwitchUrls', 5);
        }

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                if (substr(CoreLocal::get('CacheCardType'), 0, 3) == 'EBT' && $ebtbalance > 0 && $ebtbalance < $xml->get_first('AUTHORIZE')) {
                    // if EBT is declined but lists a balance less than the
                    // requested authorization, it may be possible to
                    // charge the card for a less amount. different return
                    // it to try a less amount immediatley without making
                    // the customer re-enter information
                    CoreLocal::set('PaycardRetryBalanceLimit', sprintf('%.2f', $ebtbalance));    
                    CoreLocal::set('paycard_amount', $ebtbalance);
                    TransRecord::addcomment("");
                    CoreLocal::set('paycard_id', CoreLocal::get('paycard_id') + 1);

                    return PaycardLib::PAYCARD_ERR_NSF_RETRY;
                }
                UdpComm::udpSend('termReset');
                CoreLocal::set('ccTermState','swipe');
                // intentional fallthrough
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                CoreLocal::set("boxMsg","Error: $texts");
                TransRecord::addcomment("");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
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
        $xml = new xmlData($resp);
        $dbTrans = PaycardLib::paycard_db();
        $request = $this->last_request;
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request, $authResult);

        $validResponse = ($xml->isValid()) ? 1 : 0;

        $responseCode = $xml->get("CMDSTATUS");
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        } else {
            $validResponse = -3;
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->get_first("DSIXRETURNCODE");
        $response->setResultCode($resultCode);
        $resultMsg = $xml->get_first("CMDSTATUS");
        if ($resultMsg) {
            $rMsg = $resultMsg;
        }
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
                CoreLocal::set("boxMsg","No response from processor<br />
                            The transaction did not go through");

                return PaycardLib::PAYCARD_ERR_PROC;
            } 

            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
        }

        switch (strtoupper($xml->get("CMDSTATUS"))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // if declined, try again with a regular Void op
                // and no reversal information
                $skipReversal = CoreLocal::get("MercuryE2ESkipReversal");
                if ($skipReversal == true) {
                    CoreLocal::set("MercuryE2ESkipReversal", false);
                } else {
                    return $this->send_void(true);
                }
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                CoreLocal::set("boxMsg","Error: $texts");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    public function cleanup($json)
    {
        switch (CoreLocal::get("paycard_mode")) {
            case PaycardLib::PAYCARD_MODE_ADDVALUE:
            case PaycardLib::PAYCARD_MODE_ACTIVATE:
                CoreLocal::set("autoReprint",1);
                $ttl = CoreLocal::get("paycard_amount");
                $dept = CoreLocal::get('PaycardDepartmentGift');
                $dept = $dept == '' ? 902 : $dept;
                PrehLib::deptkey($ttl*100, $dept . '0');
                $bal = CoreLocal::get('GiftBalance');
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>New card balance: $" . $bal . "
                                           <p>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_BALANCE:
                $bal = CoreLocal::get('DatacapBalanceCheck');
                CoreLocal::set("boxMsg","<b>Success</b><font size=-1>
                                           <p>Card balance: $" . $bal . "
                                           <p>\"rp\" to print
                                           <br>[enter] to continue</font>"
                );
                break;
            case PaycardLib::PAYCARD_MODE_AUTH:
                // cast to string. tender function expects string input
                // numeric input screws up parsing on negative values > $0.99
                $amt = "".(-1*(CoreLocal::get("paycard_amount")));
                $type = CoreLocal::get("CacheCardType");
                $t_type = 'CC';
                if (CoreLocal::get('paycard_issuer') == 'American Express') {
                    $t_type = 'AX';
                }
                if ($type == 'EBTFOOD') {
                    // extra tax exemption steps
                    TransRecord::addfsTaxExempt();
                    CoreLocal::set("fntlflag",0);
                    Database::setglobalvalue("FntlFlag", 0);
                }
                list($tender_code, $tender_description) = PaycardLib::getTenderInfo($type, CoreLocal::get('paycard_issuer'));

                // if the transaction has a non-zero paycardTransactionID,
                // include it in the tender line
                $record_id = $this->last_paycard_transaction_id;
                $charflag = ($record_id != 0) ? 'PT' : '';
                TransRecord::addFlaggedTender($tender_description, $tender_code, $amt, $record_id, $charflag);

                $appr_type = 'Approved';
                if (CoreLocal::get('paycard_partial')){
                    $appr_type = 'Partial Approval';
                } elseif (CoreLocal::get('paycard_amount') == 0) {
                    $appr_type = 'Declined';
                    $json['receipt'] = 'ccDecline';
                }
                CoreLocal::set('paycard_partial', false);

                $isCredit = (CoreLocal::get('CacheCardType') == 'CREDIT' || CoreLocal::get('CacheCardType') == '') ? true : false;
                $needSig = (CoreLocal::get('paycard_amount') > CoreLocal::get('CCSigLimit') || CoreLocal::get('paycard_amount') < 0) ? true : false;
                if (($isCredit || CoreLocal::get('EmvSignature') === true) && $needSig) {
                    CoreLocal::set("boxMsg",
                            "<b>$appr_type</b>
                            <font size=-1>
                            <p>Please verify cardholder signature
                            <p>[enter] to continue
                            <br>\"rp\" to reprint slip
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                    if (CoreLocal::get('PaycardsSigCapture') != 1) {
                        $json['receipt'] = 'ccSlip';
                    }
                } else {
                    CoreLocal::set("boxMsg",
                            "<b>$appr_type</b>
                            <font size=-1>
                            <p>No signature required
                            <p>[enter] to continue
                            <br>[void] " . _('to reverse the charge') . "
                            </font>");
                } 
                break;
            case PaycardLib::PAYCARD_MODE_VOID:
                $void = new Void();
                $void->voidid(CoreLocal::get("paycard_id"), array());
                // advanced ID to the void line
                CoreLocal::set('paycard_id', CoreLocal::get('paycard_id')+1);
                CoreLocal::set("boxMsg","<b>Voided</b>
                                           <p><font size=-1>[enter] to continue
                                           <br>\"rp\" to reprint slip</font>"
                );
                break;
        }

        return $json;
    }

    public function doSend($type)
    {
        $this->second_try = false;
        switch ($type) {
            case PaycardLib::PAYCARD_MODE_AUTH: 
                return $this->send_auth();
            case PaycardLib::PAYCARD_MODE_VOID: 
                return $this->send_void(); 
            default:
                PaycardLib::paycard_reset();
                return $this->setErrorMsg(0);
        }
    }

    /**
      Updated for E2E
      Status: Should be functional once device is available
    */
    private function send_auth($domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans) {
            PaycardLib::paycard_reset();
            // database error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
        }

        $request = new PaycardRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('MercuryE2E');

        if (CoreLocal::get("paycard_voiceauthcode") != "") {
            $request->setMode("VoiceAuth");
        } else if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
            $request->setMode("Voucher");
        }
        $password = $this->getPw();
        $e2e = EncBlock::parseEncBlock(CoreLocal::get("paycard_PAN"));
        $pin = EncBlock::parsePinBlock(CoreLocal::get("CachePinEncBlock"));
        $request->setIssuer($e2e['Issuer']);
        CoreLocal::set('paycard_issuer',$e2e['Issuer']);
        $request->setCardholder($e2e['Name']);
        $request->setPAN($e2e['Last4']);
        CoreLocal::set('paycard_partial',false);
        $request->setSent(0, 0, 0, 1);
        
        // store request in the database before sending it
        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            PaycardLib::paycard_reset();
            // internal error, nothing sent (ok to retry)
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        $msgXml = $this->beginXmlRequest($request);
        if (substr($request->type,0,3) == 'EBT') {
            $msgXml .= '<TranType>EBT</TranType>';
            if ($request->type == 'EBTFOOD') {
                CoreLocal::set('EbtFsBalance', 'unknown');
                $msgXml .= '<CardType>Foodstamp</CardType>';
            } else if ($request->type == 'EBTCASH') {
                $msgXml .= '<CardType>Cash</CardType>';
                CoreLocal::set('EbtCaBalance', 'unknown');
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
        if (CoreLocal::get("paycard_voiceauthcode") != "") {
            $msgXml .= "<TransInfo>";
            $msgXml .= "<AuthCode>";
            $msgXml .= CoreLocal::get("paycard_voiceauthcode");
            $msgXml .= "</AuthCode>";
            $msgXml .= "</TransInfo>";
        } else if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
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
      Prepare an XML request body for an PDCX
      or EMVX transaction
      @param $type [string] card type
      @param $amount [number] authorization amount
      @return [string] XML request body
    */
    public function prepareDataCapAuth($type, $amount, $prompt=false)
    {
        $request = new PaycardRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('MercuryE2E');
        $tran_code = $amount > 0 ? 'Sale' : 'Return';
        if ($type == 'EMV') {
            $tran_code = 'EMV' . $tran_code;
        } elseif ($type == 'GIFT') {
            $tran_code = $amount > 0 ? 'NoNSFSale' : 'Return';
        } else if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
            $tran_code = 'Voucher';
        }

        $host = $this->getAxHost();

        $tran_type = 'Credit';
        $card_type = false;
        if ($type == 'DEBIT') {
            $tran_type = 'Debit';
        } elseif ($type == 'EBTFOOD') {
            $tran_type = 'EBT';
            $card_type = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tran_type = 'EBT';
            $card_type = 'Cash';
        } elseif ($type == 'GIFT') {
            $tran_type = 'PrePaid';
        }

        $request->setManual($prompt ? 1 : 0);

        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            // TODO: cancel request on JS side
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }
        CoreLocal::set('LastEmvPcId', $request->last_paycard_transaction_id);
        CoreLocal::set('LastEmvReqType', 'normal');

        // start with fields common to PDCX and EMVX
        $msgXml = $this->beginXmlRequest($request);
        $msgXml .= '<TranCode>' . $tran_code . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>';
        if ($type == 'EMV') { // add EMV specific fields
            $dc_host = CoreLocal::get('PaycardsDatacapLanHost');
            if (empty($dc_host)) {
                $dc_host = '127.0.0.1';
            }
            $msgXml .= '
            <HostOrIP>' . $dc_host . '</HostOrIP>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <CollectData>CardholderName</CollectData>
            <PartialAuth>Allow</PartialAuth>';
            if ($prompt) {
                $msgXml .= '
                    <Account>
                        <AcctNo>Prompt</AcctNo>
                    </Account>';
            }
            if (CoreLocal::get('PaycardsDatacapMode') == 2) {
                $msgXml .= '<MerchantLanguage>English</MerchantLanguage>';
            } elseif (CoreLocal::get('PaycardsDatacapMode') == 3) {
                $msgXml .= '<MerchantLanguage>French</MerchantLanguage>';
            }
        } else {
            $msgXml .= '
            <Account>
                <AcctNo>' . ($prompt ? 'Prompt' : 'SecureDevice') . '</AcctNo>
            </Account>
            <TranType>' . $tran_type . '</TranType>';
            if ($card_type) {
                $msgXml .= '<CardType>' . $card_type . '</CardType>';
            }
            if ($type == 'CREDIT') {
                $msgXml .= '<PartialAuth>Allow</PartialAuth>';
            }
            if ($type == 'GIFT') {
                $msgXml .= '<IpPort>9100</IpPort>';
                $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
            }
            if (CoreLocal::get("ebt_authcode") != "" && CoreLocal::get("ebt_vnum") != "") {
                $msgXml .= $this->ebtVoucherXml();
            }
        }
        $msgXml .= '
            </Transaction>
            </TStream>';
        
        $this->last_request = $request;

        return $msgXml;
    }

    /**
      Prepare an XML request body to void an PDCX
      or EMVX transaction
      @param $pcID [int] PaycardTransactions record ID
      @return [string] XML request
    */
    public function prepareDataCapVoid($pcID)
    {
        $dbc = Database::tDataConnect();
        $prep = $dbc->prepare('SELECT transNo, registerNo FROM PaycardTransactions WHERE paycardTransactionID=?');
        $row = $dbc->getRow($prep, $pcID);
        CoreLocal::set('paycard_trans', CoreLocal::get('CashierNo') . '-' . $row['registerNo'] . '-' . $row['transNo']);

        $request = new PaycardVoidRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('MercuryE2E');

        $host = $this->getAxHost();
        $request->last_paycard_transaction_id = $pcID; 
        try {
            $prev = $request->findOriginal();
        } catch (Exception $ex) {
            CoreLocal::set('boxMsg', 'Transaction not found');
            return 'Error';
        }

        try {
            $request->saveRequest();
            CoreLocal::set('LastEmvPcId', $request->last_paycard_transaction_id);
            CoreLocal::set('LastEmvReqType', 'void');
        } catch (Exception $ex) {
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }

        /* Determine reversal method based on
           original transaction.
           EMV and Credit are voided
           PIN Debit and EBT run an opposite transaction
           (e.g., Return after a Sale)
        */
        $tran_code = '';
        $tran_type = '';
        $card_type = false;
        if ($prev['mode'] == 'EMVSale') {
            $tran_code = 'EMVVoidSale';
            $tran_type = 'EMV';
        } elseif ($prev['mode'] == 'EMVReturn') {
            $tran_code = 'EMVVoidReturn';
            $tran_type = 'EMV';
        } elseif ($prev['mode'] == 'NoNSFSale') {
            $tran_type = 'PrePaid';
            $tran_code = 'VoidSale';
        } else {
            switch ($prev['cardType']) {
                case 'Credit':
                    $tran_code = ($prev['mode'] == 'Sale') ? 'VoidSaleByRecordNo' : 'VoidReturnByRecordNo';
                    $tran_type = 'Credit';
                    break;
                case 'Debit':
                    $tran_code = ($prev['mode'] == 'Sale') ? 'ReturnByRecordNo' : 'SaleByRecordNo';
                    $tran_type = 'Debit';
                    break;
                case 'EBT':
                    $tran_code = ($prev['mode'] == 'Sale') ? 'ReturnByRecordNo' : 'SaleByRecordNo';
                    $tran_type = 'EBT';
                    $card_type = $prev['issuer'];
                    break;
            }
        }

        // common fields
        $request->setAmount(abs($prev['amount']));
        $msgXml = $this->beginXmlRequest($request);
        $msgXml .= '<TranCode>' . $tran_code . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>';
        if ($tran_type == 'EMV') { // add EMV specific fields
            $dc_host = CoreLocal::get('PaycardsDatacapLanHost');
            if (empty($dc_host)) {
                $dc_host = '127.0.0.1';
            }
            $msgXml .= '
            <HostOrIP>' . $dc_host . '</HostOrIP>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <CollectData>CardholderName</CollectData>
            <PartialAuth>Allow</PartialAuth>';
            if (CoreLocal::get('PaycardsDatacapMode') == 2) {
                $msgXml .= '<MerchantLanguage>English</MerchantLanguage>';
            } elseif (CoreLocal::get('PaycardsDatacapMode') == 3) {
                $msgXml .= '<MerchantLanguage>French</MerchantLanguage>';
            }
        } else { // add non-EMV fields
            $msgXml .= '
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <TranType>' . $tran_type . '</TranType>';
            if ($card_type) {
                $msgXml .= '<CardType>' . $card_type . '</CardType>';
            }
            if ($tran_type == 'PrePaid') {
                $msgXml .= '<IpPort>9100</IpPort>';
                $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
            }
        }
        /**
          Add token and reversal data fields if available
        */
        if ($prev['token']) {
            $msgXml .= '<RecordNo>' . $prev['token'] . '</RecordNo>';
        }
        if ($prev['processData']) {
            $msgXml .= '<ProcessData>' . $prev['processData'] . '</ProcessData>';
        }
        if ($prev['acqRefData']) {
            $msgXml .= '<AcqRefData>' . $prev['acqRefData'] . '</AcqRefData>';
        }
        $msgXml .= '
            <AuthCode>' . $prev['xApprovalNumber'] . '</AuthCode>
            </Transaction>
            </TStream>';

        $this->last_request = $request;

        return $msgXml;
    }

    /**
      Prepare an XML request body for an PDCX
      card balance inquiry
      @param $type [string] card type
      @return [string] XML request body
    */
    public function prepareDataCapBalance($type, $prompt=false)
    {
        CoreLocal::set('DatacapBalanceCheck', '??');
        $termID = $this->getTermID();
        $operatorID = CoreLocal::get("CashierNo");
        $transID = CoreLocal::get('paycard_id');
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }
        $refNum = $this->refnum($transID);

        $host = $this->getAxHost();
        $live = 1;
        if (CoreLocal::get("training") == 1) {
            $live = 0;
            $operatorID = 'test';
        }

        $tran_type = '';
        $card_type = '';
        if ($type == 'EBTFOOD') {
            $tran_type = 'EBT';
            $card_type = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tran_type = 'EBT';
            $card_type = 'Cash';
        } elseif ($type == 'GIFT') {
            $tran_type = 'PrePaid';
        }

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranType>' . $tran_type . '</TranType>
            <TranCode>Balance</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            <Memo>CORE POS 1.0.0 PDCX</Memo>
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <Amount>
                <Purchase>0.00</Purchase>
            </Amount>';
        if ($card_type) {
            $msgXml .= '<CardType>' . $card_type . '</CardType>';
        }
        if ($type == 'GIFT') {
            $msgXml .= '<IpPort>9100</IpPort>';
            $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
        }
        $msgXml .= '</Transaction></TStream>';

        if ($prompt) {
            $msgXml = str_replace('<AcctNo>SecureDevice</AcctNo>',
                '<AcctNo>Prompt</AcctNo>', $msgXml);
        }

        return $msgXml;
    }

    public function prepareDataCapGift($mode, $amount, $prompt)
    {
        $request = new PaycardGiftRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('MercuryE2E');

        $host = "g1.mercurypay.com";
        if (CoreLocal::get("training") == 1) {
            $host = "g1.mercurydev.net";
        }
        $tran_code = 'Issue';
        if ($mode == PaycardLib::PAYCARD_MODE_ADDVALUE) {
            $tran_code = 'Reload';
        }
        $request->setMode($mode);
        $request->setManual($prompt ? 1 : 0);
        $request->setAmount($amount);

        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            // TODO: cancel request on JS side
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }
        CoreLocal::set('LastEmvPcId', $request->last_paycard_transaction_id);
        CoreLocal::set('LastEmvReqType', 'gift');
        CoreLocal::set('paycard_amount', $amount);
        CoreLocal::set('paycard_id', CoreLocal::get('LastID'+1));
        CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
        CoreLocal::set('CacheCardType', 'GIFT');
        CoreLocal::set('paycard_mode', $mode);

        $msgXml = $this->beginXmlRequest($request);
        $msgXml .= '<TranType>PrePaid</TranType>
            <TranCode>' . $tran_code . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <IpPort>9100</IpPort>';
        $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
        $msgXml .= '</Transaction></TStream>';

        if ($prompt) {
            $msgXml = str_replace('<AcctNo>SecureDevice</AcctNo>',
                '<AcctNo>Prompt</AcctNo>', $msgXml);
        }

        $this->last_request = $request;

        return $msgXml;
    }

    /**
      Examine XML response from Datacap transaction,
      log results, determine next step
      @return [int] PaycardLib error code
    */
    public function handleResponseDataCap($xml)
    {
        $rawXml = $xml;
        $ref = $this->refnum(CoreLocal::get('paycard_id'));
        $request = $this->getRequestObj($ref);
        $request->last_paycard_transaction_id = CoreLocal::get('LastEmvPcId');
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request,array(
            'curlTime' => 0,
            'curlErr' => 0,
            'curlHTTP' => 200,
        ));

        $xml = new BetterXmlData($xml);

        $validResponse = 1;
        $responseCode = $xml->query('/RStream/CmdResponse/CmdStatus');
        $resultMsg = $responseCode;
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        } else {
            $validResponse = -3;
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->query('/RStream/CmdResponse/DSIXReturnCode');
        $response->setResultCode($resultCode);
        $apprNumber = $xml->query('/RStream/TranResponse/AuthCode');
        $response->setApprovalNum($apprNumber);
        $rMsg = $resultMsg;
        if ($resultMsg) {
            $rMsg = $resultMsg;
            if ($responseCode == 1) { // approved
                if ($apprNumber) {
                    $rMsg .= ' ' . $apprNumber;
                }
            } else {
                $processor_text = $xml->query('/RStream/CmdResponse/TextResponse');
                if ($processor_text) {
                    $rMsg = $processor_text;
                }
            }
        }
        $response->setResultMsg($rMsg);
        $xTransID = $xml->query('/RStream/TranResponse/RefNo');
        $response->setTransactionID($xTransID);
        if ($xTransID === false) {
            $validResponse = -3;
        }

        $issuer = $xml->query('/RStream/TranResponse/CardType');
        $resp_balance = $xml->query('/RStream/TranResponse/Amount/Balance');
        $ebtbalance = 0;
        if ($issuer == 'Foodstamp' && $resp_balance !== false) {
            $issuer = 'EBT';
            CoreLocal::set('EbtFsBalance', $resp_balance);
            $ebtbalance = $resp_balance;
        } elseif ($issuer == 'Cash' && $resp_balance !== false) {
            $issuer = 'EBT';
            CoreLocal::set('EbtCaBalance', $resp_balance);
            $ebtbalance = $resp_balance;
        } elseif ($xml->query('/RStream/TranResponse/TranType') == 'PrePaid' && $resp_balance !== false) {
            $issuer = 'NCG';
            $ebtbalance = $resp_balance;
            CoreLocal::set('GiftBalance', $resp_balance);
        }
        $response->setBalance($ebtbalance);

        $dbc = Database::tDataConnect();

        $tran_code = $xml->query('/RStream/TranResponse/TranCode');
        if (substr($tran_code, 0, 3) == 'EMV') {
            if (strpos($rawXml, 'x____') !== false) {
                CoreLocal::set('EmvSignature', true);
            } else {
                CoreLocal::set('EmvSignature', false);
            }
            $printData = $xml->query('/RStream/PrintData/*', false);
            if (strlen($printData) > 0) {
                $receiptID = $transID;
                if (CoreLocal::get('paycard_mode') == PaycardLib::PAYCARD_MODE_VOID) {
                    $receiptID++;
                }
                $printP = $dbc->prepare('
                    INSERT INTO EmvReceipt
                        (dateID, tdate, empNo, registerNo, transNo, transID, content)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?)');
                $dbc->execute($printP, array(date('Ymd'), date('Y-m-d H:i:s'), $cashierNo, $laneNo, $transNo, $receiptID, $printData));
            }
        }

        // put normalized value in validResponse column
        $normalized = $this->normalizeResponseCode($responseCode, $validResponse);
        $response->setNormalizedCode($normalized);
        $response->setToken(
            $xml->query('/RStream/TranResponse/RecordNo'),
            $xml->query('/RStream/TranResponse/ProcessData'),
            $xml->query('/RStream/TranResponse/AcqRefData')
        );

        try {
            $response->saveResponse();
        } catch (Exception $ex) {
            echo $ex->getMessage() . "\n";
        }

        /** handle partial auth **/
        if ($responseCode == 1) {
            $amt = $xml->query('/RStream/TranResponse/Amount/Authorize');
            $this->handlePartial($amt, $request);
        }

        $pan = $xml->query('/RStream/TranResponse/AcctNo');
        $resp_name = $xml->query('/RStream/TranResponse/CardholderName');
        $name = $resp_name ? $resp_name : 'Cardholder';
        $request->updateCardInfo($pan, $name, $issuer);

        switch (strtoupper($xml->query('/RStream/CmdResponse/CmdStatus'))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                if ($issuer == 'EBT') {
                    // if EBT is declined but lists a balance less than the
                    // requested authorization, it may be possible to
                    // charge the card for a less amount. 
                    TransRecord::addcomment("");
                    CoreLocal::set('boxMsg', sprintf('Card Balance: $%.2f', $ebtbalance));
                } elseif (substr($tran_code, 0, 3) == 'EMV') {
                    CoreLocal::set('paycard_amount', 0);
                    return PaycardLib::PAYCARD_ERR_OK;
                }
                UdpComm::udpSend('termReset');
                CoreLocal::set('ccTermState','swipe');
                // intentional fallthrough
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->query('/RStream/CmdResponse/TextResponse');
                CoreLocal::set("boxMsg","Error: $texts");
                $dsix = $xml->query('/RStream/CmdResponse/DSIXReturnCode');
                if ($dsix == '001007' || $dsix == '003007' || $dsix == '003010') {
                    /* These error codes indicate a potential connectivity
                     * error mid-transaction. Do not add a comment record to
                     * the transaction to avoid incrementing InvoiceNo
                     */
                } else {
                    TransRecord::addcomment("");
                }
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
                TransRecord::addcomment("");    
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
      Examine XML response from Datacap transaction,
      extract balance and/or error determine next step
      @return [int] PaycardLib error code
    */
    public function handleResponseDataCapBalance($xml)
    {
        $xml = new xmlData($xml);
        $validResponse = ($xml->isValid()) ? 1 : 0;
        $responseCode = $xml->get("CMDSTATUS");
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        } else {
            $validResponse = -3;
        }

        $balance = $xml->get_first('BALANCE');

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                CoreLocal::set('DatacapBalanceCheck', $balance);
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // intentional fallthrough
            case 'ERROR':
                CoreLocal::set("boxMsg","");
                $texts = $xml->get_first("TEXTRESPONSE");
                CoreLocal::set("boxMsg","Error: $texts");
                TransRecord::addcomment("");
                break;
            default:
                CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
                TransRecord::addcomment("");    
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
      Updated for E2E
    */
    private function send_void($skipReversal=False,$domain="w1.mercurypay.com")
    {
        // initialize
        $dbTrans = PaycardLib::paycard_db();
        if (!$dbTrans){
            PaycardLib::paycard_reset();
            return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
        }

        if ($skipReversal) {
            CoreLocal::set("MercuryE2ESkipReversal", true);
        } else {
            CoreLocal::set("MercuryE2ESkipReversal", false);
        }

        $request = new PaycardVoidRequest($this->refnum(CoreLocal::get('paycard_id')));
        $request->setProcessor('MercuryE2E');
        $request->setMode('VoidSaleByRecordNo');

        $password = $this->getPw();
        $transID = CoreLocal::get("paycard_id");
        $this->voidTrans = $transID;
        $this->voidRef = CoreLocal::get("paycard_trans");

        try {
            $res = $request->findOriginal();
        } catch (Exception $ex) {
            PaycardLib::paycard_reset();
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
            } else if (substr($res['mode'],-7)=="_Return") {
                $mode = 'SaleByRecordNo';
            }
            CoreLocal::set("MercuryE2ESkipReversal", true);
        } else if (substr($res['mode'],-7)=="_Return") {
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
        $transNo   = (int)CoreLocal::get("transno");
        $cashierNo = (int)CoreLocal::get("CashierNo");
        $laneNo    = (int)CoreLocal::get("laneno");    

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
        if (CoreLocal::get("training") == 1) {
            if (CoreLocal::get('CacheCardType') == 'EMV') {
                return '337234005'; // emv
            } else {
                return '019588466313922';
                return '118725340908147'; // newer
            }
            //return "395347308=E2ETKN"; // old test ID
        } else {
            return CoreLocal::get('MercuryE2ETerminalID');
        }
    }

    /**
      Return real or testing password depending on
      whether training mode is on
    */
    private function getPw()
    {
        if (CoreLocal::get("training") == 1) {
            return 'xyz';
            return "123E2ETKN";
        } else {
            return CoreLocal::get('MercuryE2EPassword');
        }
    }

    public function myRefNum($ref)
    {
        if (strlen($ref) == 16 && preg_match('/^[0-9]+$/', $ref)) {
            return true;
        } else {
            return false;
        }
    }

    public function lookupTransaction($ref, $local, $mode)
    {
        $ws_params = array(
            'merchant' => CoreLocal::get('MercuryE2ETerminalID'),
            'pw' => CoreLocal::get('MercuryE2EPassword'),
            'invoice' => $ref,
        );

        // emp_no 9999 => test transaction
        if (substr($ref, 4, 4) == "9999") {
            $ws_params['merchant'] = '395347308=E2ETKN';
            $ws_params['pw'] = '123E2ETKN';
        }

        $this->SOAPACTION = 'http://www.mercurypay.com/CTranDetail';
        $soaptext = $this->soapify('CTranDetail', $ws_params, 'http://www.mercurypay.com');
        $this->GATEWAY = 'https://' . self::PRIMARY_URL . '/ws/ws.asmx';

        $curl_result = $this->curlSend($soaptext, 'SOAP', false, array(), false);

        if ($curl_result['curlErr'] != CURLE_OK || $curl_result['curlHTTP'] != 200) {
            $this->GATEWAY = 'https://' . self::BACKUP_URL . '/ws/ws.asmx';
            $curl_result = $this->curlSend($soaptext, 'SOAP', false, array(), false);
            if ($curl_result['curlErr'] != CURLE_OK || $curl_result['curlHTTP'] != 200) {
                return array(
                    'output' => DisplayLib::boxMsg('No response from processor', '', true),
                    'confirm_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
                    'cancel_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
                );
            }
        }

        $directions = 'Press [enter] or [clear] to continue';
        $resp = array(
            'confirm_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
            'cancel_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
        );
        $info = new Paycards();
        $url_stem = $info->pluginUrl();

        $xml_resp = $this->desoapify('CTranDetailResponse', $curl_result['response']);
        $xml = new xmlData($xml_resp);

        $status = trim($xml->get_first('STATUS'));
        if ($status === '') {
            $status = 'NOTFOUND';
            $directions = 'Press [enter] to try again, [clear] to stop';
            $query_string = 'id=' . ($local ? '_l' : '') . $ref . '&mode=' . $mode;
            $resp['confirm_dest'] = $url_stem . '/gui/PaycardTransLookupPage.php?' . $query_string;
        } else if ($local == 1 && $mode == 'verify') {
            // Update PaycardTransactions record to contain
            // actual processor result and finish
            // the transaction correctly
            $responseCode = -3;
            $resultCode = 0;
            $normalized = 0;
            if ($status == 'Approved') {
                $responseCode = 1;
                $normalized = 1;
                PaycardLib::paycard_wipe_pan();
                $this->cleanup(array());
                $resp['confirm_dest'] = $url_stem . '/gui/paycardSuccess.php';
                $resp['cancel_dest'] = $url_stem . '/gui/paycardSuccess.php';
                $directions = 'Press [enter] to continue';
            } else if ($status == 'Declined') {
                PaycardLib::paycard_reset();
                $responseCode = 2;
                $normalized = 2;
            } else if ($status == 'Error') {
                PaycardLib::paycard_reset();
                $responseCode = 0;
                $resultCode = -1; // CTranDetail does not provide this value
                $normalized = 3;
            } else {
                // Unknown status; clear any data
                PaycardLib::paycard_reset();
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

            $db = Database::tDataConnect(); 
            $upP = $db->prepare("
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
                CoreLocal::get('paycard_id'),
            );
            $upR = $db->execute($upP, $args);
        }

        switch(strtoupper($status)) {
            case 'APPROVED':
                $line1 = $status . ' ' . $xml->get_first('authcode');
                $line2 = 'Amount: ' . sprintf('%.2f', $xml->get_first('total'));
                $trans_type = $xml->get_first('trantype');
                $line3 = 'Type: ' . $trans_type;
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

    private function giftServerIP()
    {
        $host = 'g1.mercurypay.com';
        if (CoreLocal::get('training') == 1) {
            $host = 'g1.mercurydev.net';
        }
        $host_cache = CoreLocal::get('DnsCache');
        if (!is_array($host_cache)) {
            $host_cache = array();
        }
        if (isset($host_cache[$host])) {
            return $host_cache[$host];
        } else {
            $addr = gethostbyname($host);
            if ($addr === $host) { // name did not resolve
                return $host;
            } else { // cache IP for next time
                $host_cache[$host] = $addr;
                CoreLocal::set('DnsCache', $host_cache);
                return $addr;
            }
        }
    }

    private function responseToNumber($responseCode)
    {
        // map response status to 0/1/2 for compatibility
        if ($responseCode == "Approved") {
            return 1;
        } elseif ($responseCode == "Declined") {
            return 2;
        } elseif ($responseCode == "Error") {
            return 0;
        } else {
            return -1;
        }
    }

    private function ebtVoucherXml()
    {
        return "<TranInfo>
            <AuthCode>
            " . CoreLocal::get("ebt_authcode") . "
            </AuthCode>
            <VoucherNo>
            " . CoreLocal::get("ebt_vnum") . "
            </VoucherNo>
            </TranInfo>";
    }
    
    private function normalizeResponseCode($responseCode, $validResponse)
    {
        $normalized = ($validResponse == 0) ? 4 : 0;
        if ($responseCode == 1) {
            $normalized = 1;
        } else if ($responseCode == 2) {
            $normalized = 2;
        } else if ($responseCode == 0) {
            $normalized = 3;
        }

        return $normalized;
    }

    private function beginXmlRequest($request, $ref_no=false, $record_no=false)
    {
        $termID = $this->getTermID();
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = CoreLocal::get('laneno');
        }

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$request->cashierNo.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <InvoiceNo>'.$request->refNum.'</InvoiceNo>
            <RefNo>'. ($ref_no ? $ref_no : $request->refNum) .'</RefNo>
            <Memo>CORE POS 1.0.0</Memo>
            <RecordNo>' . ($record_no ? $record_no : 'RecordNumberRequested') . '</RecordNo>
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
        if (CoreLocal::get('MercurySwitchUrls') > 0) {
            if (!$this->second_try) {
                $domain = self::BACKUP_URL;    
            } else {
                $domain = self::PRIMARY_URL;
            }
        } else {
            if (!$this->second_try) {
                $domain = self::PRIMARY_URL;
            } else {
                $domain = self::BACKUP_URL;    
            }
        }

        /**
          SwitchUrls is a counter
          Go back to normal order when it reaches zero 
        */    
        if (CoreLocal::get('MercurySwitchUrls') > 0) {
            $switch_count = CoreLocal::get('MercurySwitchUrls');
            $switch_count--;
            CoreLocal::set('MercurySwitchUrls', $switch_count);
        }

        return $domain;
    }

    private function getWsUrl($domain)
    {
        if (CoreLocal::get("training") == 1) {
            return "https://w1.mercurydev.net/ws/ws.asmx";
        } else {
            return "https://$domain/ws/ws.asmx";
        }
    }

    private function getAxHost()
    {
        $host = "x1.mercurypay.com";
        if (CoreLocal::get("training") == 1) {
            $host = "x1.mercurydev.net";
        }

        return $host;
    }

    private function handlePartial($amt, $request)
    {
        if ($amt != abs(CoreLocal::get("paycard_amount"))) {
            $request->changeAmount($amt);

            CoreLocal::set("paycard_amount",$amt);
            CoreLocal::set("paycard_partial",True);
            UdpComm::udpSend('goodBeep');
        }
    }

    private function getRequestObj($ref)
    {
        if (CoreLocal::get('LastEmvReqType') == 'void') {
            return new PaycardVoidRequest($ref);
        } elseif (CoreLocal::get('LastEmvReqType') == 'gift') {
            return new PaycardGiftRequest($ref);
        } else {
            return new PaycardRequest($ref);
        }
    }
}

