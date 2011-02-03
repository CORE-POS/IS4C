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

function loadglobalvalue($param,$val){
	global $IS4C_LOCAL;
	switch(strtoupper($param)){
	case 'CASHIERNO':
		$IS4C_LOCAL->set("CashierNo",$val);	
		break;
	case 'CASHIER':
		$IS4C_LOCAL->set("cashier",$val);
		break;
	case 'LOGGEDIN':
		$IS4C_LOCAL->set("LoggedIn",$val);
		break;
	case 'TRANSNO':
		$IS4C_LOCAL->set("transno",$val);
		break;
	case 'TTLFLAG':
		$IS4C_LOCAL->set("ttlflag",$val);
		break;
	case 'FNTLFLAG':
		$IS4C_LOCAL->set("fntlflag",$val);
		break;
	case 'TAXEXEMPT':
		$IS4C_LOCAL->set("TaxExempt",$val);
		break;
	}
}

function setglobalvalue($param, $value) {
	global $IS4C_LOCAL;

	$db = pDataConnect();
	
	if (!is_numeric($value)) {
		$value = "'".$value."'";
	}
	
	$strUpdate = "update globalvalues set ".$param." = ".$value;

	$db->query($strUpdate);
	$db->close();
}

function setglobalvalues($arr){
	$setStr = "";
	foreach($arr as $param => $value){
		$setStr .= $param." = ";
		if (!is_numeric($value))
			$setStr .= "'".$value."',";
		else
			$setStr .= $value.",";
		loadglobalvalue($param,$value);
	}
	$setStr = rtrim($setStr,",");

	$db = pDataConnect();
	$upQ = "UPDATE globalvalues SET ".$setStr;
	$db->query($upQ);
	$db->close();
}

function setglobalflags($value) {
	$db = pDataConnect();

	$db->query("update globalvalues set TTLFlag = ".$value.", FntlFlag = ".$value);
	$db->close();
}

?>
