<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMTenderDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'tender');
    protected $report_headers = array('Date', 'Tender', 'Amount', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $tender = $this->form->tender;
        } catch (Exception $ex) {
            return array();
        }

        $date = date('Y-m-d', strtotime($date));
        $dlog = DTransactionsModel::selectDlog($date);

        $query = "SELECT tdate,
                description,
                -1*total AS total,
                trans_num
            FROM {$dlog} AS d
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
                AND d.trans_type='T'
                AND d.trans_subtype=?
            ORDER BY tdate";

        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date . ' 00:00:00', $date . ' 23:59:59', $store, $tender));
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

