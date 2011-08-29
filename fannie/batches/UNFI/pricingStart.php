<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$res = $dbc->query("SELECT s.superID,super_name
		FROM unfi_order AS u INNER JOIN
		products AS p ON u.upcc = p.upc
		LEFT JOIN superdepts AS s ON
		s.dept_ID = p.department
		LEFT JOIN superDeptNames AS n
		on s.superID=n.superID
		GROUP BY s.superID,super_name");
$opts = "<option value=99 selected>All</option>";
while($row = $dbc->fetch_row($res))
	$opts .= "<option value=$row[0]>$row[1]</option>";

$page_title = "Fannie : Check UNFI Pricing";
$header = "Check UNFI Pricing";
include($FANNIE_ROOT.'src/header.html');
?>
<body>Select a buyer if you like....
<form action=price_compare.php method=post>
<select name=buyer>
<?php echo $opts; ?>
</select>
<br>
Show all items <select name=filter>
<option>No</option>
<option>Yes</option>
</select>
<br />
<input type=submit value=Onward name=select>
</form>
<?php include($FANNIE_ROOT.'src/footer.html'); ?>
