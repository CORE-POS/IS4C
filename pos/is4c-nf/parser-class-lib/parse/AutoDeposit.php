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
		global $CORE_LOCAL;

		$db = Database::pDataConnect();
		$query = "select description,scale,tax,foodstamp,discounttype,
			discount,department,normal_price
		       	from products where upc='".$upc."'";
		$result = $db->query($query);

		if ($db->num_rows($result) <= 0) return;

		$row = $db->fetch_array($result);
		
		$description = $row["description"];
		$description = str_replace("'", "", $description);
		$description = str_replace(",", "", $description);

		$scale = 0;
		if ($row["scale"] != 0) $scale = 1;

		$tax = 0;
		if ($row["tax"] > 0 && $CORE_LOCAL->get("toggletax") == 0) $tax = $row["tax"];
		elseif ($row["tax"] > 0 && $CORE_LOCAL->get("toggletax") == 1) {
			$tax = 0;
			$CORE_LOCAL->set("toggletax",0);
		}
		elseif ($row["tax"] == 0 && $CORE_LOCAL->get("toggletax") == 1) {
			$tax = 1;
			$CORE_LOCAL->set("toggletax",0);
		}
						
		$foodstamp = 0;
		if ($row["foodstamp"] != 0 && $CORE_LOCAL->get("togglefoodstamp") == 0) $foodstamp = 1;
		elseif ($row["foodstamp"] != 0 && $CORE_LOCAL->get("togglefoodstamp") == 1) {
			$foodstamp = 0;
			$CORE_LOCAL->set("togglefoodstamp",0);
		}
		elseif ($row["foodstamp"] == 0 && $CORE_LOCAL->get("togglefoodstamp") == 1) {
			$foodstamp = 1;
			$CORE_LOCAL->set("togglefoodstamp",0);
		}

		$discounttype = MiscLib::nullwrap($row["discounttype"]);
		$discountable = $row["discount"];

		$quantity = 1;
		if ($CORE_LOCAL->get("quantity") != 0) $quantity = $CORE_LOCAL->get("quantity");

		$save_refund = $CORE_LOCAL->get("refund");

		TransRecord::addItem($upc,$description,"I"," "," ",$row["department"],
			$quantity,$row["normal_price"],
			$quantity*$row["normal_price"],$row["normal_price"],
			$scale,$tax,$foodstamp,0,0,$discountable,$discounttype,
			$quantity,0,0,0,0,0,0);

		$CORE_LOCAL->set("refund",$save_refund);
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
				<td colspan=2><i>
				This module is deprecated and disabled. Use the deposit field in the products table to tie deposit PLUs to items
				</i></td>
			</tr>
			</table>";

	}
}

?>
