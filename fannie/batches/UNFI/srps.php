<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/* SRPs are re-calculated based on the current margin or testing
   settings, which may have changed since the order was imported */

/* configuration for your module - Important */
include("../../config.php");
/* html header, including navbar */
$page_title = "Fannie - Vendor SRPs";
$header = "Recalculate SRPs from Margins";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_REQUEST['vendorID'])){

	$id = $_REQUEST['vendorID'];

	$delQ = "DELETE FROM vendorSRPs WHERE vendorID=$id";
	$delR = $dbc->query($delQ);

	$fetchQ = "select v.upc,v.cost,
		case when d.margin is not null then d.margin
		     when m.margin is not null then m.margin
		     else 0 end as margin
		from 
		vendorItems as v left join
		vendorDepartments as d
		on v.vendorID=d.vendorID
		and v.vendorDept=d.deptID
		left join products as p
		on v.upc=p.upc
		left join deptMargin as m
		on p.department=m.dept_ID
		where v.vendorID=$id
		and (d.margin is not null or m.margin is not null)";
	$fetchR = $dbc->query($fetchQ);
	while ($fetchW = $dbc->fetch_array($fetchR)){
		// calculate a SRP from unit cost and desired margin
		$srp = round($fetchW['cost'] / (1 - $fetchW['margin']),2);

		// prices should end in 5 or 9, so add a cent until that's true
		while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" and
		       substr($srp,strlen($srp)-1,strlen($srp)) != "9")
			$srp+=.01;
		$insQ = sprintf("INSERT INTO vendorSRPs VALUES (%d,%s,%f)",
			$id,$dbc->escape($fetchW['upc']),$srp);
		$insR = $dbc->query($insQ);
	}

	echo "<b>SRPs have been updated</b><br />";
	echo "<a href=index.php>Main Menu</a>";
}
else{
$q = "SELECT vendorID,vendorName FROM vendors";
$r = $dbc->query($q);
$opts = "";
while($w = $dbc->fetch_row($r))
	$opts .= "<option value=$w[0]>$w[1]</option>";
?>
<body>
<form action=srps.php method=get>
Recalculate margins for which vendor?<br />
<select name=vendorID><?php echo $opts; ?></select>
<input type=submit value="Recalculate" />
</form>

</body>
</html>
<?php
}

/* html footer */
include($FANNIE_ROOT.'src/footer.html');
?>
