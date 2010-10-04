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
 
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");
if (!function_exists('array_to_json')) include($_SERVER['DOCUMENT_ROOT'].'/lib/array_to_json.php');

$decision = isset($_REQUEST['input'])?strtoupper(trim($_REQUEST["input"])):'CL';

if ($IS4C_LOCAL->get("requestType") != "" && strlen($decision) <= 0)
	$decision = "CL";

$ret = array('dest_page'=>'/gui-modules/pos2.php',
		'endorse'=>false);

if ($decision == "CL") {
	$IS4C_LOCAL->set("msgrepeat",0);
	$IS4C_LOCAL->set("toggletax",0);
	$IS4C_LOCAL->set("chargetender",0);
	$IS4C_LOCAL->set("togglefoodstamp",0);
	$IS4C_LOCAL->set("endorseType","");
	$IS4C_LOCAL->set("warned",0);
	$IS4C_LOCAL->set("warnBoxType","");
}
elseif (strlen($decision) > 0) {

	$IS4C_LOCAL->set("msgrepeat",1);
	$IS4C_LOCAL->set("strRemembered",$IS4C_LOCAL->get("strEntered"));
	
	if ($IS4C_LOCAL->get("requestType") != ""){
		$IS4C_LOCAL->set("requestMsg",$decision);
	}
}
else {
	$IS4C_LOCAL->set("msgrepeat",1);
	$IS4C_LOCAL->set("strRemembered",$IS4C_LOCAL->get("strEntered"));

	if ($IS4C_LOCAL->get("endorseType") != ""){
		$ret['endorse'] = true;
	}
}

echo array_to_json($ret);

?>
