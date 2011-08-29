<?php

require('../../config.php');

require($FANNIE_ROOT.'auth/login.php');
require('pricePerOunce.php');

$user = checklogin();

$buyers = array('all'=>'All','jim'=>'Bulk',
		'joeu'=>'Grocery','brad'=>'Cool',
		'jillhall'=>'HBC','susans'=>'Gen Merch',
		'jesse'=>'Meat','eric'=>'Deli');

$upc=str_pad($_GET['upc'],13,0,STR_PAD_LEFT);
$clearUPC = substr($upc,-11);
$price = isset($_GET['saleprice'])?$_GET['saleprice']:'';
$batchID = isset($_GET['batchID'])?$_GET['batchID']:'';
$del = isset($_GET['delete'])?$_GET['delete']:'';

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$unfiQ = "SELECT DISTINCT * FROM vendorItems where upc = '$upc'";
//echo $unfiQ;

$unfiR = $sql->query($unfiQ);
$unfiN = $sql->num_rows($unfiR);

$prodQ = "SELECT * FROM products where upc='$upc'";
//echo $prodQ;
$prodR = $sql->query($prodQ);
$prodW = $sql->fetch_array($prodR);
if (empty($price))
  $price = $prodW['normal_price'];

if (!empty($batchID)){
  $selBListQ = "select * from batchList where upc='$upc' and batchID=$batchID";
  $selBListR = $sql->query($selBListQ);
  $selBListN = $sql->num_rows($selBListR);

  if($del == 1){
     $delBListQ = "DELETE FROM batchList WHERE upc = '$upc' AND
                  batchID = $batchID";
     $delBListR = $sql->query($delBListQ);
  }else{
        if($selBListN == 0){
           $insBItemQ = "INSERT INTO batchList(upc,batchID,salePrice)
                         VALUES('$upc',$batchID,$price)";
           //echo $insBItemQ;
           $insBItemR = $sql->query($insBItemQ);
        }else{
           $upBItemQ = "UPDATE batchList SET salePrice=$price WHERE upc = '$upc' 
                     AND batchID = $batchID";
           //echo $upBItemQ;
           $upBItemR = $sql->query($upBItemQ);
        }
  }
}

//echo $unfiN;
echo "New Shelf Tag:<br> " . str_pad($clearUPC,13,'0',STR_PAD_LEFT);
$prodExtraN = 0;
$ppo = "";
if($unfiN == 1){
   $unfiW = $sql->fetch_array($unfiR);
   $size = $unfiW['size'];
   $brand = $unfiW['brand'];
   $units = $unfiW['units'];
   $sku = $unfiW['sku'];
   $desc = $unfiW['description'];
   $ppo = pricePerOunce($price,$size);
}
else {
	$prodExtraQ = "select manufacturer,distributor from prodExtra where upc='$upc'";
	$prodExtraR = $sql->query($prodExtraQ);
	$prodExtraN = $sql->num_rows($prodExtraR);
	if ($prodExtraN == 1){
		$prodExtraW = $sql->fetch_array($prodExtraR);
		$brand = $prodExtraW[0];
		$vendor = $prodExtraW[1];
	}
}

?>
<body bgcolor='ffffcc'>
<form method='post' action='addShelfTag1.php'>
<input type='hidden' name=upc value='<?php echo $upc; ?>'>
<input type='hidden' name=batchID value='<?php echo $batchID; ?>'
<input type='hidden' name=del value='<?php echo $del; ?>'
<font color='blue'>Description</font>
<input type='text' name='description' size=27 maxlength=27
<?php
   if($unfiN == 1){
   	echo "value='".strtoupper($desc)."'";
   }else{
	echo "value='".strtoupper($prodW['description'])."'";
   }
?>
><br>
Brand: <input type='text' name='brand' size=15 maxlength=15 
<?php 
   if($unfiN == 1){
	echo "value='".strtoupper($brand)."'"; 
   }
   else if ($prodExtraN == 1)
	echo "value='$brand'";
?>
><br>
Units: <input type='text' name='units' size=10
<?php
   if($unfiN == 1){
	echo "value='".$units."'";
   }
?>
>
Size: <input type='text' name='size' size=10
<?php
   if($unfiN == 1){
   	echo "value='".$size."'";
   }
?>
><br>
PricePer: <input type=text name=ppo size=15
<?php echo "value=\"$ppo\"" ?> /><br />
Vendor: <input type='text' name='vendor' size=15
<?php
   if($unfiN == 1){
   	echo "value='UNFI'";
   }
   else if ($prodExtraN == 1)
	echo "value='$vendor'";
?>
><br>
SKU: <input type='text' name='sku' size=8
<?php
   if($unfiN == 1){
   	echo "value='".$sku."'";
   }
?>
>
Price: <font color='green' size=+1><b><?php echo $price; ?><input type='hidden' name='price' size=8 value=<?php echo $price; ?> ></b></font>
<?php 

if($del <> 1){
   echo "<input type='submit' value='New' name='submit'>";
}else{
   echo "<input type='submit' value='Delete shelf tag' name='submit'>";
}
?>
Barcode page: <select name=barcodepage>
<?php
foreach($buyers as $b){
	if (isset($buyers[$user]) && $buyers[$user] == $b)
		echo "<option selected>$b</option>";
	else
		echo "<option>$b</option>";
}
?>
</select><br />
<div style="font-size: 85%; margin-top: 10px;">
<?php
if ($user)
	echo "You're logged in as $user. <a href={$FANNIE_URL}auth/ui/loginform.php?logout=yes>Log out</a>";
else
	echo "You're not logged in. <a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/queries/addShelfTag.php?upc=$clearUPC>Log in now</a>";
?>
</div>
</form>
</body>
<?php

?>
