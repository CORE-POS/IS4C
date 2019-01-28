<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class RoundUpAccountReport extends FannieReportPage
{
    protected $header = 'Round Up Report';
    protected $title = 'Round Up Report';
    public $description = '[Round Up Account Report] shows round up donations for a given year and customer account';

    protected $required_fields = array('year', 'cardNo');
    protected $report_headers = array('Month', 'Total');

    private function guessDepartment()
    {
        $prep = $this->connection->prepare("SELECT dept_no FROM departments WHERE dept_name LIKE '%DONAT%'");
        return $this->connection->getValue($prep);
    }

    public function fetch_report_data()
    {
        try {
            $year = $this->form->year;
            $cardNo = $this->form->cardNo;
        } catch (Exception $ex) {
            return array();
        }
        $start = date('Y-m-d', mktime(0,0,0,1,1,$year));
        $end = date('Y-m-d', mktime(0,0,0,12,31,$year));
        $dlog = DTransactionsModel::selectDlog($start, $end);

        $query = "SELECT MONTH(tdate) AS month, SUM(total) AS ttl
            FROM {$dlog}
            WHERE tdate BETWEEN ? AND ?
                AND card_no=?
                AND department=?
            GROUP BY MONTH(tdate)";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep,
                array($start . ' 00:00:00', $end . ' 23:59:59', $cardNo, $this->guessDepartment()));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $time = mktime(0,0,0,$row['month'],1,$year);
            $data[] = array(
                date('m F', $time),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $ttl = array_reduce($data, function ($c, $i) { return $c + $i[1]; });
        return array('Total', number_format($ttl, 2));
    }

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Year</label>
        <input type="number" class="form-control" name="year" />
    </div>
    <div class="form-group">
        <label>Customer #</label>
        <input type="number" class="form-control" name="cardNo" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

