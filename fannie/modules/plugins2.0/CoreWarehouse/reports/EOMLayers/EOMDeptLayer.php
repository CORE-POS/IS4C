<?php

use COREPOS\Fannie\API\item\StandardAccounting;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMDeptLayer extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('month', 'year', 'store', 'dept');
    protected $report_headers = array('Date', 'Dept#', 'Super#', 'Sales Account', 'Department', 'Amount');

    public function fetch_report_data()
    {
        try {
            $month = $this->form->month;
            $year = $this->form->year;
            $store = $this->form->store;
            $dept = $this->form->dept;
        } catch (Exception $ex) {
            return array();
        }

        $tstamp = mktime(0,0,0,$month,1,$year);
        $start = date('Y-m-01', $tstamp);
        $end = date('Y-m-t', $tstamp);
        $idStart = date('Ym01', $tstamp);
        $idEnd = date('Ymt', $tstamp);
        $warehouse = $this->config->get('PLUGIN_SETTINGS');
        $warehouse = $warehouse['WarehouseDatabase'];
        $warehouse .= $this->connection->sep();

        $query1="SELECT t.date_id,
            t.department,
            d.dept_name,
            m.superID,
            MAX(d.salesCode) AS salesCode,
            SUM(t.total) AS ttl,
            t.store_id
        FROM {$warehouse}sumDeptSalesByDay AS t
            INNER JOIN departments as d ON t.department = d.dept_no
            LEFT JOIN MasterSuperDepts AS m ON t.department=m.dept_ID
        WHERE date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 't') . "
            AND t.department = ?
        GROUP BY t.date_id,
            t.department,
            d.dept_name,
            m.superID,
            t.store_id
        ORDER BY t.date_id, d.salesCode, t.store_id";
        $prep = $this->connection->prepare($query1);
        $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $dept));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', strtotime($row['date_id']));
            $link = sprintf('<a href="EOMDeptDetail.php?date=%s&store=%d&dept=%d">%s</a>',
                $date, $store, $dept, $date);
            $code = StandardAccounting::extend($row['salesCode'], $row['store_id']);
            $data[] = array(
                $link,
                $row['department'],
                $row['superID'],
                $code . ' ',
                $row['dept_name'],
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

