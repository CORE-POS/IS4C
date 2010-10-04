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
if (!function_exists("additem")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/additem.php");
if (!function_exists("pDataConnect")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("nullwrap")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/lib.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

/*
 * This class is a bit of a hack
 * It doesn't change the input string and it relies on
 * quantity, so it doesn't belong in the first parse chain
 *
 * It also isn't a stopping point, so it ALWAYS returns false
 * If the correct UPC is being parsed, the matching deposit gets
 * added on.
 *
 * MUST preceed UPC in the 2nd parse chain
 */
class AutoDeposit extends Parser {
	function check($str){
		return False;
		if (ltrim($str,"0") == "1090"){
			$this->process("0000000001091");
		}
		elseif (ltrim($str,"0") == "1031" ||
			ltrim($str,"0") == "1032" ||
			ltrim($str,"0") == "1033" ||
			ltrim($str,"0") == "1034"){
			$this->process("0000009999905");
		}
		return False;
	}

	// add the item. This extremely abbreviated version
	// of UPC only handles a few things: quantity, price
	// tax/foodstamp, discountable, and department
	function process($upc){
		global $IS4C_LOCAL;

		$db = pDataConnect();
		$query = "select * from products where upc='".$upc."'";
		$result = $db->query($query);

		if ($db->num_rows($result) <= 0) return;

		$row = $db->fetch_array($result);
		
		$description = $row["description"];
		$description = str_replace("'", "", $description);
		$description = str_replace(",", "", $description);

		$scale = 0;
		if ($row["scale"] != 0) $scale = 1;

		$tax = 0;
		if ($row["tax"] > 0 && $IS4C_LOCAL->get("toggletax") == 0) $tax = $row["tax"];
		elseif ($row["tax"] > 0 && $IS4C_LOCAL->get("toggletax") == 1) {
			$tax = 0;
			$IS4C_LOCAL->set("toggletax",0);
		}
		elseif ($row["tax"] == 0 && $IS4C_LOCAL->get("toggletax") == 1) {
			$tax = 1;
			$IS4C_LOCAL->set("toggletax",0);
		}
						
		$foodstamp = 0;
		if ($row["foodstamp"] != 0 && $IS4C_LOCAL->get("togglefoodstamp") == 0) $foodstamp = 1;
		elseif ($row["foodstamp"] != 0 && $IS4C_LOCAL->get("togglefoodstamp") == 1) {
			$foodstamp = 0;
			$IS4C_LOCAL->set("togglefoodstamp",0);
		}
		elseif ($row["foodstamp"] == 0 && $IS4C_LOCAL->get("togglefoodstamp") == 1) {
			$foodstamp = 1;
			$IS4C_LOCAL->set("togglefoodstamp",0);
		}

		$discounttype = nullwrap($row["discounttype"]);
		$discountable = $row["discount"];

		$quantity = 1;
		if ($IS4C_LOCAL->get("quantity") != 0) $quantity = $IS4C_LOCAL->get("quantity");

		$save_refund = $IS4C_LOCAL->get("refund");

		additem($upc,$description,"I"," "," ",$row["department"],
			$quantity,$row["normal_price"],
			$quantity*$row["normal_price"],$row["normal_price"],
			$scale,$tax,$foodstamp,0,0,$discountable,$discounttype,
			$quantity,0,0,0,0,0,0);

		$IS4C_LOCAL->set("refund",$save_refund);
	}

	function parse($str){
		return False;
	}

	function isFirst(){
		return True;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>1090</td><td>Automatically add item 1091</td>
			</tr>
			<tr>
				<td>1031</td><td>Automatically add item 9999905</td>
			</tr>
			<tr>
				<td>1032</td><td>Automatically add item 9999905</td>
			</tr>
			<tr>
				<td>1033</td><td>Automatically add item 9999905</td>
			</tr>
			<tr>
				<td>1034</td><td>Automatically add item 9999905</td>
			</tr>
			<tr>
				<td colspan=2><i>This module is used to add items to a transaction automatically & conditionally. Our usage is for bottle deposits</td>
			</tr>
			</table>";

	}
}

?>
