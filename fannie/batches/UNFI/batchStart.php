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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$res = $dbc->query("select superID,super_name from MasterSuperDepts
	WHERE superID > 0
	group by superID,super_name");
$opts = "<option value=99 selected>All</option>";
while($row = $dbc->fetch_row($res))
	$opts .= "<option value=$row[0]>$row[1]</option>";

$res = $dbc->query("SELECT vendorID,vendorName FROM vendors");
$vopts = "";
while($w = $dbc->fetch_row($res))
	$vopts .= "<option value=$w[0]>$w[1]</option>";

$page_title = "Fannie : Create Price Change Batch";
$header = "Create Price Change Batch";
include($FANNIE_ROOT.'src/header.html');
?>
<body>Select a vendor &amp; a department:
<form action=batchTool.php method=post>
<select name=vid>
<?php echo $vopts; ?>
</select>
<br />
<select name=super>
<?php echo $opts; ?>
</select>
<br>
Show all items <select name=filter>
<option>No</option>
<option>Yes</option>
</select>
<br />
<input type=submit value=Continue name=select>
</form>
<?php include($FANNIE_ROOT.'src/footer.html'); ?>
