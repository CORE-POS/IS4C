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
if (!function_exists("tender")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");

class DefaultTender extends Parser {
	function check($str){
		if (!is_numeric(substr($str,-2)) && 
		    is_numeric(substr($str,0,strlen($str)-2)))
			return True;
		return False;
	}

	function parse($str){
		$left = substr($str,0,strlen($str)-2);
		$right = substr($str,-2);
		$ret = tender($right,$left);
		return $ret;
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
				<td><b>ANYTHING</b></td>
				<td>If all else fails, assume the last
				two letters are a tender code and the
				rest is an amount</td>
			</tr>
			<tr>
				<td colspan=2><i>This module is last. Cashier training
				can ignore this completely</i></td>
			</tr>
			</table>";
	}
}

?>
