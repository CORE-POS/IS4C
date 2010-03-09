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

if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tDataConnect")) include("connect.php");

function loadglobalvalues() {

	$query = "select * from globalvalues";
	$db = pDataConnect();
	$result = sql_query($query, $db);
	$row = sql_fetch_array($result);

	$_SESSION["CashierNo"] = $row["CashierNo"];
	$_SESSION["cashier"] = $row["Cashier"];
	$_SESSION["LoggedIn"] = $row["LoggedIn"];
	$_SESSION["transno"] = $row["TransNo"];
	$_SESSION["ttlflag"] = $row["TTLFlag"];
	$_SESSION["fntlflag"] = $row["FntlFlag"];
	$_SESSION["TaxExempt"] = $row["TaxExempt"];

	sql_close($db);
}

function setglobalvalue($param, $value) {

	$db = pDataConnect();

	if (!is_numeric($value)) {
		$value = "'".$value."'";
	}
	
	$strUpdate = "update globalvalues set ".$param." = ".$value;
		
	sql_query($strUpdate, $db);
	sql_close($db);
}

function setglobalflags($value) {
	$db = pDataConnect();

	sql_query("update globalvalues set ttlflag = ".$value.", fntlflag = ".$value, $db);
	sql_close($db);
}

function nexttransno() {
	$next_trans_no = 1;
	
	$db = pDataConnect();

	sql_query("update globalvalues set transno = transno + 1", $db);
	$result = sql_query("select transno from globalvalues", $db);
	$num_rows = sql_num_rows($result);

	if ($num_rows != 0) {
		$row = sql_fetch_array($result);
		$next_trans_no = $row["transno"];
	}

	sql_close($db);
}

?>
