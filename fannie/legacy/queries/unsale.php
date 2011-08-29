<?php
include('../../config.php');

$upc = $_GET['upc'];

if (!isset($_GET['yes'])){
  echo "You are about to take item $upc off sale and delete it from";
  echo " any currently active sales batches.  Are you sure?<br />";
  echo "<table cellspacing=5 cellpadding=5><tr><td>";
  echo "<form action=unsale.php method=get>";
  echo "<input type=hidden name=upc value=$upc>";
  echo "<input type=hidden name=yes value=yes>";
  echo "<input type=submit value=Yes>  ";
  echo "</form>";
  echo "</td><td>";
  echo "<form action=productTest.php method=get>";
  echo "<input type=hidden name=upc value=$upc>";
  echo "<input type=submit value=No>";
  echo "</form>";
  echo "</td></tr></table>";
}
else {
  if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."sql/SQLManager.php");
  include('../db.php');
  
  // find the discount type for the selected upc
  $discountQ = "select discounttype from products where upc = '$upc'";
  $discountR = $sql->query($discountQ);
  $discountRow = $sql->fetch_array($discountR);
  $discounttype = $discountRow['discounttype'];
  
  // find the batchID(s) of active batches
  // containing the upc
  $batchIDQ = "select b.batchID from batches as b, batchList as l where
               b.batchID = l.batchID and l.upc = '$upc' and b.discounttype = $discounttype
               and datediff(dd,getdate(),b.startdate) < 1
               and datediff(dd,getdate(),b.enddate) > 0";
  echo $batchIDQ."<p />";;
  $batchIDR = $sql->query($batchIDQ);

  // if there isn't a batch putting that item on sale, then
  // i don't know what's going on.  SO DON'T CHANGE ANYTHING
  if ($sql->num_rows($batchIDR) != 0){
    // now delete the upc from the batch list(s)
    while ($row = $sql->fetch_array($batchIDR)){
      $batchID = $row['batchID'];
      $batchQ = "delete from batchList where
               upc = '$upc' and batchID = $batchID";
      echo $batchQ."<p />";
      $batchR = $sql->query($batchQ);
    }
    
    // take the item off sale in products
    $unsaleQ = "update products set start_date = 0, end_date = 0,
                discounttype = 0, special_price = 0
                where upc = '$upc'";
    echo $unsaleQ."<p />";
    $unsaleR = $sql->query($unsaleQ);
  
    // fire change to the lanes
    require('laneUpdates.php');
    updateProductAllLanes($upc);

    echo "Item <a href=productTest.php?upc=$upc>$upc</a> is no longer on sale "; 
  }
  else if (isset($_GET['force'])){
    // take the item off sale in products
    $unsaleQ = "update products set start_date = 0, end_date = 0,
                discounttype = 0, special_price = 0
                where upc = '$upc'";
    echo $unsaleQ."<p />";
    $unsaleR = $sql->query($unsaleQ);
  
    // fire change to the lanes
    require('laneUpdates.php');
    updateProductAllLanes($upc,$db);

    echo "Item <a href=productTest.php?upc=$upc>$upc</a> is no longer on sale "; 
  }
  else {
    echo "Item <a href=productTest.php?upc=$upc>$upc</a> doesn't appear to be  on sale ";
    echo "<br /><a href=unsale.php?upc=$upc&yes=yes&force=yes>Force unsale</a>";
  }
}

?>
