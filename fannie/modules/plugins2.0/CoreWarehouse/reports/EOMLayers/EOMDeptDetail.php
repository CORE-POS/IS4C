<?php

use COREPOS\Fannie\API\item\StandardAccounting;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMDeptDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'dept');
    protected $report_headers = array('Date', 'Dept#', 'Super#', 'Sales Account', 'Department', 'Description', 'Amount', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $pcode = $this->form->dept;
        } catch (Exception $ex) {
            return array();
        }

        $date = date('Y-m-d', strtotime($date));
        $dlog = DTransactionsModel::selectDlog($date);

        $query = "SELECT tdate,
                description,
                total AS total,
                d.store_id,
                d.department,
                t.dept_name,
                m.superID,
                t.dept_no,
                t.salesCode,
                trans_num
            FROM {$dlog} AS d
                INNER JOIN departments AS t ON d.department=t.dept_no
                LEFT JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
                AND d.trans_type IN ('I', 'D')
                AND d.department=?
            ORDER BY tdate";

        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date . ' 00:00:00', $date . ' 23:59:59', $store, $pcode));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = StandardAccounting::extend($row['salesCode'], $row['store_id']);
            $data[] = array(
                $row['tdate'],
                $row['department'],
                $row['superID'],
                $code . ' ',
                $row['dept_name'],
                $row['description'],
                sprintf('%.2f', $row['total']),
                $row['trans_num'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

