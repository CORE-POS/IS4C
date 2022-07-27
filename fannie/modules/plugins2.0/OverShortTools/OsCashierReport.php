<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsCashierReport extends FannieReportPage 
{
    protected $header = 'Employee Variances Report';
    protected $title = 'Employee Variances Report';
    protected $required_fields = array('date1', 'date2', 'store', 'emp');
    protected $new_tablesorter = true;
    protected $report_headers = array('Date', 'Variance');

    public function fetch_report_data()
    {
        $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);
        $cashP = $this->connection->prepare("SELECT YEAR(tdate), MONTH(tdate), DAY(tdate), -1 * SUM(total) AS ttl
                FROM {$dlog} AS d
                WHERE tdate BETWEEN ? AND ? AND trans_type='T' AND store_id=?
                    AND (trans_subtype='CA' OR (trans_subtype='CK' AND description='Check'))
                    AND emp_no=?
                GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
                ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate)");
        $cashR = $this->connection->execute($cashP, array(
            $this->form->date1,
            $this->form->date2 . ' 23:59:59',
            $this->form->store,
            $this->form->emp,
        ));

        $countP = $this->connection->prepare("SELECT amt
            FROM " . FannieDB::fqn('dailyDeposit', 'trans') . "
            WHERE storeID=?
                AND rowName LIKE ?");
        $data = array();
        while ($row = $this->connection->fetchRow($cashR)) {
            $dateID = date('Ymd', mktime(0, 0, 0, $row[1], $row[2], $row[0]));
            $tdate = date('Y-m-d', mktime(0, 0, 0, $row[1], $row[2], $row[0]));
            $args = array($this->form->store, 'drop' . $dateID . '-' . $this->form->emp);
            $counted = $this->connection->getValue($countP, $args);
            $data[] = array(
                $tdate,
                sprintf('%.2f', $counted - $row['ttl']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $ttl = 0;
        foreach ($data as $row) {
            $ttl += $row[1];
        }
        $divisor = count($data) > 0 ? count($data) : 1;

        return array(
            array('Total', sprintf('%.2f', $ttl)),
            array('Average', sprintf('%.2f', $ttl / $divisor)),
        );
    }

    public function form_content()
    {
        $store = FormLib::storePicker();
        $dates = FormLib::standardDateFields();

        return <<<HTML
<form method="get" action="OsCashierReport.php">
<div class="row">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Cashier #</label>
            <input type="text" class="form-control" name="emp" />
        </div>
        <div class="form-group">
            <label>Store</label>
            {$store['html']}
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Get Report</button>
        </div>
    </div>
    {$dates}
</div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();
