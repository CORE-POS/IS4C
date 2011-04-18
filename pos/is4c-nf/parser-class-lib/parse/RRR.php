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
if (!function_exists("additem")) include_once($IS4C_PATH."lib/additem.php");
if (!function_exists("pDataConnect")) include_once($IS4C_PATH."lib/connect.php");
if (!function_exists("lastpage")) include_once($IS4C_PATH."lib/listitems.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class RRR extends Parser {
	function check($str){
		if ($str == "RRR" || substr($str,-4)=="*RRR"){
			return True;
		}
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		$ret = $this->default_json();
		$qty = 1;
		if ($str != "RRR"){
			$split = explode("*",$str);
			if (!is_numeric($split[0])) return True;
			$qty = $split[0];
		}
		$this->add($qty);

		$ret['output'] = lastpage();

		getsubtotals();
		if ($IS4C_LOCAL->get("runningTotal") == 0){
			$IS4C_LOCAL->set("End",2);
			$ret['receipt'] = 'none';
		}
		return $ret;
	}

	// gross misuse of field!
	// quantity is getting shoved into the volume special
	// column so that basket-size stats aren't skewed
	function add($qty) {
		addItem("RRR", "$qty RRR DONATED", "I", "", "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $qty, 0, 0, 0);
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>RRR</td>
				<td>Add donated RRR card punch</td>
			</tr>
			<tr>
				<td><i>number</i>*RRR</td>
				<td>Add multiple donated punches</td>
			</tr>
			</table>";
	}

}

?>
