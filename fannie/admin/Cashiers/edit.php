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

$emp_no = (isset($_REQUEST['emp_no']))?$_REQUEST['emp_no']:0;

if (isset($_REQUEST['fname'])){
	$fn = $_REQUEST['fname'];
	$ln = $_REQUEST['lname'];
	$passwd = $_REQUEST['passwd'];
	$fes = $_REQUEST['fes'];
	$active = (isset($_REQUEST['active']))?1:0;

	$dbc->query("UPDATE employees SET
		FirstName='$fn',
		LastName='$ln',
		CashierPassword='$passwd',
		AdminPassword='$passwd',
		frontendsecurity=$fes,
		backendsecurity=$fes,
		EmpActive=$active
		WHERE emp_no=$emp_no");

	echo "<div align=center><i>Cashier Updated. <a href=view.php>Back to List of Cashiers</a></i></div>";
}


$infoR = $dbc->query("SELECT CashierPassword,FirstName,LastName,EmpActive,frontendsecurity
		FROM employees WHERE emp_no=$emp_no");
$info = $dbc->fetch_row($infoR);

echo "<form action=edit.php method=post>";
echo "<table cellspacing=4 cellpadding=4>";
echo "<tr><th>First Name</th><td><input type=text name=fname value=\"$info[1]\" /></td>";
echo "<th>Last Name</th><td><input type=text name=lname value=\"$info[2]\" /></td></tr>";
echo "<tr><th>Password</th><td><input type=text name=passwd value=\"$info[0]\" /></td>";
echo "<th>Privileges</th><td><select name=fes>";
if ($info[4] <= 20){
	echo "<option value=20 selected>Regular</option>";
	echo "<option value=30>Manager</option>";
}
else {
	echo "<option value=20>Regular</option>";
	echo "<option value=30 selected>Manager</option>";
}
echo "</select></td></tr>";
echo "<tr><th>Active</th><td><input type=checkbox name=active ".($info[3]==1?'checked':'')." /></td>";
echo "<td colspan=2><input type=submit value=Save /></td></tr>";
echo "<input type=hidden name=emp_no value=$emp_no />";
echo "</table></form>";

include($FANNIE_ROOT.'src/footer.html');
?>
