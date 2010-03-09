<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

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
 // session_start(); 

include_once("connect.php");
if (!function_exists("returnHome")) include("maindisplay.php");
if (!function_exists("receipt")) include("clientscripts.php");

$_SESSION["away"] = 1;

$password = strtoupper(trim($_POST["reginput"]));

if (!isset($password) || strlen($password) < 1 || $password == "CL") {
	gohome();
}
elseif (!is_numeric($password)) {
	header("Location:mgrinvalid.php");
}
elseif ($password > 9999 || $password < 1) {
	header("Location:mgrinvalid.php");
}
else {



	$query = "select emp_no, FirstName, LastName from employees where empactive = 1 and frontendsecurity >= 11 "
		."and (cashierpassword = ".$password." or adminpassword = ".$password.")";


	$db = pDataConnect();
	$result = sql_query($query, $db);
	$num_rows = sql_num_rows($result);


	if ($num_rows != 0) {
		cancelorder();
	
	}
	else {
		header("Location:mgrinvalid.php");
	}

	// sql_close($db);
}

function cancelorder() {
	$_SESSION["msg"] = 2;
	$_SESSION["plainmsg"] = "transaction cancelled";
	rePoll();
	receipt("cancelled");

	// returnHome();
}

?>