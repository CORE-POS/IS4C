<?php
include('../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class EquityOwnersReport extends FannieReportPage
{
    protected $report_headers = array('Date', 'Total Equity Sold', 'Not New Owners');
    protected $required_fields = array('date1', 'date2');
    protected $title = 'Equity Owners Report';
    protected $header = 'Equity Owners Report';

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
    CoreChart.lineChart('chartCanvas', xData, [yData, y2Data], ['All Equity', 'Not New']);
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

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        
        $tdate = $this->connection->dateymd('d.tdate');
        $mdate = $this->connection->dateymd('m.start_date');
        $query = "
            SELECT YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                SUM(total) as allEq,
                SUM(CASE WHEN {$tdate} > {$mdate} THEN total ELSE 0 END) AS notNew,
                COUNT(*) as paidInFull
            FROM {$dlog} AS d
                INNER JOIN memDates AS m ON d.card_no=m.card_no
            WHERE d.tdate BETWEEN ? AND ?
                AND department IN (991, 992)
                AND register_no <> 30
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)";
        $prep = $this->connection->prepare($query);
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        $data = array();
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $ts = mktime(0,0,0,$row[1],$row[2],$row[0]);
            $data[] = array(
                date('Y-m-d', $ts),
                sprintf('%d', $row[3]),
                sprintf('%d', $row['notNew']),
            );
        }

        /*
        if ($today) {
            $prep = $this->connection->prepare('SELECT SUM(total)/20 FROM ' . FannieDB::fqn('dlog', 'trans') . " WHERE department=992");
            $data[] = array(
                date('Y-m-d'),
                sprintf('%d', $this->connection->getValue($prep)),
            );
        }
         */

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
            sprintf('<br /><a href="NewOwnersReport.php?date1=%s&date2=%s">New Owners This Period</a>',
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

