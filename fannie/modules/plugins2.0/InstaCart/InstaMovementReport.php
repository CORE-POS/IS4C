<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaMovementReport extends FannieReportPage
{
    protected $title = 'InstaCart Movement Report';
    protected $header = 'InstaCart Movement Report';
    public $description = '[InstaCart Movement Report] displays movement data for a given date range';

    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('UPC', 'Brand', 'Item', 'Quantity', '$ Total');

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $super = $this->form->super;
        } catch (Exception $ex) {
            return array();
        }

        $prep = $this->connection->prepare("
            SELECT
                i.upc,
                p.brand,
                p.description,
                SUM(i.quantity) AS qty,
                SUM(i.retailTotal) AS ttl
            FROM " . FannieDB::fqn('InstaTransactions', 'plugin:InstaCartDB') . " AS i
                INNER JOIN products AS p ON i.upc=p.upc AND i.storeID=p.store_id
                INNER JOIN superdepts AS s ON p.department=s.dept_ID
            WHERE s.superID=?
                AND i.orderDate BETWEEN ? AND ?
            GROUP BY i.upc, p.brand, p.description
            ");
        $res = $this->connection->execute($prep, array(
            $super,
            $date1,
            $date2 . ' 23:59:59',
        ));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $model = new SuperDeptNamesModel($this->connection);
        $opts = $model->toOptions();
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
<div class="row">
<div class="col-sm-5">
    <div class="form-group">
        <label>Super Department</label>
        <select name="super" class="form-control">
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</div>
    <p>
    {$dates}
    </p>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

