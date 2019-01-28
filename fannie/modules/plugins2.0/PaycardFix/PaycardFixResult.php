<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('MSoapClient')) {
    include(__DIR__ . '/../RecurringEquity/MSoapClient.php');
}

class PaycardFixResult extends FannieRESTfulPage
{

    protected $header = 'Correct Payment Card Transaction SQL';
    protected $title = 'Correct Payment Card Transaction SQL';
    public $discoverable = true;
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    protected function post_handler()
    {
        $dateID = str_replace('-', '', FormLib::get('date'));
        $ref =trim(FormLib::get('ref'));
        $xml = trim(FormLib::get('xml'));
        // if copy/pasting out of vim
        $xml = str_replace('^M', '', $xml);

        $findP = $this->connection->prepare("
            SELECT paycardTransactionID
            FROM " . FannieDB::fqn('PaycardTransactions', 'trans') . "
            WHERE dateID=?
                AND refNum=?
                AND (1=1 or xResultMessage='' OR xResultMessage IS NULL)");
        $ptID = $this->connection->getValue($findP, array($dateID, $ref));
        if ($ptID === false) {
            $this->error = 'Transaction not found';
            return true;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXpath($dom);
        $status = $this->xpathGet($xpath, '/RStream/CmdResponse/CmdStatus');
        $resultCode = 0;
        if ($status === 'Approved') {
            $resultCode = 1;
        } elseif ($status === 'Declined') {
            $resultCode = 2;
        }
        $responseCode = (int)$this->xpathGet($xpath, '/RStream/CmdResponse/DSIXReturnCode');
        $apprNumber = $this->xpathGet($xpath, '/RStream/TranResponse/AuthCode');
        $rMsg = $status . ' ' . $apprNumber;
        if ($resultCode != 1 || !$apprNumber) {
            $rMsg = $this->xpathGet($xpath, '/RStream/CmdResponse/TextResponse');
        }
        $xTransID = $this->xpathGet($xpath, '/RStream/TranResponse/RefNo');
        $token = $this->xpathGet($xpath, '/RStream/TranResponse/RecordNo');
        $procData = $this->xpathGet($xpath, '/RStream/TranResponse/ProcessData');
        $acq = $this->xpathGet($xpath, '/RStream/TranResponse/AcqRefData');

        $upP = $this->connection->prepare("UPDATE "
            . FannieDB::fqn('PaycardTransactions', 'trans') . "
            SET xResultCode=?,
                xApprovalNumber=?,
                xResponseCode=?,
                xResultMessage=?,
                xTransactionID=?,
                xToken=?,
                xProcessorRef=?,
                xAcquirerRef=?
            WHERE dateID=?
                AND refNum=?
                AND paycardTransactionID=?");
        $this->connection->execute($upP, array(
            $resultCode,
            $apprNumber,
            $responseCode,
            $rMsg,
            $xTransID,
            $token,
            $procData,
            $acq,
            $dateID,
            $ref,
            $ptID,
        ));
        $this->success = $ptID;

        $issuer = $this->xpathGet($xpath, '/RStream/TranResponse/CardType');
        $pan = $this->xpathGet($xpath, '/RStream/TranResponse/AcctNo');
        $prep = $this->connection->prepare('UPDATE '
            . FannieDB::fqn('PaycardTransactions', 'trans') . '
            SET issuer=?,
                PAN=?
            WHERE dateID=?
                AND refNum=?
                AND paycardTransactionID=?');
        $this->connection->execute($prep, array($issuer, $pan, $dateID, $ref, $ptID));
        if ($resultCode == 1 && $apprNumber) {
            $amt = $this->xpathGet($xpath, '/RStream/TranResponse/Amount/Authorize');
            if ($amt) {
                $prep = $this->connection->prepare('UPDATE '
                    . FannieDB::fqn('PaycardTransactions', 'trans') . '
                    SET amount=?
                    WHERE dateID=?
                        AND refNum=?
                        AND paycardTransactionID=?');
                $this->connection->execute($prep, array($amt, $dateID, $ref, $ptID));
            }
        }

        return true;
    }

    protected function post_view()
    {
        if (isset($this->error)) {
            return '<div class="alert alert-danger">' . $this->error . '</div>';
        }

        return '<div class="alert alert-success">Record Updated ' . $this->success . '</div>';
    }

    private function xpathGet($xpath, $str)
    {
        $res = $xpath->query($str);
        if ($res === false || $res->length !== 1) {
            return false;
        }

        return $res->item(0)->textContent;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Date</label>
    <input type="text" name="date" class="form-control date-field" required /> 
</div>
<div class="form-group">
    <label>Reference #</label>
    <input type="text" name="ref" class="form-control" required />
</div>
<div class="form-group">
    <label>XML</label>
    <textarea name="xml" class="form-control" rows="20"></textarea>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Update Records</button>
</div>
</form>
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<p>This is an administrative tool to update database records for integrated
transactions. In the event of an error where the transaction response is not
recorded properly but can be recovered from another log or source, enter the
XML response and transaction identifying information here to update the
existing database record.</p>
<p>Capturing token data specifically creates some correction possibilities.
This is probably just one step in solving an issue.</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

