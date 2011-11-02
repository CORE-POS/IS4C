<?php

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'item/pricePerOunce.php');

$page_title = "Fannie : Department Shelf Tags";
$header = "Department Shelf Tags";
include($FANNIE_ROOT.'src/header.html');

$deptsQ = "select dept_no,dept_name from departments order by dept_no";
$deptsR = $dbc->query($deptsQ);
$deptsList = "";

$deptSubQ = "SELECT superID,super_name FROM MasterSuperDepts
		GROUP BY superID,super_name
		ORDER BY superID";
$deptSubR = $dbc->query($deptSubQ);

$deptSubList = "";
while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
}
while ($deptsW = $dbc->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

if (isset($_REQUEST['deptStart'])){
	$q = sprintf("select p.upc,p.description,p.normal_price,
		x.manufacturer,x.distributor,v.sku,v.size,
		CASE WHEN v.units IS NULL THEN 1 ELSE v.units END as units
		FROM products as p
		left join prodExtra as x on p.upc=x.upc
		left join vendorItems as v ON p.upc=v.upc
		left join vendors as n on v.vendorID=n.vendorID
		where p.department BETWEEN %d AND %d AND (
			x.distributor=n.vendorName
			or (x.distributor='' and n.vendorName='UNFI')
			or (x.distributor is null and n.vendorName='UNFI')
			or (n.vendorName is NULL)
		)",$_REQUEST['deptStart'],$_REQUEST['deptEnd']);
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
	printf("<h3>Created tags for departments #%d through #%d</h3>",
		$_REQUEST['deptStart'],$_REQUEST['deptEnd']);
}
?>
<script type="text/javascript">
function swap(src,dst){
	var val = document.getElementById(src).value;
	document.getElementById(dst).value = val;
}
</script>
<form action="depttags.php" method="get">
<table>
<tr> 
	<td align="right"> <p><b>Department Start</b></p>
	<p><b>End</b></p></td>
	<td> <p>
	<select id=deptStartSel onchange="swap('deptStartSel','deptStart');">
	<?php echo $deptsList ?>
	</select>
	<input type=text name=deptStart id=deptStart size=5 value=1 />
	</p>
	<p>
	<select id=deptEndSel onchange="swap('deptEndSel','deptEnd');">
	<?php echo $deptsList ?>
	</select>
	<input type=text name=deptEnd id=deptEnd size=5 value=1 />
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
