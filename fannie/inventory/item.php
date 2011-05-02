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

// This script retrieves all the records from the membership table.

include('../config.php');
$page_title='Fannie - Item Inventory Info';
$header='Item Inventory Info';
include($FANNIE_ROOT.'src/header.html');

require_once ($FANNIE_ROOT.'src/mysql_connect.php'); // Connect to the DB.

$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);

$query = "select i.upc,p.description,
	CASE WHEN u.upc IS NULL then v.brand else u.brand END
	as brand,
	i.OrderedQty,i.SoldQty,
	i.Adjustments,i.LastAdjustDate,
	i.CurrentStock,v.size,v.units,
	d.vendorName
	from InvCache as i
	left join vendorItems as v
	on i.upc=v.upc
	left join vendors as d on
	v.vendorID=d.vendorID
	left join products as p
	on p.upc=i.upc
	left join productUser as u
	on i.upc=u.upc
	where i.upc=".$dbc->escape($upc);
$result = $dbc->query($query);

if ($dbc->num_rows($result) == 0){
	echo "<b>Error: no inventory info for this item</b>";
}
else {
	$row = $dbc->fetch_row($result);

	echo "<b>Product Information</b>";
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>UPC</th><td>".$row['upc']."</td>";
	echo "<th>Desc</th><td>".$row['description']."</td></tr>";
	echo "<tr><th>Brand</th><td>".$row['brand']."</td>";
	echo "<th>Vendor</th><td>".$row['vendorName']."</td></tr>";
	echo "<tr><th>Unit size</th><td>".$row['size']."</td>";
	echo "<th>Pack</th><td>".$row['units']."</td></tr>";
	echo "</table>";

	echo "<br />";

	echo "<b>Inventory</b>";
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Qty Ordered</th><th>Qty Sold</th>";
	echo "<th>Adj.</th><th>Est. Stock</th></tr>";
	echo "<tr>";
	echo "<td align=center>".$row['OrderedQty']."</td>";
	echo "<td align=center>".$row['SoldQty']."</td>";
	echo "<td align=center>".$row['Adjustments']."</td>";
	echo "<td align=center>".$row['CurrentStock']."</td>";
	echo "</tr></table>";
	echo "<i>Last adjustment: ".$row['LastAdjustDate']."</i>";

	echo "<br /><br />";

	echo "<b>Order History</b>";
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Invoice Date</th><th>Qty</th><th>Price</th>
		<th>Vendor</th></tr>";

	$query = "select inv_date,quantity,price,v.vendorName,
		i.vendor_id
		from InvDelivery as i
		left join vendors as v
		on i.vendor_id = v.vendorID
		where upc=".$dbc->escape($upc)."
		union all
		select inv_date,quantity,price,v.vendorName,
		i.vendor_id
		from InvDeliveryLM as i
		left join vendors as v
		on i.vendor_id = v.vendorID
		where upc=".$dbc->escape($upc)."
		order by inv_date desc";
	$result = $dbc->query($query);
	while($row = $dbc->fetch_row($result)){
		$tmp = explode(' ',$row['inv_date']);
		$row['inv_date'] = $tmp[0];
		printf("<tr><td><a href=invoice.php?date=%s&vendor=%d>%s</a></td>
			<td>%d</td><td>%.2f</td><td>%s</td></tr>",
			$row['inv_date'],$row['vendor_id'],
			$row['inv_date'],$row['quantity'],$row['price'],
			$row['vendorName']);
	}

	$query = "select year(inv_date),month(inv_date),quantity,price,v.vendorName
		from InvDeliveryArchive as i
		left join vendors as v
		on i.vendor_id = v.vendorID
		where upc=".$dbc->escape($upc)."
		order by inv_date desc";
	$result = $dbc->query($query);
	while($row = $dbc->fetch_row($result)){
		printf("<tr><td>%s</td><td>%d</td><td>%.2f</td><td>%s</td></tr>",
			date('M Y',mktime(0,0,0,$row[1],1,$row[0])),
			$row['quantity'],$row['price'],
			$row['vendorName']);
	}
	echo "</table>";
}

include($FANNIE_ROOT.'src/footer.html');
?>
