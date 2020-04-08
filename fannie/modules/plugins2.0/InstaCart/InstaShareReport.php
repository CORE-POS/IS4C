<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaShareReport extends FannieReportPage
{
    protected $title = 'InstaCart Report';
    protected $header = 'InstaCart Report';
    public $description = '[InstaCart Report] displays data as it will be exported to InstaCart';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Date', 'Sales', 'IC Sales', '%');

    public function fetch_report_data()
    {
        $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);

        $prep = $this->connection->prepare("
            SELECT
                YEAR(tdate), MONTH(tdate), DAY(tdate),
                SUM(total)
            FROM {$dlog} AS d
                INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            WHERE m.superID <> 0
                AND d.tdate BETWEEN ? AND ?
            GROUP BY
                YEAR(tdate), MONTH(tdate), DAY(tdate)
            ORDER BY
                YEAR(tdate), MONTH(tdate), DAY(tdate)");
        $icP = $this->connection->prepare("SELECT SUM(total) FROM "
            . FannieDB::fqn('InstaTransactions', 'plugin:InstaCartDB') . "
            WHERE orderDate BETWEEN ? AND ?");
        $data = array();
        $res = $this->connection->execute($prep, array($this->form->date1, $this->form->date2 . ' 23:59:59'));
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', mktime(0,0,0, $row[1], $row[2], $row[0]));
            $icSales = $this->connection->getValue($icP, array($date, $date . ' 23:59:59'));
            $data[] = array(
                $date,
                sprintf('%.2f', $row[3]),
                sprintf('%.2f', $icSales),
                sprintf('%.2f', ($icSales / $row[3]) * 100),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    <p>
    {$dates}
    <div class="row"></div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

