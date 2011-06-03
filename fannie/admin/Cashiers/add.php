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
	$rd = $FANNIE_URL.'admin/Cashiers/add.php';
	header("Location: $url?redirect=$rd");
	return;
}

$page_title = "Fannie : Add Cashier";
$header = "Add Cashier";
include($FANNIE_ROOT.'src/header.html');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_REQUEST['fname'])){
	$fn = $_REQUEST['fname'];
	$ln = $_REQUEST['lname'];
	$fes = $_REQUEST['fes'];

	$passwd = '';
	srand();
	while($passwd == ''){
		$newpass = rand(1000,9999);
		$checkR = $dbc->query("SELECT * FROM employees WHERE CashierPassword=$newpass");
		if ($dbc->num_rows($checkR) == 0)
			$passwd = $newpass;
	}

	$idR = $dbc->query("SELECT max(emp_no)+1 FROM employees WHERE emp_no < 1000");
	$emp_no = array_pop($dbc->fetch_row($idR));

	$insQ = sprintf("INSERT INTO Employees (emp_no,CashierPassword,AdminPassword,FirstName,
			LastName,JobTitle,EmpActive,frontendsecurity,backendsecurity)
			VALUES (%d,%d,%d,'%s','%s','',1,%d,%d)",$emp_no,$passwd,$passwd,
			$fn,$ln,$fes,$fes);
	$dbc->query($insQ);

	printf("<blockquote>Cashier Created<br />Name:%s<br />Emp#:%d<br />Password:%d</blockquote>",
		$fn.' '.$ln,$emp_no,$passwd);
}
?>

<form action="add.php" method="post">
<table cellspacing=4 cellpadding=4 border=0>
<tr><th>First Name</th><td><input type=text name=fname /></td></tr>
<tr><th>Last Name</th><td><input type=text name=lname /></td></tr>
<tr><th>Privileges</th><td><select name=fes>
<option value=20>Regular</option>
<option value=30>Manager</option>
</select></td></tr></table>
<input type="submit" value="Create Cashier" />
</form>

<?php
include($FANNIE_ROOT.'src/footer.html');
?>
