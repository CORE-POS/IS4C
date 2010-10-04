<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

if (!class_exists("Parser")) include_once($_SERVER["DOCUMENT_ROOT"]."/parser-class-lib/Parser.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class CaseDiscount extends Parser {
	
	function check($str){
		if (strstr($str,"CT"))
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		$split = explode("CT", $str);
		$remainder = "";
		if (is_numeric($split[0]) && strlen($split[0] > 1) && strlen($split[1] > 1)){
			if ($split[0] != 5 && $split[0] != 10){
				$remainder = "cdinvalid";
				$IS4C_LOCAL->set("casediscount",$split[0]);
			}
			elseif ($IS4C_LOCAL->get("isStaff") == 1)
				$remainder = "cdStaffNA";
			elseif ($IS4C_LOCAL->get("SSI") == 1)
				$remainder = "cdSSINA";
			elseif ($IS4C_LOCAL->get("isMember") == 1){
				$IS4C_LOCAL->set("casediscount",10);
				$remainder = $split[1];
			}
			elseif ($IS4C_LOCAL->get("isMember") != 1){
				$IS4C_LOCAL->set("casediscount",5);
				$remainder = $split[1];
			}
		}	
		return $remainder;	
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>discount</i>CT<i>item</i></td>
				<td>Sets case discount <i>discount</i>
				for the item. <i>Discount</i> should be a
				number, <i>item</i> can be any valid ring
				(e.g., UPC or open-ring to a department).
				</td>
			</tr>
			</table>";
	}
}

?>
