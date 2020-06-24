<?php

class USFTask extends FannieTask
{
    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $store = $this->config->get('STORE_ID');
        $this->connection = FannieDB::get($this->config->get('OP_DB'));
        $vendorID = $this->getVendorID();
        $doUpdates = false;

        $cmd = realpath(__DIR__ . '/noauto/usf.py')
            . ' -u '
            . escapeshellarg($settings['USFInvoiceUser'])
            . ' -p '
            . escapeshellarg($settings['USFInvoicePass']);
        exec($cmd, $output);

        $za = new ZipArchive();
        $try = $za->open("/tmp/usf/invoiceDetails.ZIP");
        if ($try !== true) {
            $this->cronMsg("Problem getting US Foods invoices", FannieLogger::ALERT);
            return false;
        }

        $orders = array();
        for ($i=0; $i<$za->numFiles; $i++) {
            $info = $za->statIndex($i);
            if (substr(strtolower($info['name']), -4) != '.csv') {
                continue;
            }
            $fp = $za->getStream($info['name']);
            if (!$fp) {
                continue;
            }
            echo $info['name'] . "\n";

            $this->connection->startTransaction();
            $first = true;
            $orderTotal = 0;
            $invoiceTotal = 0;
            $orderID = false;
            $shipDate = false;
            $csvout = tempnam(sys_get_temp_dir(), 'usf');
            $csvFP = fopen($csvout, 'w');
            while (!feof($fp)) {
                $data = fgetcsv($fp);
                fputcsv($csvFP, $data);

                $invoice = $this->getField($data, 0);
                $orderDate = $this->getField($data, 9);
                $credit = $this->getField($data, 16);
                if ($credit) {
                    $invoice = $credit;
                    $orderDate = $this->getField($data, 17);
                }
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
                $invoiceTotal = $this->getField($data, 14);
                $orderTotal += $fullPrice;

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
                    $unitPrice = $units == 0 ? $price : $price / $units;
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
                $orderID = $this->findPO($store, $invoice, $vendorID, $orderDate, $first);
                if (!$orderID) continue;
                $this->updatePO($orderID, $shipDate, $ordered, $shipped, $sku, $upc, $item, $brand, $size, $units, $unitPrice, $fullPrice);
                $orders[$orderID] = $invoice;
                $first = false;
            }
            fclose($csvFP);
            if ($orderID && abs($orderTotal - $invoiceTotal) > 0.005) {
                $tax = $invoiceTotal - $orderTotal;
                $descriptor = abs($tax - 4) < 0.005 ? 'FUEL' : 'TAX';
                $poi = new PurchaseOrderItemsModel($this->connection);
                $poi->orderID($orderID);
                $poi->sku($descriptor);
                $poi->quantity(1);
                $poi->unitCost($tax);
                $poi->caseSize(1);
                $poi->receivedQty(1);
                $poi->receivedDate($shipDate);
                $poi->receivedTotalCost($tax);
                $poi->brand($descriptor);
                $poi->description($descriptor);
                $poi->unitSize('n/a');
                $poi->internalUPC('0000000000000');
                $poi->save();

                $dest = __DIR__ . '/../../../purchasing/noauto/invoices/' . $orderID . '.csv';
                rename($csvout, $dest);
                chmod($dest, 0644);
                
            }
            $this->connection->commitTransaction();
            if (file_exists($csvout)) {
                unlink($csvout);
            }
        }

        if (count($orders) == 0) {
            $this->cronMsg("Found zero US Foods invoices", FannieLogger::ALERT);
        }
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

    private function getCode($upc, $sku)
    {
        $upcP = $this->connection->prepare("SELECT d.salesCode
            FROM products AS p
                INNER JOIN departments AS d ON p.department=d.dept_no
            WHERE p.upc=?");
        $code = $this->connection->getValue($upcP, array($upc));
        if ($code == 41201 || $code == 41205) {
            return $code;
        }
        $skuP = $this->connection->prepare("SELECT d.salesCode
            FROM vendorItems AS v
                INNER JOIN products AS p ON v.upc=p.upc
                INNER JOIN departments AS d ON p.department=d.dept_no
            WHERE v.sku=?
                AND v.vendorID=35");
        $code = $this->connection->getValue($upcP, array($upc));
        if ($code == 41201 || $code == 41205) {
            return $code;
        }

        return 41201;
    }

    private $poi = false;
    private function updatePO($orderID, $shipDate, $ordered, $shipped, $sku, $upc, $item, $brand, $size, $units, $price, $fullPrice)
    {
        if (!$this->poi) {
            $poi = new PurchaseOrderItemsModel($this->connection);
        }
        $poi->orderID($orderID);
        $poi->sku($sku);
        $code = $this->getCode($upc, $sku);
        if (trim($brand) == 'ECOLAB') {
            $code = 63320;
        }
        if ($poi->load()) {
            $poi->quantity($poi->quantity() + $ordered);
            $poi->receivedQty($poi->receivedQty() + ($shipped * $units));
            $poi->receivedTotalCost($poi->receivedTotalCost() + $fullPrice);
            if ($poi->salesCode() == 0 || $code == 63320) {
                $poi->salesCode($code);
            }
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
            $poi->salesCode($code);
            $poi->save();
        }
    }

    private $poP = false;
    private function findPO($store, $invoice, $vendor, $date, $first)
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

        } elseif ($first) {
            // zero out old amounts to avoid doubling them on re-import
            $prep = $this->connection->prepare("UPDATE PurchaseOrderItems SET quantity=0, receivedQty=0, receivedTotalCost=0
                WHERE orderID=?");
            $this->connection->execute($prep, array($orderID));
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
}

