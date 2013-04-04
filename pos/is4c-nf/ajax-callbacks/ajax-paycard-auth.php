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

ini_set('display_errors','Off');
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

// send the request
$result = 0; // 0 is never returned, so we use it to make sure it changes
$myObj = 0;
$json = array();
$json['main_frame'] = MiscLib::base_url().'gui-modules/paycardSuccess.php';
$json['receipt'] = false;
foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
	$myObj = new $rpc();
	if ($myObj->handlesType($CORE_LOCAL->get("paycard_type"))){
		break;
	}
}

$st = MiscLib::sigTermObject();

$result = $myObj->doSend($CORE_LOCAL->get("paycard_mode"));
if ($result == PaycardLib::PAYCARD_ERR_OK){
	PaycardLib::paycard_wipe_pan();
	$json = $myObj->cleanup($json);
	$CORE_LOCAL->set("strRemembered","");
	$CORE_LOCAL->set("msgrepeat",0);
	if (is_object($st))
		$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
}
else {
	PaycardLib::paycard_reset();
	$CORE_LOCAL->set("msgrepeat",0);
	$json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
	if (is_object($st))
		$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
}

echo JsonLib::array_to_json($json);
