<?php

include('config.php');
include('classlib2.0/FannieAPI.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

$res = $dbc->query('
    SELECT s.upc, SUM(s.quantity) AS qty
    FROM core_shelfaudit.sa_inventory AS s
        INNER JOIN products AS p ON s.upc=p.upc AND s.storeID=p.store_id
    WHERE p.default_vendor_id = 22
        AND s.storeID=2
        AND s.clear=0
    GROUP BY s.upc');

$invP = $dbc->prepare("
    UPDATE InventoryCounts
    SET count=?
    WHERE countDate='2017-10-01'
        AND uid=550
        AND upc=?
        AND storeID=2");
while ($row = $dbc->fetchRow($res)) {
    echo $row['upc'] . "\t" . $row['qty'] . "\n";
    $args = array($row['qty'], $row['upc']);
    $dbc->execute($invP, $args);
}
