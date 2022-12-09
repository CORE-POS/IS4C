<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class OutOfStockSalesReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie :  Out of Stock Report";
    protected $header = "Out of Stock Report";
    public $themed = true;
    protected $new_tablesorter = true;
    protected $required_fields = array('store', 'super');
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Vendor', 'Qty LQ', '$ LQ');

    protected $sort_column = 5;
    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare("
            SELECT p.upc, brand, description, vendorName, qtyLastQuarter, totalLastQuarter
            FROM products AS p
                LEFT JOIN " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS s ON p.upc=s.upc AND p.store_id=s.storeID
                LEFT JOIN vendors AS v on p.default_vendor_id=v.vendorID
                LEFT JOIN superdepts AS d ON p.department=d.dept_ID
            WHERE (numflag & (1 << (19-1))) <> 0
                AND p.store_id=?
                AND d.superID=?");
        $res = $this->connection->execute($prep, array($this->form->store, $this->form->super));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['vendorName'],
                sprintf('%.2f', $row['qtyLastQuarter']),
                sprintf('%.2f', $row['totalLastQuarter']),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $sd = new SuperDeptNamesModel($this->connection);
        $sdOpts = $sd->toOptions(22);

        return <<<HTML
<form method="get" action="OutOfStockSalesReport.php">
    <div class="form-group">
        <label>Super Dept.</label>
        <select name="super" class="form-control">{$sdOpts}</select>
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();


