<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMCouponLayer extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('month', 'year', 'store', 'coupon');
    protected $report_headers = array('Date', 'Tender', 'Amount', 'Qty');

    public function fetch_report_data()
    {
        try {
            $month = $this->form->month;
            $year = $this->form->year;
            $store = $this->form->store;
            $coupon = $this->form->coupon;
        } catch (Exception $ex) {
            return array();
        }

        $tstamp = mktime(0,0,0,$month,1,$year);
        $start = date('Y-m-01', $tstamp);
        $end = date('Y-m-t', $tstamp);
        $dlog = DTransactionsModel::selectDlog($start, $end);

        $query2 = "SELECT 
            YEAR(tdate) AS year,
            MONTH(tdate) AS month,
            DAY(tdate) AS day,
            d.description,
            -sum(d.total) as total, COUNT(d.total) AS qty
        FROM {$dlog} AS d
        WHERE tdate BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd'); 
        $args = array($start . ' 00:00:00', $end . ' 23:59:59', $store);
        if ($coupon) {
            $upc = '00499999' . str_pad($coupon, 5, '0', STR_PAD_LEFT);
            $query2 .= ' AND d.upc=?';
            $args[] = $upc;
        } else {
            $query2 .= " AND d.upc='0' AND d.trans_type='T' AND d.trans_subtype='IC'";
        }
        $query2 .= "GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                d.description
            ORDER BY MIN(tdate)";
        $prep = $this->connection->prepare($query2);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', mktime(0,0,0, $row['month'], $row['day'], $row['year']));
            $link = sprintf('<a href="EOMCouponDetail.php?date=%s&store=%d&coupon=%d">%s</a>',
                $date, $store, $coupon, $date);
            $data[] = array(
                $link,
                $row['description'],
                sprintf('%.2f', $row['total']),
                sprintf('%d', $row['qty']),
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

