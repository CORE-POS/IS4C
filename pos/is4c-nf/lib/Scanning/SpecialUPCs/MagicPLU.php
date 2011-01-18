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

if (!function_exists('boxMsg')) include($IS4C_PATH."lib/drawscreen.php");

class MagicPLU extends SpecialUPC {

	function is_special($upc){
		if ($upc == "0000000008005" || $upc == "0000000008006")
			return true;

		return false;
	}

	function handle($upc,$json){
		global $IS4C_LOCAL,$IS4C_PATH;

		switch(ltrim($upc,'0')){
		case '8006':
			if ($IS4C_LOCAL->get("memberID") == 0)
				$json['main_frame'] = $IS4C_PATH.'gui-modules/memlist.php';
			else if ($IS4C_LOCAL->get("msgrepeat") == 0){
				$IS4C_LOCAL->set("endorseType","stock");
				$IS4C_LOCAL->set("tenderamt",$total);
				$IS4C_LOCAL->set("boxMsg","<B>".$total." stock payment</B><BR>insert form<BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>");
				$ret["main_frame"] = $IS4C_PATH."gui-modules/boxMsg2.php";
			}
			break;
		case '8005':
			if ($IS4C_LOCAL->get("memberID") == 0)
				$json['main_frame'] = $IS4C_PATH.'gui-modules/memlist.php';
			elseif ($IS4C_LOCAL->get("isMember") == 0)
				$json['output'] = boxMsg("<br />member discount not applicable");
			elseif ($IS4C_LOCAL->get("percentDiscount") > 0)
				$json['output'] = boxMsg($IS4C_LOCAL->get("percentDiscount")."% discount already applied");
			break;	
		}

		// magic plu, but other conditions not matched
		if ($json['main_frame'] === false && empty($json['output']))
			$json['output'] = boxMsg($upc."<br />is not a valid item");

		return $json;
	}
}

?>
