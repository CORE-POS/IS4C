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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

function loadglobalvalues() {
	global $IS4C_LOCAL;

	$query = "select CashierNo,Cashier,LoggedIn,TransNo,TTLFlag,
		FntlFlag,TaxExempt from globalvalues";
	$db = pDataConnect();
	$result = $db->query($query);
	$row = $db->fetch_array($result);

	$IS4C_LOCAL->set("CashierNo",$row["CashierNo"]);
	$IS4C_LOCAL->set("cashier",$row["Cashier"]);
	$IS4C_LOCAL->set("LoggedIn",$row["LoggedIn"]);
	$IS4C_LOCAL->set("transno",$row["TransNo"]);
	$IS4C_LOCAL->set("ttlflag",$row["TTLFlag"]);
	$IS4C_LOCAL->set("fntlflag",$row["FntlFlag"]);
	$IS4C_LOCAL->set("TaxExempt",$row["TaxExempt"]);

	$db->close();
}

function setglobalvalue($param, $value) {
	global $IS4C_LOCAL;

	$db = pDataConnect();
	
	if (!is_numeric($value)) {
		$value = "'".$value."'";
	}
	
	$laneno = $IS4C_LOCAL->get('laneno');
	
	$strUpdate = "update globalvalues set ".$param." = ".$value;
	$ccUpdate = "update globalvalues set ".$param."=".$value." WHERE lane =".$laneno;

	$db->query($strUpdate);
	$db->close();
	
	// ***** CvR 09/22/05 if CCIntegrate is on, test for PCCharge server ***** END
	if($IS4C_LOCAL->get("CCintegrate") == 1){
	   testcc();
	}
	
	// ***** CvR 09/22/05 if successful test of PCCharge database, update globalvalues table on is4cc on PCCharge server ***** END
	/*
	if($_SESSION["ccMysql"] == 1){
	   $cn = cDataConnect(); 
	
	   $result1 = $cn->query($ccUpdate);	
	   $cn->close();
	}
	 */
}

function setglobalflags($value) {
	$db = pDataConnect();

	$db->query("update globalvalues set TTLFlag = ".$value.", FntlFlag = ".$value);
	$db->close();
}

?>
