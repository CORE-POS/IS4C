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

$repeat_cmd = CoreLocal::get('strEntered');
if (isset($_REQUEST['cmd']) && !empty($_REQUEST['cmd'])) {
    $repeat_cmd = $_REQUEST['cmd'];
}

if ($decision == "CL") {
	CoreLocal::set("msgrepeat",0);
	CoreLocal::set("lastRepeat",'');
	CoreLocal::set("toggletax",0);
	CoreLocal::set("togglefoodstamp",0);
	CoreLocal::set("RepeatAgain", false);
	$ret['cleared'] = true;
} elseif (strlen($decision) > 0) {

	CoreLocal::set("msgrepeat",1);
	CoreLocal::set("strRemembered", $repeat_cmd);
} else {
	CoreLocal::set("msgrepeat",1);
	CoreLocal::set("strRemembered", $repeat_cmd);
}

echo JsonLib::array_to_json($ret);

