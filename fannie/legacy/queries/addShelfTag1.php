<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

extract($_POST);

$buyers = array('Bulk'=>1,'Grocery'=>4,
		'Cool'=>2,'HBC'=>5,
		'Gen Merch'=>9,'Meat'=>8,
		'Deli'=>3);

$id = 0;
if ($barcodepage != "All")
	$id = $buyers[$barcodepage];

$checkUPCQ = "SELECT * FROM shelftags where upc = '$upc' AND id=$id";
$checkUPCR = $sql->query($checkUPCQ);
$checkUPCN = $sql->num_rows($checkUPCR);
//echo $del . ":<br>";
$insQ = "";
if($del == 1){
   $insQ = "DELETE FROM shelftags  where upc = '$upc'";
}elseif($checkUPCN == 0){
   $insQ = "INSERT INTO shelftags VALUES($id,'$upc','$description',$price,'$brand','$sku','$size','$units','$vendor','$ppo')";
}else{
   $insQ = "UPDATE shelftags SET normal_price = $price, pricePerUnit='$ppo' WHERE upc = '$upc'";
}

$insR = $sql->query($insQ);


//header('location=productTest.php?upc=$upc');
//echo "javascript:close()";
?>
<html>
<head>
<title>(Type a title for your page here)</title>

<SCRIPT language=JavaScript>
window.opener.location.reload();
window.close();
</SCRIPT>


</head>

</html>
