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
 

include("ini/ini.php");
if(!function_exists("pconnect")) include("connect.php");
if(!function_exists("addactivity")) include("additem.php");

// set_error_handler("auth3_dataError");

$_SESSION["away"] = 1;
rePoll();

$password = strtoupper(trim($_POST["input"]));
$password = str_replace("'", "", $password);
$password = str_replace(",", "", $password);
$paswword = str_replace("+", "", $password);

if ($password == "TRAINING") $password = 9999;

if (!is_numeric($password)) header("Location:invalid3.php");
elseif ($password > 9999 || $password < 1) header("Location:invalid3.php");
else {
	$query_g = "select * from globalvalues";
	$db_g = pDataConnect();
	$result_g = sql_query($query_g, $db_g);
	$row_g = sql_fetch_array($result_g);

	if ($row_g["LoggedIn"] == 0) {
		$query_q = "select emp_no, FirstName, LastName from employees where empactive = 1 "
			."and cashierpassword = ".$password;
		$db_q = pDataConnect();
		$result_q = sql_query($query_q, $db_q);
		$num_rows_q = sql_num_rows($result_q);

		if ($num_rows_q > 0) {


			$row_q = sql_fetch_array($result_q);
			testremote();
			setglobalvalue("CashierNo", $row_q["emp_no"]);
			setglobalvalue("cashier", $row_q["FirstName"]." ".substr($row_q["LastName"], 0, 1).".");

			// loadconfigdefault();
			loadglobalvalues();

			$transno = gettransno($password);
			$_SESSION["transno"] = $transno;
			setglobalvalue("transno", $transno);
			setglobalvalue("LoggedIn", 1);

			if ($transno == 1) addactivity(1);

			addactivity(4);
			header("Location:pos2.php");
		}
		else header("Location:invalid3.php");

		sql_close($db_g);
	}
	else {

		if ($password == $row_g["CashierNo"]) {
			// loadconfigdefault();
			loadglobalvalues();
			addactivity(4);

			header("Location:pos2.php");
		}
		else {
			$query_a = "select emp_no, FirstName, LastName "
				."from employees "
				."where empactive = 1 "
				."and frontendsecurity >= 11 "
				."and (cashierpassword = ".$password." or adminpassword = ".$password.")";
			$db_a = pDataConnect();
			$result_a = sql_query($query_a, $db_a);
			$num_rows_a = sql_num_rows($result_a);

			if ($num_rows_a > 0) {
				// loadconfigdefault();
				loadglobalvalues();
				addactivity(4);
				header("Location:pos2.php");
			}
			else header("Location:invalid3.php");

			sql_close($db_a);
		}
	}

//	sql_close($db_g);
}

getsubtotals();
datareload();
/*
if ($_SESSION["LastID"] != 0 && $_SESSION["memberID"] != "0" and $_SESSION["memberID"]) {
	$_SESSION["unlock"] = 1;
	memberID($_SESSION["memberID"]);
}
*/
function datareload() {
	$query_mem = "select * from custdata where CardNo='205203'";
	$query_prod = "select * from products where upc='0000000000090'";
	$query_temp = "select * from localtemptrans";

	$db_bdat = pDataConnect();
	sql_query($query_prod, $db_bdat);
	sql_query($query_mem, $db_bdat);
	sql_close($db_bdat);

	$db_trans = tDataConnect();
	sql_query($query_temp, $db_trans);
	sql_close($db_trans);

	$_SESSION["datetimestamp"] = strftime("%Y-%m-%m/%d/%y %T",time());
}


function auth3_dataError($Type, $msg, $file, $line, $context) {


	$_SESSION["errorMsg"] = $Type." ".$msg." ".$file." ".$line." ".$context;
	if ($Type != 8) {
		$_SESSION["standalone"] = 1;
	}
}

?>
