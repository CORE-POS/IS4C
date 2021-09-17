<?php

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpOrderReport extends FannieReportPage
{
    protected $header = 'Orders Report';
    protected $title = 'Orders Report';
    protected $new_tablesorter = true;
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Date', 'Alberts', 'CPW', 'RDW', 'Total');

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        $store = FormLib::get('store');

        $prep = $this->connection->prepare("SELECT YEAR(placedDate) AS y, MONTH(placedDate) AS m, DAY(placedDate) AS d,
            vendorID, SUM(i.unitCost * i.caseSize * i.quantity) AS ttl
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.placedDate BETWEEN ? AND ?
                AND o.storeID=?
                AND o.userID=-99
                AND o.vendorID IN (292, 293, 136)
            GROUP BY YEAR(placedDate), MONTH(placedDate), DAY(placedDate), vendorID");
        $res = $this->connection->execute($prep, array($date1, $date2 . ' 23:59:59', $store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $key = date('Y-m-d', mktime(0, 0, 0, $row['m'], $row['d'], $row['y']));
            if (!isset($data[$key])) {
                $data[$key] = array();
            }
            $data[$key][$row['vendorID']] = $row['ttl'];
        }

        $ret = array();
        foreach ($data as $key => $val) {
            $record = array($key);
            $total = 0;
            $record[] = isset($val[292]) ? sprintf('%.2f', $val[292]) : 0;
            $total += isset($val[292]) ? $val[292] : 0;
            $record[] = isset($val[293]) ? sprintf('%.2f', $val[293]) : 0;
            $total += isset($val[293]) ? $val[293] : 0;
            $record[] = isset($val[136]) ? sprintf('%.2f', $val[136]) : 0;
            $total += isset($val[136]) ? $val[136] : 0;
            $record[] = $total;
            $ret[] = $record;
        }

        return $ret;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();

        return <<<HTML
<form method="get" action="RpOrderReport.php">
    {$dates}
    <div class="row"></div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button class="btn btn-default">Get Order Totals</button>
    </div>
HTML;
    }
}

FannieDispatch::conditionalExec();

