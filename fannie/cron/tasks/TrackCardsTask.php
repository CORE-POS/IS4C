<?php

class TrackCardsTask extends FannieTask
{
    public $name = 'Track Cards';

    public $description = 'Compiles usage data on non-owner payment cards';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $startTS = strtotime('yesterday');
        $this->checkDay(date('Y-m-d', $startTS));
        //$this->allocateAccounts();
    }

    private function checkDay($date)
    {
        $dlog = DTransactionsModel::selectDlog($date);
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $ptP = $dbc->prepare("SELECT * FROM " . FannieDB::fqn('PaycardTransactions', 'trans') . "
            WHERE dateID=? AND empNo=? AND registerNo=? AND transNo=? AND amount=? AND xResultMessage LIKE '%AP%'");

        $transP = $dbc->prepare("SELECT * FROM {$dlog} WHERE tdate BETWEEN ? AND ?
                AND trans_type='T' AND charflag='PT' AND card_no IN (9, 11)");
        $transR = $dbc->execute($transP, array($date, $date . ' 23:59:59'));
        while ($transW = $dbc->fetchRow($transR)) {
            $ptArgs = array(
                date('Ymd', strtotime($transW['tdate'])),
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
                $model = new TrackedCardsModel($dbc);
                $model->hash($hash);
                if ($model->load()) {
                    $model->lastSeen($transW['tdate']);
                    $model->times($model->times() + 1);
                    $model->save();
                } else {
                    $model->PAN($pan);
                    $model->name($name);
                    $model->firstSeen($transW['tdate']);
                    $model->times(1);
                    $model->save();
                }
            }
        }
    }

    private function allocateAccounts()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $res = $dbc->query("SELECT * FROM TrackedCards WHERE "
            . $dbc->monthdiff($dbc->now(), 'firstSeen') . " <= times
            AND times > 5
            AND cardNo IS NULL
            ORDER BY times ASC");
        var_dump($dbc->numRows($res));
        $tagP = $dbc->prepare("UPDATE TrackedCards SET cardNo=? WHERE hash=?");
        $whMonths = array();
        while ($row = $dbc->fetchRow($res)) {
            var_dump($row); exit;
            $whMonths = $this->trackUser($dbc, $row['name'], $row['PAN'], $row['hash'], $row['firstSeen'], $whMonths);
            $dbc->execute($tagP, array($max, $row['hash']));
            break;
        }
        foreach ($whMonths as $dm => $set) {
            $year = substr($dm, 0, 4);
            $month = ltrim(substr($dm, -2), '0');
            echo "Reloading warehouse $year-$month (1/2)\n";
            $model = new SumMemSalesByDayModel($dbc);
            $model->refresh_data($this->config->get('TRANS_DB'), $year, $month);
            echo "Reloading warehouse $year-$month (2/2)\n";
            $model = new SumMemTypeSalesByDayModel($dbc);
            $model->refresh_data($this->config->get('TRANS_DB'), $year, $month);
        }
    }

    private function trackUser($dbc, $name, $pan, $hash, $startDate, $whMonths)
    {
        $maxP = $dbc->prepare("SELECT MAX(CardNo) FROM custdata");
        $max = $dbc->getValue($maxP) + 1;
        $member = array(
            'cardNo' => $max,
            'memberStatus' => 'REG',
            'customerTypeID' => 7,
            'startDate' => $startDate,
            'customers' => array(
                array(
                    'accountHolder' => true,
                    'firstName' => '',
                    'lastName' => $name,
                ),
            ),
        );
        $startTS = strtotime($startDate);
        $endTS = strtotime('yesterday');
        $ptP = $dbc->prepare("SELECT * FROM " . FannieDB::fqn('PaycardTransactions', 'trans') . "
                WHERE dateID BETWEEN ? AND ?
                    AND name LIKE ?
                    AND PAN = ?");
        $setP = $dbc->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
                    SET card_no=?
                    WHERE card_no IN (9, 11)
                        AND tdate BETWEEN ? AND ?
                        AND emp_no=?
                        AND register_no=?
                        AND trans_no=?");
        while ($startTS <= $endTS) {
            echo "User {$max} " . date('Y-m', $startTS) . "\n";
            $ptArgs = array(date('Ymd', $startTS), date('Ymt', $startTS), '%' . $name . '%', $pan);
            $ptRows = $dbc->getAllRows($ptP, $ptArgs);
            if (count($ptRows) > 0) {
                $whMonths[date('Ym', $startTS)] = true;
                foreach ($ptRows as $ptRow) {
                    $date = date('Y-m-d', strtotime($ptRow['requestDatetime']));
                    $args = array(
                        $date,
                        $date . ' 23:59:59',
                        $ptRow['empNo'],
                        $ptRow['registerNo'],
                        $ptRow['transNo'],
                    );
                    $dbc->execute($setP, $args);
                }        
            }
            $startTS = mktime(0, 0, 0, date('n',$startTS) + 1, date('j', $startTS), date('Y', $startTS));
        }

        return $whMonths;
    }
}

