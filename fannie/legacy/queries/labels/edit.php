<?php

require('../../sql/SQLManager.php');
include('../../db.php');

$id = 0;
if (isset($_REQUEST["id"])){
	$id = $_REQUEST["id"];
}

if (isset($_POST["submit"])){
    $prep = $sql->prepare("UPDATE shelftags SET description=?,
            normal_price=?,
            brand=?,
            sku=?,
            size=?,
            units=?,
            vendor=?,
            pricePerUnit=?
            WHERE upc=? and id=?");
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

        $sql->execute($prep, array($desc, $price, $brand, $sku, $size, $units, $vendor, $ppo, $upc, $id));
	}
	header("Location: index.php");
	return;
}


echo "<html><head><title>Edit shelftags</title>
<style type=text/css>
.one {
	background: #ffffff;
}
.two {
	background: #ffffcc;
}
</style></head>";

echo "<form action=edit.php method=post>";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>UPC</th><th>Desc</th><th>Price</th><th>Brand</th><th>SKU</th>";
echo "<th>Size</th><th>Units</th><th>Vendor</th><th>PricePer</th></tr>";

$class = array("one","two");
$c = 1;
$query = $sql->prepare("select upc,description,normal_price,brand,sku,size,units,vendor,pricePerUnit from shelftags
	where id=? order by upc");
$result = $sql->execute($query, array($id));
while ($row = $sql->fetch_row($result)){
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
echo "</html>";

?>
