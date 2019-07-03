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
            AND times >= 10 
            AND cardNo IS NULL
            ORDER BY times DESC");
        echo $dbc->numRows($res) . " available cards\n";
        $whMonths = array();
        $limit = 1;
        $count = 0;
        while ($row = $dbc->fetchRow($res)) {
            echo "{$row['name']} seen {$row['times']} times\n";
            $whMonths = $this->trackUser($dbc, $row['name'], $row['PAN'], $row['hash'], $row['firstSeen'], $whMonths);
            $count++;
            if ($count >= $limit) {
                break;
            }
        }
        foreach ($whMonths as $ymd => $set) {
            $stamp = strtotime($ymd);
            $month = date('n', $stamp);
            $year = date('Y', $stamp);
            $day = date('j', $stamp);
            echo "Reloading warehouse $ymd (1/3)\n";
            $model = new SumMemSalesByDayModel($dbc);
            $model->refresh_data($this->config->get('TRANS_DB'), $month, $year, $day);
            echo "Reloading warehouse $ymd (2/3)\n";
            $model = new SumMemTypeSalesByDayModel($dbc);
            $model->refresh_data($this->config->get('TRANS_DB'), $month, $year, $day);
            echo "Reloading warehouse $ymd (3/3)\n";
            $model = new TransactionSummaryModel($dbc);
            $model->refresh_data($this->config->get('TRANS_DB'), $month, $year, $day);
        }
    }

    private function getCardNo($dbc)
    {
        $limit = $this->config->get('CARDNO_MAX', 1000000000);
        $maxP = $dbc->prepare("SELECT MAX(CardNo) FROM custdata WHERE CardNo > ?");
        $max = $dbc->getValue($maxP, $limit);

        return $max ? $max + 1 : $limit + 1;
    }

    private function trackUser($dbc, $name, $pan, $hash, $startDate, $whMonths)
    {
        $max = $this->getCardNo($dbc);
        echo "$hash will be " . number_format($max) . "\n";
        $fn = '';
        $ln = $name;
        if (strstr($name, '/')) {
            $temp = explode('/', $name, 2);
            $temp = array_map('trim', $temp);
            if ($temp[0] != '' && $temp[1] != '') {
                $ln = $temp[0];
                $fn = $temp[1];
            }
        }
        $member = array(
            'cardNo' => $max,
            'memberStatus' => 'REG',
            'customerTypeID' => 7,
            'startDate' => $startDate,
            'customers' => array(
                array(
                    'accountHolder' => true,
                    'firstName' => $fn,
                    'lastName' => $ln,
                ),
            ),
        );
        COREPOS\Fannie\API\member\MemberREST::post($max, $member);
        $trackP = $dbc->prepare("UPDATE TrackedCards SET cardNo=? WHERE hash=?");
        $dbc->execute($trackP, array($max, $hash));
        $startTS = strtotime($startDate);
        $endTS = strtotime('yesterday');
        $ptP = $dbc->prepare("SELECT * FROM " . FannieDB::fqn('PaycardTransactions', 'trans') . "
                WHERE dateID BETWEEN ? AND ?
                    AND name LIKE ?
                    AND PAN = ?");
        $setP = $dbc->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
                    SET card_no=?
                    WHERE card_no IN (9, 11)
                        AND datetime BETWEEN ? AND ?
                        AND emp_no=?
                        AND register_no=?
                        AND trans_no=?");
        while ($startTS <= $endTS) {
            echo "User {$max} " . date('Y-m', $startTS) . "\n";
            $ptArgs = array(date('Ymd', $startTS), date('Ymt', $startTS), '%' . $name . '%', $pan);
            $ptRows = $dbc->getAllRows($ptP, $ptArgs);
            if (count($ptRows) > 0) {
                foreach ($ptRows as $ptRow) {
                    $date = date('Y-m-d', strtotime($ptRow['requestDatetime']));
                    $whMonths[$date] = true;
                    $args = array(
                        $max,
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

