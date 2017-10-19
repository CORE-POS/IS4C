<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMTenderLayer extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('month', 'year', 'store', 'tender');
    protected $report_headers = array('Date', 'Tender', 'Amount', 'Qty');

    public function fetch_report_data()
    {
        try {
            $month = $this->form->month;
            $year = $this->form->year;
            $store = $this->form->store;
            $tender = $this->form->tender;
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

        $query2 = "SELECT 
            d.date_id,
            t.TenderName,
            -sum(d.total) as total, SUM(d.quantity) AS qty
        FROM {$warehouse}sumTendersByDay AS d
            left join tenders as t ON d.trans_subtype=t.TenderCode
        WHERE d.date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
            AND d.trans_subtype = ?
        GROUP BY d.date_id, t.TenderName";
        $prep = $this->connection->prepare($query2);
        $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $tender));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', strtotime($row['date_id']));
            $link = sprintf('<a href="EOMTenderDetail.php?date=%s&store=%d&tender=%s">%s</a>',
                $date, $store, $tender, $date);
            $data[] = array(
                $link,
                $row['TenderName'],
                sprintf('%.2f', $row['total']),
                sprintf('%d', $row['qty']),
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

