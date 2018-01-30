<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMCouponDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'coupon');
    protected $report_headers = array('Date', 'Description', 'Amount', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $coupon = $this->form->coupon;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date);

        $query2 = "SELECT tdate,
            d.description,
            -d.total as total, trans_num
        FROM {$dlog} AS d
        WHERE tdate BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd'); 
        $args = array($date . ' 00:00:00', $date . ' 23:59:59', $store);
        if ($coupon) {
            $upc = '00499999' . str_pad($coupon, 5, '0', STR_PAD_LEFT);
            $query2 .= ' AND d.upc=?';
            $args[] = $upc;
        } else {
            $query2 .= " AND d.upc='0' AND d.trans_type='T' AND d.trans_subtype='IC'";
        }
        $query2 .= "ORDER BY tdate";
        $prep = $this->connection->prepare($query2);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['description'],
                sprintf('%.2f', $row['total']),
                $row['trans_num'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

