<?php

class TrackCardsLiveTask extends FannieTask
{
    public $name = 'Track Cards (live)';

    public $description = 'Monitors current activity for known payment cards and re-numbers transactions with the appropriate customer';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => 10,
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    /**
     * Look up non-owner card transactions for the current day
     * Check whether these cards have been assigned a tracking customer number
     * If so, update the transaction data w/ the tracking number
     */
    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ptP = $dbc->prepare("SELECT * FROM " . FannieDB::fqn('PaycardTransactions', 'trans') . "
            WHERE dateID=? AND empNo=? AND registerNo=? AND transNo=? AND amount=? AND xResultMessage LIKE '%AP%'");
        $tcP = $dbc->prepare("SELECT cardNo FROM " . FannieDB::fqn('TrackedCards', 'op') . " WHERE hash=?");
        $upP = $dbc->prepare("UPDATE " . FannieDB::fqn('dtransactions', 'trans') . "
            SET card_no=?
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND store_id=?");
        $lastSeenP = $dbc->prepare("UPDATE " .FannieDB::fqn('TrackedCards', 'op') . " SET lastSeen=?, times=times+1 WHERE hash=?");
        $transR = $dbc->query("SELECT * FROM " . FannieDB::fqn('dlog', 'trans') . " WHERE trans_type='T' AND charflag='PT' and card_no IN (9, 11)");
        while ($transW = $dbc->fetchRow($transR)) {
            $stamp = strtotime($transW['tdate']);
            $ptArgs = array(
                date('Ymd', $stamp),
                $transW['emp_no'],
                $transW['register_no'],
                $transW['trans_no'],
                abs($transW['total']),
            );
            $ptRow = $dbc->getRow($ptP, $ptArgs);
            if ($ptRow) {
                $name = trim($ptRow['name']);
                $pan = str_replace('X', '*', $ptRow['PAN']);
                if ($name == 'Cardholder' || $name == '/' || $name == 'Customer' || $name == '') {
                    continue;
                }
                $hash = md5($pan . $name);
                $tracked = $dbc->getValue($tcP, array($hash));
                if ($tracked) {
                    $upArgs = array(
                        $tracked,
                        date('Y-m-d 00:00:00', $stamp),
                        date('Y-m-d 23:59:59', $stamp),
                        $transW['emp_no'],
                        $transW['register_no'],
                        $transW['trans_no'],
                        $transW['store_id'],
                    );
                    $dbc->execute($upP, $upArgs);
                    $dbc->execute($lastSeenP, array($transW['tdate'], $hash));
                }
            }
        }
    }
}
