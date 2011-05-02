<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$id = 0;
if (isset($_GET['id'])) $id = $_GET['id'];
$checkNoQ = "SELECT * FROM newbarcodes";
$checkNoR = $sql->query($checkNoQ);

$checkNoN = $sql->num_rows($checkNoR);
if($checkNoN == 0){
   echo "<body bgcolor='669933'>";
   echo "Barcode table is empty. <a href='../../../IT/batches/'>Click here to continue</a>";
}else{
   if(isset($_GET['submit']) && $_GET['submit']==1){
      echo "<body bgcolor='669933'>";
      
      $deleteQ = "DELETE FROM shelftags WHERE id=$id";
	echo $deleteQ;
      $deleteR = $sql->query($deleteQ);
      echo "Barcode table cleared <a href='../../../IT/batches/'>Click here to continue</a>";
   }else{
      echo "<body bgcolor=red";
      echo "<b><a href='dumpBarcodes.php?id=$id&submit=1'>Click here to clear barcodes</a></b>";
   }
}

echo "</body>";

?>
