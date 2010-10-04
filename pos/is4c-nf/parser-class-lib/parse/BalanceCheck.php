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
if (!function_exists("chargeOK")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class BalanceCheck extends Parser {
	function check($str){
		if ($str == "BQ")
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		$ret = $this->default_json();
		chargeOK();
		$memChargeCommitted=$IS4C_LOCAL->get("availBal") - $IS4C_LOCAL->get("memChargeTotal");
		$ret['output'] = boxMsg("Member #". $IS4C_LOCAL->get("memberID")."<br />Current AR balance is " . $IS4C_LOCAL->get("balance") . "<br />Available AR balance is ".$IS4C_LOCAL->get("availBal"). "</b>","",True);
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>BQ</td>
				<td>Display store charge balance for
				currently entered member</td>
			</tr>
			</table>";
	}

}

?>
