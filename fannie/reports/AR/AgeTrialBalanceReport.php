<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class AgeTrialBalanceReport extends FannieReportPage 
{
    protected $header = "Aged Trial Balances Report";
    protected $title = "Aged Trial Balances Report";
    protected $required_fields = array('period');

    protected $report_headers = array('#', 'Name', 'Prior Balance', 
        'Charge', 'Payment', 'Balance',
        'Charge', 'Payment', 'Balance',
        'Charge', 'Payment', 'Balance',
    );

    public function fetch_report_data()
    {
        $mems = array();

        $period = new PeriodsModel($this->connection);
        $period->periodID($this->form->period);
        $matches = $period->find();
        $period = $matches[0];

        $prep = $this->connection->prepare("SELECT card_no, FirstName, LastName FROM " . FannieDB::fqn('ar_history', 'trans') . " AS a
                LEFT JOIN custdata AS c ON a.card_no=c.CardNo AND c.personNum=1
            WHERE tdate BETWEEN ? AND ?
                AND card_no <> 0");
        $res = $this->connection->execute($prep, array(
            $period->startDate(),
            str_replace('00:00:00', '23:59:59', $period->endDate()),
        ));
        while ($row = $this->connection->fetchRow($res)) {
            $mems[$row['card_no']] = $row['FirstName'] . ' ' . $row['LastName'];
        }

        $prep = $this->connection->prepare("SELECT card_no, FirstName, LastName, SUM(charges) - SUM(payments) AS balance
            FROM " . FannieDB::fqn('ar_history', 'trans') . " AS a
                LEFT JOIN custdata AS c ON a.card_no=c.CardNo AND c.personNum=1
            WHERE tdate < ?
                AND card_no <> 0
            GROUP BY card_no, FirstName, LastName
            HAVING ABS(SUM(charges) - SUM(payments)) > 0.005");
        $res = $this->connection->execute($prep, array(str_replace('00:00:00', '23:59:59', $period->endDate())));
        while ($row = $this->connection->fetchRow($res)) {
            $mems[$row['card_no']] = $row['FirstName'] . ' ' . $row['LastName'];
        }

        $data = array();
        foreach ($mems as $num => $name) {
            $data[$num] = array(
                $num,
                $name,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
            );
        }

        $subset = $this->getSubSection($period->startDate(), str_replace('00:00:00', '23:59:59', $period->endDate()), array_keys($mems));
        foreach ($mems as $num => $bool) {
            if (isset($subset[$num])) {
                $data[$num][8] = $subset[$num]['starting'];
                $data[$num][9] = $subset[$num]['charges'];
                $data[$num][10] = $subset[$num]['payments'];
                $data[$num][11] = $subset[$num]['ending'];
            }
        }
        $this->report_headers[11] = 'Balance on ' . date('m/j', strtotime($period->endDate()));

        $period->reset();
        $period->periodID($this->form->period - 1);
        $matches = $period->find();
        $period = $matches[0];
        $subset = $this->getSubSection($period->startDate(), str_replace('00:00:00', '23:59:59', $period->endDate()), array_keys($mems));
        foreach ($mems as $num => $bool) {
            if (isset($subset[$num])) {
                $data[$num][5] = $subset[$num]['starting'];
                $data[$num][6] = $subset[$num]['charges'];
                $data[$num][7] = $subset[$num]['payments'];
            }
        }
        $this->report_headers[8] = 'Balance on ' . date('m/j', strtotime($period->endDate()));

        $period->reset();
        $period->periodID($this->form->period - 2);
        $matches = $period->find();
        $period = $matches[0];
        $subset = $this->getSubSection($period->startDate(), str_replace('00:00:00', '23:59:59', $period->endDate()), array_keys($mems));
        foreach ($mems as $num => $bool) {
            if (isset($subset[$num])) {
                $data[$num][2] = $subset[$num]['starting'];
                $data[$num][3] = $subset[$num]['charges'];
                $data[$num][4] = $subset[$num]['payments'];
            }
        }
        $this->report_headers[5] = 'Balance on ' . date('m/j', strtotime($period->endDate()));

        return $this->dekey_array($data);
    }

    private function getSubSection($start, $end, $mems)
    {
        $ret = array();
        foreach ($mems as $mem) {
            $ret[$mem] = array(
                'starting' => 0,
                'charges' => 0,
                'payments' => 0,
                'ending' => 0,
            );
        }

        list($inStr,$args) = $this->connection->safeInClause($mems);

        $balP = $this->connection->prepare("SELECT card_no, SUM(charges) - SUM(payments) AS balance 
            FROM " . FannieDB::fqn('ar_history', 'trans') . "
            WHERE card_no IN ({$inStr})
                AND tdate < ?
            GROUP BY card_no");
        $startArgs = $args;
        $startArgs[] = $start;
        $balR = $this->connection->execute($balP, $startArgs);
        while ($balW = $this->connection->fetchRow($balR)) {
            $ret[$balW['card_no']]['starting'] = $balW['balance'];
        }
        $endArgs = $args;
        $endArgs[] = $end;
        $balR = $this->connection->execute($balP, $endArgs);
        while ($balW = $this->connection->fetchRow($balR)) {
            $ret[$balW['card_no']]['ending'] = $balW['balance'];
        }

        $activityP = $this->connection->prepare("SELECT card_no, SUM(charges) AS charges, SUM(payments) AS payments
            FROM " . FannieDB::fqn('ar_history', 'trans') . "
            WHERE card_no IN ({$inStr})
                AND tdate BETWEEN ? AND ?
            GROUP BY card_no");
        $actArgs = $args;
        $actArgs[] = $start;
        $actArgs[] = $end;
        $activityR = $this->connection->execute($activityP, $actArgs);
        while ($actW = $this->connection->fetchRow($activityR)) {
            $ret[$actW['card_no']]['charges'] = $actW['charges'];
            $ret[$actW['card_no']]['payments'] = $actW['payments'];
        }

        return $ret;
    }

    public function calculate_footers($data)
    {
        $ret = array('Total', null, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        foreach ($data as $row) {
            for ($i=2; $i<=11; $i++) {
                $ret[$i] += $row[$i];
            }
        }

        return $ret;
    }

    public function form_content()
    {
        $res = $this->connection->query("SELECT * FROM Periods ORDER BY periodID");
        $opts = '';
        $selected = 1;
        $now = mktime();
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option value="%d">%s (%s to %s)</option>',
                $row['periodID'],
                $row['year'] . '-' . str_pad($row['num'], 2, '0', STR_PAD_LEFT),
                $row['startDate'],
                $row['endDate']
            );
            $start = strtotime($row['startDate']);
            $end = strtotime($row['endDate']);
            if ($now >= $start && $now <= $end) {
                $selected = $row['periodID'] - 1;
            }
        }
        $this->addOnloadCommand('$(\'#period\').val(' . $selected . ');');

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Period</label>
        <select name="period" id="period" class="form-control">
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();
