<?php
include('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (!isset($_GET['batchID'])) {
    return;
}

$batchID = $_GET['batchID'];

$batchInfoQ = $sql->prepare("SELECT * FROM batchTest WHERE batchID = ?");
$batchInfoR = $sql->execute($batchInfoQ, array($batchID));

$batchInfoW = $sql->fetchRow($batchInfoR);

$forceQ = $sql->prepare("UPDATE products AS p
        LEFT JOIN batchListTest as l
        ON l.upc=p.upc
              SET normal_price = l.salePrice,
              modified = now()
              WHERE l.batchID = ?");

//echo $forceQ;
$forceR = $sql->execute($forceQ, array($batchID));

$columnsP = $sql->prepare('
    SELECT p.upc,
        p.normal_price,
        p.special_price,
        p.modified,
        p.specialpricemethod,
        p.specialquantity,
        p.specialgroupprice,
        p.discounttype,
        p.mixmatchcode,
        p.start_date,
        p.end_date
    FROM products AS p
        INNER JOIN batchListTest AS b ON p.upc=b.upc
        WHERE b.batchID=?');
/**
  Get changed columns for each product record
*/
$upcs = array();
$columnsR = $sql->execute($columnsP, array($batchID));
while ($w = $sql->fetch_row($columnsR)) {
    $upcs[$w['upc']] = $w;
}
$updateQ = '
    UPDATE products AS p SET
        p.normal_price = ?,
        p.special_price = ?,
        p.modified = ?,
        p.specialpricemethod = ?,
        p.specialquantity = ?,
        p.specialgroupprice = ?,
        p.discounttype = ?,
        p.mixmatchcode = ?,
        p.start_date = ?,
        p.end_date = ?
    WHERE p.upc = ?';
/**
  Update all records on each lane before proceeding
  to the next lane. Hopefully faster / more efficient
*/
for ($i = 0; $i < count($FANNIE_LANES); $i++) {
    $lane_sql = new SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
        $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
        $FANNIE_LANES[$i]['pw']);
    
    if (!isset($lane_sql->connections[$FANNIE_LANES[$i]['op']]) || $lane_sql->connections[$FANNIE_LANES[$i]['op']] === false) {
        // connect failed
        continue;
    }

    $updateP = $lane_sql->prepare($updateQ);
    foreach ($upcs as $upc => $data) {
        $lane_sql->execute($updateP, array(
            $data['normal_price'],
            $data['special_price'],
            $data['modified'],
            $data['specialpricemethod'],
            $data['specialquantity'],
            $data['specialgroupprice'],
            $data['discounttype'],
            $data['mixmatchcode'],
            $data['start_date'],
            $data['end_date'],
            $upc,
        ));
    }
}

$update = new ProdUpdateModel($sql);
$updateType = ($batchInfoW['discountType'] == 0) ? ProdUpdateModel::UPDATE_PC_BATCH : ProdUpdateModel::UPDATE_BATCH;
$update->logManyUpdates(array_keys($upcs), $updateType);

echo "Batch $batchID has been forced";

