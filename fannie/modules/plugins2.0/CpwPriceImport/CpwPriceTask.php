<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CpwPriceTask extends FannieTask
{
    private function download($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $filename = tempnam(sys_get_temp_dir(), 'cpw');
        $file = fopen($filename, 'w');
        curl_setopt($curl, CURLOPT_FILE, $file);
        $result = curl_exec($curl);
        curl_close($curl);
        fclose($file);

        return $filename;
    }

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $url = $settings['CpwPriceURL'];
        $mode = $settings['CpwCostUpdates'];

        $filename = $this->download($url);

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $idP = $dbc->prepare('SELECT vendorID FROM vendors WHERE vendorName=\'CPW\'');
        $vendorID = $dbc->getValue($idP);

        $prodP = $dbc->prepare('UPDATE products SET cost=?, modified=' . $dbc->now() . ' WHERE upc=?');
        $upcs = array();

        $vendP = $dbc->prepare('SELECT sku FROM vendorItems WHERE sku=? AND vendorID=?');
        $insP = $dbc->prepare('INSERT INTO vendorItems 
            (upc, sku, brand, description, size, units, cost, saleCost, vendorDept, vendorID, modified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ' . $dbc->now() . ')');
        $upP = $dbc->prepare('UPDATE vendorItems SET cost=?, saleCost=?, vendorDept=?, modified=' . $dbc->now() . '
            WHERE sku=? AND vendorID=?');

        $deptP = $dbc->prepare('SELECT deptID FROM vendorDepartments WHERE deptID=? AND vendorID=?');
        $newDeptP = $dbc->prepare('INSERT INTO vendorDepartments (vendorID, deptID, name, margin, testing, posDeptID)
            VALUES (?, ?, ?, 0, 0, 0)');

        $file = fopen($filename, 'r');
        $dbc->startTransaction();
        while (!feof($file)) {
            $data = fgetcsv($file);
            $sku = trim($data[8]);
            $upc = trim($data[7]);
            if (empty($upc) && empty($sku)) {
                continue;
            }
            $upc = str_replace('-', '', $upc);
            $upc = substr($upc, 0, strlen($upc)-1);
            if (!is_numeric($upc) && !is_numeric($sku)) {
                continue;
            }
            $upc = BarcodeLib::padUPC($upc);

            $price = trim($data[6]);
            if (!is_numeric($price)) {
                continue;
            }
            $regPrice = trim($data[15]);
            $salePrice = 0;
            if ($price != $regPrice) {
                $salePrice = $price;
            }

            $size = trim($data[11]);
            if (is_numeric($size)) {
                $size .= ' ' . trim($data[12]);
            }

            $caseSize = trim($data[13]);
            if ($caseSize == 1 && strpos($data[11], '-')) {
                list($start,$end) = explode('-', trim($caseSize));
                $caseSize = ($start + $end) / 2;
            } elseif ($caseSize == 1 && is_numeric(trim($data[11]))) {
                $caseSize = trim($data[11]);
                $size = trim($data[12]);
            }
            $regPrice /= $caseSize;
            $salePrice /= $caseSize;

            $dept = trim($data[16]);
            $brand = trim($data[1]);
            if (!empty(trim($data[2]))) {
                $brand = trim($data[2]);
            }
            $brand = preg_replace('/\(.+\)/', '', $brand);
            $description = trim($data[4]);

            $exists = $dbc->getValue($vendP, array($sku, $vendorID));
            if ($exists) {
                $dbc->execute($upP, array($regPrice, $salePrice, $dept, $sku, $vendorID));
            } else {
                $dbc->execute($insP, array($upc, $sku, $brand, $description, $size, $caseSize, $regPrice, $salePrice, $dept, $vendorID));
            }
            if ($mode) {
                $dbc->execute($prodP, array($regPrice, $upc));
                $upcs[] = $upc;
            }

            $exists = $dbc->getValue($deptP, array($dept, $vendorID));
            if (!$exists) {
                $dbc->execute($newDeptP, array($vendorID, $dept, trim($data[0])));
            }
        }
        $dbc->commitTransaction();
        fclose($file);

        if ($mode && count($upcs)) {
            $model = new ProdUpdateModel($dbc);
            $model->logManyUpdates($upcs, 'EDIT');
        }

        unlink($filename);
    }
}

