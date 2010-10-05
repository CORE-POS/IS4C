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
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class ScaleInput extends Parser {
	function check($str){
		if (substr($str,0,3) == "S11" ||
		    substr($str,0,4) == "S143")
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		if (substr($str,0,3) == "S11"){
			$weight = substr($str,3);
			if (is_numeric($weight) || $weight < 9999){
				$IS4C_LOCAL->set("scale",1);
				$IS4C_LOCAL->set("weight",$weight / 100);
			}
		}
		else
			$IS4C_LOCAL->set("scale",0);

		$ret = $this->default_json();
		$ret['scale'] = $str;
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>S11</td>
				<td>Catch scale's input</td>
			</tr>
			<tr>
				<td>S143</td>
				<td>Catch scale's input</td>
			</tr>
			<tr>
				<td colspan=2>These are so the scanner-scale
				can talk to IS4C. Users wouldn't use these
				key strokes</td>
			</tr>
			</table>";

	}

}

?>
