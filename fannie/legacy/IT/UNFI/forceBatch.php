<?php
include('../../../config.php');
include('../../queries/funct1Mem.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$batchID = $_GET['batchID'];

$batchInfoQ = "SELECT * FROM batchTest WHERE batchID = $batchID";
$batchInfoR = $sql->query($batchInfoQ);

$batchInfoW = $sql->fetch_array($batchInfoR);

$forceQ = "UPDATE products AS p
		LEFT JOIN batchListTest as l
		ON l.upc=p.upc
              SET normal_price = l.salePrice,
              modified = now()
              WHERE l.batchID = $batchID";

//echo $forceQ;
$forceR = $sql->query($forceQ);

$upQ = "INSERT INTO prodUpdate
	SELECT p.upc,description,normal_price,
	department,tax,foodstamp,scale,0,
	modified,0,qttyEnforced,discount,inUse
	FROM products as p,
	batchListTest as l
	WHERE l.upc = p.upc
	AND l.batchID = $batchID";
$sql->query($upQ);

//$query1R = $sql->query($query1Q);

//$batchUpQ = "EXEC productsUpdateAll";
//$batchUpR = $sql->query($batchUpQ);

//exec("php fork.php sync products");
include($FANNIE_ROOT.'legacy/queries/laneUpdates.php');
syncProductsAllLanes();

echo "Batch $batchID has been forced";


?>

