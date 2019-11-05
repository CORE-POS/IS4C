<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMSuperLayer extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('month', 'year', 'store', 'super');
    protected $report_headers = array('Date', 'Super#', 'Super', 'Amount');

    public function fetch_report_data()
    {
        try {
            $month = $this->form->month;
            $year = $this->form->year;
            $store = $this->form->store;
            $super = $this->form->super;
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
            m.superID,
            m.super_name,
            SUM(t.total) AS ttl
        FROM {$warehouse}sumDeptSalesByDay AS t
            LEFT JOIN MasterSuperDepts AS m ON t.department=m.dept_ID
        WHERE date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 't') . "
            AND m.superID = ?
        GROUP BY t.date_id,
            m.superID,
            m.super_name
        ORDER BY t.date_id, m.superID";
        $prep = $this->connection->prepare($query1);
        $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $super));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', strtotime($row['date_id']));
            $link = sprintf('<a href="EOMSuperDetail.php?date=%s&store=%d&super=%d">%s</a>',
                $date, $store, $super, $date);
            $data[] = array(
                $link,
                $row['superID'],
                $row['super_name'],
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

