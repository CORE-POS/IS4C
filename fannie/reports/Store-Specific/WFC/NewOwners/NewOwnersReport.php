<?php
include('../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class NewOwnersReport extends FannieReportPage
{
    protected $report_headers = array('Date', '# of New Owners', 'Paid in Full');
    protected $required_fields = array('date1', 'date2');
    protected $title = 'New Owners Report';
    protected $header = 'New Owners Report';

    public function preprocess()
    {
        $this->addScript($this->config->get('URL') . 'src/javascript/Chart.min.js');
        $this->addScript($this->config->get('URL') . 'src/javascript/CoreChart.js');

        return parent::preprocess();
    }

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div id="chartDiv"><canvas id="chartCanvas"></canvas></div>';
            $this->addOnloadCommand('showGraph()');
        }

        return $default;
    }

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return '';
        }

        return <<<JAVASCRIPT
function showGraph() {
    var xData = $('td.reportColumn0').toArray().map(x => x.innerHTML.trim());
    var yData = $('td.reportColumn1').toArray().map(x => Number(x.innerHTML.trim()));
    var y2Data = $('td.reportColumn2').toArray().map(x => Number(x.innerHTML.trim()));
    CoreChart.lineChart('chartCanvas', xData, [yData, y2Data], ['New Owners', 'Paid in Full']);
}
JAVASCRIPT;
    }

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        
        $today = strtotime($date2) >= strtotime(date('Y-m-d'));

        $query = "
            SELECT YEAR(start_date),
                MONTH(start_date),
                DAY(start_date),
                COUNT(*),
                SUM(CASE WHEN e.payments >= 100 THEN 1 ELSE 0 END) as paidInFull
            FROM " . FannieDB::fqn('memDates', 'op') . " AS m
                INNER JOIN " . FannieDB::fqn('custdata', 'op') . " AS c ON c.CardNo=m.card_no AND c.personNum=1
                LEFT JOIN " . FannieDB::fqn('suspensions', 'op') . " AS s ON m.card_no=s.cardno
                LEFT JOIN " . FannieDB::fqn('equity_live_balance', 'trans') . " AS e ON m.card_no=e.memnum
            WHERE m.start_date BETWEEN ? AND ?
                AND (c.Type='PC' OR s.memtype1='PC')
            GROUP BY YEAR(start_date),
                MONTH(start_date),
                DAY(start_date)";
        $prep = $this->connection->prepare($query);
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        $data = array();
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $ts = mktime(0,0,0,$row[1],$row[2],$row[0]);
            $data[] = array(
                date('Y-m-d', $ts),
                sprintf('%d', $row[3]),
                sprintf('%d', $row['paidInFull']),
            );
        }

        if ($today) {
            $prep = $this->connection->prepare('SELECT SUM(total)/20 FROM ' . FannieDB::fqn('dlog', 'trans') . " WHERE department=992");
            $data[] = array(
                date('Y-m-d'),
                sprintf('%d', $this->connection->getValue($prep)),
            );
        }

        return $data;
    }

    function calculate_footers($data)
    {
        $sum = array_reduce($data, function($c, $i) { return $c + $i[1]; }, 0);
        $sum2 = array_reduce($data, function($c, $i) { return $c + $i[2]; }, 0);
        return array('Total', $sum, $sum2);
    }

    public function report_description_content()
    {
        return array(
            sprintf('<br /><a href="EquityOwnersReport.php?date1=%s&date2=%s">All Equity This Period</a>',
                FormLib::get('date1'), FormLib::get('date2')),
        );
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

