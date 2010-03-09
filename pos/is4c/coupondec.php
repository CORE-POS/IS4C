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

if (!function_exists("gohome")) include_once ("maindisplay.php");		// apbw 5/3/05 BlueSkyFix	
if (!function_exists("pdataconnect")) include_once ("connect.php");	// apbw 5/3/05 BlueSkyFix	
if (!function_exists("addcoupon")) include_once ("additem.php");		// apbw 5/3/05 BlueSkyFix

$dept = strtoupper(trim($_POST["dept"]));
$dept = str_replace(".", "", $dept);

if ($dept == "CL") {
	gohome();
}
elseif (is_numeric(substr($dept, 2))) {	// apbw 5/3/05 BlueSkyFix
	$dept = substr($dept, 2);		// apbw 5/3/05 BlueSkyFix	
	$upc = $_SESSION["couponupc"];
	$val = $_SESSION["couponamt"];

	$query = "select * from departments where dept_no = '".$dept."'";
	$db = pDataConnect();
	$result = sql_query($query, $db);
	$num_rows = sql_num_rows($result);

	if ($num_rows != 0) {
		addcoupon($upc, $dept, $val);
		gohome();
	}
	else {
		header("Location:coupondeptinvalid.php");
	}

	sql_close($db);
}
else {
	header("Location:coupondeptinvalid.php");
}




?>
