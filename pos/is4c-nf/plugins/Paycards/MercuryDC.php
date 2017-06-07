<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\plugins\Paycards\sql\PaycardRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardGiftRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardVoidRequest;
use COREPOS\pos\plugins\Paycards\sql\PaycardResponse;
use COREPOS\pos\plugins\Paycards\xml\BetterXmlData;
use COREPOS\pos\plugins\Paycards\xml\XmlData;

class MercuryDC extends MercuryE2E
{
    /**
      Prepare an XML request body for an PDCX
      or EMVX transaction
      @param $type [string] card type
      @param $amount [number] authorization amount
      @return [string] XML request body
    */
    public function prepareDataCapAuth($type, $amount, $prompt=false)
    {
        $request = new PaycardRequest($this->refnum($this->conf->get('paycard_id')), PaycardLib::paycard_db());
        $request->setProcessor('MercuryE2E');
        $tranCode = $amount > 0 ? 'Sale' : 'Return';
        if ($type == 'EMV') {
            $tranCode = 'EMV' . $tranCode;
        } elseif ($type == 'GIFT') {
            $tranCode = $amount > 0 ? 'NoNSFSale' : 'Return';
        } elseif ($this->conf->get("ebt_authcode") != "" && $this->conf->get("ebt_vnum") != "") {
            $tranCode = 'Voucher';
        }

        $tranType = 'Credit';
        $cardType = false;
        if ($type == 'DEBIT') {
            $tranType = 'Debit';
        } elseif ($type == 'EBTFOOD') {
            $tranType = 'EBT';
            $cardType = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tranType = 'EBT';
            $cardType = 'Cash';
        } elseif ($type == 'GIFT') {
            $tranType = 'PrePaid';
        }

        $request->setManual($prompt ? 1 : 0);

        try {
            $request->saveRequest();
        } catch (Exception $ex) {
            // TODO: cancel request on JS side
            $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
            return 'Error';
        }
        $this->conf->set('LastEmvPcId', $request->last_paycard_transaction_id);
        $this->conf->set('LastEmvReqType', 'normal');

        // start with fields common to PDCX and EMVX
        $msgXml = $this->beginXmlRequest($request);
        $msgXml .= '<TranCode>' . $tranCode . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>';
        if ($type == 'EMV') { // add EMV specific fields
            $dcHost = $this->conf->get('PaycardsDatacapLanHost');
            $dcHost = $this->pickHost(empty($dcHost) ? '127.0.0.1' : $dcHost);

            $msgXml .= '
            <HostOrIP>' . $dcHost . '</HostOrIP>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <CollectData>CardholderName</CollectData>
            <OKAmount>Disallow</OKAmount>
            <PartialAuth>Allow</PartialAuth>';
            $msgXml .= '
            <Account>
                <AcctNo>' . ($prompt ? 'Prompt' : 'SecureDevice') . '</AcctNo>
            </Account>';
            if ($this->conf->get('PaycardsDatacapMode') == 2) {
                $msgXml .= '<MerchantLanguage>English</MerchantLanguage>';
            } elseif ($this->conf->get('PaycardsDatacapMode') == 3) {
                $msgXml .= '<MerchantLanguage>French</MerchantLanguage>';
            }
        } else {
            $msgXml .= '
            <Account>
                <AcctNo>' . ($prompt ? 'Prompt' : 'SecureDevice') . '</AcctNo>
            </Account>
            <TranType>' . $tranType . '</TranType>';
            if ($cardType) {
                $msgXml .= '<CardType>' . $cardType . '</CardType>';
            }
            if ($type == 'CREDIT') {
                $msgXml .= '<PartialAuth>Allow</PartialAuth>';
            }
            if ($type == 'GIFT') {
                $msgXml .= '<IpPort>9100</IpPort>';
                $msgXml .= '<IpAddress>' . $this->giftServerIP() . '</IpAddress>';
            }
            if ($this->conf->get("ebt_authcode") != "" && $this->conf->get("ebt_vnum") != "") {
                $msgXml .= $this->ebtVoucherXml();
            }
        }
        $msgXml .= '
            </Transaction>
            </TStream>';
        
        $this->last_request = $request;

        return $msgXml;
    }

    public function switchToRecurring($xml)
    {
        $xml = str_replace('OneTime', 'Recurring', $xml);
        $dbc = Database::tDataConnect();
        $query = 'UPDATE PaycardTransactions
            SET transType=' . $dbc->concat("'R.'", 'transType', '') . '
            WHERE paycardTransactionID=?';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($this->last_request->last_paycard_transaction_id));

        return $res ? $xml : false;
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
        $this->conf->set('paycard_trans', $this->conf->get('CashierNo') . '-' . $row['registerNo'] . '-' . $row['transNo']);

        $request = new PaycardVoidRequest($this->refnum($this->conf->get('paycard_id')), $dbc);
        $request->setProcessor('MercuryE2E');

        $request->last_paycard_transaction_id = $pcID; 
        try {
            $prev = $request->findOriginal();
        } catch (Exception $ex) {
            $this->conf->set('boxMsg', 'Transaction not found');
            return 'Error';
        }

        try {
            $request->saveRequest();
            $this->conf->set('LastEmvPcId', $request->last_paycard_transaction_id);
            $this->conf->set('LastEmvReqType', 'void');
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
        $tranCode = '';
        $tranType = '';
        $cardType = false;
        if ($prev['cardType'] == 'EMV' && substr($prev['mode'], -4) == 'Sale') {
            $tranCode = 'EMVVoidSale';
            $tranType = 'EMV';
        } elseif ($prev['cardType'] == 'EMV' && $prev['mode'] == 'Return') {
            $tranCode = 'EMVVoidReturn';
            $tranType = 'EMV';
        } elseif ($prev['mode'] == 'NoNSFSale') {
            $tranType = 'PrePaid';
            $tranCode = 'VoidSale';
        } else {
            switch ($prev['cardType']) {
                case 'Credit':
                    $tranCode = ($prev['mode'] == 'Sale') ? 'VoidSaleByRecordNo' : 'VoidReturnByRecordNo';
                    $tranType = 'Credit';
                    break;
                case 'Debit':
                    $tranCode = ($prev['mode'] == 'Sale') ? 'ReturnByRecordNo' : 'SaleByRecordNo';
                    $tranType = 'Debit';
                    break;
                case 'EBTFOOD':
                case 'EBTCASH':
                    $tranCode = ($prev['mode'] == 'Sale') ? 'ReturnByRecordNo' : 'SaleByRecordNo';
                    $tranType = 'EBT';
                    $cardType = ($prev['cardType'] === 'EBTFOOD') ? 'Foodstamp' : 'Cash';
                    break;
            }
        }

        // common fields
        $request->setAmount(abs($prev['amount']));
        $msgXml = $this->beginXmlRequest($request);
        $msgXml .= '<TranCode>' . $tranCode . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>';
        if ($tranType == 'EMV') { // add EMV specific fields
            $dcHost = $this->conf->get('PaycardsDatacapLanHost');
            $dcHost = $this->pickHost(empty($dcHost) ? '127.0.0.1' : $dcHost);
            $msgXml .= '
            <HostOrIP>' . $dcHost . '</HostOrIP>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <CollectData>CardholderName</CollectData>
            <OKAmount>Disallow</OKAmount>
            <PartialAuth>Allow</PartialAuth>';
            if ($this->conf->get('PaycardsDatacapMode') == 2) {
                $msgXml .= '<MerchantLanguage>English</MerchantLanguage>';
            } elseif ($this->conf->get('PaycardsDatacapMode') == 3) {
                $msgXml .= '<MerchantLanguage>French</MerchantLanguage>';
            }
        } else { // add non-EMV fields
            $msgXml .= '
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <TranType>' . $tranType . '</TranType>';
            if ($cardType) {
                $msgXml .= '<CardType>' . $cardType . '</CardType>';
            }
            if ($tranType == 'PrePaid') {
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
        $this->conf->set('DatacapBalanceCheck', '??');
        $termID = $this->getTermID();
        $operatorID = $this->conf->get("CashierNo");
        $transID = $this->conf->get('paycard_id');
        $mcTerminalID = $this->conf->get('PaycardsTerminalID');
        if ($mcTerminalID === '') {
            $mcTerminalID = $this->conf->get('laneno');
        }
        $refNum = $this->refnum($transID);

        $live = 1;
        if ($this->conf->get("training") == 1) {
            $live = 0;
            $operatorID = 'test';
        }

        $tranType = '';
        $cardType = '';
        if ($type == 'EBTFOOD') {
            $tranType = 'EBT';
            $cardType = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tranType = 'EBT';
            $cardType = 'Cash';
        } elseif ($type == 'GIFT') {
            $tranType = 'PrePaid';
        }

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranType>' . $tranType . '</TranType>
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
        if ($cardType) {
            $msgXml .= '<CardType>' . $cardType . '</CardType>';
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
        $request = new PaycardGiftRequest($this->refnum($this->conf->get('paycard_id')), PaycardLib::paycard_db());
        $request->setProcessor('MercuryE2E');

        $host = "g1.mercurypay.com";
        if ($this->conf->get("training") == 1) {
            $host = "g1.mercurydev.net";
        }
        $tranCode = 'Issue';
        if ($mode == PaycardLib::PAYCARD_MODE_ADDVALUE) {
            $tranCode = 'Reload';
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
        $this->conf->set('LastEmvPcId', $request->last_paycard_transaction_id);
        $this->conf->set('LastEmvReqType', 'gift');
        $this->conf->set('paycard_amount', $amount);
        $this->conf->set('paycard_id', $this->conf->get('LastID'+1));
        $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
        $this->conf->set('CacheCardType', 'GIFT');
        $this->conf->set('paycard_mode', $mode);

        $msgXml = $this->beginXmlRequest($request);
        $msgXml .= '<TranType>PrePaid</TranType>
            <TranCode>' . $tranCode . '</TranCode>
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
        $ref = $this->refnum($this->conf->get('paycard_id'));
        $transID = $this->conf->get('paycard_id');
        $request = $this->getRequestObj($ref);
        $request->last_paycard_transaction_id = $this->conf->get('LastEmvPcId');
        $this->last_paycard_transaction_id = $request->last_paycard_transaction_id;
        $response = new PaycardResponse($request,array(
            'curlTime' => 0,
            'curlErr' => 0,
            'curlHTTP' => 200,
        ), PaycardLib::paycard_db());

        $xml = new BetterXmlData($xml);

        $responseCode = $xml->query('/RStream/CmdResponse/CmdStatus');
        $resultMsg = $responseCode;
        $validResponse = -3;
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        }
        $response->setResponseCode($responseCode);
        $resultCode = $xml->query('/RStream/CmdResponse/DSIXReturnCode');
        $response->setResultCode($resultCode);
        $apprNumber = $xml->query('/RStream/TranResponse/AuthCode');
        $response->setApprovalNum($apprNumber);
        $rMsg = $resultMsg;
        if ($resultMsg) {
            $rMsg = $resultMsg;
            $processorText = $xml->query('/RStream/CmdResponse/TextResponse');
            if ($responseCode == 1 && $apprNumber) { // approved
                $rMsg .= ' ' . $apprNumber;
            } elseif ($processorText) {
                $rMsg = $processorText;
            }
        }
        $response->setResultMsg($rMsg);
        $xTransID = $xml->query('/RStream/TranResponse/RefNo');
        $response->setTransactionID($xTransID);
        if ($xTransID === false) {
            $validResponse = -3;
        }

        $issuer = $xml->query('/RStream/TranResponse/CardType');
        $respBalance = $xml->query('/RStream/TranResponse/Amount/Balance');
        $ebtbalance = 0;
        if ($issuer == 'Foodstamp' && $respBalance !== false) {
            $issuer = 'EBT';
            $this->conf->set('EbtFsBalance', $respBalance);
            $ebtbalance = $respBalance;
        } elseif ($issuer == 'Cash' && $respBalance !== false) {
            $issuer = 'EBT';
            $this->conf->set('EbtCaBalance', $respBalance);
            $ebtbalance = $respBalance;
        } elseif ($xml->query('/RStream/TranResponse/TranType') == 'PrePaid' && $respBalance !== false) {
            $issuer = 'NCG';
            $ebtbalance = $respBalance;
            $this->conf->set('GiftBalance', $respBalance);
        }
        $response->setBalance($ebtbalance);

        $dbc = Database::tDataConnect();

        $tranCode = $xml->query('/RStream/TranResponse/TranCode');
        if (substr($tranCode, 0, 3) == 'EMV') {
            $this->conf->set('EmvSignature', false);
            if (strpos($rawXml, 'x____') !== false) {
                $this->conf->set('EmvSignature', true);
            }
            $printData = $xml->query('/RStream/PrintData/*', false);
            /* Code Climate's syntax highlighting gets confused by the previous line */
            if (strlen($printData) > 0) {
                $receiptID = $transID;
                if ($this->conf->get('paycard_mode') == PaycardLib::PAYCARD_MODE_VOID) {
                    $receiptID++;
                }
                $printP = $dbc->prepare('
                    INSERT INTO EmvReceipt
                        (dateID, tdate, empNo, registerNo, transNo, transID, content)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?)');
                $dbc->execute($printP, array(date('Ymd'), date('Y-m-d H:i:s'), $this->conf->get('CashierNo'), $this->conf->get('laneno'), $this->conf->get('transno'), $receiptID, $printData));
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
        $respName = $xml->query('/RStream/TranResponse/CardholderName');
        $name = $respName ? $respName : 'Cardholder';
        $request->updateCardInfo($pan, $name, $issuer);

        switch (strtoupper($xml->query('/RStream/CmdResponse/CmdStatus'))) {
            case 'APPROVED':
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // intentional fallthrough
            case 'ERROR':
                $this->conf->set("boxMsg","");
                $texts = $xml->query('/RStream/CmdResponse/TextResponse');
                $this->conf->set("boxMsg","Error: $texts");
                if ($issuer == 'EBT' && $ebtbalance) {
                    // if EBT is declined but lists a balance less than the
                    // requested authorization, it may be possible to
                    // charge the card for a lesser amount.
                    $this->conf->set('boxMsg', sprintf('Error: %s<br />Card Balance: $%.2f', $texts, $ebtbalance));
                }
                $dsix = $xml->query('/RStream/CmdResponse/DSIXReturnCode');
                if ($dsix !== '001007' || $dsix !== '003007' || $dsix !== '003010') {
                    /* These error codes indicate a potential connectivity
                     * error mid-transaction. Do not add a comment record to
                     * the transaction to avoid incrementing InvoiceNo
                     */
                    TransRecord::addcomment("");
                }
                UdpComm::udpSend('termReset');
                $this->conf->set('ccTermState','swipe');
                break;
            default:
                $this->conf->set("boxMsg","An unknown error occurred<br />at the gateway");
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
        $xml = new XmlData($xml);
        $responseCode = $xml->get("CMDSTATUS");
        $validResponse = -3;
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        }

        $balance = $xml->get_first('BALANCE');

        switch (strtoupper($xml->get_first("CMDSTATUS"))) {
            case 'APPROVED':
                $this->conf->set('DatacapBalanceCheck', $balance);
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
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

    private function pickHost($hosts)
    {
        // split on any delimiter
        $names = preg_split('/[^0-9\.]+/', $hosts, -1, PREG_SPLIT_NO_EMPTY);
        shuffle($names);
        if (count($names) == 0) {
            return '127.0.0.1';
        } else {
            return array_reduce($names, function($c, $i){ return $c . trim($i) . ','; });
        }
    }
}

