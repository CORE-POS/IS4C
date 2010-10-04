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

class PercentDiscount extends Parser {
	var $remainder;
	
	function check($str){
		if (strstr($str,"DI")){
			$split = explode("DI",$str);
			if (is_numeric($split[0])){
				$this->remainder = $split[1];
				$IS4C_LOCAL->set("itemPD",(int)$split[0]);
				return True;
			}
		}
		else if (strpos($str,"PD") > 0){
			$split = explode("PD",$str);	
			if (is_numeric($split[0]) && strlen($split[1]) > 0){
				$this->remainder = $split[1];
				$IS4C_LOCAL->set("itemDiscount",(int)$split[0]);
				return True;
			}
		}
		return False;
	}

	function parse($str){
		return $this->remainder;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>discount</i>DI<i>item</i></td>
				<td>Set a percent discount <i>discount</i>
				for just one item <i>item</i></td>
			</tr>
			<tr>
				<td><i>discount</i>PD<i>item</i></td>
				<td>Same as DI above</td>
			</tr>
			</table>";
	}
}

?>
