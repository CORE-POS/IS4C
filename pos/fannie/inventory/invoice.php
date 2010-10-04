<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');
require_once ($FANNIE_ROOT.'src/mysql_connect.php'); // Connect to the DB.

$date = isset($_REQUEST['date'])?$_REQUEST['date']:'';
$vendor = isset($_REQUEST['vendor'])?$_REQUEST['vendor']:1;
if(isset($_REQUEST['super']) && empty($_REQUEST['super']))
	unset($_REQUEST['super']);

$vdepts = array();
if (isset($_REQUEST['vdept'])){
	foreach($_REQUEST['vdept'] as $v)
		$vdepts[$v] = $v;
}

if (!isset($_REQUEST['excel']) && !isset($_REQUEST['po'])){
	$page_title='Fannie - Invoice Info';
	$header='View Invoice';
	include($FANNIE_ROOT.'src/header.html');
	echo "<form action=invoice.php name=invform id=invform method=post>";
	echo "<div style=\"float:right;\">
	<input type=submit name=excel value=\"Save as CSV\" />
	<br />
	<input type=submit name=po value=\"Create Purchase Order\" />
	</div>";
	echo "Invoice: ";
	echo "<select name=date onchange=\"document.invform.submit()\";>";
	$res = $dbc->query("SELECT inv_date FROM InvDelivery GROUP
			BY inv_date ORDER BY inv_date DESC");
	while ($row = $dbc->fetch_row($res)){
		$tmp = explode(' ',$row[0]);
		if(empty($date)) $date = $tmp[0];
		printf("<option value=\"%s\" %s>%s</option>",
			$tmp[0],
			($tmp[0]==$date?'selected':''),
			$tmp[0]);
	}
	echo "</select>";
	echo "<br /><br />";
	echo "Filters: ";
	echo "<select name=super >";
	echo "<option value=\"\">Any</option>";
	$res = $dbc->query("SELECT superID,super_name FROM
		MasterSuperDepts GROUP BY
		superID,super_name ORDER BY
		superID");
	while($row=$dbc->fetch_row($res)){
		printf("<option value=%d %s>%s</option>",$row[0],
			((isset($_REQUEST['super'])&&$_REQUEST['super']==$row[0])?'selected':''),	
			$row[1]);
	}
	echo "</select>";
	echo " <input type=submit name=refilter value=\"Re-Filter\" />";

	echo "<script type=\"text/javascript\" src=\"{$FANNIE_URL}src/jquery/jquery.js\">
		</script>";
	echo "<br />";
	echo "<a href=\"\" onclick=\"\$('#cats').toggle();return false;\">Vendor Categories</a>";
	echo "<div id=cats style=\"display:none;\">";
	$q = "SELECT deptID,name FROM vendorDepartments WHERE vendorID=".((int)$vendor)."
		ORDER BY deptID";
	$r = $dbc->query($q);
	echo "<table>";
	$i = 0;	
	while($w = $dbc->fetch_row($r)){
		if ($i % 2 == 0){
			if ($i != 0) echo "</tr>";
			echo "<tr>";
		}
		echo "<td><input type=checkbox name=vdept[] ";
		if (isset($vdepts[$w[0]])) echo " checked ";
		echo "value=$w[0] /></td>";
		echo "<td>$w[1]</td>";
		$i++;
	}
	echo "</tr></table>";
	echo "</div>";
}

$query = "select i.upc,v.brand,
	case when p.description is null then v.description 
	else p.description end as description,
	d.vendorName,i.quantity,v.units,
	i.quantity/v.units as cases,i.price
	from invDelivery as i
	inner join vendorItems as v
	on i.upc=v.upc left join
	vendors as d on i.vendor_id=d.vendorID
	left join products as p on
	i.upc=p.upc left join
	MasterSuperDepts as m ON
	p.department=m.dept_ID
	where datediff(dd,inv_date,".$dbc->escape($date).")=0
	and vendor_id=".((int)$vendor);
if (isset($_REQUEST['super']) && !empty($_REQUEST['super'])) 
	$query .= " AND m.superID=".((int)($_REQUEST['super']));
if (count($vdepts) > 0){
	$query .= " AND v.vendorDept IN (";
	foreach($vdepts as $k=>$v)
		$query .= ((int)$v).",";
	$query = substr($query,0,strlen($query)-1).")";
}
$query .= " ORDER BY i.upc";

if (isset($_REQUEST['po'])){
	$name = "Auto ".date("m/d/y");
	$poQ = "INSERT INTO PurchaseOrder (name,stamp) 
		VALUES ('$name',".$dbc->now().")";
	$dbc->query($poQ);

	$idQ = "SELECT MAX(id) FROM PurchaseOrder
		WHERE name='$name'";
	$idR = $dbc->query($idQ);
	$id = array_pop($dbc->fetch_row($idR));

	$result = $dbc->query($query);
	while($row = $dbc->fetch_row($result)){
		$q = sprintf("INSERT INTO PurchaseOrderItems (upc,vendor_id,
			order_id,quantity) VALUES (%s,%d,%d,%d)",
			$dbc->escape($row['upc']),$vendor,$id,$row['cases']);
		$dbc->query($q);
	}
	header("Location: purchase.php");
	exit;
}

ob_start();

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>UPC</th><th>Brand</th><th>Desc</th><th>Vendor</th>
<th>Qty</th><th>Pack</th><th>Cases</th><th>Price</th></tr>";
$result = $dbc->query($query);
while($row = $dbc->fetch_row($result)){
	printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td>
		<td>%d</td><td>%d</td><td>%d</td><td>%.2f</td></tr>",
		$row['upc'],$row['brand'],$row['description'],
		$row['vendorName'],$row['quantity'],$row['units'],
		$row['cases'],$row['price']);
}
echo "</table>";

$data = ob_get_contents();
ob_end_clean();

if (!isset($_REQUEST['excel'])){
	echo $data;
	printf("<input type=hidden name=vendor value=\"%d\" />",$vendor);
	echo "</form>";
	include($FANNIE_ROOT.'src/footer.html');
}
else {
	include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
	include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');

	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="invoice '.$date.'.csv"');

	$html = HtmlToArray($data);
	$csv = ArrayToCsv($html);
	echo $csv;
}
?>
