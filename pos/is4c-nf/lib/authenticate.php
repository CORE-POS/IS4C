<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
 
// session_cache_limiter('nocache');
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include($IS4C_PATH."ini.php");
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("addactivity")) include($IS4C_PATH."lib/additem.php");
if (!function_exists("memberID")) include($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("rePoll")) include($IS4C_PATH."lib/lib.php");
if (!function_exists("drawerKick")) include($IS4C_PATH."lib/printLib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

function authenticate($password,$activity=1){
	global $IS4C_LOCAL;

	$IS4C_LOCAL->set("away",1);
	rePoll();
	$IS4C_LOCAL->set("training",0);

	$password = strtoupper($password);
	$password = str_replace("'", "", $password);
	$password = str_replace(",", "", $password);
	$paswword = str_replace("+", "", $password);

	if ($password == "TRAINING") $password = 9999; // if password is training, change to '9999'

	if (!is_numeric($password)) return False; // if password is non-numeric, not a valid password
	elseif ($password > 9999 || $password < 1) return False; // if password is greater than 4 digits or less than 1, not a valid password

	$query_g = "select * from globalvalues";
	$db_g = pDataConnect();
	$result_g = $db_g->query($query_g);
	$row_g = $db_g->fetch_array($result_g);

	if ($row_g["LoggedIn"] == 0) {
		$query_q = "select emp_no, FirstName, LastName from employees where EmpActive = 1 "
			."and CashierPassword = ".$password;
		$db_q = pDataConnect();
		$result_q = $db_q->query($query_q);
		$num_rows_q = $db_q->num_rows($result_q);

		if ($num_rows_q > 0) {
			$row_q = $db_q->fetch_array($result_q);

			testremote();

			setglobalvalue("CashierNo", $row_q["emp_no"]);
			setglobalvalue("cashier", $row_q["FirstName"]." ".substr($row_q["LastName"], 0, 1).".");

			loadglobalvalues();

			$transno = gettransno($row_q["emp_no"]);
			$IS4C_LOCAL->set("transno",$transno);
			setglobalvalue("transno", $transno);
			setglobalvalue("LoggedIn", 1);

			if ($transno == 1) addactivity($activity);
			
		} elseif ($password == 9999) {
			setglobalvalue("CashierNo", 9999);
			setglobalvalue("cashier", "Training Mode");
			$transno = gettransno(9999);
			$IS4C_LOCAL->set("transno",$transno);
			setglobalvalue("transno", $transno);
			setglobalvalue("LoggedIn", 1);
			loadglobalvalues();
			$IS4C_LOCAL->set("training",1);
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

		$db_a = pDataConnect();
		$result_a = $db_a->query($query_a);	

		$num_rows_a = $db_a->num_rows($result_a);

		$db_a->db_close();

		if ($num_rows_a > 0) {

			loadglobalvalues();

			testremote();
		}
		elseif ($row_g["CashierNo"] == "9999" && $password == "9999"){
			loadglobalvalues();
			testremote();
			$IS4C_LOCAL->set("training",1);
		}
		else return False;
	}

	$db_g->db_close();
	
	// this is should really be placed more logically... andy
	customreceipt();

	if ($IS4C_LOCAL->get("LastID") != 0 && $IS4C_LOCAL->get("memberID") != "0" && $IS4C_LOCAL->get("memberID") != "") {
		$IS4C_LOCAL->set("unlock",1);
		memberID($IS4C_LOCAL->get("memberID"));
	}
	$IS4C_LOCAL->set("inputMasked",0);

	return True;
}

function nsauthenticate($password){
	global $IS4C_LOCAL;
	$IS4C_LOCAL->set("away",1);

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


/* fetch customer receipt header & footer lines
 * use to be in ini.php and on the remote DB, doesn't
 * belong on either */
function customreceipt(){
	global $IS4C_LOCAL;

	$db = pDataConnect(); 
	$headerQ = "select text from customReceipt where type='header' order by seq";
	$headerR = $db->query($headerQ);
	$IS4C_LOCAL->set("receiptHeaderCount",$db->num_rows($headerR));
	for ($i = 1; $i <= $IS4C_LOCAL->get("receiptHeaderCount"); $i++){
		$headerW = $db->fetch_array($headerR);
		$IS4C_LOCAL->set("receiptHeader$i",$headerW[0]);
	}
	$footerQ = "select text from customReceipt where type='footer' order by seq";
	$footerR = $db->query($footerQ);
	$IS4C_LOCAL->set("receiptFooterCount",$db->num_rows($footerR));

	for ($i = 1; $i <= $IS4C_LOCAL->get("receiptFooterCount"); $i++){
		$footerW = $db->fetch_array($footerR);
		$IS4C_LOCAL->set("receiptFooter$i",$footerW[0]);
	}
	
	$db->db_close();
}

?>
