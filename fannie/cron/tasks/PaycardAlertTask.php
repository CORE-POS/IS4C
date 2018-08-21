<?php

class PaycardAlertTask extends FannieTask
{
    public $name = 'Paycard Alert';

    public $description = 'Check for odd looking card transactions & send emails';

    public $default_schedule = array(
        'min' => '5',
        'hour' => '6-23',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
        $dateID = date('Ymd');
        $hour = date('G') - 2;
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $today = date('Y-m-d');
        $start = "$today $hour:00:00";
        $end = "$today $hour:59:59";

        $prep = $dbc->prepare("SELECT *
            FROM PaycardTransactions
            WHERE dateID=?
                AND requestDatetime BETWEEN ? AND ?
                AND (
                    xResultMessage IS NULL
                    OR xResultMessage = ''
                    OR httpCode <> 200
                )");
        $res = $dbc->execute($prep, array($dateID, $start, $end));
        $out = '';
        while ($row = $dbc->fetchRow($res)) {
            $receipt = $row['empNo'] . '-' . $row['registerNo'] . '-' . $row['transNo'];
            $out .= sprintf('%s http://%s%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s',
                $receipt,
                $this->config->get('HTTP_HOST'),
                $this->config->get('URL'),
                $today,
                $receipt);
            $out .= "\n";
        }

        if ($out) {
            $this->cronMsg($out, FannieLogger::ALERT);
            file_put_contents('php://stderr', $out, FILE_APPEND);
        }
    }
}

