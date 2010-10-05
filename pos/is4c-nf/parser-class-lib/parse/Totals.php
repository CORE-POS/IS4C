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
if (!function_exists("fsEligible")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("addcomment")) include_once($IS4C_PATH."lib/additem.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class Totals extends Parser {

	function check($str){
		if ($str == "FNTL" || $str == "TETL" ||
		    $str == "FTTL" || $str == "TL" ||
		    substr($str,0,2) == "FN")
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL,$IS4C_PATH;
		$ret = $this->default_json();
		if ($str == "FNTL"){
			$ret['main_frame'] = $IS4C_PATH.'gui-modules/fsTotalConfirm.php';
		}
		elseif ($str == "TETL"){
			if ($IS4C_LOCAL->get("requestType") == ""){
				$IS4C_LOCAL->set("requestType","tax exempt");
				$IS4C_LOCAL->set("requestMsg","Enter the tax exempt ID");
				$ret['main_frame'] = $IS4C_PATH.'gui-modules/requestInfo.php';
			}
			else if ($IS4C_LOCAL->get("requestType") == "tax exempt"){
				addTaxExempt();
				addcomment("Tax Ex ID#".$IS4C_LOCAL->get("requestMsg"));
				$IS4C_LOCAL->set("requestType","");
			}
		}
		elseif ($str == "FTTL")
			finalttl();
		elseif ($str == "TL"){
			ttl();
		}

		if (!$ret['main_frame']){
			$ret['output'] = lastpage();
			$ret['redraw_footer'] = True;
		}
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>FNTL</td>
				<td>Foodstamp eligible total</td>
			</tr>
			<tr>
				<td>TETL</td>
				<td>Tax exempt total</td>
			</tr>
			<tr>
				<td>FTTL</td>
				<td>Final total</td>
			</tr>
			<tr>
				<td>TL</td>
				<td>Re-calculate total</td>
			</tr>
			</table>";
	}
}

?>
