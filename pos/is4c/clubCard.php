<?php

/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

function clubCard($intItemNum) 

{
	$query = "select * from localtemptrans where trans_id = " . $intItemNum;	
	$connection = tDataConnect();
	$result = sql_query($query, $connection);
	$row = sql_fetch_array($result);
	$num_rows = sql_num_rows($result);

	if ($num_rows > 0) {

	$strUPC = $row["upc"];
	$strDescription = $row["description"];
	$dblVolSpecial = $row["VolSpecial"];			
	$dblquantity = -0.5 * $row["quantity"];

	$dblTotal = truncate2(-1 * 0.5 * $row["total"]); 		// invoked truncate2 rounding function to fix half-penny errors apbw 3/7/05

	$strCardNo = $_SESSION["memberID"];
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
	} elseif ($_SESSION["tenderTotal"] < 0 and $intFoodStamp == 1 and (-1 * $dblTotal) > $_SESSION["fsEligible"]) {
		boxMsg("Item already paid for");
	} elseif ($_SESSION["tenderTotal"] < 0 and (-1 * $dblTotal) > ($_SESSION["runningTotal"] - $_SESSION["taxTotal"])) {
		boxMsg("Item already paid for");
	} 
	else {

		// --- added partial item desc to club card description - apbw 2/15/05 --- 
		addItem($strUPC, "Club Card: " . substr($strDescription, 0, 19), "I", "", "J", $row["department"], $dblquantity, $dblUnitPrice, $dblTotal, 0.5 * $row["regPrice"], $intScale, $row["tax"], $intFoodStamp, $dblDiscount, $dblmemDiscount, $intDiscountable, $intdiscounttype, $dblquantity, $row["volDiscType"], $row["volume"], $dblVolSpecial, 0, 0, 0);

		$update = "update localtemptrans set voided = 20 where trans_id = " . $intItemNum;
		$connection = tDataConnect();
		sql_query($update, $connection);

		$_SESSION["TTLflag"] = 0;
		$_SESSION["TTLRequested"] = 0;

		lastpage();
	}

	}

}

?>