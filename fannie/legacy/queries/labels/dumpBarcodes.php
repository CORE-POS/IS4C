<?php
if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
include('../../db.php');

$id = 0;
if (isset($_GET['id'])) $id = $_GET['id'];
$checkNoQ = $sql->prepare("SELECT * FROM shelftags where id=?");
$checkNoR = $sql->execute($checkNoQ, array($id));

$checkNoN = $sql->num_rows($checkNoR);
if($checkNoN == 0){
   echo "<body bgcolor='669933'>";
   echo "Barcode table is empty. <a href='../../../IT/batches/'>Click here to continue</a>";
}else{
   if(isset($_GET['submit']) && $_GET['submit']==1){
      echo "<body bgcolor='669933'>";
      
      $deleteQ = $sql->prepare("UPDATE shelftags SET id=-1*id WHERE id=?");
      $args = array($id);
      if ($id==0) {
          $deleteQ = $sql->prepare("UPDATE shelftags SET id=-999 WHERE id=0");
          $args = array();
      }
      $deleteR = $sql->execute($deleteQ, $args);
      echo "Barcode table cleared <a href='../../../IT/batches/'>Click here to continue</a>";
   }else{
      echo "<body bgcolor=red";
      echo "<b><a href='dumpBarcodes.php?id=$id&submit=1'>Click here to clear barcodes</a></b>";
   }
}

echo "</body>";

