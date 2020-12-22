<?php
/**
 * Helper script to re-import invoices if needed
 * One of the provided ID columns is subject to an 
 * integer overflow bug and stops being unique after
 * reaching 2^31
 */
include('../../../../config.php');
include('../../../../classlib2.0/FannieAPI.php');

$settings = FannieConfig::config('PLUGIN_SETTINGS');
$dbc = FannieDB::get($settings['InstaCartDB']);

$start = mktime(0, 0, 0, 6, 12, 2020);
$end = mktime(0, 0, 0, 9, 18, 2020);
while ($start < $end) {
    $date1 = date('Y-m-d', $start);
    $start = mktime(0, 0, 0, date('n', $start), date('j', $start)+1, date('Y', $start));
    $date2 = date('Y-m-d', $start);
    $filename = "tlogs/tlog_{$date1}_$date2.csv";
    echo "Processing {$filename}\n";
    $fp = fopen($filename, 'r');
    while (!feof($fp)) {
        $data = fgetcsv($fp);
        if (!is_numeric($data[0])) {
            continue;
        }
        $model = new InstaTransactionsModel($dbc);
        $model->userID($data[0]);
        $model->orderID($data[2]);
        $model->deliveryID($data[3]);
        $model->orderDate(dateToLocal($data[4]));
        $model->deliveryDate(dateToLocal($data[5]));
        $model->itemID($data[6]);
        $model->upc(fixUPC($data[7]));
        $model->quantity($data[12]);
        $model->retailPrice($data[10]);
        $model->retailTotal($data[13]);
        $model->onlinePrice($data[14]);
        $model->onlineTotal($data[15]);
        $model->tax($data[16] ? $data[16] : 0);
        $model->deposit($data[17] ? $data[17] : 0);
        $model->bagFee($data[18] ? $data[18] : 0);
        $model->total($data[19]);
        $model->cardNo($data[22] ? findOwner($dbc, $data[22]) : 11);
        $model->storeID($data[24]);
        $model->signupZip($data[1]);
        $model->deliveryZip($data[23]);
        $model->fullfillmentType(substr($data[38], 0, 1));
        $model->platform($data[40]);
        $model->isExpress($data[41] == 'TRUE' ? 1 : 0);
        $model->save();
    }
    fclose($fp);
}

$cardP = false;
function findOwner($dbc, $card)
{
    global $cardP;
        if (!$cardP) {
            $cardP = $dbc->prepare("SELECT card_no FROM " . FannieDB::fqn('memberCards', 'op') . " WHERE upc LIKE ?");
        }
        if (strlen($card) < 10) {
            return $card;
        }
        echo "Checking against $card\n";
        $suffix = $dbc->getValue($cardP, array('%' . $card));
        if ($suffix !== false) {
            var_dump($suffix);
            return $suffix;
        }
        $nocheck = substr($card, 0, strlen($card) - 1);
        echo "Also checking $nocheck\n";
        $suffix = $dbc->getValue($cardP, array('%' . $nocheck));
        if ($suffix !== false) {
            var_dump($suffix);
            return $suffix;
        }

        return $card;
    }

    function dateToLocal($str)
    {
        $stamp = strtotime($str);
        return date('Y-m-d H:i:s', $stamp);
    }

    function fixUPC($str)
    {
        $str = ltrim($str, '0');
        if (strlen($str) < 7) {
            return BarcodeLib::padUPC($str);
        } elseif ($str[0] == 2 && substr($str, -6) == '000000') {
            return BarcodeLib::padUPC($str);
        }

        return BarcodeLib::padUPC(substr($str, 0, strlen($str) - 1));
    }

