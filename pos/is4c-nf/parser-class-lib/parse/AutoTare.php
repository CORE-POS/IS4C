<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

if (!class_exists("Parser")) include_once($CORE_PATH."parser-class-lib/Parser.php");
if (!function_exists("addtare")) include_once($CORE_PATH."lib/additem.php");
if (!function_exists("boxMsg")) include_once($CORE_PATH."lib/drawscreen.php");
if (!function_exists("lastpage")) include_once($CORE_PATH."lib/listitems.php");
if (!function_exists("truncate2")) include_once($CORE_PATH."lib/lib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class AutoTare extends Parser {
	function check($str){
		if (substr($str,-2) == "TW"){
			$left = substr($str,0,strlen($str)-2);
			if ($left == "" || is_numeric($left))
				return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();

		$left = substr($str,0,strlen($str)-2);
		if ($left == "")
			$left = 1;	

		if (strlen($left) > 4)
			$ret['output'] = boxMsg(truncate2($left/100)." tare not supported");
		elseif ($left/100 > $CORE_LOCAL->get("weight") && $CORE_LOCAL->get("weight") > 0) 
			$ret['output'] = boxMsg("Tare cannot be<BR>greater than item weight");
		else {
			addtare($left);
			$ret['output'] = lastpage();
		}

		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>number</i>TW</td>
				<td>Set tare weight to <i>number</i></td>
			</tr>
			<tr>
				<td>TW</td>
				<td>Set tare weight 1. Same as 1TW</td>
			</tr>
			</table>";
	}

}

?>
