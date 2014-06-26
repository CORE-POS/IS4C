<?php
include('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
require($FANNIE_ROOT.'item/pricePerOunce.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$buyID = (isset($_POST['buyID']))?$_POST['buyID']:0;
$buyer = "All";
if ($buyID == 99){
	$buyID=0;
}
else if ($buyID != 0){
	$getBuyerQ = $sql->prepare("SELECT subdept_name from subdepts where subdept_no = ?");

	$getBuyerR = $sql->execute($getBuyerQ, array($buyID));
	$getBuyerW = $sql->fetch_array($getBuyerR);
	$buyer = $getBuyerW['subdept_name'];
}
$date = date('mjY');
$batchName = "priceUpdate".$buyer.$date;

$insBatchQ = $sql->prepare("INSERT INTO batchTest(startDate,endDate,batchName,batchType,discounttype) 
              VALUES(".$sql->now().','.$sql->now().",?,7,0)");
//echo $insBatchQ;
$insBatchR = $sql->execute($insBatchQ, array($batchName));
$maxID = $sql->insert_id();

echo "<b>".$buyer."</b><br>";
echo "<html><head><title>Check tag info</title></head><body bgcolor='ffffcc'>";
echo "<table border=1 cellspacing=0 cellpadding=0><th>UPC<th><font color=blue>Description</font><th>SKU<th>Brand<th>Pack<th>Size<th>Price";
echo "<form action=newBarBatch.php method=Post>";
$getUNFIPriceQ = $sql->prepare('SELECT upc, srp as wfc_srp FROM vendorSRPs WHERE vendorID=1 AND upc=?');
$insBItemQ = $sql->prepare("INSERT INTO batchListTest(upc,batchID,salePrice)
            VALUES(?,?,?)");
$getTagInfoQ = $sql->prepare('SELECT description as item_desc, sku as unfi_sku, brand, units as pack, size as pack_size
                            FROM vendorItems WHERE vendorID=1 AND upc LIKE ?');
$shelftag = new ShelftagsModel($sql);
foreach ($_POST["pricechange"] as $value) {
      //echo $getUNFIPriceQ . "<br>";
      $getUNFIPriceR = $sql->execute($getUNFIPriceQ, array($value));
      $getUNFIPriceW = $sql->fetch_array($getUNFIPriceR);
      $upc = $getUNFIPriceW['upc'];
      $upcl = ltrim($getUNFIPriceW['upc'],0);
      $upcl = str_pad($upcl,10,"0",STR_PAD_LEFT);
      //$upc = str_pad($upc,11,0,STR_PAD_LEFT);
      $unfiPrice  = $getUNFIPriceW['wfc_srp'];
      
      $insBItemR = $sql->execute($insBItemQ, array($upc, $maxID, $unfiPrice));
      //echo $insBItemQ;

      //echo $getTagInfoQ;
      $getTagInfoR = $sql->execute($getTagInfoQ, array('%'.$upcl.'%'));
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
      $shelftag->id($buyID);
      $shelftag->upc($upc);
      $shelftag->description($desc);
      $shelftag->normal_price($unfiPrice);
      $shelftag->brand($brand);
      $shelftag->sku($sku);
      $shelftag->units($size);
      $shelftag->size($pak);
      $shelftag->pricePerUnit($ppo);
      $shelftag->vendor('UNFI');
      $shelftag->save();
      
      echo "<tr><td>$upc</td><td><font color=blue><b>$desc</b></font></td><td>$sku</td>";
      echo "<td>$brand</td><td>$pak</td><td>$size</td>";
      echo "<td><font color=green><b>$unfiPrice</b></font></td><td>UNFI</td></tr>";

}
echo "</form>";
echo "</table>";

echo "<a href=/queries/labels/barcodenew.php?id=$buyID>Go to barcode page</a>";
?>
