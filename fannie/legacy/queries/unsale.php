<?php
include('../../config.php');
include('classlib2.0/FannieAPI.php');

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
  include('../db.php');
  
  // find the discount type for the selected upc
  $model = new ProductsModel($sql);
  $model->upc($upc);
  $model->load();
  $discounttype = $model->discounttype();
  
  // find the batchID(s) of active batches
  // containing the upc
  $batchIDQ = $sql->prepare("select b.batchID from batches as b, batchList as l where
               b.batchID = l.batchID and l.upc = ? and b.discountType = ?
           AND ".$sql->now()." BETWEEN b.startDate and b.endDate");
  $batchIDR = $sql->execute($batchIDQ, array($upc, $discounttype));

  // if there isn't a batch putting that item on sale, then
  // i don't know what's going on.  SO DON'T CHANGE ANYTHING
  if ($sql->num_rows($batchIDR) != 0){
    // now delete the upc from the batch list(s)
    while ($row = $sql->fetchRow($batchIDR)){
      $batchID = $row['batchID'];
      $batchQ = $sql->prepare("delete from batchList where
               upc = ? and batchID = ?");
      echo $batchQ."<p />";
      $batchR = $sql->execute($batchQ, array($upc, $batchID));
    }
    
    // take the item off sale in products
    $model->start_date(0); 
    $model->end_date(0); 
    $model->special_price(0); 
    $model->discounttype(0); 
    $model->save();
    $model->pushToLanes();
  
    echo "Item <a href=productTest.php?upc=$upc>$upc</a> is no longer on sale "; 
  }
  else if (isset($_GET['force'])){
    // take the item off sale in products
    $model->start_date(0); 
    $model->end_date(0); 
    $model->special_price(0); 
    $model->discounttype(0); 
    $model->save();
    $model->pushToLanes();
  
    echo "Item <a href=productTest.php?upc=$upc>$upc</a> is no longer on sale "; 
  }
  else {
    echo "Item <a href=productTest.php?upc=$upc>$upc</a> doesn't appear to be  on sale ";
    echo "<br /><a href=unsale.php?upc=$upc&yes=yes&force=yes>Force unsale</a>";
  }
}

