<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

echo "<form action=index.php method=get>";
echo "<b>UPC</b>: <input type=text name=upc />";
echo " <input type=submit value=Submit />";
echo "</form>";

if (isset($_GET['modified'])){
	$modified = $_GET['modified'];
	$upc = $_GET['upc'];
	$desc = $_GET['desc'];
	$price = $_GET['price'];
	$tax = $_GET['tax'];
	$fs = $_GET['fs'];
	$scale = $_GET['scale'];
	$likecode = $_GET['likecode'];
	$qty = $_GET['qty'];
	$discount = $_GET['discount'];
	$inuse = $_GET['inuse'];
	

	$checkQ = "select upc from products where upc='$upc'";
	$checkR = $sql->query($checkQ);
	if ($sql->num_rows($checkR) == 0){
		$fixQ = "insert products values ('$upc','$desc',$price,0,.0,0,.0,0,.0,0,'1900-01-01 00:00:00','1900-01-01 00:00:00',
			 $dept,0,$tax,$fs,$scale,0,now(),0,0,$discount,0,0,0,$qty,$inuse)";
		$fixR = $sql->query($fixQ);

		$checkQ2 = "select upc from prodExtra where upc='$upc'";
		$checkR2 = $sql->query($checkQ2);
		if ($sql->num_rows($checkR2) == 0){
			$fixQ2 = "insert into prodExtra values ('$upc','','',0,0,0)";
			$fixR2 = $sql->query($fixQ2);
		}
	}
	else {
		$fixQ = "update products set description='$desc',
			 normal_price = $price,
			 tax = $tax,
			 foodstamp = $fs,
			 Scale = $scale,
			 discount = $discount,
			 qttyEnforced = $qty,
			 inUse = $inuse
			 where upc='$upc'";
		$fixR = $sql->query($fixQ);
		
		if ($likecode != -1){
			$checkQ2 = "select upc from upclike where upc='$upc' and likecode=$likecode";
			$checkR2 = $sql->query($checkQ2);
			if ($sql->num_rows($checkR2) == 0){
				$fixQ2 = "insert into upclike values ('$upc',$likecode)";
				$fixR2 = $sql->query($fixQ2);
			}
		}
		else {
			$fixQ2 = "delete from upclike where upc='$upc'";
			$fixR2 = $sql->query($fixQ2);
		}
	}
}

if (isset($_GET['upc'])){
	$upc = str_pad($_GET['upc'],13,'0',STR_PAD_LEFT);
	
	$fetchQ = "select * from prodUpdate where upc='$upc' order by modified desc";
	$fetchR = $sql->query($fetchQ);
	
	echo "<table cellspacing=2 cellpadding=0 border=1>";
	echo "<tr><th>Modified</th>";
	echo "<th>Description</th><th>Price</th><th>Dept</th><th>Tax</th><th>FS</th><th>Scale</th><th>Like code</th>";
	echo "<th>Qty Force</th><th>Discount</th><th>In Use</th></tr>";
	
	while($fetchW = $sql->fetch_array($fetchR)){
		echo "<form method=get action=index.php><tr>";
		echo "<input type=hidden name=upc value=$upc />";
		echo "<td><input name=modified type=hidden value=\"{$fetchW['modified']}\" /> {$fetchW['modified']}</td>";
		echo "<td><input name=descr type=text value=\"{$fetchW['description']}\" /></td>";
		echo "<td><input name=price type=text size=4 value=\"{$fetchW['price']}\" /></td>";
		echo "<td><input name=dept type=text size=3 value=\"{$fetchW['dept']}\" /></td>";
		echo "<td><input name=tax type=text size=2 value=\"{$fetchW['tax']}\" /></td>";
		echo "<td><input name=fs type=text size=2 value=\"{$fetchW['fs']}\" /></td>";
		echo "<td><input name=scale type=text size=2 value=\"{$fetchW['scale']}\" /></td>";
		echo "<td><input name=likecode type=text size=2 value=\"{$fetchW['likecode']}\" /></td>";
		echo "<td><input name=qty type=text size=2 value=\"{$fetchW['qtyFrce']}\" /></td>";
		echo "<td><input name=discount type=text size=2 value=\"{$fetchW['discount']}\" /></td>";
		echo "<td><input name=inuse type=text size=2 value=\"{$fetchW['inUse']}\" /></td>";
		echo "<td><input type=submit value=Revert /></td>";
		echo "</tr></form>";	
	}
	echo "</table>";
}
?>
