<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('MSoapClient')) {
    include(__DIR__ . '/../RecurringEquity/MSoapClient.php');
}

class PaycardFixVoid extends FannieRESTfulPage
{

    protected $header = 'Refund Payment Card Transaction';
    protected $title = 'Refund Payment Card Transaction';
    public $discoverable = true;
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    public function preprocess()
    {
        $this->addRoute('get<resultID>');
        return parent::preprocess();
    }

    private function paycardTransP($table)
    {
        return $this->connection->prepare("
            SELECT *
            FROM {$table}
            WHERE dateID=?
                AND refNum=?
                AND xResultCode=1
                AND xResultMessage LIKE '%approve%'
                AND xResultMessage NOT LIKE '%decline%'
                AND transType='Sale'
                AND xToken <> ''
                AND xToken IS NOT NULL
        ");
    }

    private function refnum($emp, $reg, $trans, $id)
    {
        $ref = "";
        $ref .= date("md");
        $ref .= str_pad($emp, 4, "0", STR_PAD_LEFT);
        $ref .= str_pad($reg,    2, "0", STR_PAD_LEFT);
        $ref .= str_pad($trans,   3, "0", STR_PAD_LEFT);
        $ref .= str_pad($id,   3, "0", STR_PAD_LEFT);
        return $ref;
    }

    protected function get_resultID_view()
    {
        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'PaycardTransactions';
        $prep = $this->connection->prepare("SELECT * FROM {$table} WHERE storeRowID=? and dateID=?");
        $row = $this->connection->getRow($prep, array($this->resultID, date('Ymd')));
        $ret = '<table class="table table-bordered table-striped">';
        foreach ($row as $key => $val) {
            if (!is_numeric($key)) {
                $ret .= "<tr><th>{$key}</th><td>{$val}</td></tr>";
            }
        }
        $ret .= '</table>';

        return $ret;
    }

    /**
     * Process the actual refund. Long so comments are
     * internal
     */
    protected function post_handler()
    {
        try {
            $pDate = $this->form->date;
            $invoice = $this->form->invoice;
        } catch (Exception $ex) {
            echo 'Bad submission';
            return false;
        }

        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'PaycardTransactions';
        $findP = $this->paycardTransP($table);
        $ptrans = $this->connection->getRow($findP, array(date('Ymd', strtotime($pDate)), $invoice));
        if ($ptrans == false) {
            echo 'Bad submission' . $pDate;
            return false;
        }

        // figure out POS transaction numbering depending whether this
        // is a new POS transaction or not
        $EMP = $ptrans['empNo'];
        $REG = $ptrans['registerNo'];
        $TRANS = $ptrans['transNo'];
        $TRANS_ID = $ptrans['transID'];
        $newInvoice = $this->refnum($EMP, $REG, $TRANS, $TRANS_ID);

        // prepare PaycardTransactions INSERT
        $ptransP = $this->connection->prepare("INSERT INTO {$table} (dateID, empNo, registerNo, transNo, transID,
            previousPaycardTransactionID, processor, refNum, live, cardType, transType, amount, PAN, issuer,
            name, manual, requestDatetime, responseDatetime, seconds, commErr, httpCode, validResponse,
            xResultCode, xApprovalNumber, xResponseCode, xResultMessage, xTransactionID, xBalance, xToken,
            xProcessorRef, xAcquirerRef) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $pcRow = array(
            date('Ymd'),
            $EMP,
            $REG,
            $TRANS,
            $TRANS_ID,
            $ptrans['paycardTransactionID'],
            $ptrans['processor'],
            $newInvoice,
            1,
            'CREDIT',
            'VOID',
            $ptrans['amount'],
            $ptrans['PAN'],
            $ptrans['issuer'],
            $ptrans['name'],
            $ptrans['manual'],
            date('Y-m-d H:i:s'),
        );

        $credentials = json_decode(file_get_contents(__DIR__ . '/../RecurringEquity/credentials.json'), true);
        $storeID = $ptrans['registerNo'] < 10 ? 1 : 2;
        $hostOrIP = '127.0.0.1';
        if (!is_array($credentials) || !isset($credentials[$storeID])) {
            echo 'Cannot find acct info';
            return false;
        }

        $terminalID = '';
        if ($ptrans['processor'] == 'RapidConnect') {
            $hostOrIP = $credentials['hosts']['RapidConnect' . $storeID][0];
            $storeID = "RapidConnect" . $storeID;
            $terminalID = '<TerminalID>' . $credentials[$storeID][1] . '</TerminalID>';
        }

        $reqXML = <<<XML
<?xml version="1.0"?>
<TStream>
    <Transaction>
        <HostOrIP>{$hostOrIP}</HostOrIP>
        <IpPort>9000</IpPort>
        <MerchantID>{$credentials[$storeID][0]}</MerchantID>
        {$terminalID}
        <OperatorID>{$EMP}</OperatorID>
        <TranType>Credit</TranType>
        <TranCode>VoidSaleByRecordNo</TranCode>
        <SecureDevice>{{SecureDevice}}</SecureDevice>
        <ComPort>{{ComPort}}</ComPort>
        <InvoiceNo>{$newInvoice}</InvoiceNo>
        <RefNo>{$ptrans['xTransactionID']}</RefNo>
        <Amount>
            <Purchase>{$ptrans['amount']}</Purchase>
        </Amount>
        <Account>
            <AcctNo>SecureDevice</AcctNo>
        </Account>
        <LaneID>{$REG}</LaneID>
        <SequenceNo>{{SequenceNo}}</SequenceNo>
        <RecordNo>{$ptrans['xToken']}</RecordNo>
        <AuthCode>{$ptrans['xApprovalNumber']}</AuthCode>
        <Frequency>OneTime</Frequency>
XML;
        if ($ptrans['xProcessorRef']) {
            $reqXML .= '<ProcessData>' . $ptrans['xProcessorRef'] . '</ProcessData>' . "\n";
        }
        if ($ptrans['xAcquirerRef']) {
            $reqXML .= '<AcqRefData>' . $ptrans['xAcquirerRef'] . '</AcqRefData>' . "\n";
        }
        $reqXML .= <<<XML
    </Transaction>
</TStream>
XML;
        $fp = fopen(__DIR__ . '/log.xml', 'a');
        fwrite($fp, $reqXML . "\n");
        $startTime = microtime(true);
        $success = false;

        $curl = curl_init('http://' . $credentials['hosts'][$storeID][0] . ':8999');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $reqXML);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        $respXML = curl_exec($curl);
        fwrite($fp, $respXML . "\n");
        $resp = simplexml_load_string($respXML);
        if (strlen($respXML) > 0 && $resp !== false) {
            $elapsed = microtime(true) - $startTime;
            $pcRow[] = date('Y-m-d H:i:s');
            $pcRow[] = $elapsed;
            $pcRow[] = 0;
            $pcRow[] = 200;
            $pcRow[] = 1; // valid response
            $status = strtolower($resp->CmdResponse->CmdStatus[0]);
            if ($status == 'approved') { // finish record as approved
                $success = true;
                $pcRow[] = 1;
                $pcRow[] = $resp->TranResponse->AuthCode[0];
                $pcRow[] = $resp->CmdResponse->DSIXReturnCode[0];
                $pcRow[] = $resp->CmdResponse->TextResponse[0];
                $pcRow[] = $resp->TranResponse->RefNo[0];
                $pcRow[] = 0; // xBalance
                $pcRow[] = $resp->TranResponse->RecordNo[0];
                $pcRow[] = $resp->TranResponse->ProcessData[0];
                $pcRow[] = $resp->TranResponse->AcqRefData[0];
            } else { // finish record as declined or errored
                $pcRow[] = $status == 'declined' ? 2 : 3;
                $pcRow[] = ''; // xApprovalNumber
                $pcRow[] = $resp->CmdResponse->DSIXReturnCode[0];
                $pcRow[] = $status == 'declined' ? 'DECLINED' : $resp->CmdResponse->TextResponse[0];
                $pcRow[] = ''; // xTransactionID
                $pcRow[] = 0; // xBalance
                $pcRow[] = ''; // xToken
                $pcRow[] = ''; // xProcessorRef
                $pcRow[] = ''; // xAcquirerRef
            }
        } else {
            $elapsed = microtime(true) - $startTime;
            $pcRow[] = date('Y-m-d H:i:s');
            $pcRow[] = $elapsed;
            $pcRow[] = curl_errno($curl);
            $pcRow[] = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $pcRow[] = 1; // valid response
            $pcRow[] = 3; // xResultCode
            $pcRow[] = ''; // xApprovalNumber
            $pcRow[] = 0; // xResponseCode
            $pcRow[] = curl_error($curl);
            $pcRow[] = ''; // xTransactionID
            $pcRow[] = 0; // xBalance
            $pcRow[] = ''; // xToken
            $pcRow[] = ''; // xProcessorRef
            $pcRow[] = ''; // xAcquirerRef
        }

        $this->connection->execute($ptransP, $pcRow);
        $pcID = $this->connection->insertID();

        return 'PaycardFixVoid.php?resultID=' . $pcID;
    }

    /**
     * Validate the submitted form info
     *
     * Make sure that:
     *  1. PaycardTransaction record exists
     *  2. PaycardTransaction record is unique
     *
     * If validation succeeds, create a POST form to continue
     */
    protected function get_id_view()
    {
        try {
            $pDate = trim($this->form->date);
        } catch (Exception $ex) {
            return '<div class="alert alert-danger">Invalid data</div>'
                . $this->get_view();
        }

        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'PaycardTransactions';
        $findP = $this->paycardTransP($table);
        $findR = $this->connection->execute($findP, array(date('Ymd', strtotime($pDate)), $this->id));
        if ($this->connection->numRows($findR) == 0) {
            return '<div class="alert alert-danger">Paycard transaction not found</div>'
                . $this->get_view();
        }
        if ($this->connection->numRows($findR) > 1) {
            return '<div class="alert alert-danger">Multiple matching transactions; cannot continue</div>'
                . $this->get_view();
        }
        $ptrans = $this->connection->fetchRow($findR);

        return <<<HTML
<p>
Voidng \${$ptrans['amount']} payment on card {$ptrans['PAN']}.
</p>
<form method="post" action="PaycardFixVoid.php">
    <input type="hidden" name="date" value="{$pDate}" />
    <input type="hidden" name="invoice" value="{$this->id}" />
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Process Void</button>
    </div>
</form>
HTML;
    }

    /**
     * Create a form to start the refund process
     */
    protected function get_view()
    {
        return <<<HTML
<p class="well">
This will try to run a void transaction via Mercury.
If you've already issued a refund to the card (e.g., by calling the processor)
use <a href="PaycardFixGeneric.php">this tool</a> instead to create a corresponding
POS transaction.
</p>
<form method="get">
    <div class="form-group">
        <label>Card Transaction Date</label>
        <input type="text" class="form-control date-field" required name="date" />
    </div>
    <div class="form-group">
        <label>Card Transaction Invoice #</label>
        <input type="text" class="form-control" required name="id" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Continue</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

