<?php

use COREPOS\Fannie\API\item\StandardAccounting;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMPCodeDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'pcode');
    protected $report_headers = array('Date', 'Sales Account', 'Description', 'Amount', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $pcode = $this->form->pcode;
        } catch (Exception $ex) {
            return array();
        }

        $date = date('Y-m-d', strtotime($date));
        $dlog = DTransactionsModel::selectDlog($date);

        $query = "SELECT tdate,
                description,
                total AS total,
                d.store_id,
                t.salesCode,
                trans_num
            FROM {$dlog} AS d
                INNER JOIN departments AS t ON d.department=t.dept_no
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
                AND d.trans_type IN ('I', 'D')
                AND t.salesCode=?
            ORDER BY tdate";

        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date . ' 00:00:00', $date . ' 23:59:59', $store, $pcode));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = StandardAccounting::extend($row['salesCode'], $row['store_id']);
            $data[] = array(
                $row['tdate'],
                $code . ' ',
                $row['description'],
                sprintf('%.2f', $row['total']),
                $row['trans_num'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

