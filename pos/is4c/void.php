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


function voiditem($item_num) {



	if ($item_num) {
		$query = "select upc, quantity, ItemQtty, foodstamp, total, voided from localtemptrans where "
			."trans_id = ".$item_num;

		$db = tDataConnect();
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);



		if ($num_rows == 0) boxMsg("Item not found");

		else {
			$row = sql_fetch_array($result);

			if ((!$row["upc"] || strlen($row["upc"]) < 1) && $row["voided"] == 1) {

				boxMsg("Item already voided");
			}
			elseif (!$row["upc"] || strlen($row["upc"]) < 1) {

				voidid($item_num);
			}
			elseif ($_SESSION["discounttype"] == 3) {

				voidupc($row["quantity"]."*".$row["upc"]);
			}
			else  {

				voidupc($row["ItemQtty"]."*".$row["upc"]);
			}
		}
	// sql_close($db);
	}
}

//---------------------------------------------------

function voidid($item_num) {

echo "Hello";


	$query = "select * from localtemptrans where trans_id = ".$item_num;
	$db = tDataConnect();
	$result = sql_query($query, $db);
	$row = sql_fetch_array($result);

	$upc = $row["upc"];
	$VolSpecial = $row["VolSpecial"];
	$quantity = -1 * $row["quantity"];

	if ($row["trans_subtype"] == "FS") {
		$total = -1 * $row["unitPrice"];
	}
	else  {
		$total = -1 * $row["total"];
	}

	$CardNo = $_SESSION["memberID"];
	$discount = -1 * $row["discount"];
	$memDiscount = -1 * $row["memDiscount"];
	$discountable = $row["discountable"];
	$unitPrice = $row["unitPrice"];
	$scale = nullwrap($row["scale"]);

	if ($row["foodstamp"] != 0) $foodstamp = 1;
	else $foodstamp = 0;

	$discounttype = nullwrap($row["discounttype"]);

	if ($_SESSION["tenderTotal"] < 0 && $foodstamp = 1 && (-1 * $total) > $_SESSION["fsEligible"]) {
		boxMsg("Item already paid for");
	}
	elseif ($_SESSION["tenderTotal"] < 0 && (-1 * $total) > $_SESSION["runningTotal"] - $_SESSION["taxTotal"]) {
		boxMsg("Item already paid for");
	}
	else {
		$update = "update localtemptrans set voided = 1 where trans_id = ".$item_num;
		sql_query($update, $db);
		addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], $quantity, $unitPrice, $total, $row["regPrice"], $scale, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $quantity, $row["volDiscType"], $row["volume"], $VolSpecial, 0, 0, 1);

		if ($row["trans_type"] != "T") {
			$_SESSION["ttlflag"] = 0;
			$_SESSION["ttlrequested"] = 0;
		}
		else ttl();

		lastpage();
	}
	// sql_close($db);
}

//----------------------------------------------------------

function voidupc($upc) {


	$lastpageflag = 1;
	$deliflag = 0;

	if (strpos($upc, "*") && (strpos($upc, "**") || strpos($upc, "*") == 0 || strpos($upc, "*") == strlen($upc)-1)) {
		$upc = "stop";
	}

	elseif (strpos($upc, "*")) {

		$voidupc = explode("*", $upc);

		if (!is_numeric($voidupc[0])) $upc = "stop";
		else {
			$quantity = $voidupc[0];
			$upc = $voidupc[1];
			$weight = 0;

		}
	}
	elseif (!is_numeric($upc) && !strpos($upc, "DP")) $upc = "stop";
	else {
		$quantity = 1;
		$weight = $_SESSION["weight"];
	}

	if (is_numeric($upc)) {
		$upc = substr("0000000000000".$upc, -13);
		if (substr($upc, 0, 3) == "002" && substr($upc, -5) != "00000") {
			$scaleprice = substr($upc, 10, 4)/100;
			$upc = substr($upc, 0, 8)."0000";
			$deliflag = 1;
		}
		elseif (substr($upc, 0, 3) == "002" && substr($upc, -5) == "00000") {
			$scaleprice = $_SESSION["scaleprice"];
			$deliflag = 1;
		}
	}

	if ($upc == "stop") inputUnknown();
	else {

		$db = tDataConnect();

		if ($_SESSION["discounttype"] == 3) {

			$query = "select sum(quantity) as voidable, max(scale), as scale, max(volDiscType) as volDiscType "
				."from localtemptrans where upc = '".$upc."' and discounttype = 3 and unitPrice = "
				.$_SESSION["caseprice"]." group by upc";
		}
		elseif ($deliflag == 0) {

			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
				."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
				."' and discounttype <> 3 group by upc, discounttype";
		}
		else {

			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
				."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
				."' and unitPrice = ".$scaleprice." and discounttype <> 3 group by upc";
		}

		if ($_SESSION["ddNotify"] == 1) {

			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
				."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
				."' and discounttype <> 3 and discountable = ".$_SESSION["discountable"]." group by upc, discounttype, discountable";

		}

		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);

		if ($num_rows == 0) {
			boxMsg("Item not found");
		}
		else {
			
			$row = sql_fetch_array($result);

			if (($row["scale"] == 1) && $weight > 0) {
				$quantity = $weight - $_SESSION["tare"];
				$_SESSION["tare"] = 0;
			}

			$volDiscType = $row["volDiscType"];
			$voidable = nullwrap($row["voidable"]);

			$VolSpecial = 0;
			$volume = 0;
			$scale = nullwrap($row["scale"]);

			if ($voidable == 0 && $quantity == 1) boxMsg("Items already voided");
			elseif ($voidable == 0 && $quantity > 1) boxMsg("Items already voided");
			elseif ($scale == 1 && $quantity < 0) boxMsg("tare weight cannot be greater than item weight");
			elseif ($voidable < $quantity && $row["scale"] == 1) {
				$message = "Void request exceeds<BR>weight of item rung in<P><B>You can void up to "
					.$row["voidable"]." lb</B>";
				boxMsg($message);
			}
			elseif ($voidable < $quantity) {
				$message = "Void request exceeds<BR>number of items rung in<P><B>You can void up to "
					.$row["voidable"]."</B>";
				boxMsg($message);
			}
			else  {

				unset($result);
			
//--------------------------------Void Item----------------------------

				if ($_SESSION["discounttype"] == 3) {
					$query_upc = "select * from localtemptrans where upc = '".$upc
						."' and discounttype = 3 and unitPrice = ".$_SESSION["caseprice"];
				}
				elseif ($deliflag == 0) {
					$query_upc = "select * from localtemptrans where upc = '".$upc
						."' and discounttype <> 3";
				}
				else {
					$query_upc = "select * from localtemptrans where upc = '".$upc."' and unitPrice = "
						.$scaleprice;
				}

				$_SESSION["discounttype"] = 9;
	
				$result = sql_query($query_upc, $db);
				$row = sql_fetch_array($result);
				

				$ItemQtty = $row["ItemQtty"];
				$foodstamp = nullwrap($row["foodstamp"]);
				$discounttype = nullwrap($row["discounttype"]);
				$mixMatch = nullwrap($row["mixMatch"]);
	
				if (($_SESSION["isMember"] != 1 && $row["discounttype"] == 2) || ($_SESSION["isStaff"] == 0 && $row["discounttype"] == 4)) {
					$unitPrice = $row["regPrice"];
				}
				elseif ((($_SESSION["isMember"] == 1 && $row["discounttype"] == 2) || ($_SESSION["isStaff"] != 0 && $row["discounttype"] == 4)) && ($row["unitPrice"] == $row["regPrice"])) {
	
					$db_p = pDataConnect();
					$query_p = "select * from products where upc = '".$upc."'";
					$result_p = sql_query($query_p, $db_p);
					$row_p = sql_fetch_array($result_p);
					
					$unitPrice = $row_p["special_price"];
	
					sql_close($db_p);
				}

				else {
					$unitPrice = $row["unitPrice"];
				}
				
				$discount = -1 * $row["discount"];
				$memDiscount = -1 * $row["memDiscount"];
				$discountable = $row["discountable"];

				if ($_SESSION["ddNotify"] == 1) {
					$discountable = $_SESSION["discountable"];
				}

				//----------------------mix match---------------------

				if ($volDiscType >= 1) {

					

					$db_mm = tDataConnect();
					$query_mm = "select sum(ItemQtty) as mmqtty from localtemptrans where mixMatch = "
						.$mixMatch;
					
					$result_mm = sql_query($query_mm, $db_mm);
					$row_mm = sql_fetch_array($result_mm);
	
					$mmqtty = nullwrap($row_mm["mmqtty"]);
	
					sql_close($db_mm);
	
					$db_pq = pDataConnect();
					$query_pq = "select * from products where upc = '".$upc."'";
	
					$result_pq = sql_query($query_pq, $db_pq);
					$row_pq = sql_fetch_array($result_pq);
	
					if ($volDiscType == 1) {


						$unitPrice = truncate2($row_pq["groupprice"]/$row_pq["quantity"]);

					}
					elseif ($discounttype == 1) {
						$unitPrice = $row_pq["special_price"];
						$VolSpecial = nullwrap($row_pq["specialgroupprice"]);


					}
					else {
						$unitPrice = $row_pq["normal_price"];
						$VolSpecial = nullwrap($row_pq["groupprice"]);
					}
	
					if ($row_pq["advertised"] == 0) {
						$volume = nullwrap($row_pq["quantity"]);
					}
					else {
						$volume = nullwrap($row_pq["specialquantity"]);
					}
	
					sql_close($db_pq);
					
					$volmulti = (int) ($quantity/$volume);
	
					$vmremainder = $quantity % $volume;
	
					if ($mixMatch == 0) {
						$mm = (int) ($voidable/$volume);
						$mmremainder = $voidable % $volume;
					}
					else {
						$mm = (int) ($mmqtty/$volume);
						$mmremainder = $mmqtty % $volume;
					}
	
					if ($volmulti > 0) {

						addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], -1* $volmulti, $VolSpecial, -1 * $volmulti * $VolSpecial, $VolSpecial, 0, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, -1 * $volmulti * $volume, $volDiscType, $volume, $VolSpecial, $mixMatch, -1 * $volume * $volmulti, 1);
						$quantity = $vmremainder;
					}
					if ($vmremainder > $mmremainder) {
						
						$voladj = $row["VolSpecial"] - ($unitPrice * ($volume - 1));

						addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], -1, $voladj, -1 * $voladj, $voladj, 0, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, -1, $volDiscType, $volume, $VolSpecial, $mixMatch, -1 * $volume, 1);
						$quantity = $quantity - 1;
					}
				}
				//-----------------------------------------------------------------------
	
				$quantity = -1 * $quantity;
				$total = truncate2($quantity * $unitPrice);
			
	
				$CardNo = $_SESSION["memberID"];
	
				$discounttype = nullwrap($row["discounttype"]);
	
				if ($discounttype == 3) {
					$quantity = -1 * $ItemQtty;
				}
	
				if ($_SESSION["tenderTotal"] < 0 && $foodstamp == 1 && (-1 * $total) > $_SESSION["fsEligible"]) {
					boxMsg("Item already paid for");
					$lastpageflag = 0;
				}
				elseif ($_SESSION["tenderTotal"] < 0 && (-1 * $total) > $_SESSION["runningTotal"] - $_SESSION["taxTotal"]) {
					boxMsg("Item already paid for");
					$lastpageflag = 0;
				}
				elseif ($quantity != 0) {
					addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], $quantity, $unitPrice, $total, $row["regPrice"], $scale, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $quantity, $volDiscType, $volume, $VolSpecial, $mixMatch, 0, 1);

					if ($row["trans_type"] != "T") {
						$_SESSION["ttlflag"] = 0;
						$_SESSION["ttlrequested"] = 0;
						$_SESSION["discounttype"] = 0;
					}
				}
//----------------------------------------------------------------------------

				if ($lastpageflag == 1) lastpage();
				else $lastpageflag = 1;
			}
		}
		// sql_close($db);
	}

}
//----------------------------------------------------------------------------------

?>
