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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');

include($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('editcashiers')){
	$url = $FANNIE_URL.'auth/ui/loginform.php';
	$rd = $FANNIE_URL.'admin/Cashiers/view.php';
	header("Location: $url?redirect=$rd");
	return;
}

$page_title = "Fannie : View Cashiers";
$header = "View Cashiers";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');
?>
<script type="text/javascript">
function deleteEmp(emp_no,filter){
	if (confirm('Deleting a cashier completely removes them. If you just want to disable the login, use "Edit" instead.')){
		window.location='view.php?filter='+filter+'&emp_no='+emp_no+'&delete=yes';	
	}
}
</script>
<?php

if (isset($_REQUEST['delete']) && isset($_REQUEST['emp_no'])){
	$dbc->query(sprintf("DELETE FROM employees WHERE emp_no=%d",$_REQUEST['emp_no']));
}

$filter = 1;
if (isset($_REQUEST['filter'])) $filter = $_REQUEST['filter'];
$order = (isset($_REQUEST['order']))?$_REQUEST['order']:'emp_no';
$dir = (isset($_REQUEST['dir']))?$_REQUEST['dir']:'ASC';

echo "Showing: <select onchange=\"location='view.php?filter='+this.value;\">";
if ($filter == 1){
	echo "<option value=1 selected>Active Cashiers</option>";
	echo "<option value=0>Disabled Cashiers</option>";
}
else{
	echo "<option value=1>Active Cashiers</option>";
	echo "<option value=0 selected>Disabled Cashiers</option>";
}
echo "</select><hr />";

echo "<table cellpadding=4 cellspacing=0 border=1><tr>";
echo "<th><a href=view.php?filter=$filter&order=emp_no&dir=".($dir=='ASC'?'DESC':'ASC').">Emp#</th>";
echo "<th><a href=view.php?filter=$filter&order=FirstName&dir=".($dir=='ASC'?'DESC':'ASC').">Name</th>";
echo "<th><a href=view.php?filter=$filter&order=CashierPassword&dir=".($dir=='ASC'?'DESC':'ASC').">Password</th>";
echo "<th><a href=view.php?filter=$filter&order=frontendsecurity&dir=".($dir=='ASC'?'DESC':'ASC').">Privileges</th>";
echo "<th>&nbsp;</th><th>&nbsp;</th></tr>";
$empR = $dbc->query("SELECT emp_no,CashierPassword,FirstName,LastName,frontendsecurity
		FROM employees WHERE EmpActive=$filter 
		ORDER BY $order $dir");
while($row = $dbc->fetch_row($empR)){
	printf("<tr><td>%d</td><td>%s</td><td>%d</td><td>%s</td>",
		$row[0],$row[2].' '.$row[3],$row[1],
		($row[4]<=20)?'Regular':'Manager');
	printf("<td><a href=\"edit.php?emp_no=%d\"><img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" 
		alt=\"Edit\" border=0 /></a></td>
		<td><a href=\"\" onclick=\"deleteEmp(%d,%d); return false;\"><img alt=\"Delete\"
		src=\"{$FANNIE_URL}src/img/buttons/b_drop.png\" border=0 /></a></td></tr>",
		$row[0],$row[0],$filter);
}
echo "</table>";

include($FANNIE_ROOT.'src/footer.html');
?>
