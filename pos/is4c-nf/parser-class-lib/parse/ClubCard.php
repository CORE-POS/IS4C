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
if (!function_exists("addItem")) include_once($CORE_PATH."lib/additem.php");
if (!function_exists("boxMsg")) include_once($CORE_PATH."lib/drawscreen.php");
if (!function_exists("lastpage")) include_once($CORE_PATH."lib/listitems.php");
if (!function_exists("nullwrap")) include_once($CORE_PATH."lib/lib.php");
if (!function_exists("tDataConnect")) include_once($CORE_PATH."lib/connect.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class ClubCard extends Parser {
	function check($str){
		if ($str == "50JC"){
			return True;
		}
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$query = "select upc,description,VolSpecial,quantity,
			total,discount,memDiscount,discountable,
			unitPrice,scale,foodstamp,voided,discounttype,
			trans_type,trans_status,department,regPrice,
			tax,volume,volDiscType
			from localtemptrans where 
			trans_id = " . $CORE_LOCAL->get("currentid");	
		$connection = tDataConnect();
		$result = $connection->query($query);
		$num_rows = $connection->num_rows($result);

		if ($num_rows > 0) {
			$row = $connection->fetch_array($result);
			$strUPC = $row["upc"];
			$strDescription = $row["description"];
			$dblVolSpecial = $row["VolSpecial"];			
			$dblquantity = -0.5 * $row["quantity"];

			$dblTotal = truncate2(-1 * 0.5 * $row["total"]); 		// invoked truncate2 rounding function to fix half-penny errors apbw 3/7/05

			$strCardNo = $CORE_LOCAL->get("memberID");
			$dblDiscount = $row["discount"];
			$dblmemDiscount = $row["memDiscount"];
			$intDiscountable = $row["discountable"];
			$dblUnitPrice = $row["unitPrice"];
			$intScale = nullwrap($row["scale"]);

			if ($row["foodstamp"] <> 0) {
				$intFoodStamp = 1;
			} else {
				$intFoodStamp = 0;
			}

			$intdiscounttype = nullwrap($row["discounttype"]);

			if ($row["voided"] == 20) {
				boxMsg("Discount already taken");
			} elseif ($row["trans_type"] == "T" or $row["trans_status"] == "D" or $row["trans_status"] == "V" or $row["trans_status"] == "C") {
				boxMsg("Item cannot be discounted");
			} elseif (strncasecmp($strDescription, "Club Card", 9) == 0 ) {		//----- edited by abpw 2/15/05 -----
				boxMsg("Item cannot be discounted");
			} elseif ($CORE_LOCAL->get("tenderTotal") < 0 and $intFoodStamp == 1 and (-1 * $dblTotal) > $CORE_LOCAL->get("fsEligible")) {
				boxMsg("Item already paid for");
			} elseif ($CORE_LOCAL->get("tenderTotal") < 0 and (-1 * $dblTotal) > ($CORE_LOCAL->get("runningTotal") - $CORE_LOCAL->get("taxTotal"))) {
				boxMsg("Item already paid for");
			} 
			else {
				// --- added partial item desc to club card description - apbw 2/15/05 --- 
				addItem($strUPC, "Club Card: " . substr($strDescription, 0, 19), "I", "", "J", $row["department"], $dblquantity, $dblUnitPrice, $dblTotal, 0.5 * $row["regPrice"], $intScale, $row["tax"], $intFoodStamp, $dblDiscount, $dblmemDiscount, $intDiscountable, $intdiscounttype, $dblquantity, $row["volDiscType"], $row["volume"], $dblVolSpecial, 0, 0, 0);

				$update = "update localtemptrans set voided = 20 where trans_id = " . $CORE_LOCAL->get("currentid");
				$connection = tDataConnect();
				$connection->query($update);

				$CORE_LOCAL->set("TTLflag",0);
				$CORE_LOCAL->set("TTLRequested",0);

				lastpage();
			}
		}	
		return False;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>50JC</td>
				<td>Some kind of card special. I don't
				actually use this one myself</td>
			</tr>
			</table>";
	}
}

?>
