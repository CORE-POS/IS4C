<?php

$filename = '';
$shift = true;

include(__DIR__ . '/../../../../config.php');
include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');

$COL_ORDER_ID = 7;
$COL_STORE_ID = 3;
$COL_UTC_DATE = 1;
if (!$shift) {
    $COL_ORDER_ID = 6;
}

$dbc = FannieDB::get(FannieConfig::config('OP_DB'));

$del1 = $dbc->prepare("DELETE FROM " . FannieDB::fqn('dtransactions', 'trans') . "
    WHERE datetime BETWEEN ? AND ?
        AND register_no=40
        AND emp_no=1001
        AND trans_no=?
        AND store_id=?
    ");
$del2 = $dbc->prepare("DELETE FROM " . FannieDB::fqn('transarchive', 'trans') . "
    WHERE datetime BETWEEN ? AND ?
        AND register_no=40
        AND emp_no=1001
        AND trans_no=?
        AND store_id=?
    ");
$del3 = $dbc->prepare("DELETE FROM " . FannieDB::fqn('bigArchive', 'arch') . "
    WHERE datetime BETWEEN ? AND ?
        AND register_no=40
        AND emp_no=1001
        AND trans_no=?
        AND store_id=?
    ");
$del4 = $dbc->prepare("DELETE FROM " . FannieDB::fqn('dlog_15', 'trans') . "
    WHERE tdate BETWEEN ? AND ?
        AND register_no=40
        AND emp_no=1001
        AND trans_no=?
        AND store_id=?
    ");

$fp = fopen($filename, 'r');
if (!$fp) {
    echo "File not found?\m";
    return;
}
$current = false;
while (!feof($fp)) {
    $data = fgetcsv($fp);
    $orderID = $data[$COL_ORDER_ID];
    if ($orderID != $current) {
        $current = $orderID;
        if (is_numeric($orderID)) {
            $mStoreID = $data[$COL_STORE_ID];
            $storeID = $mStoreID == 1692 ? 1 : 2;
            echo $orderID . "\n";
            $utc = new DateTime($data[$COL_UTC_DATE] . ' UTC');
            $local = $utc->setTimeZone(new DateTimeZone('America/Chicago'));
            $date = $local->format('Y-m-d');
            $args = array($date, $date . ' 23:59:59', $orderID, $storeID);
            $dbc->execute($del1, $args);
            $dbc->execute($del2, $args);
            $dbc->execute($del3, $args);
            $dbc->execute($del4, $args);
        }
    }
}
fclose($fp);

$intake = new MercatoIntake($dbc);
if ($shift) {
    $intake->shift();
}
$intake->process($filename);

echo "Finished reprocessing\n";

