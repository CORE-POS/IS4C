<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!function_exists("pDataConnect")) include($CORE_PATH."lib/connect.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

/**
 @file
 @brief Functions dealing with the globalvalues table
 @deprecated See Database
*/

/**
  Read globalvalues settings into $CORE_LOCAL.
*/
function loadglobalvalues() {
	global $CORE_LOCAL;

	$query = "select CashierNo,Cashier,LoggedIn,TransNo,TTLFlag,
		FntlFlag,TaxExempt from globalvalues";
	$db = pDataConnect();
	$result = $db->query($query);
	$row = $db->fetch_array($result);

	$CORE_LOCAL->set("CashierNo",$row["CashierNo"]);
	$CORE_LOCAL->set("cashier",$row["Cashier"]);
	$CORE_LOCAL->set("LoggedIn",$row["LoggedIn"]);
	$CORE_LOCAL->set("transno",$row["TransNo"]);
	$CORE_LOCAL->set("ttlflag",$row["TTLFlag"]);
	$CORE_LOCAL->set("fntlflag",$row["FntlFlag"]);
	$CORE_LOCAL->set("TaxExempt",$row["TaxExempt"]);

	$db->close();
}

/**
  Set new value in $CORE_LOCAL.
  @param $param keycode
  @param $val new value
*/
function loadglobalvalue($param,$val){
	global $CORE_LOCAL;
	switch(strtoupper($param)){
	case 'CASHIERNO':
		$CORE_LOCAL->set("CashierNo",$val);	
		break;
	case 'CASHIER':
		$CORE_LOCAL->set("cashier",$val);
		break;
	case 'LOGGEDIN':
		$CORE_LOCAL->set("LoggedIn",$val);
		break;
	case 'TRANSNO':
		$CORE_LOCAL->set("transno",$val);
		break;
	case 'TTLFLAG':
		$CORE_LOCAL->set("ttlflag",$val);
		break;
	case 'FNTLFLAG':
		$CORE_LOCAL->set("fntlflag",$val);
		break;
	case 'TAXEXEMPT':
		$CORE_LOCAL->set("TaxExempt",$val);
		break;
	}
}

/**
  Update setting in globalvalues table.
  @param $param keycode
  @param $value new value
*/
function setglobalvalue($param, $value) {
	global $CORE_LOCAL;

	$db = pDataConnect();
	
	if (!is_numeric($value)) {
		$value = "'".$value."'";
	}
	
	$strUpdate = "update globalvalues set ".$param." = ".$value;

	$db->query($strUpdate);
	$db->close();
}

/**
  Update many settings in globalvalues table
  and in $CORE_LOCAL
  @param $arr An array of keys and values
*/
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

/**
  Sets TTLFlag and FntlFlag in globalvalues table
  @param $value value for both fields.
*/
function setglobalflags($value) {
	$db = pDataConnect();

	$db->query("update globalvalues set TTLFlag = ".$value.", FntlFlag = ".$value);
	$db->close();
}

?>
