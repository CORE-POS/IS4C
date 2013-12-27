<?php
include('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (!isset($_GET['batchID'])) {
    exit;
}

$batchID = $_GET['batchID'];

$batchInfoQ = $sql->prepare("SELECT * FROM batchTest WHERE batchID = ?");
$batchInfoR = $sql->execute($batchInfoQ, array($batchID));

$batchInfoW = $sql->fetch_array($batchInfoR);

$forceQ = $sql->prepare("UPDATE products AS p
		LEFT JOIN batchListTest as l
		ON l.upc=p.upc
              SET normal_price = l.salePrice,
              modified = now()
              WHERE l.batchID = ?");

//echo $forceQ;
$forceR = $sql->execute($forceQ, array($batchID));

$upcQ = $sql->prepare('SELECT upc FROM batchListTest WHERE batchID=?');
$upcR = $sql->execute($upcQ, array($batchID));
$prodUpdate = new ProdUpdateModel($sql);
while($upcW = $sql->fetch_row($upcR)) {
    $prodUpdate->reset();
    $prodUpdate->upc($upcW['upc']);
    $prodUpdate->logUpdate(ProdUpdateModel::UPDATE_PC_BATCH);
}

$all = $sql->prepare('SELECT upc FROM batchListTest WHERE batchID=?');
$all = $sql->execute($all, array($batchID));
while($row = $sql->fetch_row($all)) {
    $model = new ProductsModel($row['upc']);
    $model->pushToLanes();
}

echo "Batch $batchID has been forced";

?>

