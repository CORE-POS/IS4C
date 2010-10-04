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

if (!function_exists("addcomment")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/additem.php");
if (!function_exists("array_to_json")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/array_to_json.php");
if (!function_exists("paycard_reset")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

// send the request
$result = 0; // 0 is never returned, so we use it to make sure it changes
$myObj = 0;
$json = array();
$json['main_frame'] = '/gui-modules/paycardSuccess.php';
$json['receipt'] = false;
foreach($IS4C_LOCAL->get("RegisteredPaycardClasses") as $rpc){
	if (!class_exists($rpc)) include_once($_SERVER["DOCUMENT_ROOT"]."/cc-modules/$rpc.php");
	$myObj = new $rpc();
	if ($myObj->handlesType($IS4C_LOCAL->get("paycard_type"))){
		break;
	}
}

$result = $myObj->doSend($IS4C_LOCAL->get("paycard_mode"));
if ($result == PAYCARD_ERR_OK){
	paycard_wipe_pan();
	$json = $myObj->cleanup($json);
	$IS4C_LOCAL->set("strRemembered","");
	$IS4C_LOCAL->set("msgrepeat",0);
}
else {
	paycard_reset();
	$IS4C_LOCAL->set("msgrepeat",0);
	$json['main_frame'] = '/gui-modules/boxMsg2.php';
}

echo array_to_json($json);
