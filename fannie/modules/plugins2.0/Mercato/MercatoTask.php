<?php

class MercatoTask extends FannieTask
{
    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        if (!class_exists('InstaFileV3')) {
            include(__DIR__ . '/InstaFileV3.php');
        }
        $csvfile = tempnam(sys_get_temp_dir(), 'ICT');
        $insta = new InstaFileV3($dbc, $this->config);
        $insta->getFile($csvfile);

        $storeID = $this->config->get('STORE_ID');
        $deptP = $dbc->prepare("SELECT modified, last_sold FROM products AS p WHERE p.upc=? AND p.store_id=?");

        $outputFile = "/tmp/wfc_" . date('Ymd_Hi') . ".csv";
        $out = fopen($outputFile, 'w');

        $fp = fopen($csvfile, 'r');
        fgetcsv($fp); // discard headers
        fwrite($out,"product-code,product-name,sku,price,price-type,price-quantity,last-updated,instock,last-sold-date,taxable\r\n");
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
            fwrite($out, "1,");
            $modTS = strtotime($info['modified']);
            fwrite($out, ($modTS ? date('Ymd', $modTS) : '') . ",");
            fwrite($out, "Y,");
            $soldTS = strtotime($info['last_sold']);
            fwrite($out, ($soldTS ? date('Ymd', $soldTS) : '') . ",");
            fwrite($out, ($data[11] > 0 ? 'Y' : 'N') . "\r\n");
        }

        unlink($csvfile);

        echo $outputFile . "\n";
    }
}

