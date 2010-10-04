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
if (!function_exists("madCoupon")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!function_exists("lastpage")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/listitems.php");

class MadCoupon extends Parser {
	function check($str){
		if ($str == "MA")
			return True;
		return False;
	}

	function parse($str){
		madCoupon();
		$ret = $this->default_json();
		$ret['output'] = lastpage();
		$ret['redraw_footer'] = True;
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>MA</td>
				<td>Add quarterly member coupon
				(WFC specific)</td>
			</tr>
			</table>";
	}

}

?>
