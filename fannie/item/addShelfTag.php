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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 21Mar2013 EL Hacked FANNIE_POUNDS_AS_POUNDS until established.
	*              Use input description width 30, not 27, OK per AT.
	* 16Mar2013 Eric Lee Need to get the vendor name either from the form
	*            or from, ideally, vendors, or prodExtra.
	*            Currently the vendor name input is just text, not controlled.
	*           It would be better if it used size and unitofmeasure from the form.
	*            In update, would need a post-update shelftag create as in insertItem.php

*/

require('pricePerOunce.php');

$upc=str_pad($_GET['upc'],13,0,STR_PAD_LEFT);

require('../config.php');
require_once($FANNIE_ROOT.'src/mysql_connect.php');
$FANNIE_POUNDS_AS_POUNDS = 1;
if ( isset($FANNIE_COUNTRY) && $FANNIE_COUNTRY != 'US' ) {
	require('pricePerSI.php');
}

// EL 16Mar13 Get vendorItem and vendor data for the item being edited or that was just created.
// This favours UNFI which traditionally has vendorID 1.
//was: $unfiQ = "SELECT DISTINCT * FROM vendorItems WHERE upc = '$upc' ORDER BY vendorID";
$vendiQ = "SELECT DISTINCT vi.*, v.vendorID vid, v.vendorName
	FROM vendorItems AS vi
		LEFT JOIN vendors AS v ON v.vendorID=vi.vendorID
	WHERE vi.upc = '$upc'
	ORDER BY vi.vendorID";
$vendiR = $dbc->query($vendiQ);
$vendiN = $dbc->num_rows($vendiR);

$prodQ = "SELECT p.*, s.superID FROM products AS p
	LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
	where upc='$upc'";
$prodR = $dbc->query($prodQ);
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

if($vendiN > 0){
 // Use only the first hit.
 $vendiW = $dbc->fetch_array($vendiR);
 // Composed: "200 g"
 $size = $vendiW['size'];
 $brand = $vendiW['brand'];
 $units = $vendiW['units'];
 $sku = $vendiW['sku'];
 if ( $vendiW['vendorName'] != "" ) {
	 $vendor = $vendiW['vendorName'];
 } else {
	if ($dbc->table_exists('prodExtra')) {
		$prodExtraQ = "select distributor from prodExtra where upc='$upc'";
		$prodExtraR = $dbc->query($prodExtraQ);
		$prodExtraN = $dbc->num_rows($prodExtraR);
		if ($prodExtraN > 0){
			$prodExtraW = $dbc->fetch_array($prodExtraR);
			$vendor = $prodExtraW['distributor'];
		}
	}
 }
 if ( isset($FANNIE_COUNTRY) && $FANNIE_COUNTRY != 'US' ) {
	 $ppo = pricePerSI($price,$size, $prodW['size'], $prodW['unitofmeasure'], 'SI');
 } else {
	 $ppo = pricePerOunce($price,$size);
 }
}
else {
	if ($dbc->table_exists('prodExtra')) {
		$prodExtraQ = "select manufacturer,distributor from prodExtra where upc='$upc'";
		$prodExtraR = $dbc->query($prodExtraQ);
		$prodExtraN = $dbc->num_rows($prodExtraR);
		if ($prodExtraN == 1){
			$prodExtraW = $dbc->fetch_array($prodExtraR);
			$brand = $prodExtraW['manufacturer'];
			$vendor = $prodExtraW['distributor'];
		}
	}
}

echo "<body bgcolor='ffffcc'>";
echo "New Shelf Tag:<!-- br / --> " . $upc;
?>
<form method='post' action='addShelfTag1.php'>
<input type='hidden' name=upc value='<?php echo $upc; ?>'>
<font color='blue'>Description</font>
<input type='text' name='description' size=30 maxlength=30
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
Price: <font color='green' size=+1><b><?php echo printf("%.2f",$price); ?><input type='hidden' name='price' size=8 value=<?php echo $price; ?> ></b></font>
<?php 

echo "<input type='submit' value='New' name='submit'>";

?>
<br />Barcode page: <select name=subID>
<?php
$subsQ = "SELECT superID,super_name FROM superDeptNames";
$subsR = $dbc->query($subsQ);
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
