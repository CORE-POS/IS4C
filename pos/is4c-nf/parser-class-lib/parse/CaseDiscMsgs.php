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
if (!function_exists("boxMsg")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class CaseDiscMsgs extends Parser {
	function check($str){
		if ($str == "cdinvalid" ||
		    $str == "cdStaffNA" ||
	    	    $str == "cdSSINA"){
			return True;
		}
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		if ($str == "cdInvalid") 
			boxMsg($IS4C_LOCAL->get("casediscount")."% case discount invalid");
		elseif ($str == "cdStaffNA") 
			boxMsg("case discount not applicable to staff");
		elseif ($str == "cdSSINA") 
			boxMsg("hit 10% key to apply case discount for member ".$IS4C_LOCAL->get("memberID"));
	
		return False;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>cdInvalid</td>
				<td>Display error message</td>
			</tr>
			<tr>
				<td>cdStaffNA</td>
				<td>Display error message</td>
			</tr>
			<tr>
				<td>cdSSINA</td>
				<td>Display instructional message</td>
			</tr>
			<tr>
				<td colspan=2><i>I'm not entirely sure
				what this one's for. It's just here
				to reproduce original pos2.php 
				functionality</td>
			</tr>
			</table>";
	}
}

?>
