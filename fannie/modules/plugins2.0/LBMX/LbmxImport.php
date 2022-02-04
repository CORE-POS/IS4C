<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class LbmxImport extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'LBMX Data Import';
    protected $title = 'LBMX Data Import';

    protected $preview_opts = array(
        'vendorID' => array(
            'display_name' => 'Vendor ID',
            'default' => 0,
            'required' => true
        ),
        'invoice' => array(
            'display_name' => 'Invoice #',
            'default' => 2,
            'required' => true
        ),
        'date' => array(
            'display_name' => 'Invoice Date',
            'default' => 5,
            'required' => true
        ),
        'customerID' => array(
            'display_name' => 'Customer ID',
            'default' => 9,
            'required' => true
        ),
        'qty' => array(
            'display_name' => 'Quantity',
            'default' => 34,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU',
            'default' => 36,
            'required' => true
        ),
        'item' => array(
            'display_name' => 'Item',
            'default' => 40,
            'required' => true
        ),
        'unitCost' => array(
            'display_name' => 'Unit Cost',
            'default' => 43,
            'required' => true
        ),
        'upc' => array(
            'display_name' => 'UPC',
            'default' => 45,
            'required' => true
        ),
    );

    private $results = '';

    public function process_file($linedata, $indexes)
    {
        $getStoreP = $this->connection->prepare("SELECT posID FROM LbmxStores WHERE lbmxID=?");
        $getVendorP = $this->connection->prepare("SELECT posID FROM LbmxVendors WHERE lbmxID=?");
        $getPoP = $this->connection->prepare("SELECT orderID FROM PurchaseOrder WHERE storeID=? AND vendorID=? AND vendorInvoiceID=?");
        $poIDs = array();
        $poDates = array();

        /*
         * Find existing POs and/or generate new POs
         * as needed. Re-importing the same PO should
         * just update its data
         */
        $header = true;
        $this->connection->startTransaction();
        foreach ($linedata as $line) {
            if ($header) {
                $header = false;
                continue;
            }
            if (!isset($line[$indexes['vendorID']])) {
                continue;
            } 
            if (!isset($line[$indexes['customerID']])) {
                continue;
            } 
            if (!isset($line[$indexes['invoice']])) {
                continue;
            } 
            $key = ($line[$indexes['vendorID']]);
            $key .= '|' . ($line[$indexes['customerID']]);
            $key .= '|' . ($line[$indexes['invoice']]);
            if (isset($poIDs[$key])) {
                continue;
            }

            $storeID = $this->connection->getValue($getStoreP, array($line[$indexes['customerID']]));
            $vendorID = $this->connection->getValue($getVendorP, array($line[$indexes['vendorID']]));
            $exists = $this->connection->getValue($getPoP, array($storeID, $vendorID, $line[$indexes['invoice']]));
            $invDate = date('Y-m-d', strtotime($line[$indexes['date']]));
            $poDates[$key] = $invDate;
            if ($exists) {
                $poIDs[$key] = $exists;
                $this->results .= "Existing PO for $key is $exists<br />";
            } else {
                $model = new PurchaseOrderModel($this->connection);
                $model->vendorID($vendorID);
                $model->storeID($storeID);
                $model->creationDate($invDate);
                $model->placed(1);
                $model->placedDate($invDate);
                $model->vendorInvoiceID($line[$indexes['invoice']]);
                $newID = $model->save();
                $poIDs[$key] = $newID;
                $this->results .= "New PO for $key is $newID<br />";
            }
        }
        $this->connection->commitTransaction();

        list($inStr, $args) = $this->connection->safeInClause(array_values($poIDs));
        $clearP = $this->connection->prepare("DELETE FROM PurchaseOrderItems WHERE orderID IN ({$inStr})");
        $this->connection->execute($clearP, $args);

        $header = true;
        $insP = $this->connection->prepare("INSERT INTO PurchaseOrderItems
            (orderID, sku, quantity, unitCost, receivedDate, receivedQty, receivedTotalCost, description, internalUPC)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $this->connection->startTransaction();
        foreach ($linedata as $line) {
            if ($header) {
                $header = false;
                continue;
            }
            if (!isset($line[$indexes['vendorID']])) {
                continue;
            } 
            if (!isset($line[$indexes['customerID']])) {
                continue;
            } 
            if (!isset($line[$indexes['invoice']])) {
                continue;
            } 
            $key = ($line[$indexes['vendorID']]);
            $key .= '|' . ($line[$indexes['customerID']]);
            $key .= '|' . ($line[$indexes['invoice']]);
            if (!isset($poIDs[$key])) {
                continue;
            }
            
            $qty = $line[$indexes['qty']];
            $cost = $line[$indexes['unitCost']];
            $upc = $line[$indexes['upc']];
            $upc = BarcodeLib::padUPC($upc);
            $args = array(
                $poIDs[$key],
                $line[$indexes['sku']],
                $qty,
                $cost,
                $poDates[$key],
                $qty,
                $qty * $cost,
                $line[$indexes['item']],
                $upc,
            );
            $this->connection->execute($insP, $args);
        }
        $this->connection->commitTransaction();

        return true;
    }

    public function results_content()
    {
        return $this->results;
    }

}

FannieDispatch::conditionalExec();

