<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Community Co-op

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

if (!function_exists("changeCurrentPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!function_exists("pDataConnect")) include_once($_SESSION["INCLUDE_PATH"]."/lib/connect.php");
if (!function_exists("writeLine")) include_once($_SESSION["INCLUDE_PATH"]."/lib/printLib.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

$entered = strtoupper(trim($_POST["input"]));

if ($entered == "CL" || $entered == "") {
	changeCurrentPage("/gui-modules/pos2.php");
}
elseif (!is_numeric($entered)) {
	$IS4C_LOCAL->set("boxMsg","'$entered' is not a UPC");
	changeCurrentPage("/gui-modules/boxMsg2.php");
}
else {
	// expand UPC-E
	if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
		$p6 = substr($entered, -1);

		if ($p6 == 0) $entered = substr($entered, 0, 3)."00000".substr($entered, 3, 3);
		elseif ($p6 == 1) $entered = substr($entered, 0, 3)."10000".substr($entered, 4, 3);
		elseif ($p6 == 2) $entered = substr($entered, 0, 3)."20000".substr($entered, 4, 3);
		elseif ($p6 == 3) $entered = substr($entered, 0, 4)."00000".substr($entered, 4, 2);
		elseif ($p6 == 4) $entered = substr($entered, 0, 5)."00000".substr($entered, 6, 1);
		else $entered = substr($entered, 0, 6)."0000".$p6;
	}

	// pad upc the same way as regular upc scanning
	$upc = "";
	if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) $upc = "0".substr($entered, 0, 12);
	else $upc = substr("0000000000000".$entered, -13);

	// handle scale upcs correctly
	if (substr($upc, 0, 3) == "002") $upc = substr($upc, 0, 8)."00000";

	$db = pDataConnect();
	$query = "select description, normal_price, special_price, discounttype from
		  products where upc='$upc'";
	$result = $db->query($query);

	if ($db->num_rows($result) < 1 && substr($upc,0,3) == "005"){
		$IS4C_LOCAL->set("boxMsg","'$upc' is a coupon UPC");
		changeCurrentPage("/gui-modules/boxMsg2.php");
	}
	else {
		$receipt = centerString("UPC: ".$upc)."\n";
		$receipt .= centerString("----------------------")."\n";
		if ($db->num_rows($result) < 1)
			$receipt .= centerString("ITEM NOT FOUND");
		else {
			$row = $db->fetch_array($result);
			$receipt .= $row["description"]."\n";
			$receipt .= "PRICE: ".$row["normal_price"]."\n";
			if ($row["discounttype"] == 0)
				$receipt .= centerString("NOT ON SALE");
			else {
				if ($row["discounttype"] == 1)
					$receipt .= centerString("ON SALE");
				elseif ($row["discounttype"] == 2)
					$receipt .= centerString("ON MEMBER SPECIAL");
				else
					$receipt .= centerString("UNKNOWN SALE TYPE");
				$receipt .= "\n";
				$receipt .= "SALE PRICE: ".$row["special_price"];
			}
		}
		$receipt .= "\n\n\n\n\n\n\n";
		$receipt .= "\n\n\n\n\n\n\n";
		writeLine($receipt.chr(27).chr(105));
		changeCurrentPage("/gui-modules/pos2.php");
	}

	$db->close();
}
