<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMCountLayer extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('month', 'year', 'store', 'type');
    protected $report_headers = array('Date', 'Customer Type', 'Transactions');

    public function fetch_report_data()
    {
        try {
            $month = $this->form->month;
            $year = $this->form->year;
            $store = $this->form->store;
            $type = $this->form->type;
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
            m.memDesc,
            t.memType,
            COUNT(*) AS qty
        FROM {$warehouse}transactionSummary AS t
            INNER JOIN memtype m ON t.memType = m.memtype
        WHERE date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 't') . "
            AND t.memType = ?
        GROUP BY t.date_id, t.memType, m.memDesc
        ORDER BY t.date_id, m.memDesc";
        $prep = $this->connection->prepare($query1);
        $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $type));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', strtotime($row['date_id']));
            $link = sprintf('<a href="EOMCountDetail.php?date=%s&store=%d&type=%d">%s</a>',
                $date, $store, $type, $date);
            $data[] = array(
                $link,
                $row['memDesc'],
                sprintf('%d', $row['qty']),
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

