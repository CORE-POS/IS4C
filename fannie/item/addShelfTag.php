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

require('pricePerOunce.php');

$upc=str_pad($_GET['upc'],13,0,STR_PAD_LEFT);

require('../config.php');
require_once($FANNIE_ROOT.'src/mysql_connect.php');


$unfiQ = $dbc->prepare_statement("SELECT DISTINCT * FROM vendorItems where upc = ? ORDER BY vendorID");
//echo $unfiQ;

$unfiR = $dbc->exec_statement($unfiQ,array($upc));
$unfiN = $dbc->num_rows($unfiR);

$prodQ = $dbc->prepare_statement("SELECT p.*,s.superID FROM products AS p
	LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
	where upc=?");
//echo $prodQ;
$prodR = $dbc->exec_statement($prodQ,array($upc));
$prodW = $dbc->fetch_array($prodR);
$price = $prodW['normal_price'];
$desc = $prodW['description'];
$brand = '';
$size = '';
$units = '';
$sku = '';
$vendor = '';
$ppo = '';
$superID = $prodW['superID'];

echo "New Shelf Tag:<br> " . $upc;
$prodExtraN = 0;
if($unfiN == 1){
   $unfiW = $dbc->fetch_array($unfiR);
   $size = $unfiW['size'];
   $brand = $unfiW['brand'];
   $units = $unfiW['units'];
   $sku = $unfiW['sku'];
   $vendor = 'UNFI';
   $ppo = pricePerOunce($price,$size);
}
else if ($dbc->table_exists('prodExtra')) {
	$prodExtraQ = $dbc->prepare_statement("select manufacturer,distributor from prodExtra where upc=?");
	$prodExtraR = $dbc->exec_statement($prodExtraQ,array($upc));
	$prodExtraN = $dbc->num_rows($prodExtraR);
	if ($prodExtraN == 1){
		$prodExtraW = $dbc->fetch_array($prodExtraR);
		$brand = $prodExtraW[0];
		$vendor = $prodExtraW[1];
	}
}

?>
<body bgcolor='ffffcc'>
<form method='post' action='addShelfTag1.php'>
<input type='hidden' name=upc value='<?php echo $upc; ?>'>
<font color='blue'>Description</font>
<input type='text' name='description' size=27 maxlength=27
<?php
echo "value='".strtoupper($desc)."'";
?>
><br>
Brand: <input type='text' name='brand' size=15 maxlength=15 
<?php 
echo "value='".strtoupper($brand)."'"; 
?>
><br>
Units: <input type='text' name='units' size=10
<?php
echo "value='".$units."'";
?>
>
Size: <input type='text' name='size' size=10
<?php
echo "value='".$size."'";
?>
><br>
PricePer: <input type=text name=ppo size=15
<?php echo "value=\"$ppo\"" ?> /><br />
Vendor: <input type='text' name='vendor' size=15
<?php
echo "value='$vendor'";
?>
><br>
SKU: <input type='text' name='sku' size=8
<?php
echo "value='".$sku."'";
?>
>
Price: <font color='green' size=+1><b><?php echo $price; ?><input type='hidden' name='price' size=8 value=<?php echo $price; ?> ></b></font>
<?php 

echo "<input type='submit' value='New' name='submit'>";

?>
Barcode page: <select name=subID>
<?php
$subsQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames");
$subsR = $dbc->exec_statement($subsQ);
while($subsW = $dbc->fetch_row($subsR)){
	if ($subsW[0] == 0) $subsW[1] = 'All';
	$checked = ($subsW[0]==$superID)?'selected':'';
	echo "<option value=\"$subsW[0]\" $checked>$subsW[1]</option>";
}
?>
</select><br />
</form>
</body>
<?php

?>
