<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'item/pricePerOunce.php');

$buyID = (isset($_POST['buyID']))?$_POST['buyID']:0;
$buyer = "All";
if ($buyID == 99){
	$buyID=0;
}
else if ($buyID != 0){
	$getBuyerQ = "SELECT super_name from superDeptNames where superID = $buyID";
	$getBuyerR = $dbc->query($getBuyerQ);
	$getBuyerW = $dbc->fetch_array($getBuyerR);
	$buyer = $getBuyerW['super_name'];
}
$date = date('mjY');
$batchName = "priceUpdate".$buyer.$date;

$insBatchQ = "INSERT INTO batches (startDate,endDate,batchName,batchType,discounttype) 
              VALUES(".$dbc->now().",".$dbc->now().",'$batchName',4,0)";
$insBatchR = $dbc->query($insBatchQ);

$page_title = "Fannie : Review Tags";
$header = "Shelftags Created";
include($FANNIE_ROOT.'src/header.html');

echo "<b>".$buyer."</b><br>";
echo "<table border=1 cellspacing=0 cellpadding=0><th>UPC<th><font color=blue>Description</font><th>SKU<th>Brand<th>Pack<th>Size<th>Price";
echo "<form action=newBarBatch.php method=Post>";
foreach ($_POST["pricechange"] as $value) {
      $getUNFIPriceQ = "SELECT * FROM unfi_all where upc = '".$value."'";
      //echo $getUNFIPriceQ . "<br>";
      $getUNFIPriceR = $dbc->query($getUNFIPriceQ,$db);
      $getUNFIPriceW = $dbc->fetch_array($getUNFIPriceR);
      $upc = $getUNFIPriceW['upc'];
      $upcl = ltrim($getUNFIPriceW['upc'],0);
      $upcl = str_pad($upcl,10,"0",STR_PAD_LEFT);
      //$upc = str_pad($upc,11,0,STR_PAD_LEFT);
      $unfiPrice  = $getUNFIPriceW['wfc_srp'];
      
      $maxBatchQ = "SELECT max(batchID) as maxID FROM batches";
      $maxBatchR = $dbc->query($maxBatchQ);
      $maxBatchW = $dbc->fetch_array($maxBatchR);
      $maxID = $maxBatchW['maxID'];

      $insBItemQ = "INSERT INTO batchList(upc,batchID,salePrice)
                    VALUES('$upc',$maxID,$unfiPrice)";      
      $insBItemR = $dbc->query($insBItemQ);
      //echo $insBItemQ;

      $getTagInfoQ = "SELECT * FROM unfi_order WHERE upcc LIKE '%".$upcl."%'";
      //echo $getTagInfoQ;
      $getTagInfoR = $dbc->query($getTagInfoQ);
      $getTagInfoW = $dbc->fetch_array($getTagInfoR);
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
      $insR = $dbc->query($insQ);
      
      echo "<tr><td>$upc</td><td><font color=blue><b>$desc</b></font></td><td>$sku</td>";
      echo "<td>$brand</td><td>$pak</td><td>$size</td>";
      echo "<td><font color=green><b>$unfiPrice</b></font></td><td>UNFI</td></tr>";

}
echo "</form>";
echo "</table>";
include($FANNIE_ROOT.'src/footer.html');
?>
