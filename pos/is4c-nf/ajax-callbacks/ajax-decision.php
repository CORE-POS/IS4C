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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }
 
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

$decision = isset($_REQUEST['input'])?strtoupper(trim($_REQUEST["input"])):'CL';

if ($CORE_LOCAL->get("requestType") != "" && strlen($decision) <= 0)
	$decision = "CL";

$ret = array('dest_page'=>$CORE_PATH.'gui-modules/pos2.php',
		'endorse'=>false);

if ($decision == "CL") {
	$CORE_LOCAL->set("msgrepeat",0);
	$CORE_LOCAL->set("toggletax",0);
	$CORE_LOCAL->set("chargetender",0);
	$CORE_LOCAL->set("togglefoodstamp",0);
	$CORE_LOCAL->set("endorseType","");
	$CORE_LOCAL->set("warned",0);
	$CORE_LOCAL->set("warnBoxType","");
}
elseif (strlen($decision) > 0) {

	$CORE_LOCAL->set("msgrepeat",1);
	$CORE_LOCAL->set("strRemembered",$CORE_LOCAL->get("strEntered"));
	
	if ($CORE_LOCAL->get("requestType") != ""){
		$CORE_LOCAL->set("requestMsg",$decision);
	}
}
else {
	$CORE_LOCAL->set("msgrepeat",1);
	$CORE_LOCAL->set("strRemembered",$CORE_LOCAL->get("strEntered"));

	if ($CORE_LOCAL->get("endorseType") != ""){
		$ret['endorse'] = true;
	}
}

echo JsonLib::array_to_json($ret);

?>
