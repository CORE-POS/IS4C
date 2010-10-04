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

class Quantity extends Parser {
	
	function check($str){
		if (strstr($str,"*"))
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		if (!strpos($str,"**") && strpos($str,"*") != 0 &&
		    strpos($str,"*") != strlen($str)-1){
			$split = explode("*",$str);
			if (is_numeric($split[0]) &&
			   (strpos($split[1],"DP") || is_numeric($split[1]))){
				   $IS4C_LOCAL->set("quantity",$split[0]);
				   $IS4C_LOCAL->set("multiple",1);
				   $str = $split[1];
			}
		}
		return $str;
	}

	function isLast(){
		return True;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>number</i>*<i>item</i></td>
				<td>Enter <i>item</i> <i>number</i> times
				(e.g., 2*item to ring up two of the same
				item)</td>
			</tr>
			</table>";
	}
}

?>
