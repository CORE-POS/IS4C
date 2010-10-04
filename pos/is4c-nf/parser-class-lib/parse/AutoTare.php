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
if (!function_exists("addtare")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/additem.php");
if (!function_exists("boxMsg")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!function_exists("lastpage")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/listitems.php");
if (!function_exists("truncate2")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/lib.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

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
		global $IS4C_LOCAL;
		$ret = $this->default_json();

		$left = substr($str,0,strlen($str)-2);
		if ($left == "")
			$left = 1;	

		if (strlen($left) > 4)
			$ret['output'] = boxMsg(truncate2($left/100)." tare not supported");
		elseif ($left/100 > $IS4C_LOCAL->get("weight") && $IS4C_LOCAL->get("weight") > 0) 
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
