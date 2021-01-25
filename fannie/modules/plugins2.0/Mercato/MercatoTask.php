<?php

use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;

class MercatoTask extends FannieTask
{
    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $settings = $this->config->get('PLUGIN_SETTINGS');

        if (!class_exists('InstaFileV3')) {
            include(__DIR__ . '/InstaFileV3.php');
        }
        $csvfile = tempnam(sys_get_temp_dir(), 'ICT');
        $insta = new InstaFileV3($dbc, $this->config);
        $insta->getFile($csvfile);

        $storeID = $this->config->get('STORE_ID');
        $deptP = $dbc->prepare("SELECT modified, last_sold,department, special_price, start_date, end_date, batchID FROM products AS p WHERE p.upc=? AND p.store_id=?");
        $pieceP = $dbc->prepare("SELECT pieceWeight FROM MercatoItems WHERE upc=?");

        $outputFile = "/tmp/" . $settings['MercatoFtpUser'] . '_' . date('Ymd_Hi') . ".csv";
        $saleFile = "/tmp/_" . $settings['MercatoFtpUser'] . '_' . date('Ymd_Hi') . ".csv";
        $out = fopen($outputFile, 'w');
        $saleOut = fopen($saleFile, 'w');

        /**
         * Generate CSV lines for available items. Utilizes Instacart logic
         * for deciding which items are available.
         */
        $fp = fopen($csvfile, 'r');
        fgetcsv($fp); // discard headers
        fwrite($out,"product-code,product-name,sku,price,price-type,weight,price-quantity,last-updated,instock,last-sold-date,taxable,department-id\r\n");
        fwrite($saleOut, "SKU,START DATE,END DATE,PRICE,QTY\r\n");
        $upcs = array();
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            $upc = $data[0];
            if (!is_numeric($upc)) {
                continue;
            }
            if (strlen($upc) > 6 && substr($upc, -6) != '000000') {
                $upc = substr($upc, 0, strlen($upc) - 1);
            }
            $upc = BarcodeLib::padUPC($upc);
            $info = $dbc->getRow($deptP, array($upc, $storeID));
            fwrite($out,$data[0] . ",");
            $name = $data[3];
            if ($data[5]) {
                $name = $data[5] . ' ' . $data[3];
            }
            fwrite($out, '"' . $name . '",');
            fwrite($out, $data[8] . ",");
            if ($data[12] > 0) {
                $data[1] += $data[12];
            }
            fwrite($out, sprintf('%.2f', $data[1]) . ",");
            fwrite($out, ($data[2] == 'lb' ? 'P' : 'U') . ",");
            $weight = $dbc->getValue($pieceP, array($upc));
            fwrite($out, $weight . ',');
            fwrite($out, "1,");
            $modTS = strtotime($info['modified']);
            fwrite($out, ($modTS ? date('Ymd', $modTS) : '') . ",");
            fwrite($out, "Y,");
            $soldTS = strtotime($info['last_sold']);
            fwrite($out, ($soldTS ? date('Ymd', $soldTS) : '') . ",");
            fwrite($out, ($data[11] > 0 ? 'Y' : 'N') . ",");
            fwrite($out, $info['department'] . "\r\n");

            if ($info['special_price'] < $data[1]) {
                fwrite($saleOut, $upc . ",");
                fwrite($saleOut, date('n/j/Y', strtotime($info['start_date'])) . ",");
                fwrite($saleOut, date('n/j/Y', strtotime($info['end_date'])) . ",");
                fwrite($saleOut, sprintf('%.2f', $info['special_price']) . ",");
                fwrite($saleOut, "1\r\n");
            }

            $upcs[] = $data[8];
        }

        /**
         * Update table of all UPCs ever submitted to Mercato
         */
        $chkP = $dbc->prepare("SELECT upc FROM MercatoItems WHERE upc=? AND storeID=?");
        $addP = $dbc->prepare("INSERT INTO MercatoItems (upc, storeID) VALUES (?, ?)");
        $dbc->startTransaction();
        foreach ($upcs as $upc) {
            if (!$dbc->getValue($chkP, array($upc, $storeID))) {
                $dbc->execute($addP, array($upc, $storeID));
            }
        }
        $dbc->commitTransaction();

        /**
         * Lookup previously submitted items that are no longer available. Include them
         * in the upload with the in-stock flag set to "no".
         */
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $oosP = $dbc->prepare("SELECT upc, pieceWeight FROM MercatoItems WHERE upc NOT IN ({$inStr}) AND storeID=?");
        $args[] = $storeID;
        $oosR = $dbc->execute($oosP, $args);
        $prodP = $dbc->prepare("SELECT * FROM products WHERE upc=? AND store_id=?");
        while ($oosW = $dbc->fetchRow($oosR)) {
            $upc = $oosW['upc'];
            $row = $dbc->getRow($prodP, array($upc, $storeID));
            fwrite($out, $upc . ',');
            $name = $row['description'];
            if ($row['brand']) {
                $name = $row['brand'] . ' ' . $name;
            }
            fwrite($out, '"' . $name . '",');
            fwrite($out, $upc . ',');
            fwrite($out, sprintf('%.2f', $row['normal_price']) . ",");
            fwrite($out, ($row['scale'] == 1 ? 'P' : 'U') . ",");
            fwrite($out, $oosW['pieceWeight'] . ',');
            fwrite($out, "1,");
            $modTS = strtotime($row['modified']);
            fwrite($out, ($modTS ? date('Ymd', $modTS) : '') . ",");
            fwrite($out, "N,");
            $soldTS = strtotime($row['last_sold']);
            fwrite($out, ($soldTS ? date('Ymd', $soldTS) : '') . ",");
            fwrite($out, ($row['tax'] > 0 ? 'Y' : 'N') . ",");
            fwrite($out, $row['department'] . "\r\n");
        }

        unlink($csvfile);

        echo $outputFile . "\n";

        if (class_exists('League\\Flysystem\\Sftp\\SftpAdapter')) {
            $adapter = new SftpAdapter(array(
                'host' => $settings['MercatoFtpHost'],
                'username' => $settings['MercatoFtpUser'],
                'password' => $settings['MercatoFtpPw'],
                'port' => 22,
            ));
            $filesystem = new Filesystem($adapter);
            $success = $filesystem->put('inventory/' . basename($outputFile), file_get_contents($outputFile));
            if ($success) echo "Upload succeeded\n";

            $success = $filesystem->put('ad-tpr/' . substr(basename($saleFile), 1), file_get_contents($saleFile));
            if ($success) echo "Upload succeeded\n";
        }

        //unlink($outputFile);
        unlink($saleFile);
    }
}

