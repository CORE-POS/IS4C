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
    protected $proc_name = 'MercuryE2E';
    public function __construct($name='MercuryE2E')
    {
        parent::__construct();
        if (strlen($name) > 0) {
            $this->proc_name = $name;
        }
        // testing temp; needs config setting later
        $this->conf->set('EWICBins', '610349:7112913');
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
        $request = new PaycardRequest($this->refnum($this->conf->get('paycard_id')), PaycardLib::paycard_db());
        $request->setProcessor($this->proc_name);
        $tranCode = $amount > 0 ? 'Sale' : 'Return';

        $tipped = false;
        if ($type == 'EMVTIP') {
            $tipped = true;
            $type = 'EMV';
            $this->conf->set('CachedCardType', 'EMV');
        }

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
        $msgXml = $this->beginXmlRequest($request, false, false, $tipped);
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
            <PartialAuth>Allow</PartialAuth>';
            if (!$tipped) {
                $msgXml .= '<OKAmount>Disallow</OKAmount>';
            }
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
            <CollectData>CardholderName</CollectData>
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

    public function prepareDataCapWic($itemData, $tranCode, $last4, $prompt=false)
    {
        $request = new PaycardRequest($this->refnum($this->conf->get('paycard_id')), PaycardLib::paycard_db());
        $request->setProcessor($this->proc_name);

        $tranType = 'EBT';
        $cardType = 'EWIC';

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
            <SecureDevice>' . ($last4 ? 'NONE' : '{{SecureDevice}}') . '</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <Account>
                <AcctNo>SecureDevice</AcctNo>
            </Account>
            <TranType>EBT</TranType>
            <CardType>EWIC</CardType>
            <PartialAuth>Allow</PartialAuth>';
        if ($last4) {
            $msgXml .= '<UseLastCardID>' . $last4 . '</UseLastCardID>';
        }
        $msgXml .= '<EWICBins>' . $this->conf->get('EWICBins') . '</EWICBins>'
            . $itemData
            . '</Transaction>
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
        if ($row == false || count($row) == 0) {
            $server = Database::mDataConnect();
            $prep = $server->prepare('SELECT transNo, registerNo FROM PaycardTransactions WHERE paycardTransactionID=? AND registerNo=?');
            $row = $server->getRow($prep, array($pcID, $this->conf->get('laneno')));
        }
        $this->conf->set('paycard_trans', $this->conf->get('CashierNo') . '-' . $row['registerNo'] . '-' . $row['transNo']);

        $request = new PaycardVoidRequest($this->refnum($this->conf->get('paycard_id')), $dbc);
        $request->setProcessor($this->proc_name);

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
                    $tranCode = ($prev['mode'] == 'Sale') ? 'Return' : 'Sale';
                    $tranType = 'Debit';
                    break;
                case 'EBTFOOD':
                case 'EBTCASH':
                    $tranCode = ($prev['mode'] == 'Sale') ? 'Return' : 'Sale';
                    $tranType = 'EBT';
                    $cardType = ($prev['cardType'] === 'EBTFOOD') ? 'Foodstamp' : 'Cash';
                    break;
                case 'EWIC':
                    $tranType = 'EBT';
                    $tranCode = 'ReverseSale';
                    $cardType = 'EWIC';
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
                <AcctNo>' . ($prev['manual'] == 0 ? 'Prompt' : 'SecureDevice') . '</AcctNo>
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
        if ($cardType == 'EWIC') {
            $msgXml .= '<EWICBins>' . $this->conf->get('EWICBins') . '</EWICBins>';
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
        $this->conf->set('EWICBalance', '??');
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
        $tranCode = 'Balance';
        if ($type == 'EBTFOOD') {
            $tranType = 'EBT';
            $cardType = 'Foodstamp';
        } elseif ($type == 'EBTCASH') {
            $tranType = 'EBT';
            $cardType = 'Cash';
        } elseif ($type == 'GIFT') {
            $tranType = 'PrePaid';
        } elseif ($type == 'EWIC') {
            $tranType = 'EBT';
            $cardType = 'EWIC';
        } elseif ($type == 'EWICVAL') {
            $tranType = 'EBT';
            $cardType = 'EWIC';
            $tranCode = 'BalancePreVal';
        }

        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <MerchantID>'.$termID.'</MerchantID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <LaneID>'.$mcTerminalID.'</LaneID>
            <TranType>' . $tranType . '</TranType>
            <TranCode>' . $tranCode . '</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            <Memo>CORE POS 1.0.0 PDCX</Memo>
            <Account>
                <AcctNo>' . ($prompt ? 'Prompt' : 'SecureDevice') . '</AcctNo>
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
        } elseif ($type == 'EWIC' || $type == 'EWICVAL') {
            $msgXml .= '<EWICBins>' . $this->conf->get('EWICBins') . '</EWICBins>';
        }
        $msgXml .= '</Transaction></TStream>';

        return $msgXml;
    }

    public function prepareDataCapGift($mode, $amount, $prompt)
    {
        $request = new PaycardGiftRequest($this->refnum($this->conf->get('paycard_id')), PaycardLib::paycard_db());
        $request->setProcessor($this->proc_name);

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

        $dbc = Database::tDataConnect();

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
        } elseif ($issuer == 'EWIC' && $responseCode == 1) {
            $this->wicReceiptHandler($dbc, $xml, $transID);
            $balance = 'WIC';
        } elseif ($xml->query('/RStream/TranResponse/TranType') == 'PrePaid' && $respBalance !== false) {
            $issuer = 'NCG';
            $ebtbalance = $respBalance;
            $this->conf->set('GiftBalance', $respBalance);
        }
        $response->setBalance($ebtbalance);

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

            $tipAmount = $xml->query('/RStream/TranResponse/Amount/Gratuity');
            if ($tipAmount) {
                $dept = $this->conf->get('PaycardsTipDepartment');
                $deptObj = new COREPOS\pos\lib\DeptLib($this->conf);
                $deptObj->deptkey($tipAmount*100, $dept . '0');
            }
        }

        $pan = $xml->query('/RStream/TranResponse/AcctNo');
        $respName = $xml->query('/RStream/TranResponse/CardholderName');
        $entryMethod = $xml->query('/RStream/TranResponse/EntryMethod');
        $name = $respName ? $respName : 'Cardholder';
        $request->updateCardInfo($pan, $name, $issuer, $entryMethod);
        if (false && strtoupper($entryMethod) == 'CHIP' && $this->conf->get('EmvSignature')) {
            $this->conf->set('EmvSignature', false);
        }

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
                if ($dsix !== '001007' && $dsix !== '003007' && $dsix !== '003010') {
                    /* These error codes indicate a potential connectivity
                     * error mid-transaction. Do not add a comment record to
                     * the transaction to avoid incrementing InvoiceNo
                     */
                    TransRecord::addcomment("");
                }
                UdpComm::udpSend('termReset');
                $this->conf->set('ccTermState','swipe');
                if (method_exists($request, 'dropTransID')) {
                    $request->dropTransID();
                }
                break;
            default:
                $this->conf->set("boxMsg","An unknown error occurred<br />at the gateway");
        }

        return PaycardLib::PAYCARD_ERR_PROC;
    }

    /**
     * Recording eWIC balances is complicated. 
     *
     * A sale includes a balance-check transaction followed by a sale
     * transaction. Both responses include balance data.
     *
     * A reverse sale transaction *does not* include a balance in the
     * response so if a reversal succeeds we need to remove the saved
     * balance from the sale transaction effectively reverting to the
     * balance from the prior balance-check transaction
     */
    private function wicReceiptHandler($dbc, $xml, $transID)
    {
        if ($xml->query('/RStream/TranResponse/TranCode') == 'ReverseSale') {
            $clearP = $dbc->prepare('DELETE FROM EmvReceipt WHERE dateID=? AND empNo=? AND registerNo=? AND transNo=? AND transID=?');
            $dbc->execute($clearP, array(
                date('Ymd'),
                $this->conf->get('CashierNo'),
                $this->conf->get('laneno'),
                $this->conf->get('transno'),
                $transID));
        } else {
            $wicBal = $this->eWicBalanceToArray($xml);
            $receipt = $this->eWicBalanceToString($wicBal);
            $this->conf->set('EWicBalance', $wicBal);
            $wicExpires = $xml->query('/RStream/TranResponse/EarliestBenefitExpDate');
            $wicExpires = substr($wicExpires, 0, 2) . '/' . substr($wicExpires, 2, 2) . '/' . substr($wicExpires, -4);
            $receipt .= "\n\nBenefits expire on {$wicExpires}\n";
            $this->conf->set('EWicBalanceReceipt', $receipt);
            if ($xml->query('/RStream/TranResponse/TranCode') != 'Balance') {
                $printP = $dbc->prepare('
                    INSERT INTO EmvReceipt
                        (dateID, tdate, empNo, registerNo, transNo, transID, content)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?)');
                $dbc->execute($printP, array(
                    date('Ymd'),
                    date('Y-m-d H:i:s'),
                    $this->conf->get('CashierNo'),
                    $this->conf->get('laneno'),
                    $this->conf->get('transno'),
                    $transID,$receipt
                ));
            }
        }
    }

    /**
      Examine XML response from Datacap transaction,
      extract balance and/or error determine next step
      @return [int] PaycardLib error code
    */
    public function handleResponseDataCapBalance($xml)
    {
        $better = new BetterXmlData($xml);
        $responseCode = $better->query('/RStream/CmdResponse/CmdStatus');
        $validResponse = -3;
        if ($responseCode) {
            $responseCode = $this->responseToNumber($responseCode);
        }

        $dbc = Database::tDataConnect();

        $balance = $better->query('/RStream/TranResponse/Amount/Balance');
        $cardType = $better->query('/RStream/TranResponse/CardType');
        if ($cardType == 'EWIC') {
            $dbc = Database::tDataConnect();
            $this->wicReceiptHandler($dbc, $better, $this->conf->get('LastID'));
            $balance = 'WIC';
            $last4 = $better->query('/RStream/TranResponse/AcctNo');
            if ($last4) {
                $this->conf->set('EWicLast4', substr($last4, -4));
            }
        }

        switch (strtoupper($better->query("/RStream/CmdResponse/CmdStatus"))) {
            case 'APPROVED':
                $this->conf->set('DatacapBalanceCheck', $balance);
                return PaycardLib::PAYCARD_ERR_OK;
            case 'DECLINED':
                // intentional fallthrough
            case 'ERROR':
                $this->conf->set("boxMsg","");
                $texts = $better->query("/RStream/CmdResponse/TextResponse");
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
     * Convert XML eWic balance to a 2d array
     * @param $xml [BetterXmlData] response
     * @return [
     *      [ cat => [category data], subcat => [subcategory data], qty => number ],
     *      [ cat => [category data], subcat => [subcategory data], qty => number ],
     *      ...
     *  ]
     */
    private function eWicBalanceToArray($xml)
    {
        $ret = array();
        $dbc = Database::tDataConnect();
        $catP = $dbc->prepare('SELECT * FROM ' . $this->conf->get('pDatabase') . $dbc->sep() . 'EWicCategories WHERE eWicCategoryID=?');
        $subP = $dbc->prepare('SELECT * FROM ' . $this->conf->get('pDatabase') . $dbc->sep() . 'EWicSubCategories WHERE eWicCategoryID=? and eWicSubCategoryID=?');
        $i = 1;
        $cache = array(
            'cat' => array(),
            'subcat' => array(),
        );

        while (true) {
            $cat = $xml->query('/RStream/TranResponse/ProductData/ProductCat' . $i);
            if ($cat === false) {
                break; // end of data
            }
            $subcat = $xml->query('/RStream/TranResponse/ProductData/ProductSubCat' . $i);
            $qty = $xml->query('/RStream/TranResponse/ProductData/ProductQty' . $i);

            if (!isset($cache['cat'][$cat])) {
                $row = $dbc->getRow($catP, array($cat));
                $cache['cat'][$cat] = $row;
            }

            if ($subcat != 0) {
                if (!isset($cache['subcat'][$subcat])) {
                    $row = $dbc->getRow($subP, array($cat, $subcat));
                    $cache['subcat'][$subcat] = $row;
                }

                $ret[] = array('cat' => $cache['cat'][$cat], 'subcat' => $cache['subcat'][$subcat], 'qty'=>$qty);
            } else {
                $ret[] = array('cat' => $cache['cat'][$cat], 'qty'=>$qty);
            }

            $i++;
            if ($i > 1000) break; // safety check
        }

        return $ret;
    }

    /**
     * Convert XML eWic balance to receipt-friendly string
     * @param $data [array] balance response
     * @return [string]
     */
    private function eWicBalanceToString($data)
    {
        $ret = "";
        foreach ($data as $row) {

            if (isset($row['subcat']) && $row['subcat']['qtyMethod']) { 
                $ret .= '$';
            }
            $qty = sprintf('%.2f', $row['qty']);
            $ret .= str_pad($qty, 8, ' ', STR_PAD_RIGHT);
            if (isset($row['subcat']) && $row['subcat']) {

                $ret .= $row['cat']['name'] . ' ' . $row['subcat']['name'] . ' ';
            } else {
                $ret .= $row['cat']['name'] . ' ';
            }

            $ret .= "\n";
        }

        return $ret;
    }

    private function pickHost($hosts)
    {
        // split on any delimiter
        $names = preg_split('/,/', $hosts, -1, PREG_SPLIT_NO_EMPTY);
        shuffle($names);
        if (count($names) == 0) {
            return '127.0.0.1';
        } else {
            return array_reduce($names, function($c, $i){ return $c . trim($i) . ','; });
        }
    }

    private function batchXmlInit($type)
    {
        $termID = $this->getTermID();
        $msg = <<<XML
<?xml version="1.0">
<TStream>
    <Admin>
        <MerchantID>{$termID}</MerchantID>
        <TranCode>{$type}</TranCode>
        <SecureDevice>{{SecureDevice}}</SecureDevice>
        <ComPort>{{ComPort}}</ComPort>
XML;
        return $msg;
    }
}

