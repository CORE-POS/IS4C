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
$page_title='Fannie -  Recent Orders';
$header='Recent Order Info';
include($FANNIE_ROOT.'src/header.html');

require_once ($FANNIE_ROOT.'src/mysql_connect.php'); // Connect to the DB.

$order = isset($_REQUEST['order'])?$_REQUEST['order']:'t.upc';

$query = "SELECT t.upc,v.brand,v.description,t.inv_date,
	r.quantity as orderQty, s.quantity as soldQty,
	v.units as caseQty
	FROM InvDeliveryTotals AS t LEFT JOIN
	InvRecentOrders AS r ON t.upc=r.upc AND t.inv_date=r.inv_date
	LEFT JOIN InvRecentSales AS s
	ON t.upc=s.upc LEFT JOIN vendorItems AS v
	ON t.upc=v.upc
	ORDER BY $order";
$result = $dbc->query($query);

echo "<table cellpadding=4 cellspacing=0 border=1>";
echo "<tr><th>UPC</th><th>Brand</th><th>Description</th>
<th>Last Ordered</th><th>Qty Ordered</th><th>Qty Sold</th>
<th>Diff</th><th>Case Qty</th></tr>";
while($row = $dbc->fetch_row($result)){
	printf("<tr><td><a href=item.php?upc=%s>%s</a></td>
		<td>%s</td><td>%s</td><td>%s</td>
		<td align=center>%d</td>
		<td align=center>%d</td>
		<td align=center>%d</td>
		<td align=center>%d</td>
		</tr>",$row['upc'],$row['upc'],$row['brand'],
		$row['description'],
		array_shift(explode(' ',$row['inv_date'])),
		$row['orderQty'],
		$row['soldQty'],
		$row['orderQty'] - $row['soldQty'],
		$row['caseQty']);	
}
echo "</table>";

include($FANNIE_ROOT.'src/footer.html');
?>
