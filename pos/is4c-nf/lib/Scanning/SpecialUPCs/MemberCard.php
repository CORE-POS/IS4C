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

if (!class_exists("SpecialUPC")) include($CORE_PATH."lib/Scanning/SpecialUPC.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

if (!function_exists('pDataConnect')) include($CORE_PATH."lib/connect.php");
if (!function_exists('boxMsg')) include($CORE_PATH."lib/drawscreen.php");
if (!function_exists('memberID')) include($CORE_PATH."lib/prehkeys.php");

/**
  @class MemberCard
  WFC barcoded member ID implementation

  Checks for UPC prefix 0042363
  (004, ASCII WFC)

  Looks up member number via memberCards table
*/
class MemberCard extends SpecialUPC {

	function is_special($upc){
		if (substr($upc,0,8) == "00073021")
			return true;

		return false;
	}

	function handle($upc,$json){
		global $CORE_LOCAL,$CORE_PATH;

		$db = pDataConnect();
		$query = "select card_no from memberCards where upc='$upc'";
		$result = $db->query($query);

		if ($db->num_rows($result) < 1){
			$json['output'] = boxMsg("Card not assigned");
			return $json;
		}

		$row = $db->fetch_array($result);
		$CORE_LOCAL->set("memberCardUsed",1);
		$json = memberID($row[0]);
		return $json;
	}
}

?>
