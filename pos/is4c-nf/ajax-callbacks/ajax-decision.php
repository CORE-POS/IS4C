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

$decision = isset($_REQUEST['input'])?strtoupper(trim($_REQUEST["input"])):'CL';

$ret = array('dest_page'=>MiscLib::base_url().'gui-modules/pos2.php',
		'endorse'=>False, 'cleared'=>False);

if ($decision == "CL") {
	$CORE_LOCAL->set("msgrepeat",0);
	$CORE_LOCAL->set("lastRepeat",'');
	$CORE_LOCAL->set("toggletax",0);
	$CORE_LOCAL->set("togglefoodstamp",0);
	$CORE_LOCAL->set("RepeatAgain", false);
	$ret['cleared'] = True;
}
elseif (strlen($decision) > 0) {

	$CORE_LOCAL->set("msgrepeat",1);
	$CORE_LOCAL->set("strRemembered",$CORE_LOCAL->get("strEntered"));
}
else {
	$CORE_LOCAL->set("msgrepeat",1);
	$CORE_LOCAL->set("strRemembered",$CORE_LOCAL->get("strEntered"));
}

echo JsonLib::array_to_json($ret);

?>
