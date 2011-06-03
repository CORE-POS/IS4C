<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
 
// session_cache_limiter('nocache');
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

include($CORE_PATH."ini.php");
if (!function_exists("pDataConnect")) include($CORE_PATH."lib/connect.php");
if (!function_exists("addactivity")) include($CORE_PATH."lib/additem.php");
if (!function_exists("memberID")) include($CORE_PATH."lib/prehkeys.php");
if (!function_exists("rePoll")) include($CORE_PATH."lib/lib.php");
if (!function_exists("drawerKick")) include($CORE_PATH."lib/printLib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

function authenticate($password,$activity=1){
	global $CORE_LOCAL;

	$CORE_LOCAL->set("away",1);
	rePoll();
	$CORE_LOCAL->set("training",0);

	$password = strtoupper($password);
	$password = str_replace("'", "", $password);
	$password = str_replace(",", "", $password);
	$paswword = str_replace("+", "", $password);

	if ($password == "TRAINING") $password = 9999; // if password is training, change to '9999'

	if (!is_numeric($password)) return False; // if password is non-numeric, not a valid password
	elseif ($password > 9999 || $password < 1) return False; // if password is greater than 4 digits or less than 1, not a valid password

	$query_g = "select LoggedIn,CashierNo from globalvalues";
	$db_g = pDataConnect();
	$result_g = $db_g->query($query_g);
	$row_g = $db_g->fetch_array($result_g);

	if ($row_g["LoggedIn"] == 0) {
		$query_q = "select emp_no, FirstName, LastName from employees where EmpActive = 1 "
			."and CashierPassword = ".$password;
		$result_q = $db_g->query($query_q);
		$num_rows_q = $db_g->num_rows($result_q);

		if ($num_rows_q > 0) {
			$row_q = $db_g->fetch_array($result_q);

			//testremote();
			loadglobalvalues();

			$transno = gettransno($row_q["emp_no"]);
			$CORE_LOCAL->set("transno",$transno);

			$globals = array(
				"CashierNo" => $row_q["emp_no"],
				"Cashier" => $row_q["FirstName"]." ".substr($row_q["LastName"], 0, 1).".",
				"TransNo" => $transno,
				"LoggedIn" => 1
			);
			setglobalvalues($globals);

			if ($transno == 1) addactivity($activity);
			
		} elseif ($password == 9999) {
			loadglobalvalues();
			$transno = gettransno(9999);
			$CORE_LOCAL->set("transno",$transno);
			$CORE_LOCAL->set("training",1);

			$globals = array(
				"CashierNo" => 9999,
				"Cashier" => "Training Mode",
				"TransNo" => $transno,
				"LoggedIn" => 1
			);
			setglobalvalues($globals);
		}
		else return False;
	}
	else {
		// longer query but simpler. since someone is logged in already,
		// only accept password from that person OR someone with a high
		// frontendsecurity setting
		$query_a = "select emp_no, FirstName, LastName "
			."from employees "
			."where EmpActive = 1 and "
			."(frontendsecurity >= 30 or emp_no = ".$row_g["CashierNo"].") "
			."and (CashierPassword = '".$password."' or AdminPassword = '".$password."')";

		$result_a = $db_g->query($query_a);	

		$num_rows_a = $db_g->num_rows($result_a);

		if ($num_rows_a > 0) {

			loadglobalvalues();
			//testremote();
		}
		elseif ($row_g["CashierNo"] == "9999" && $password == "9999"){
			loadglobalvalues();
			//testremote();
			$CORE_LOCAL->set("training",1);
		}
		else return False;
	}

	$db_g->db_close();
	
	if ($CORE_LOCAL->get("LastID") != 0 && $CORE_LOCAL->get("memberID") != "0" && $CORE_LOCAL->get("memberID") != "") {
		$CORE_LOCAL->set("unlock",1);
		memberID($CORE_LOCAL->get("memberID"));
	}
	$CORE_LOCAL->set("inputMasked",0);

	return True;
}

function nsauthenticate($password){
	global $CORE_LOCAL;
	$CORE_LOCAL->set("away",1);

	$password = strtoupper(trim($password));
	if ($password == "TRAINING") 
		$password = 9999;

	if (!is_numeric($password)) 
		return False;
	elseif ($password > "9999" || $password < "1") 
		return False;
	elseif (empty($password))
		return False;

	$db = pDataConnect();
	$query2 = "select emp_no, FirstName, LastName from employees where empactive = 1 and "
		."frontendsecurity >= 11 and (cashierpassword = ".$password." or adminpassword = "
		.$password.")";
	$result2 = $db->query($query2);
	$num_row2 = $db->num_rows($result2);

	if ($num_row2 > 0) {
		drawerKick();
		return True;
	}
	return False;
}

?>
