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
$page_title = "Fannie : Manage Departments";
$header = "Manage Departments";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
include('ajax.php');

$depts = "<option value=0>Select a department...</option>";
$depts .= "<option value=-1>Create a new department</option>";
$resp = $dbc->query("SELECT dept_no,dept_name FROM departments
			ORDER BY dept_no");
while($row = $dbc->fetch_row($resp)){
	if (isset($_REQUEST['did']) && $_REQUEST['did']==$row[0])
		$depts .= "<option value=$row[0] selected>$row[0] $row[1]</option>";
	else
		$depts .= "<option value=$row[0]>$row[0] $row[1]</option>";
}
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="dept.js" type="text/javascript"></script>
<div id="deptdiv">
<b>Department</b> <select id="deptselect" onchange="deptchange();">
<?php echo $depts ?>
</select>
</div>
<hr />
<div id="infodiv"></div>
<?php
if (isset($_REQUEST['did']))
	echo "<script type=\"text/javascript\">deptchange();</script>";
include($FANNIE_ROOT.'src/footer.html');
?>
