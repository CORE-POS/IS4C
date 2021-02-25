<?php

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpManualEntries extends FannieReportPage
{
    protected $header = 'Manually Added Items';
    protected $title = 'Manually Added Items';
    public $discoverable = false;
    protected $new_tablesorter = true;
    protected $required_fields = array();
    protected $report_headers = array('LC', 'Item', 'Category', 'Store', 'Vendor');

    public function fetch_report_data()
    {
        $res = $this->connection->query("
            SELECT r.upc, r.vendorItem, c.name, s.description, v.vendorName
            FROM RpOrderItems AS r
                LEFT JOIN Stores AS s ON r.storeID=s.storeID
                LEFT JOIN RpOrderCategories AS c ON c.rpOrderCategoryID=r.categoryID
                LEFT JOIN vendors AS v ON r.vendorID=v.vendorID
            WHERE r.addedBy > 0");
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            if (substr($row['upc'], 0, 2) == 'LC') {
                $row['upc'] = substr($row['upc'], 2);
            } else {
                $row['upc'] = 'UPC' . $row['upc'];
            }
            $data[] = array(
                $row['upc'],
                $row['vendorItem'],
                $row['name'],
                $row['description'],
                str_replace(' (Produce)', '', $row['vendorName']),
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();


