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
if (!function_exists("memberID")) include_once($CORE_PATH."lib/prehkeys.php");
if (!function_exists("lastpage")) include_once($CORE_PATH."lib/listitems.php");

class MemberID extends Parser {
	function check($str){
		if (substr($str,-2) == "ID")
			return True;
		return False;
	}

	function parse($str){
		if ($str == "0ID"){
			clearMember();
			$ret = array("main_frame"=>false,
				"output"=>lastpage(),
				"target"=>".baseHeight",
				"redraw_footer"=>true
			);
			return $ret;
		}
		else{
			$ret = memberID(substr($str,0,strlen($str)-2));
			return $ret;
		}
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>number</i>ID</td>
				<td>Set member <i>number</i></td>
			</tr>
			</table>";
	}
}

?>
