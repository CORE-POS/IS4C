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

$upQ = $sql->prepare("INSERT INTO prodUpdate
	SELECT p.upc,description,normal_price,
	department,tax,foodstamp,scale,0,
	modified,0,qttyEnforced,discount,inUse
	FROM products as p,
	batchListTest as l
	WHERE l.upc = p.upc
	AND l.batchID = ?");
$sql->execute($upQ, array($batchID));


$all = $sql->prepare('SELECT upc FROM batchListTest WHERE batchID=?');
$all = $sql->execute($all, array($batchID));
while($row = $sql->fetch_row($all)) {
    $model = new ProductsModel($row['upc']);
    $model->pushToLanes();
}

echo "Batch $batchID has been forced";

?>

