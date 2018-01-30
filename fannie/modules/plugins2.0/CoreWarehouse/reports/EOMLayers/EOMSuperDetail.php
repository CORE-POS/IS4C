<?php

use COREPOS\Fannie\API\item\StandardAccounting;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMSuperDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'super');
    protected $report_headers = array('Date', 'Super#', 'Super', 'Description', 'Amount', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $super = $this->form->super;
        } catch (Exception $ex) {
            return array();
        }

        $date = date('Y-m-d', strtotime($date));
        $dlog = DTransactionsModel::selectDlog($date);

        $query = "SELECT tdate,
                description,
                total AS total,
                m.superID,
                m.super_name,
                trans_num
            FROM {$dlog} AS d
                LEFT JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
                AND d.trans_type IN ('I', 'D')
                AND m.superID=?
            ORDER BY tdate";

        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date . ' 00:00:00', $date . ' 23:59:59', $store, $super));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = StandardAccounting::extend($row['salesCode'], $row['store_id']);
            $data[] = array(
                $row['tdate'],
                $row['superID'],
                $row['super_name'],
                $row['description'],
                sprintf('%.2f', $row['total']),
                $row['trans_num'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

