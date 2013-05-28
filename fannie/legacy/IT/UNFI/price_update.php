<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/functions.php');
require($FANNIE_ROOT.'item/pricePerOunce.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$buyID = (isset($_POST['buyID']))?$_POST['buyID']:0;
$buyer = "All";
if ($buyID == 99){
	$buyID=0;
}
else if ($buyID != 0){
	$getBuyerQ = "SELECT subdept_name from subdepts where subdept_no = $buyID";

	$getBuyerR = $sql->query($getBuyerQ);
	$getBuyerW = $sql->fetch_array($getBuyerR);
	$buyer = $getBuyerW['subdept_name'];
}
$date = date('mjY');
$batchName = "priceUpdate".$buyer.$date;

$insBatchQ = "INSERT INTO batchTest(startDate,endDate,batchName,batchType,discounttype) 
              VALUES(now(),now(),'$batchName',7,0)";
//echo $insBatchQ;
$insBatchR = $sql->query($insBatchQ);

echo "<b>".$buyer."</b><br>";
echo "<html><head><title>Check tag info</title></head><body bgcolor='ffffcc'>";
echo "<table border=1 cellspacing=0 cellpadding=0><th>UPC<th><font color=blue>Description</font><th>SKU<th>Brand<th>Pack<th>Size<th>Price";
echo "<form action=newBarBatch.php method=Post>";
foreach ($_POST["pricechange"] as $value) {
      $getUNFIPriceQ = "SELECT * FROM unfi_all where upc = '".$value."'";
      //echo $getUNFIPriceQ . "<br>";
      $getUNFIPriceR = $sql->query($getUNFIPriceQ);
      $getUNFIPriceW = $sql->fetch_array($getUNFIPriceR);
      $upc = $getUNFIPriceW['upc'];
      $upcl = ltrim($getUNFIPriceW['upc'],0);
      $upcl = str_pad($upcl,10,"0",STR_PAD_LEFT);
      //$upc = str_pad($upc,11,0,STR_PAD_LEFT);
      $unfiPrice  = $getUNFIPriceW['wfc_srp'];
      
      $maxBatchQ = "SELECT max(batchID) as maxID FROM batchTest";
      $maxBatchR = $sql->query($maxBatchQ);
      $maxBatchW = $sql->fetch_array($maxBatchR);
      $maxID = $maxBatchW['maxID'];

      $insBItemQ = "INSERT INTO batchListTest(upc,batchID,salePrice)
                    VALUES('$upc',$maxID,$unfiPrice)";      
      $insBItemR = $sql->query($insBItemQ);
      //echo $insBItemQ;

      $getTagInfoQ = "SELECT * FROM unfi_order WHERE upcc LIKE '%".$upcl."%'";
      //echo $getTagInfoQ;
      $getTagInfoR = $sql->query($getTagInfoQ);
      $getTagInfoW = $sql->fetch_array($getTagInfoR);
      $desc = $getTagInfoW['item_desc'];
      $sku = $getTagInfoW['unfi_sku'];
      $brand = addslashes($getTagInfoW['brand']);
      $pak = $getTagInfoW['pack'];
      $size = $getTagInfoW['pack_size'];
      $ppo = pricePerOunce($unfiPrice,$size);
      
      if(empty($pak)){
         $pak = 0;
      }
      $insQ = "INSERT INTO shelftags VALUES(".$buyID.",'".$upc."','".$desc."',$unfiPrice,'".$brand."','".$sku."','".$size."',$pak,'UNFI','$ppo')";
      //echo $insQ . "<br>";
      $insR = $sql->query($insQ);
      
      echo "<tr><td>$upc</td><td><font color=blue><b>$desc</b></font></td><td>$sku</td>";
      echo "<td>$brand</td><td>$pak</td><td>$size</td>";
      echo "<td><font color=green><b>$unfiPrice</b></font></td><td>UNFI</td></tr>";

}
echo "</form>";
echo "</table>";

echo "<a href=/queries/labels/barcodenew.php?id=$buyID>Go to barcode page</a>";
?>
