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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../../config.php');
require($FANNIE_ROOT.'src/mysql_connect.php');

$id = 0;
if (isset($_REQUEST["id"])){
	$id = $_REQUEST["id"];
}

if (isset($_POST["submit"])){
	$queries = array();
	for ($i = 0; $i < count($_POST["upc"]); $i++){
		$upc = $_POST["upc"][$i];
		$desc = "";
		if (isset($_POST["desc"][$i])) $desc = $_POST["desc"][$i];
		$price = 0;
		if (isset($_POST["price"][$i])) $price = $_POST["price"][$i];
		$brand = '';
		if (isset($_POST["brand"][$i])) $brand = $_POST["brand"][$i];
		$sku = '';
		if (isset($_POST["sku"][$i])) $sku = $_POST["sku"][$i];
		$size = '';
		if (isset($_POST["size"][$i])) $size = $_POST["size"][$i];
		$units = '';
		if (isset($_POST["units"][$i])) $units = $_POST["units"][$i];
		$vendor = '';
		if (isset($_POST["vendor"][$i])) $vendor = $_POST["vendor"][$i];
		$ppo = '';
		if (isset($_POST["ppo"][$i])) $ppo = $_POST["ppo"][$i];

		$queries[$i] = "UPDATE shelftags SET description='$desc',
				normal_price=$price,
				brand='$brand',
				sku='$sku',
				size='$size',
				units='$units',
				vendor='$vendor',
				pricePerUnit='$ppo'
				WHERE upc='$upc' and id=$id";
	}
	foreach ($queries as $q){
		$r = $dbc->query($q);
	}
	header("Location: index.php");
	return;
}

$page_title = 'Fannie - Edit Shelf Tags';
$header = 'Edit Shelf Tags';
include($FANNIE_ROOT.'src/header.html');

echo "
<style type=text/css>
.one {
	background: #ffffff;
}
.two {
	background: #ffffcc;
}
</style>";

echo "<form action=edit.php method=post>";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
echo "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th></tr>";

$class = array("one","two");
$c = 1;
$query = "select upc,description,normal_price,brand,sku,size,units,vendor,pricePerUnit from shelftags
	where id=$id order by upc";
$result = $dbc->query($query);
while ($row = $dbc->fetch_row($result)){
	echo "<tr class=$class[$c]>";
	echo "<td>$row[0]</td><input type=hidden name=upc[] value=\"$row[0]\" />";
	echo "<td><input type=text name=desc[] value=\"$row[1]\" size=25 /></td>";
	echo "<td><input type=text name=price[] value=\"$row[2]\" size=5 /></td>";
	echo "<td><input type=text name=brand[] value=\"$row[3]\" size=13 /></td>";
	echo "<td><input type=text name=sku[] value=\"$row[4]\" size=6 /></td>";
	echo "<td><input type=text name=size[] value=\"$row[5]\" size=6 /></td>";
	echo "<td><input type=text name=units[] value=\"$row[6]\" size=4 /></td>";
	echo "<td><input type=text name=vendor[] value=\"$row[7]\" size=7 /></td>";
	echo "<td><input type=text name=ppo[] value=\"$row[8]\" size=10 /></td>";
	echo "</tr>";
	$c = ($c+1)%2;
}
echo "</table>";
echo "<input type=hidden name=id value=\"$id\" />";
echo "<input type=submit name=submit value=\"Update Shelftags\" />";
echo "</form>";

include($FANNIE_ROOT.'src/footer.html');
?>
