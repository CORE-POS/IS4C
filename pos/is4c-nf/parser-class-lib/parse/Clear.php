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
if (!function_exists("addtare")) include_once($IS4C_PATH."lib/additem.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class Clear extends Parser {
	function check($str){
		if ($str == "CL")
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL,$IS4C_PATH;

		$IS4C_LOCAL->set("msgrepeat",0);
		$IS4C_LOCAL->set("strendered","");
		$IS4C_LOCAL->set("strRemembered","");
		$IS4C_LOCAL->set("SNR",0);
		$IS4C_LOCAL->set("wgtRequested",1);
		// added by apbw 6/04/05 to correct voiding of refunded items
		$IS4C_LOCAL->set("refund",0);	
		//$IS4C_LOCAL->set("autoReprint",0);
		if ($IS4C_LOCAL->get("tare") > 0) 
			addtare(0);

		$ret = $this->default_json();
		$ret['main_frame'] = $IS4C_PATH."gui-modules/pos2.php";
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>CL</td>
				<td>Try to clear the screen of any
				messages</td>
			</tr>
			</table>";
	}

}

?>
