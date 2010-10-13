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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("Parser")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!function_exists("percentDiscount")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("boxMsg")) include_once($IS4C_PATH."lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class StackableDiscount extends Parser {
	var $ret;
	function check($str){
		global $IS4C_LOCAL;
		$this->ret = $this->default_json();
		if (substr($str,-2) == "SD"){
			$strl = substr($str,0,strlen($str)-2);
			if (!is_numeric($strl)) 
				return False;
			elseif ($IS4C_LOCAL->get("tenderTotal") != 0) 
				$this->ret['output'] = boxMsg("discount not applicable after tender");
			elseif ($strl > 50) 
				$this->ret['output'] = boxMsg("discount exceeds maximum");
			elseif ($strl <= 0) 
				$this->ret['output'] = boxMsg("discount must be greater than zero");
			elseif ($strl <= 50 and $strl > 0) {
				$existingPD = $IS4C_LOCAL->get("percentDiscount");
				$stackablePD = $strl;
				$equivalentPD = ($existingPD + $stackablePD);								//	sum discounts
				$this->ret = percentDiscount($equivalentPD,$this->ret);
			}
			else 
				return False;
			return True;
		}
		return False;
	}

	function parse($str){
		return $this->ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>number</i>SD</td>
				<td>Add percent discount in amount
				<i>number</i></td>
			</tr>
			</table>";
	}
}

?>
