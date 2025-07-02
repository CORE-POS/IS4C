<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class BatchExportExcel extends FannieReportPage 
{
    protected $header = 'Batch Export Excel';
    protected $title = 'Batch Export Excel';
    public $discoverable = false;

    protected $required_fields = array('id');
    protected $report_headers = array('ID', 'Name', 'Starts', 'Ends', 'Owner', 'UPC', 'Sale Price', 'Regular Price', '% Off', 'Item', 'Store', 'Store');

    public function fetch_report_data()
    {
        $batchID = FormLib::get('id');

        $lcP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $itemP = $this->connection->prepare("SELECT description FROM products WHERE upc=?");
        $storeP = $this->connection->prepare("
            SELECT s.description
            FROM StoreBatchMap AS m
                INNER JOIN Stores AS s ON s.storeID=m.storeID
            WHERE m.batchID=?
            ORDER BY s.description"); 
        $prep = $this->connection->prepare("
            SELECT b.batchID,
                b.batchName,
                b.startDate,
                b.endDate,
                b.owner,
                l.upc,
                l.salePrice,
                p.normal_price
            FROM batches AS b
                LEFT JOIN batchList AS l ON b.batchID=l.batchID
                LEFT JOIN products AS p ON l.upc=p.upc AND p.store_id=1
            WHERE b.batchID=?");
        $data = array();
        $sCache = array();
        $res = $this->connection->execute($prep, array($batchID));
        while ($row = $this->connection->fetchRow($res)) {
            $record = array(
                $batchID,
                $row['batchName'],
                $row['startDate'],
                $row['endDate'],
                $row['owner'],
                $row['upc'],
                $row['salePrice'],
                $row['normal_price'],
                sprintf('%.2f', ($row['normal_price'] - $row['salePrice']) / $row['normal_price'] * 100),
            );
            if (strstr($row['upc'], 'LC')) {
                $record[] = $this->connection->getValue($lcP, array(substr($row['upc'], 2)));
            } else {
                $record[] = $this->connection->getValue($itemP, array($row['upc']));
            }
            if (!isset($sCache[$batchID])) {
                $sCache[$batchID] = array();
                $storeR = $this->connection->execute($storeP, array($batchID));
                while ($storeW = $this->connection->fetchRow($storeR)) {
                    $sCache[$batchID][] = $storeW['description'];
                }
            }
            foreach ($sCache[$batchID] as $s) {
                $record[] = $s;
            }
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();
