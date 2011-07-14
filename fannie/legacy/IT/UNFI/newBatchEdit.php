<?php
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
$mysql = new SQLManager('mysql.wfco-op.store','MYSQL','IS4C','root');

$unfi_cats = "(";
$catQ = "select unfi_cat from unfi_cat where buyer=".$_POST["buyer"];
$catR = $mysql->query($catQ,$mydb);
while($catW = $mysql->fetch_array($catR))
	$unfi_cats .= $catW[0].",";
$unfi_cats = substr($unfi_cats,0,strlen($unfi_cats)-1).")";

$itemsQ = "select upc,description,price from newItemsBatchList
	  where batchID=".$_POST["batchID"]." and unfi_category in ".$unfi_cats;
$itemsR = $sql->query($itemsQ,$db);

echo "<table border=1 cellspacing=0 cellpadding=3><tr>";
echo "<th>UPC</th><th>Description</th><th>Price</th><th>Department</th><th>Ignore</th></tr>";
$colors = array('#FFFFCC','#FFFFFF');
$c = 0;
while ($itemsW = $sql->fetch_array($itemsR)){
	echo "<tr>";
	echo "<td bgcolor=$colors[$c]><input type=hidden name=upc[] value=$itemsW[0] />$itemsW[0]</td>";
	echo "<td bgcolor=$colors[$c]><input type=text name=description[] value=\"$itemsW[1]\" /></td>";
	echo "<td bgcolor=$colors[$c]><input type=text size=5 name=price[] value=$itemsW[2]\" /></td>";
	echo "<td bgcolor=$colors[$c]><input type=text size=5 name=department[] /></td>";
	echo "<td bgcolor=$colors[$c]><input type=checkbox name=ignore[] /></td>";
	echo "</tr>";
	$c = ($c+1)%2;
}
echo "</table>";

?>
