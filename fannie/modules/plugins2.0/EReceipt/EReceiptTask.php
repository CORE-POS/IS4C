<?php

class EReceiptTask extends FannieTask
{
    public $log_start_stop = false;

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $delP = $dbc->prepare("DELETE FROM EReceiptQueue WHERE eReceiptQueueID=?");
        $res = $dbc->query("SELECT * FROM EReceiptQueue");
        while ($row = $dbc->fetchRow($res)) {
            $msg = EReceiptLib::getReceipt($row['transNum'], $row['cardNo']);
            EReceiptLib::sendEmail($msg, $row['email'], $row['transNum']);
            $dbc->execute($delP, array($row['eReceiptQueueID']));
            $this->cronMsg("Sending {$row['transNum']} for owner {$row['cardNo']}", FannieLogger::INFO);
        }
    }
}

