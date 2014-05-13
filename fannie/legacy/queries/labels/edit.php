<?php

include('../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
require('../../sql/SQLManager.php');
include('../../db.php');

$id = 0;
if (isset($_REQUEST["id"])){
	$id = $_REQUEST["id"];
}

if (isset($_POST["submit"])){
    $tag = new ShelftagsModel($sql);
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

        $tag->id($id);
        $tag->upc($upc);
        $tag->description($desc);
        $tag->normal_price($price);
        $tag->brand($brand);
        $tag->sku($sku);
        $tag->size($size);
        $tag->units($units);
        $tag->vendor($vendor);
        $tag->pricePerUnit($ppo);
        $tag->save();
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
$tags = new ShelftagsModel($sql);
$tags->id($id);
foreach($tags->find() as $tag) {
	echo "<tr class=$class[$c]>";
    echo "<td>" . $tag->upc() . "</td><input type=hidden name=upc[] value=\"" . $tag->upc() . "\" />";
    echo "<td><input type=text name=desc[] value=\"" . $tag->description() . "\" size=25 /></td>";
    echo "<td><input type=text name=price[] value=\"" . $tag->normal_price() . "\" size=5 /></td>";
    echo "<td><input type=text name=brand[] value=\"" . $tag->brand() . "\" size=13 /></td>";
    echo "<td><input type=text name=sku[] value=\"" . $tag->sku() . "\" size=6 /></td>";
    echo "<td><input type=text name=size[] value=\"" . $tag->size() . "\" size=6 /></td>";
    echo "<td><input type=text name=units[] value=\"" . $tag->units() . "\" size=4 /></td>";
    echo "<td><input type=text name=vendor[] value=\"" . $tag->vendor() . "\" size=7 /></td>";
    echo "<td><input type=text name=ppo[] value=\"" . $tag->pricePerUnit() . "\" size=10 /></td>";
	echo "</tr>";
	$c = ($c+1)%2;
}
echo "</table>";
echo "<input type=hidden name=id value=\"$id\" />";
echo "<input type=submit name=submit value=\"Update Shelftags\" />";
echo "</form>";
echo "</html>";

?>
