<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMCountDetail extends FannieReportPage
{
    protected $header = 'EOM Report';
    protected $title = 'EOM Report';
    public $discoverable = false;
    protected $required_fields = array('date', 'store', 'type');
    protected $report_headers = array('Date', 'Customer Type', 'Inventory $', 'Inventory Qty', 'Non-inventory $', 'Non-inventory Qty', 'Receipt');

    public function fetch_report_data()
    {
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $type = $this->form->type;
        } catch (Exception $ex) {
            return array();
        }

        $tstamp = strtotime($date);
        $dateID = date('Ymd', $tstamp);
        $warehouse = $this->config->get('PLUGIN_SETTINGS');
        $warehouse = $warehouse['WarehouseDatabase'];
        $warehouse .= $this->connection->sep();

        $query1="SELECT t.date_id,
            m.memDesc,
            t.memType,
            retailTotal,
            retailQty,
            nonRetailTotal,
            nonRetailQty,
            start_time,
            trans_num
        FROM {$warehouse}transactionSummary AS t
            INNER JOIN memtype m ON t.memType = m.memtype
        WHERE date_id = ?
            AND " . DTrans::isStoreID($store, 't') . "
            AND t.memType = ?
        ORDER BY start_time";
        $prep = $this->connection->prepare($query1);
        $res = $this->connection->execute($prep, array($dateID, $store, $type));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['start_time'],
                $row['memDesc'],
                sprintf('%.2f', $row['retailTotal']),
                sprintf('%.2f', $row['retailQty']),
                sprintf('%.2f', $row['nonRetailTotal']),
                sprintf('%.2f', $row['nonRetailQty']),
                $row['trans_num'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

