<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('InventoryTask')) {
    include(__DIR__ . '/../../cron/tasks/InventoryTask.php');
}

class FairhavenInvoiceImport extends \COREPOS\Fannie\API\FannieUploadPage
{
    public $title = "Fannie - Fairhaven Invoice";
    public $header = "Upload Fairhaven Invoice";

    public $description = '[Fairhaven Invoice Import] specialized invoice import tool. Column choices
    default to Fairhaven layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 20,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 17,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Unit Cost *',
            'default' => 18,
            'required' => true
        ),
        'qty' => array(
            'display_name' => 'Quantity *',
            'default' => 16,
            'required' => true
        ),
    );

    function process_file($linedata, $indexes)
    {
        $vendorID = 302;
        $baseLine = $linedata[1];
        $invoice = $baseLine[0];
        $date = date('Y-m-d H:i:s', strtotime($baseLine[5]));
        $address = trim($baseLine[25]);
        $storeID = $address[0] == '6' ? 1 : 2;

        $checkP = $this->connection->prepare("SELECT orderID FROM PurchaseOrder WHERE vendorID=302 and vendorInvoiceID=? AND storeID=?");
        $check = $this->connection->getValue($checkP, array($invoice, $storeID));
        if ($check !== false) {
            $this->error_details = 'Already imported this invoice';
            return false;
        }

        $order = new PurchaseOrderModel($this->connection);
        $order->vendorID($vendorID);
        $order->storeID($storeID);
        $order->creationDate($date);
        $order->placedDate($date);
        $order->placed(1);
        $order->userID(0);
        $order->vendorInvoiceID($invoice);
        $orderID = $order->save();
        if ($orderID === false) {
            $this->error_details = 'Could not create purchase order';
            return false;
        }

        $itemP = $this->connection->prepare("INSERT INTO PurchaseOrderItems
            (orderID, sku, quantity, unitCost, caseSize, receivedDate, receivedQty, receivedTotalCost,
            unitSize, brand, description, internalUPC) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $this->lineCount = 0;
        $this->connection->startTransaction();
        array_shift($linedata); // discard headers
        foreach ($linedata as $data) {
            $upc = trim($data[$indexes['upc']]);
            $upc = str_replace("'", '', $upc);
            if ($upc == '') {
                continue;
            }
            $upc = BarcodeLib::padUPC(substr($upc, 0, strlen($upc) - 1));
            $cost = $data[$indexes['cost']];
            $qty = $data[$indexes['qty']];
            $desc = $data[$indexes['desc']];

            $this->connection->execute($itemP, array(
                $orderID,
                $upc,
                $qty,
                $cost,
                1,
                $date,
                $qty,
                $qty * $cost,
                '',
                '',
                $desc,
                $upc,
            ));
            $this->lineCount++;
        }
        $this->connection->commitTransaction();
        $this->orderID = $orderID;

        $task = new InventoryTask();
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->setStoreID($storeID);
        $task->setVendorID($vendorID);
        $task->run();

        return true;
    }

    function results_content()
    {
        $ret = "<p>Invoice import complete</p>";
        $ret .= "<p>Imported {$this->lineCount} records.</p>";
        $ret .= "<p><a href=\"../ViewPurchaseOrders.php?id={$this->orderID}\">View order</a></p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

