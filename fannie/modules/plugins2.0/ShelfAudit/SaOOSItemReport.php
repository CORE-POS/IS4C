<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaOOSItemReport extends FannieReportPage 
{
    public $discoverable = false;

    protected $title = "Fannie : Out of Stocks Report";
    protected $header = "Out of Stocks Report";
    protected $report_headers = array('Date', 'Day', 'UPC', 'Item');
    protected $required_fields = array('upc', 'store');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $start = date('Y-m-d', strtotime('6 months ago'));
        $dtrans = DTransactionsModel::selectDTrans($start, $yesterday);
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $store = FormLib::get('store');

        $prep = $this->connection->prepare("
            SELECT YEAR(datetime) AS year, MONTH(datetime) AS month, DAY(datetime) AS day,
                MAX(upc) AS upc, MAX(description) AS item
            FROM {$dtrans}
            WHERE datetime BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store) . "
                AND upc=?
                AND trans_status='X'
                AND mixMatch='OOS'
            GROUP BY YEAR(datetime), MONTH(datetime), DAY(datetime)");
        $args = array($start, $yesterday . ' 23:59:59', $store, $upc);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $stamp = mktime(0, 0, 0, $row['month'], $row['day'], $row['year']);
            $data[] = array(
                date('Y-m-d', $stamp),
                date('l', $stamp),
                $row['upc'],
                $row['item'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<!-- intentionally blank -->';
    }
}

FannieDispatch::conditionalExec();

