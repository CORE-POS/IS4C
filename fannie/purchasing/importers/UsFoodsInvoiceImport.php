<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class UsFoodsInvoiceImport extends FannieRESTfulPage
{
    protected $header = 'US Foods Invoice Import';
    protected $title = 'US Foods Invoice Import';

    protected function post_view()
    {
        $file = $_FILES['invoice']['tmp_name'];
        $type = $_FILES['invoice']['type'];
        $store = FormLib::get('store');
        $doUpdates = FormLib::get('updateCosts', false);
        $files = array();
        if (stristr($type, 'zip')) {
            $za = new ZipArchive();
            if (!$za->open($file)) {
                return '<div class="alert alert-danger">Invalid Zip File</div>';
            }
            for ($i=0; $i<$za->numFiles; $i++) {
                $info = $za->statIndex($i);
                if (substr(strtolower($info['name']), -4) != '.csv') {
                    // skip non-csv file
                    echo "Skipping {$info['name']}<br />";
                    continue;
                }

                $fp = $za->getStream($info['name']);
                if (!$fp) { // false or null in failure cases
                    echo "Failure {$info['name']}<br />";
                    continue;
                }

                $outfile = tempnam(sys_get_temp_dir(), 'usf');
                $out = fopen($outfile, 'w');
                while (!feof($fp)) {
                    $chunk = fread($fp, 1024);
                    fwrite($out, $chunk);
                }
                fclose($fp);
                fclose($out);
                $files[] = $outfile;
            }
        } else {
            $files[] = $file;
        }

        $vendorID = $this->getVendorID();
        $orders = array();
        foreach ($files as $file) {

            $this->connection->startTransaction();
            $fp = fopen($file, 'r');
            $count = 0;
            while (!feof($fp)) {
                $data = fgetcsv($fp);

                $invoice = $this->getField($data, 0);
                $orderDate = $this->getField($data, 9);
                $orderDate = date('Y-m-d', strtotime($orderDate));
                $shipDate = $this->getField($data, 12);
                $shipDate = date('Y-m-d', strtotime($shipDate));
                $ordered = $this->getField($data, 51);
                $shipped = $this->getField($data, 52);
                $sku = $this->getField($data, 46);
                $item = $this->getField($data, 47);
                $brand = $this->getField($data, 48);
                $sizeInfo = $this->getField($data, 49);
                $priceType = strtoupper($this->getField($data, 54));
                $price = $this->getField($data, 55);
                $fullPrice = $this->getField($data, 56);
                $unitPrice = $price;

                if (!is_numeric($sku)) continue;
                if ($priceType == '') continue;

                if ($priceType == 'LB') {
                    $size = '#';
                    $units = str_replace(' LBA', '', $sizeInfo);
                    $mult = 1;
                    if (strstr($units, '/')) {
                        list($mult, $units) = explode('/', $units, 2);
                    }
                    if (strstr($units, '-')) {
                        list($left, $right) = explode('-', $units);
                        $units = ($left + $right) / 2;
                    }
                    if (!is_numeric($units) || is_nan($units) || is_infinite($units)) {
                        $units = 1;
                    }
                } elseif ($priceType == 'EA') {
                    list($units, $size) = explode(' ', $sizeInfo, 2);
                    $size = '#';
                    $unitPrice = $price / $units;
                } elseif ($priceType == 'CS') {
                    if (strstr($sizeInfo, '/')) {
                        list($units, $size) = explode('/', $sizeInfo, 2);
                    } else {
                        list($units, $size) = explode(' ', $sizeInfo, 2);
                        $size = '#';
                    }
                    $unitPrice = $price / $units;
                }
                $unitPrice = round($unitPrice, 3);

                $upc = $this->updateCatalog($vendorID, $sku, $item, $brand, $size, $units, $unitPrice, $doUpdates);
                $orderID = $this->findPO($store, $invoice, $vendorID, $orderDate);
                if (!$orderID) continue;
                $this->updatePO($orderID, $shipDate, $ordered, $shipped, $sku, $upc, $item, $brand, $size, $units, $unitPrice, $fullPrice);
                $orders[$orderID] = $invoice;
                $count++;
            }
            $this->connection->commitTransaction();
            if (!$count) {
                echo "$file was empty<br />";
            }

            unlink($file);
        }

        $ret = '<ul>';
        foreach ($orders as $o => $i) {
            $ret .= sprintf('<li><a href="../ViewPurchaseOrders.php?id=%d">Order #%d</a></li>', $o, $i);
        }
        $ret .= '</ul>';
        $ret .= '<p><a href="UsFoodsInvoiceImport.php?store=' . $store . '">Import more</a></p>';

        return $ret;
    }

    private $vfindP = false;
    private $vupP = false;
    private $vinsP = false;
    private function updateCatalog($vendor, $sku, $item, $brand, $size, $units, $price, $doUpdates)
    {
        if (!$this->vfindP) {
            $this->vfindP = $this->connection->prepare("SELECT upc FROM vendorItems WHERE vendorID=? AND sku=?");
        }
        $exists = $this->connection->getValue($this->vfindP, array($vendor, $sku));
        $upc = $exists ? $exists : str_repeat('0', 13);

        if ($exists !== false && $doUpdates) {
            if (!$this->vupP) {
                $this->vupP = $this->connection->prepare("UPDATE vendorItems SET cost=?, modified=? WHERE vendorID=? AND sku=?");
            }
            $this->connection->execute($this->vupP, array($price, date('Y-m-d H:i:s'), $vendor, $sku));
        } elseif ($exists === false) {
            if (!$this->vinsP) {
                $this->vinsP = $this->connection->prepare("INSERT INTO vendorItems
                    (upc, sku, brand, description, size, units, cost, saleCost, vendorDept, vendorID, srp, modified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, 0, ?)");
            }
            $this->connection->execute($this->vinsP, array($upc, $sku, $brand, $item, $size, $units, $price, $vendor, date('Y-m-d H:i:s')));
        }

        return $upc;
    }

    private $poi = false;
    private function updatePO($orderID, $shipDate, $ordered, $shipped, $sku, $upc, $item, $brand, $size, $units, $price, $fullPrice)
    {
        if (!$this->poi) {
            $poi = new PurchaseOrderItemsModel($this->connection);
        }
        $poi->orderID($orderID);
        $poi->sku($sku);
        if ($poi->load()) {
            $poi->quantity($poi->quantity() + $ordered);
            $poi->receivedQty($poi->receivedQty() + ($shipped * $units));
            $poi->receivedTotalCost($poi->receivedTotalCost() + $fullPrice);
            $poi->save();
        } else {
            $poi->quantity($ordered);
            $poi->unitCost($price);
            $poi->caseSize($units);
            $poi->receivedDate($shipDate);
            $poi->receivedQty($shipped * $units);
            $poi->receivedTotalCost($fullPrice);
            $poi->unitSize($size);
            $poi->brand($brand);
            $poi->description($item);
            $poi->internalUPC($upc);
            $poi->save();
        }
    }

    private $poP = false;
    private function findPO($store, $invoice, $vendor, $date)
    {
        if (!$this->poP) {
            $this->poP = $this->connection->prepare("SELECT orderID FROM PurchaseOrder WHERE storeID=? AND vendorID=? AND vendorInvoiceID=?");
        }

        $orderID = $this->connection->getValue($this->poP, array($store, $vendor, $invoice));
        if (!$orderID) {
            $model = new PurchaseOrderModel($this->connection);
            $model->vendorID($vendor);
            $model->storeID($store);
            $model->creationDate($date);
            $model->placed(1);
            $model->placedDate($date);
            $model->vendorInvoiceID($invoice);
            $orderID = $model->save();

        }

        return $orderID;
    }

    private function getField($line, $num)
    {
        return isset($line[$num]) ? trim($line[$num]) : '';
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
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName LIKE ? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('%U%S% Foods%'));

        return $vid;
    }

    protected function get_view()
    {
        $store = FormLib::storePicker();
        return <<<HTML
<form method="post" enctype="multipart/form-data" action="UsFoodsInvoiceImport.php">
    <div class="form-group">
        <label>Select invoice(s)</label>
        <input type="file" class="form-control" name="invoice" accept=".csv,.zip" />
    </div>
    <div class="form-group">
        <label>Store</label>
        {$store['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Upload</button>
        <label><input type="checkbox" name="updateCosts" value="1" checked />
            Update Costs</label>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

