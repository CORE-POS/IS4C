<?php

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class InvHistoryPage extends FannieReportPage
{
    protected $header = 'Inventory Counts';
    protected $title = 'Inventory Counts';
    protected $must_authenticate = true;
    public $discoverable = false;
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Counted on', 'Count', 'Counted by');
    protected $new_tablesorter = true;
    protected $no_sort_but_style = true;

    public function fetch_report_data()
    {
        $vendorID = $this->form->vendor;
        $store = $this->form->store;

        $query = 'SELECT COALESCE(u.name, \'auto\') AS name,
            i.count,
            i.countDate,
            p.upc,
            p.brand,
            p.description
            FROM products AS p
                INNER JOIN InventoryCounts AS i ON p.upc=i.upc AND p.store_id=i.storeID
                LEFT JOIN Users AS u ON u.uid=i.uid
            WHERE p.default_vendor_id=?
                AND p.store_id=?
            ORDER BY p.upc,
                i.countDate DESC';
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($vendorID, $store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['countDate'],
                $row['count'],
                $row['name'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

