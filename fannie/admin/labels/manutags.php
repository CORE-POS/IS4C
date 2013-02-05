<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'item/pricePerOunce.php');

$page_title = "Fannie : Manufacturer Shelf Tags";
$header = "Manufacturer Shelf Tags";
include($FANNIE_ROOT.'src/header.html');

$deptSubQ = "SELECT superID,super_name FROM MasterSuperDepts
		GROUP BY superID,super_name
		ORDER BY superID";
$deptSubR = $dbc->query($deptSubQ);

$deptSubList = "";
while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
}

if (isset($_REQUEST['manufacturer'])){
	$cond = "";
	if (is_numeric($_REQUEST['manufacturer']))
		$cond = " p.upc LIKE '%".$_REQUEST['manufacturer']."%' ";
	else
		$cond = sprintf(" x.manufacturer LIKE %s ",$dbc->escape("%".$_REQUEST['manufacturer']."%"));
	$q = "select p.upc,p.description,p.normal_price,
		x.manufacturer,x.distributor,v.sku,v.size,
		CASE WHEN v.units IS NULL THEN 1 ELSE v.units END as units
		FROM products as p
		left join prodExtra as x on p.upc=x.upc
		left join vendorItems as v ON p.upc=v.upc
		left join vendors as n on v.vendorID=n.vendorID
		where $cond AND (
			x.distributor=n.vendorName
			or (x.distributor='' and n.vendorName='UNFI')
			or (x.distributor is null and n.vendorName='UNFI')
			or (n.vendorName is NULL)
		)";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$insQ = sprintf("INSERT INTO shelftags (id,upc,description,normal_price,
			brand,sku,size,units,vendor,pricePerUnit) VALUES (%d,%s,%s,%.2f,
			%s,%s,%s,%s,%s,%s)",$_REQUEST['sID'],$dbc->escape($w['upc']),
			$dbc->escape($w['description']),$w['normal_price'],
			$dbc->escape($w['manufacturer']),$dbc->escape($w['distributor']),
			$dbc->escape($w['sku']),$dbc->escape($w['units']),
			$dbc->escape($w['size']),$dbc->escape(pricePerOunce($w['normal_price'],$w['size']))
		);
		$dbc->query($insQ);
	}
	echo "<h3>Created tags for manufacturer</h3>";
}
?>
<script type="text/javascript">
function swap(src,dst){
	var val = document.getElementById(src).value;
	document.getElementById(dst).value = val;
}
</script>
<form action="manutags.php" method="get">
<table>
<tr> 
	<td align="right"> <p><b>Name or UPC prefix</b></p></td>
	<td> 
	</p>
	<input type=text name=manufacturer />
	</p></td>
</tr>
<tr>
	<td><p><b>Page:</b> <select name="sID"><?php echo $deptSubList; ?></select></p></td>
	<td align="right"><input type="submit" value="Create Shelftags" />
</tr>
</table>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
