<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMDiscountDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'type');
    protected $report_headers = array('Date', 'Customer Type', 'Customer#', 'Amount', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $type = $this->form->type;
        } catch (Exception $ex) {
            return array();
        }

        $date = date('Y-m-d', strtotime($date));
        $dlog = DTransactionsModel::selectDlog($date);

        $query = "SELECT tdate,
                m.memDesc,
                d.card_no,
                total AS total,
                trans_num
            FROM {$dlog} AS d
                INNER JOIN memtype m ON d.memType = m.memtype
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
                AND d.upc = 'DISCOUNT'
                AND d.total <> 0
                AND d.memType=?
            ORDER BY tdate";

        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date . ' 00:00:00', $date . ' 23:59:59', $store, $type));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['memDesc'],
                $row['card_no'],
                sprintf('%.2f', $row['total']),
                $row['trans_num'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

