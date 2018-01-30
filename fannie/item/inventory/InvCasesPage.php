<?php

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class InvCasesPage extends FannieReportPage
{
    protected $header = 'Inventory Counts';
    protected $title = 'Inventory Counts';
    protected $must_authenticate = true;
    public $discoverable = false;
    protected $report_headers = array('UPC', 'Brand', 'Description', 'SKU', 'Case Size');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $vendorID = $this->form->vendor;
        $store = $this->form->store;

        $query = "
            SELECT p.upc,
                p.brand,
                p.description,
                v.sku,
                v.units
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.default_vendor_id=v.vendorID AND p.upc=v.upc
                INNER JOIN InventoryCache AS i ON p.upc=i.upc AND p.store_id=i.storeID
            WHERE p.default_vendor_id=?
                AND p.store_id=?";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($vendorID, $store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['sku'],
                $row['units'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

