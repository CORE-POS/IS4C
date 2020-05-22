<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaWfcExport extends FannieTask 
{
    private $skipUPCs = array(
    '0000000003266' => true,
    '0000000003262' => true,
    '0000000003267' => true,
    '0000000004541' => true,
    '0000000004993' => 'ORANGE CAULIFLOWER',
    '0000000004570' => 'ROMANESCO',
    '0000000004391' => 'HAMLIN ORANGES',
    '0000000004458' => 'ALGERIAN TANGERINES',
    '0000000004055' => 'GOLDEN NUGGET MANDARINS',
    '0000000003753' => 'KISHU MANDARINS',
    '0000000004457' => 'TDE MANDARINS',
    '0082890459003' => 'MANDARINS 2# BAG',
    '0007224013377' => 'MANDARINS 2# BAG',
    '0000000004022' => 'GREEN GRAPES',
    '0000000004107' => 'SWEETANGO APPLES',
    '0000000004744' => 'BEAUTY HEART RADISHES',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        if (!class_exists('InstaFileV3')) {
            include(__DIR__ . '/InstaFileV3.php');
        }
        /*
        $csvfile = tempnam(sys_get_temp_dir(), 'ICT');
        $insta = new InstaFileV3($dbc, $this->config);
        $insta->getFile($csvfile);
        $datafile = fopen($csvfile, 'r');
         */
        $userfile = fopen('/tmp/pickupUser.csv', 'w');
        $itemfile = fopen('/tmp/pickupItem.csv', 'w');
        $userP = $dbc->prepare("SELECT
            upc, description, brand, sizing, photo, long_text, 1 AS enableOnline, soldOut, signCount, narrow
            FROM productUser WHERE upc=?");
        $itemP = $dbc->prepare("SELECT
            upc, description, brand, formatted_name, normal_price, pricemethod, groupprice, quantity,
            special_price, specialpricemethod, specialgroupprice, specialquantity, special_limit, start_date, end_date,
            department, size, tax, foodstamp, scale, scaleprice, mixmatchcode, created, modified, batchID,
            tareweight, discount, discounttype, line_item_discountable, unitofmeasure, wicable, qttyEnforced,
            idEnforced, cost, special_cost, received_cost, 1 AS inUse, numflag, subdept, deposit, local,
            store_id, default_vendor_id, current_origin_id, auto_par, price_rule_id, last_sold, id
            FROM products WHERE upc=? AND store_id=1"); 
        $upcR = $dbc->query("SELECT upc, enabled FROM PickupEnabled");
        //while (!feof($datafile)) {
        while ($upcW = $dbc->fetchRow($upcR)) {
            /*
            $line = fgetcsv($datafile);
            if (!is_numeric($line[0])) {
                continue;
            }
            $upc = $line[0];
            if (strlen($upc) > 7 && substr($upc,0,1) != '2' && substr($upc, -6) != '000000') {
                $upc = substr($upc, 0, strlen($upc) - 1);
            } elseif (strlen($upc) == 5 && (substr($upc, 0, 2) == '94' || substr($upc,0,2) == '93')) {
                $upc = substr($upc, -4);
            }
            $upc = BarcodeLib::padUPC($upc);
            if (isset($this->skipUPCs[$upc])) {
                continue;
            }
             */
            $upc = $upcW['upc'];
            $user = $dbc->getRow($userP, array($upc));
            if (!$upcW['enabled']) {
                $user[6] = 0;
                $user[7] = 1;
            }
            if ($user != false) {
                $keys = array_keys($user);
                foreach ($keys as $k) {
                    if (!is_numeric($k)) {
                        unset($user[$k]);
                    }
                }
                $user[1] = str_replace("\r", "", $user[1]);
                $user[1] = str_replace("\n", "", $user[1]);
                fputcsv($userfile, $user);
            }
            $item = $dbc->getRow($itemP, array($upc));
            if ($item != false) {
                if ($item['department'] == 39) {
                    continue; // skip frozen desserts
                }
                $keys = array_keys($item);
                foreach ($keys as $k) {
                    if (!is_numeric($k)) {
                        unset($item[$k]);
                    }
                }
                fputcsv($itemfile, $item);
            } else {
                echo $upc . " - " . $line[0] . "\n";
            }
        }
        unset($dbc);

        include(__DIR__ . '/../../../src/Credentials/OutsideDB.tunneled.php');
        $dbc->query('TRUNCATE TABLE products');
        $dbc->query("LOAD DATA LOCAL INFILE '/tmp/pickupItem.csv'
            INTO TABLE products
            FIELDS TERMINATED BY ','
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\\n'");

        $dbc->query('TRUNCATE TABLE productUser');
        $dbc->query("LOAD DATA LOCAL INFILE '/tmp/pickupUser.csv'
            INTO TABLE productUser
            FIELDS TERMINATED BY ','
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\\n'");

        $dbc->query("DELETE FROM localtemptrans WHERE trans_type='I'
            AND upc NOT IN (SELECT upc FROM productUser WHERE enableOnline=1)");
        
        /*
        unlink($csvfile);
         */
    }
}

