<?php

use Smalot\PdfParser\Parser;

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class RdwInvoiceImport extends FannieRESTfulPage
{
    protected $header = 'RDW Invoice Import';
    protected $title = 'RDW Invoice Import';

    protected function post_view()
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($_FILES['pdf']['tmp_name']);
        $text = $pdf->getText();
        unlink($_FILES['pdf']['tmp_name']);

        $invNum = false;
        $date = false;
        $inItems = false;
        $items = array();
        $lines = explode("\n", $text);
        for ($i=0; $i<count($lines); $i++) {
            $line = trim($lines[$i]);

            if ($line == 'STOP' && !$invNum && !$date) {
                // invoice metadata should be the next line
                $match = preg_match('/(\d+)\s.*?(\d+\/\d+\/\d+)/', $lines[$i+1], $matches);
                if ($match) {
                    $invNum = $matches[1];
                    $date = $matches[2];
                    $i += 1;
                }
            } elseif (!$inItems && $line == 'UPC/PLU') {
                // items should be coming next
                $inItems = true;
                continue;
            } elseif ($inItems && !is_numeric(trim($line))) {
                // probably a page break
                if (!is_numeric($lines[$i+1])) {
                    $inItems = false;
                    continue;
                }
                $item = array(
                    'cool' => trim($line),
                    'qty' => trim($lines[$i+1]),
                    'size' => trim($lines[$i+2]),
                    'description' => trim($lines[$i+3]),
                    'sku' => trim($lines[$i+4]),
                    'upc' => trim($lines[$i+9]),
                    'totalCost' => trim($lines[$i+7]),
                );
                $markup = trim($lines[$i+6]) / 100.00;
                $cost = trim($lines[$i+8]) / (1 - $markup);
                $item['unitCost'] = $cost;
                if (preg_match('/\t+/', $item['cool'])) {
                    $tmp = preg_split('/\t+/', $item['cool'], -1, PREG_SPLIT_NO_EMPTY);
                    $item['cool'] = implode(' and ', $tmp);
                }
                $items[] = $item;
                $i += 9;
            } elseif ($inItems && is_numeric(trim($line))) {
                // out-of-stock line
                if ($line == '0') {
                    $i += 8;
                    continue;
                }
                // final fee
                break;
            }
        }

        //debug
        //echo '<pre>' . print_r($items, true) . '</pre>'; return false;

        $realDate = strtotime($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            return '<div class="alert alert-danger">Could not find vendor</div>';
        }
        $order = new PurchaseOrderModel($this->connection);
        $order->vendorID($VENDOR_ID);
        $order->storeID(FormLib::get('store'));
        $order->creationDate($realDate);
        $order->placedDate($realDate);
        $order->placed(1);
        $order->userID(0);
        $order->vendorInvoiceID($invNum);
        $orderID = $order->save();
        if ($orderID === false) {
            return '<div class="alert alert-danger">Failed to create order</div>';
        }

        $this->connection->startTransaction();
        $itemP = $this->connection->prepare("INSERT INTO PurchaseOrderItems
            (orderID, sku, quantity, unitCost, caseSize, receivedDate, receivedQty, receivedTotalCost,
            unitSize, brand, description, internalUPC) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, '', ?, ?)");
        $coolP = $this->connection->prepare("SELECT coolText FROM SkuCOOLHistory WHERE vendorID=? AND sku=? AND ordinal=1");
        $coolModel = new SkuCOOLHistoryModel($this->connection);
        $logCOOL = FormLib::get('logCOOL', false);
        $this->lineCount = 0;
        $this->coolCount = 0;
        foreach ($items as $item) {
            $this->connection->execute($itemP, array($orderID, $item['sku'], $item['qty'], $item['unitCost'],
                $realDate, $item['qty'], $item['totalCost'], $item['size'], $item['description'],
                $this->cleanUPC($item['upc'])));
            $this->lineCount++;
            if ($logCOOL) {
                $current = $this->connection->getValue($coolP, array($VENDOR_ID, $item['sku']));
                if ($current === false || strtolower($current) != strtolower($item['cool'])) {
                    $coolModel->rotateIn($VENDOR_ID, $item['sku'], $item['cool']);
                    $this->coolCount++;
                }
            }
        }
        $this->connection->commitTransaction();

        return <<<HTML
<p>
    Imported {$this->lineCount} items.<br />
    Saw {$this->coolCount} origin changes.
</p>
<p>
    <a href="RdwInvoiceImport.php">Upload Another</a><br />
    <a href="../ViewPurchaseOrders.php?id={$orderID}">View Invoice</a>
</p>
HTML;
    }

    private function cleanUPC($upc)
    {
        if (strlen($upc) == 5 && substr($upc, 0, 1) == '9' && $upc !== '99999') {
            return BarcodeLib::padUPC(substr($upc, 1));
        }
        if (strstr($upc, '-')) {
            $upc = str_replace('-', '', $upc);
            return BarcodeLib::padUPC(substr($upc, 0, strlen($upc)-1));
        }
        if ($upc == '9999' || $upc == '99999') {
            return '0000000000000';
        }

        return BarcodeLib::padUPC($upc);
    }

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? OR vendorName LIKE ? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('RDW', '%RUSS DAVIS%'));

        return $vid;
    }

    protected function get_view()
    {
        $store = FormLib::storePicker();
        return <<<HTML
<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label>Select invoice</label>
        <input type="file" class="form-control" name="pdf" accept="application/pdf" />
    </div>
    <div class="form-group">
        <label>Store</label>
        {$store['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Upload</button>
        <label><input type="checkbox" name="logCOOL" value="1" checked />
            Log COOL updates</label>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

