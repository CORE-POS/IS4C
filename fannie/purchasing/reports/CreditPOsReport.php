<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class CreditPOsReport extends FannieReportPage 
{

    protected $report_headers = array('Inv#', 'Date', 'SKU', 'UPC', 'Brand', 'Item', 'Credit Amount');

    public $report_set = 'Purchasing';
    public $description = '[Credit Invoice Report] lists credit invoices';
    protected $required_fields = array('date1', 'date2');
    protected $header = 'Credits Report';
    protected $title = 'Credits Report';

    function fetch_report_data()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $store = FormLib::get('store');
        $vendor = FormLib::get('vID');

        $prep = $this->connection->prepare("
            SELECT o.vendorInvoiceID, o.orderID,
                i.brand, i.description, i.sku, i.internalUPC AS upc, i.receivedTotalCost, i.receivedDate
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems as i ON o.orderID=i.orderID
            WHERE o.storeID=?
                AND o.vendorID=?
                AND i.receivedTotalCost < 0
                AND i.receivedDate BETWEEN ? AND ?
            ORDER BY o.vendorInvoiceID");
        $res = $this->connection->execute($prep, array($store, $vendor, $this->form->date1, $this->form->date2 . ' 23:59:59'));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                sprintf('<a href="../ViewPurchaseOrders.php?id=%d">%s</a>', $row['orderID'], $row['vendorInvoiceID']),
                $row['receivedDate'],
                $row['sku'],
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['receivedTotalCost']),
            );
        }

        return $data;
    }

    function form_content()
    {
        $stores = FormLib::storePicker();
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $vendors = new VendorsModel($dbc);
        $vOpts = $vendors->toOptions();
        $dates = FormLib::standardDateFields();
        $this->addScript('../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen();\n");

        return <<<HTML
<form method="get" action="CreditPOsReport.php">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Vendor</label>
            <select name="vID" class="form-control chosen">
                {$vOpts}
            </select>
        </div>
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Get Report</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

