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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("SpecialUPC")) include($IS4C_PATH."lib/Scanning/SpecialUPC.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!function_exists('pDataConnect')) include($IS4C_PATH."lib/connect.php");
if (!function_exists('boxMsg')) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists('memberID')) include($IS4C_PATH."lib/prehkeys.php");

class MemberCard extends SpecialUPC {

	function is_special($upc){
		if (substr($upc,0,7) == "0042363")
			return true;

		return false;
	}

	function handle($upc,$json){
		global $IS4C_LOCAL,$IS4C_PATH;

		$db = pDataConnect();
		$query = "select card_no from memberCards where upc='$upc'";
		$result = $db->query($query);

		if ($db->num_rows($result) < 1){
			if ($IS4C_LOCAL->get("standalone") == 1){
				$json['output'] = boxMsg("Can't assign new cards<br />in standalone");
			}
			else {
				$json['main_frame'] = $IS4C_PATH.'gui-modules/AssignMemCard.php?upc='.$upc;
			}
			return $json;
		}

		$row = $db->fetch_array($result);
		$IS4C_LOCAL->set("memberCardUsed",1);
		$json = memberID($row[0]);
		return $json;
	}
}

?>
