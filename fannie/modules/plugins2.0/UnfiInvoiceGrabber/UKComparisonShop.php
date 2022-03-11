<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class UKComparisonShop extends FannieReportPage
{
    protected $title = 'UNFI - KeHE Comparison Shop';
    protected $header = 'UNFI - KeHE Comparison Shop';

    protected $report_headers = array('UPC', 'Brand', 'Description', 'UNFI Price', 'KeHE Price', 'Diff', 'Auto Par');
    protected $required_fields = array('vendor', 'store');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $vendorID = FormLib::get('vendor');
        $compareID = $vendorID == 1 ? 358 : 1;
        if ($compareID == 1) {
            $this->report_headers[3] = 'KeHE Price';
            $this->report_headers[4] = 'UNFI Price';
        }
        
        $prep = $this->connection->prepare("
            SELECT p.upc, p.brand, p.description, p.cost, v.cost AS compare, p.auto_par
            FROM products AS p
                INNER JOIN vendorItems AS v ON p.upc=v.upc AND v.vendorID=?
            WHERE p.default_vendor_id=?
                AND p.store_id=?
                AND v.cost < p.cost");
        $res = $this->connection->execute($prep, array($compareID, $vendorID, FormLib::get('store')));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['cost'],
                $row['compare'],
                sprintf('%.3f', $row['cost'] - $row['compare']),
                $row['auto_par'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get" action="UKComparisonShop.php">
    <div class="form-group">
        <label>Price Compare Items set to</label>
        <select name="vendor" class="form-control">
            <option value="1">UNFI</option>
            <option value="358">KeHE</option>
        </select>
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Compare</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

