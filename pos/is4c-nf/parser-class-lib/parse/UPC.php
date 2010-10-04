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
if (!function_exists("addItem")) include($_SERVER["DOCUMENT_ROOT"]."/lib/additem.php");
if (!function_exists("boxMsg")) include($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!function_exists("nullwrap")) include($_SERVER["DOCUMENT_ROOT"]."/lib/lib.php");
if (!function_exists("setglobalvalue")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/loadconfig.php");
if (!function_exists("boxMsgscreen")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/clientscripts.php");
if (!function_exists("list_items")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/listitems.php");
if (!function_exists("memberID")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class UPC extends Parser {
	function check($str){
		if (is_numeric($str) && strlen($str) < 16)
			return True;
		return False;
	}

	function parse($str){
		return $this->upcscanned($str);
	}

	function upcscanned($entered) {
		global $IS4C_LOCAL;
		$ret = $this->default_json();

		$hitareflag = 0;
		$entered = str_replace(".", " ", $entered);

		$quantity = $IS4C_LOCAL->get("quantity");
		if ($IS4C_LOCAL->get("quantity") == 0 && $IS4C_LOCAL->get("multiple") == 0) $quantity = 1;
		$scaleprice = 0;

		if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
			$p6 = substr($entered, -1);

			if ($p6 == 0) $entered = substr($entered, 0, 3)."00000".substr($entered, 3, 3);
			elseif ($p6 == 1) $entered = substr($entered, 0, 3)."10000".substr($entered, 3, 3);
			elseif ($p6 == 2) $entered = substr($entered, 0, 3)."20000".substr($entered, 3, 3);
			elseif ($p6 == 3) $entered = substr($entered, 0, 4)."00000".substr($entered, 4, 2);
			elseif ($p6 == 4) $entered = substr($entered, 0, 5)."00000".substr($entered, 5, 1);
			else $entered = substr($entered, 0, 6)."0000".$p6;
		}

		$upc = "";
		if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) $upc = "0".substr($entered, 0, 12);
		else $upc = substr("0000000000000".$entered, -13);

		if (substr($upc, 0, 3) == "002") {
			$scaleprice = truncate2(substr($upc, -4)/100);
			$upc = substr($upc, 0, 8)."00000";
			if ($upc == "0020006000000" || $upc == "0020010000000") $scaleprice *= -1;
		}

		$db = pDataConnect();
		$query = "select * from products where upc = '".$upc."'";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
		$row = $db->fetch_array($result);

		/* Implementation of inUse flag
		 *   if the flag is not set, display a warning dialog noting this
		 *   and allowing the sale to be confirmed or canceled
		 */
		if ($num_rows > 0 && $row["inUse"] == 0){
			if ($IS4C_LOCAL->get("warned") == 1 && $IS4C_LOCAL->get("warnBoxType") == "inUse"){
				$IS4C_LOCAL->set("warned",0);
				$IS4C_LOCAL->set("warnBoxType","");
			}	
			else {
				$IS4C_LOCAL->set("warned",1);
				$IS4C_LOCAL->set("warnBoxType","inUse");
				$IS4C_LOCAL->set("strEntered",$row["upc"]);
				$IS4C_LOCAL->set("boxMsg","<b>".$row["upc"]." - ".$row["description"]."</b>
					<br>Item not for sale
					<br><font size=-1>[enter] to continue sale, [clear] to cancel</font>");
				$ret['main_frame'] = "/gui-modules/boxMsg2.php";
				return $ret;
			}
		}

		if ($num_rows == 0 && substr($upc, 0, 3) == "005") 
			$ret['output'] = $this->couponcode($upc);
		elseif ($num_rows == 0 && substr($upc,0,3) == "099") 
			$ret['output'] = $this->couponcode($upc,true);
		elseif ($num_rows == 0 && substr($upc, 0, 3) == "004") 
			$ret['output'] = $this->housecoupon($upc);
		elseif ($num_rows == 0 && substr($upc, 0, 3) != "005"){
			$ret['output'] = boxMsg($upc."<BR><B>is not a valid item</B>");
		}
		elseif (strlen($row["normal_price"]) > 8){
			$ret['output'] = boxMsg("$upc<br>Claims to be more than $100,000");
		}
		elseif ($row["scale"] != 0 && $IS4C_LOCAL->get("weight") == 0 && 
			$IS4C_LOCAL->get("quantity") == 0 && substr($upc,0,3) != "002") {

			$IS4C_LOCAL->set("SNR",1);
			$ret['output'] = boxMsg("please put item on scale");
			$IS4C_LOCAL->set("wgtRequested",0);
			$ret['retry'] = $IS4C_LOCAL->get("strEntered");

		}
		elseif ($row["scale"] != 0 && $IS4C_LOCAL->get("scale") == 0) {
			$IS4C_LOCAL->set("waitforScale",1);
			$IS4C_LOCAL->set("SNR",1);
		}
		elseif ($row["scale"] == 0 && (int) $IS4C_LOCAL->get("quantity") != $IS4C_LOCAL->get("quantity") ) 
			$ret['output'] = boxMsg("fractional quantity cannot be accepted for this item");
		elseif (($upc == "0000000008005" || $upc == "0000000008006") && ($IS4C_LOCAL->get("memberID") == "0")){
			$IS4C_LOCAL->set("search_or_list",1);
			$ret['main_frame'] = "/gui-modules/memlist.php";
		}
		elseif (($upc == "0000000008005") && ($IS4C_LOCAL->get("isMember") == 0))
			$ret['output'] = boxMsg("<BR>member discount not applicable</B>");
		elseif ($upc == "0000000008005" && ($IS4C_LOCAL->get("percentDiscount") > 0)) 
			$ret['output'] = boxMsg($IS4C_LOCAL->get("percentDiscount")."% discount already applied");
		else {
			if ($row["deposit"] > 0){
				$dupc = (int)$row["deposit"];
				$this->add_deposit($dupc);
			}

			$mixMatch = 0;
			$qttyEnforced = $row["qttyEnforced"];

			if (($qttyEnforced == 1) && ($IS4C_LOCAL->get("multiple") == 0) && ($IS4C_LOCAL->get("msgrepeat") == 0)) 
				$ret['main_frame'] = "/gui-modules/qtty2.php";
			else $IS4C_LOCAL->set("qttyvalid",1);

			if ($IS4C_LOCAL->get("qttyvalid") != 1) $db->close(); 
			else {
				$upc = $row["upc"];
				$description = $row["description"];
				$description = str_replace("'", "", $description);
				$description = str_replace(",", "", $description);
				$transType = "I";
				$transsubType = "CA";
				$department = $row["department"];
				$unitPrice = $row["normal_price"];
				$cost = isset($row["cost"])?$row["cost"]:0.00;
				$numflag = isset($row["local"])?$row["local"]:0;
				$charflag = "";

				$regPrice = $row["normal_price"];
				$CardNo = $IS4C_LOCAL->get("memberID");

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

				if ($scale == 1) {
					$hitareflag = 0;

					$quantity = $IS4C_LOCAL->get("weight") - $IS4C_LOCAL->get("tare");
					if ($IS4C_LOCAL->get("quantity") != 0) 
						$quantity = $IS4C_LOCAL->get("quantity") - $IS4C_LOCAL->get("tare");

					if ($quantity <= 0) $hitareflag = 1;

					$IS4C_LOCAL->set("tare",0);
				}

				$discounttype = nullwrap($row["discounttype"]);
				$discountable = $row["discount"];
				$discountmethod = nullwrap($row["specialpricemethod"]);

				/* set unitPrice to the price from the printed label
				   early so % discount can be applied to scale-labled
				   and non-scale labeled items - andy */
				if (substr($upc, 0, 3) == "002" && ($row["scale"] == 0 || $discounttype == 0)) {
					$unitPrice = $scaleprice;
					$regPrice = $unitPrice;
					$hitareflag = 0;
					$row["scale"] = 0;
					$scale = 0;
					$quantity = $IS4C_LOCAL->get("quantity");
					if ($quantity == 0)
						$quantity = 1;
				}
				elseif (substr($upc,0,3) == "002" && $row["scale"] != 0){
					$quantity = truncate2($scaleprice / $row["normal_price"]);
					$unitPrice = $row["normal_price"];
					$regPrice = $row["normal_price"];
					$hitareflag = 0;
				}

				if ($IS4C_LOCAL->get("toggleDiscountable") == 1) {
					$IS4C_LOCAL->set("toggleDiscountable",0);
					if  ($discountable != 0) 
						$discountable = 0;
					else 
						$discountable = 1;
				}

				if ($IS4C_LOCAL->get("nd") == 1 && $discountable == 7) {
					$discountable = 3;
					$IS4C_LOCAL->set("nd",0);
				}

				if ($discounttype == 2 || $discounttype == 4) {
					$memDiscount = truncate2($row["normal_price"] * $quantity) - truncate2($row["special_price"] * $quantity);
					$discount = 0;
					$unitPrice = $row["normal_price"];

				}
				elseif ($discounttype == 1 && $discountmethod == 0) {
					$unitPrice = $row["special_price"];
					$unitDiscount = $row["normal_price"] - $row["special_price"];
					$discount = $unitDiscount * $quantity;
					$memDiscount = 0;
				}
				/* doing a % discount for members - andy */
				elseif ($discounttype == 3) {
					$discount = 0;
					$memDiscount = truncate2($row['special_price']*$unitPrice);
				}
				elseif ($discounttype == 5){
					$discount = 0;
					$memDiscount = truncate2($row["special_price"]*$quantity);
				}
				else {
					if (substr($upc,0,3) != "002")
						$unitPrice = $row["normal_price"];
					$discount = 0;
					$memDiscount = 0;
				}

				if ($IS4C_LOCAL->get("isMember") == 1 && $discounttype == 2) 
					$unitPrice = nullwrap($row["special_price"]);
				if ($IS4C_LOCAL->get("isMember") == 1 && $discounttype == 3) 
					$unitPrice = nullwrap($unitPrice - $memDiscount);
				if ($IS4C_LOCAL->get("isStaff") != 0 && $discounttype == 4) 
					$unitPrice = nullwrap($row["special_price"]);
				if ($IS4C_LOCAL->get("casediscount") > 0 && $IS4C_LOCAL->get("casediscount") <= 100) {
					$casediscount = (100 - $IS4C_LOCAL->get("casediscount"))/100;
					$unitPrice = $casediscount * $unitPrice;
				}

				//-------------Mix n Match -------------------------------------
				$matched = 0;

				$VolSpecial = nullwrap($row["groupprice"]);	
				$volDiscType = nullwrap($row["pricemethod"]);
				$volume = nullwrap($row["quantity"]);

				if ($row["specialpricemethod"] > 0){
					if (($row["discounttype"] == 2 && $IS4C_LOCAL->get("isMember") == 1) || 
					     $row["discounttype"] != 2) {
						$VolSpecial = nullwrap($row["specialgroupprice"]);
						$volDiscType = nullwrap($row["specialpricemethod"]);
						$volume = nullwrap($row["specialquantity"]);
					}
				}

				if ($volDiscType && $volDiscType >= 1 && $volDiscType <= 4) {
					// If item is on volume discount
					$mixMatch  = $row["mixmatchcode"];
					$queryt = "select sum(ItemQtty - matched) as mmqtty, mixMatch from localtemptrans "
						."where trans_status <> 'R' AND 
						mixMatch = '".$mixMatch."' group by mixMatch";
					if (!$row["mixmatchcode"] || $row["mixmatchcode"] == '0') {
						$mixMatch = 0;
						$queryt = "select sum(ItemQtty - matched) as mmqtty from "
							."localtemptrans where trans_status<>'R' AND "
							."upc = '".$row["upc"]."' group by upc";
					}

					if ($volDiscType == 1) $unitPrice = truncate2($VolSpecial/$volume);  

					$voladj = $VolSpecial - (($volume - 1) * $unitPrice); // one at special price
					$newmm = (int) ($quantity/$volume);		      // number of complete sets
				
					$dbt = tDataConnect();
					$resultt = $dbt->query($queryt);
					$num_rowst = $dbt->num_rows($resultt);

					$mmqtty = 0;
					if ($num_rowst > 0) {
						$rowt = $dbt->fetch_array($resultt);
						$mmqtty = $rowt["mmqtty"]; // number not in complete sets in localtemptrans
					}

					$newmmtotal = $mmqtty + ($quantity % $volume);		 
					$na = $newmmtotal % $volume;
					$saveQty = $quantity;
					$quantity = $quantity % $volume;

					/* not straight-up interchangable
					 * ex: buy item A, get $1 off item B
					 * need strict pairs AB */
					if ($volDiscType == 3 || $volDiscType == 4){
						$qualMM = abs($mixMatch);
						$discMM = -1*abs($mixMatch);

						$q1 = "SELECT sum(ItemQtty),max(department) FROM localtemptrans WHERE mixMatch='$qualMM' and trans_status <> 'R'";
						$r1 = $dbt->query($q1);
						$quals = 0;
						$dept1 = 0;
						if($dbt->num_rows($r1)>0){
							$row = $dbt->fetch_row($r1);
							$quals = round($row[0]);
							$dept1 = $row[1];	
						}
						$q2 = "SELECT sum(ItemQtty),max(department) FROM localtemptrans WHERE mixMatch='$discMM' and trans_status <> 'R'";
						$r2 = $dbt->query($q2);
						$dept2 = 0;
						$discs = 0;
						if($dbt->num_rows($r2)>0){
							$row = $dbt->fetch_row($r2);
							$discs = round($row[0]);
							$dept2 = $row[1];	
						}

						$q3 = "SELECT sum(matched) FROM localtemptrans WHERE
							mixmatch IN ('$qualMM','$discMM')";
						$r3 = $dbt->query($q3);
						$matches = ($dbt->num_rows($r3)>0)?array_pop($dbt->fetch_array($r3)):0;

						$matches = $matches/$volume;
						$quals -= $matches*($volume-1);
						$discs -= $matches;

						if ($mixMatch > 0){
							$quals = round(($quals >0)?$quals+$saveQty:$saveQty);
							$dept1 = $department;
						}
						else {
							$discs = round(($discs >0)?$discs+$saveQty:$saveQty);
							$dept2 = $department;
						}

						$sets = 0;
						while($discs > 0 && $quals >= ($volume-1) ){
							$discs -= 1;
							$quals -= ($volume -1);
							$sets++;
						}
						$quantity = $saveQty - $sets;
						if ($quantity < 0) $quantity = 0;
						if ($sets > 0){
							$qualDisc = $sets*($volume-1)*($VolSpecial/$volume);
							$discDisc = $sets*($VolSpecial/$volume);

							if ($saveQty != ((int)$saveQty)){
								addItem($upc, $description, "I", "", "", $department, $saveQty, truncate2($unitPrice), truncate2($saveQty * $unitPrice), truncate2($unitPrice), $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $saveQty, $volDiscType, $volume, $VolSpecial, $mixMatch, $volume * $sets, 0, truncate2($saveQty * $cost), $numflag, $charflag);
							}
							else {
								addItem($upc, $description, "I", "", "", $department, $sets, truncate2($unitPrice), truncate2($sets * $unitPrice), truncate2($unitPrice), $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $sets, $volDiscType, $volume, $VolSpecial, $mixMatch, $volume * $sets, 0, truncate2($sets * $cost), $numflag, $charflag);
							}
							$IS4C_LOCAL->set("qttyvalid",0);
						
							/* type 3 => split discount across depts
							 * type 4 => all discount on disc dept
							 */
							if ($volDiscType == 3){
								additemdiscount($dept1,$qualDisc);
								additemdiscount($dept2,$discDisc);
							}
							elseif($volDiscType == 4){
								additemdiscount($dept2,$sets*$VolSpecial);
							}
						}
						$newmm = 0;
						$newmmtotal = 0;
					}


					if ($newmm >= 1) {
						addItem($upc, $description, "I", "", "", $department, $newmm, truncate2($VolSpecial), truncate2($newmm * $VolSpecial), truncate2($VolSpecial), $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $volume * $newmm, $volDiscType, $volume, $VolSpecial, $mixMatch, $volume * $newmm, 0, truncate2($newmm*$cost), $numflag, $charflag);
						$newmm = 0;
						$IS4C_LOCAL->set("qttyvalid",0);
					}

					if ($newmmtotal >= $volume) {
						addItem($upc, $description, "I", "", "", $department, 1, $voladj, $voladj, $voladj, $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, 1, $volDiscType, $volume, $VolSpecial, $mixMatch, $volume, 0, $cost, $numflag, $charflag);
						$quantity = $quantity - 1;
						$newmmtotal = 0;
						$IS4C_LOCAL->set("qttyvalid",0);
					}

					$dbt->close();
				}
				else if ($volDiscType && $volDiscType == 5){
					$mixMatch  = $row["mixmatchcode"];
					$stem = substr($mixMatch,0,10);	
					$sets = 99;
					$dbt = tDataConnect();
					// count up complete sets
					for($i=0; $i<=$volume; $i++){
						$tmp = $stem."_q".$i;
						if ($volume == $i) $tmp = $stem.'_d';

						$chkQ = "SELECT sum(CASE WHEN scale=0 THEN ItemQtty ELSE 1 END) 
							FROM localtemptrans WHERE mixmatch='$tmp' and trans_status<>'R'";
						$chkR = $dbt->query($chkQ);
						$tsets = array_pop($dbt->fetch_row($chkR));
						if ($tsets == ""){
							$tsets = 0;
						}
						if ($tmp == $mixMatch)
							$tsets += is_int($quantity)?$quantity:1;

						if ($tsets < $sets)
							$sets = $tsets;
					}

					// count existing sets
					$matches = 0;
					$mQ = "SELECT sum(matched) FROM localtemptrans WHERE
						left(mixmatch,11)='{$stem}_'";
					$mR = $dbt->query($mQ);
					if ($dbt->num_rows($mR) > 0)
						$matches = array_pop($dbt->fetch_row($mR));

					$sets -= $matches;
					if ($sets > 0){
						if($quantity != (int)$quantity) $sets = $quantity;

						addItem($upc, $description, "I", "", "", $department, $sets, truncate2($unitPrice), truncate2($sets * $unitPrice), truncate2($unitPrice), $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $sets, $volDiscType, $volume, $VolSpecial, $mixMatch, $sets, 0, truncate2($sets*$cost), $numflag, $charflag);
						$IS4C_LOCAL->set("qttyvalid",0);
						$quantity -= $sets;

						$discount_dept = 0;
						if ($mixMatch == $stem.'_d')
							$discount_dept = $department;
						else {
							$dQ = "SELECT max(department) FROM localtemptrans
								WHERE mixmatch='{$stem}_d'";
							$dR = $dbt->query($dQ);
							$discount_dept = array_pop($dbt->fetch_row($dR));
						}
						additemdiscount($discount_dept,$sets*$VolSpecial);
					}
					$dbt->close();
				}

				$total = truncate2($unitPrice * $quantity);
				$unitPrice = truncate2($unitPrice);

				if ($upc == "0000000008010" && $IS4C_LOCAL->get("msgrepeat") == 0) {
					$IS4C_LOCAL->set("endorseType","giftcert");
					$IS4C_LOCAL->set("tenderamt",$total);
					$IS4C_LOCAL->set("boxMsg","<B>".$total." gift certificate</B><BR>insert document<BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>");
					$ret["main_frame"] = "/gui-modules/boxMsg2.php";
				}
				elseif ($upc == "0000000008006" && $IS4C_LOCAL->get("msgrepeat") == 0) {
					$IS4C_LOCAL->set("endorseType","stock");
					$IS4C_LOCAL->set("tenderamt",$total);
					$IS4C_LOCAL->set("boxMsg","<B>".$total." stock payment</B><BR>insert form<BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>");
					$ret["main_frame"] = "/gui-modules/boxMsg2.php";
				}
				elseif ($upc == "0000000008011" && $IS4C_LOCAL->get("msgrepeat") == 0) {
					$IS4C_LOCAL->set("endorseType","classreg");
					$IS4C_LOCAL->set("tenderamt",$total);
					$IS4C_LOCAL->set("boxMsg","<B>".$total." class registration</B><BR>insert form<BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>");
					$ret["main_frame"] = "/gui-modules/boxMsg2.php";
				}
				elseif ($hitareflag == 1) 
					$ret['output'] = boxMsg("item weight must be greater than tare weight");
				else {
					if ($quantity != 0) {
						$qtty = $quantity;

						if ($IS4C_LOCAL->get("casediscount") > 0) {
							addcdnotify();
							$discounttype = 3;
							$IS4C_LOCAL->set("casediscount",0);
							$quantity = 1;
							$unitPrice = $total;
							$regPrice = $total;
						}

						if ($IS4C_LOCAL->get("ddNotify") == 1 && $IS4C_LOCAL->get("itemPD") == 10) {
							$IS4C_LOCAL->set("itemPD",0);
							$discountable = 7;						
						}

						$intvoided = 0;
						if ($IS4C_LOCAL->get("ddNotify") == 1 && $discountable == 7) 
							$intvoided = 22;

						addItem($upc, $description, "I", " ", " ", $department, $quantity, $unitPrice, $total, $regPrice, $scale, $tax, $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $qtty, $volDiscType, $volume, $VolSpecial, $mixMatch, $matched, $intvoided, truncate2($quantity*$cost),$numflag,$charflag);
						$IS4C_LOCAL->set("msgrepeat",0);
						$IS4C_LOCAL->set("qttyvalid",0);

						$ret['udpmsg'] = 'goodBeep';
					}
				}

				if ($tax != 1) $IS4C_LOCAL->set("voided",0);

				if ($discounttype == 1 && $discountmethod == 0) {
					//$IS4C_LOCAL->set("ondiscount",1);
					$IS4C_LOCAL->set("voided",2);
					adddiscount($discount,$department);		/***** jqh 09/29/05 added department parameter to adddiscount function *****/
				}
				elseif ($discounttype == 2 && $IS4C_LOCAL->get("isMember") == 1) {
					//$IS4C_LOCAL->set("ondiscount",1);
					$IS4C_LOCAL->set("voided",2);
					adddiscount($memDiscount,$department);	/***** jqh 09/29/05 added department parameter to adddiscount function *****/
				}
				/* add % discount for members - andy */
				elseif ($discounttype == 3 && $IS4C_LOCAL->get("isMember") == 1) {
					//$IS4C_LOCAL->set("ondiscount",1);
					$IS4C_LOCAL->set("voided",2);
					adddiscount($memDiscount,$department);	/***** jqh 09/29/05 added department parameter to adddiscount function *****/
				}
				elseif ($discounttype == 4 && $IS4C_LOCAL->get("isStaff") != 0) {
					//$IS4C_LOCAL->set("ondiscount",1);
					$IS4C_LOCAL->set("voided",2);
					adddiscount($memDiscount,$department);	/***** jqh 09/29/05 added department parameter to adddiscount function *****/
				}
				else 
					$IS4C_LOCAL->set("voided",0);

				if ($IS4C_LOCAL->get("tare") != 0) $IS4C_LOCAL->set("tare",0);
				$IS4C_LOCAL->set("ttlflag",0);

				$IS4C_LOCAL->set("fntlflag",0);

				$IS4C_LOCAL->set("togglefoodstamp",0);
				$IS4C_LOCAL->set("toggletax",0);

				setglobalflags(0);

				if ($hitareflag != 1) $ret['output'] = lastpage();
			}
		}

		$IS4C_LOCAL->set("quantity",0);
		$IS4C_LOCAL->set("itemPD",0);

		$ret['redraw_footer'] = True;

		if ($ret['main_frame'] === false && !isset($ret['output']))
			return array();
		else
			return $ret;
	}

	function couponcode($upc,$ean=false) {
		global $IS4C_LOCAL;

		$man_id = substr($upc, 3, 5);
		$fam = substr($upc, 8, 3);
		$val = substr($upc, -2);

		$db = pDataConnect();
		$query = "select * from CouponCodes where code = '".$val."'";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		if ($num_rows == 0) 
			return boxMsg("coupon type unknown<br>please enter coupon<br>manually");
		else {
			$row = $db->fetch_array($result);
			$value = $row["Value"];
			$qty = $row["Qty"];

			if ($fam == "992") { 
				// 992 basically means blanket accept
				// Old method of asking cashier to assign a department
				// just creates confusion
				// Instead I just try to guess, otherwise use zero
				// (since that's what would happen anyway when the
				// confused cashier does a generic coupon tender)
				$value = truncate2($value);
				$IS4C_LOCAL->set("couponupc",$upc);
				$IS4C_LOCAL->set("couponamt",$value);

				$dept = 0;
				$db = tDataConnect();
				$query = "select department from localtemptrans WHERE
					substring(upc,4,5)='$man_id' group by department
					order by count(*) desc";
				$result = $db->query($query);
				if ($db->num_rows($result) > 0)
					$dept = array_pop($db->fetch_row($result));

				addcoupon($upc, $dept, $value);
				return lastpage();
			} 
			else {
				$db->close();
				$db = tDataConnect();
				$fam = substr($fam, 0, 2);

				$query = "select max(t.unitPrice) as unitPrice,
					max(t.department) as department,
					max(t.itemQtty) as itemQtty,
					sum(case when c.quantity is null then 0 else c.quantity end) as couponQtty,
					max(case when c.quantity is null then 0 else t.foodstamp end) as foodstamp,
					max(t.emp_no) as emp_no,
					max(t.trans_no) as trans_no,
					t.trans_id from
					localtemptrans as t left join couponapplied as c
					on t.emp_no=c.emp_no and t.trans_no=c.trans_no
					and t.trans_id=c.trans_id
					where (substring(t.upc,4,5)='$man_id'";
				/* not right per the standard, but organic valley doesn't
				 * provide consistent manufacturer ids in the same goddamn
				 * coupon book */
				if ($ean)
					$query .= " or substring(t.upc,3,5)='$man_id'";
				$query .= ") and t.trans_status <> 'C'
					group by t.trans_id
					order by t.unitPrice desc";
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);

				$available = array();
				$emp_no=$transno=$dept=$foodstamp=-1;
				$act_qty = 0;
				while($row = $db->fetch_array($result)){
					if ($row["itemQtty"] - $row["couponQtty"] > 0){
						$id = $row["trans_id"];
						$available["$id"] = array(0,0);
						$available["$id"][0] = $row["unitPrice"];
						$available["$id"][1] += $row["itemQtty"];
						$available["$id"][1] -= $row["couponQtty"];
						$act_qty += $available["$id"][1];
					}
					if ($emp_no == -1){
						$emp_no = $row["emp_no"];
						$transno = $row["trans_no"];
						$dept = $row["department"];
						$foodstamp = $row["foodstamp"];
					}
				}

				$result = $db->query($query);
				$num_rows = $db->num_rows($result);

				if ($num_rows > 0) {
					if (count($available) == 0) {
						return boxMsg("Coupon already applied<BR>for this item");
					}
					else {
						if ($qty <= $act_qty) {
							if ($value == 0 && count($available) > 1){
								// decide which item(s)
								// manually by cashier maybe?
							}

							$applied = 0;
							foreach(array_keys($available) as $id){
								if ($value == 0)
									$value = -1 * $available["$id"][0];
								
								if ($qty <= $available["$id"][1]){
									$q = "INSERT INTO couponApplied VALUES (
										$emp_no,$transno,$qty,$id)";
									$r = $db->query($q);
									$applied += $qty;
								}
								else {
									$q = "INSERT INTO couponApplied VALUES (
										$emp_no,$transno,".
										$available["$id"][1].",$id)";
									$r = $db->query($q);
									$applied += $available["$id"][1];
								}

								if ($applied >= $qty) break;
							}

							$value = truncate2($value);

							addcoupon($upc, $dept, $value, $foodstamp);
							return lastpage();
						}
						else 
							return boxMsg("coupon requires ".$qty."items<BR>there are only ".$act_qty." item(s)<BR>in this transaction");
					}
				}
				else 
					return boxMsg("product not found<BR>in transaction");
			}
		}
	}

	function housecoupon($upc){
		global $IS4C_LOCAL;

		$coupID = ltrim(substr($upc,-5),"0");
		$leadDigits = substr($upc,3,5);

		/* check the first 5 digits
		 * bail out if not what's expected
		 */
		if ($leadDigits != "99999"){ // I didn't make this coupon
			return $this->memberCard($upc);
		}

		/* make sure the coupon exists
		 * and isn't expired
		 */
		$db = pDataConnect();
		$infoQ = "select endDate,limit,discountType, department,
			discountValue,minType,minValue,memberOnly, 
			case when endDate is NULL then 0 else 
			datediff(dd,getdate(),endDate) end as expired
			from
			houseCoupons where coupID=".$coupID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$infoQ = str_replace("dd,getdate(),endDate","endDate,now()",$infoQ);
			$infoQ = str_replace("limit","`limit`",$infoQ);
		}
		$infoR = $db->query($infoQ);
		if ($db->num_rows($infoR) == 0){
			return boxMsg("coupon not found");
		}
		$infoW = $db->fetch_row($infoR);
		if ($infoW["expired"] < 0){
			$expired = substr($infoW["endDate"],0,strrpos($infoW["endDate"]," "));
			return boxMsg("coupon expired ".$expired);
		}
		
		/* check the number of times this coupon
		 * has been used in this transaction
		 * against the limit */
		$transDB = tDataConnect();
		$limitQ = "select case when sum(itemQtty) is null
			then 0 else sum(itemQtty) end
			from localtemptrans where
			upc = '".$upc."'";
		$limitR = $transDB->query($limitQ);
		$times_used = array_pop($transDB->fetch_row($limitR));
		if ($times_used >= $infoW["limit"]){
			return boxMsg("coupon already applied");
		}

		/* verify the minimum purchase has been made */
		switch($infoW["minType"]){
		case "Q": // must purchase at least X
			$minQ = "select case when sum(itemQtty) is null
				then 0 else sum(itemQtty) end
			       	from localtemptrans
				as l left join opData.dbo.houseCouponItems 
				as h on l.upc = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty < $infoW["minValue"]){
				return boxMsg("coupon requirements not met");
			}
			break;
		case "Q+": // must purchase more than X
			$minQ = "select case when sum(itemQtty) is null
				then 0 else sum(itemQtty) end
			       	from localtemptrans
				as l left join opData.dbo.houseCouponItems 
				as h on l.upc = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty <= $infoW["minValue"]){
				return boxMsg("coupon requirements not met");
			}
			break;
		case 'D': // must at least purchase from department
			$minQ = "select case when sum(total) is null
				then 0 else sum(total) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.department = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty < $infoW["minValue"]){
				return boxMsg("coupon requirements not met");
			}
			break;
		case 'D+': // must more than purchase from department 
			$minQ = "select case when sum(total) is null
				then 0 else sum(total) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.department = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty <= $infoW["minValue"]){
				return boxMsg("coupon requirements not met");
			}
			break;
		case 'M': // must purchase at least X qualifying items
			  // and some quantity corresponding discount items
			$minQ = "select case when sum(itemQtty) is null then 0 else
				sum(itemQtty) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=$coupID
				and h.type = 'QUALIFIER'";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));

			$min2Q = "select case when sum(itemQtty) is null then 0 else
				sum(itemQtty) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=$coupID
				and h.type = 'DISCOUNT'";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$min2Q = str_replace("dbo.","",$min2Q);
			$min2R = $transDB->query($min2Q);
			$validQtty2 = array_pop($transDB->fetch_row($min2R));

			if ($validQtty < $infoW["minValue"] || $validQtty2 <= 0){
				return boxMsg("coupon requirements not met");
			}
			break;
		case '$': // must purchase at least $ total items
			$minQ = "SELECT sum(total) FROM localtemptrans
				WHERE trans_type IN ('I','D','M')";
			$minR = $transDB->query($minQ);
			$validAmt = array_pop($transDB->fetch_row($minR));
			if ($validAmt < $infoW["minValue"]){
				return boxMsg("coupon requirements not met");
			}
			break;
		case '$+': // must purchase more than $ total items
			$minQ = "SELECT sum(total) FROM localtemptrans
				WHERE trans_type IN ('I','D','M')";
			$minR = $transDB->query($minQ);
			$validAmt = array_pop($transDB->fetch_row($minR));
			if ($validAmt <= $infoW["minValue"]){
				return boxMsg("coupon requirements not met");
			}
			break;
		case '': // no minimum
		case ' ':
			break;
		default:
			return boxMsg("unknown minimum type ".$infoW["minType"]);
		}

		if ($infoW["memberOnly"] == 1 and 
		   ($IS4C_LOCAL->get("memberID") == "0" or $IS4C_LOCAL->get("isMember") != 1)
		   ){
			return boxMsg("Member only coupon<br>Apply member number first");
		}

		if ($infoW["memberOnly"] == 1 && $IS4C_LOCAL->get("standalone")==0){
			$mDB = mDataConnect();
			$mR = $mDB->query("SELECT quantity FROM houseCouponThisMonth
				WHERE card_no=".$IS4C_LOCAL->get("memberID")." and
				upc='$upc'");
			if ($mDB->num_rows($mR) > 0){
				$uses = array_pop($mDB->fetch_row($mR));
				if ($infoW["limit"] >= $uses){
					return boxMsg("Coupon already used<br />on this membership");
				}
			}
		}

		/* if we got this far, the coupon
		 * should be valid
		 */
		$value = 0;
		$dept = 0;
		switch($infoW["discountType"]){
		case "Q": // quantity discount
			// discount = coupon's discountValue
			// times the cheapeast coupon item
			$valQ = "select unitPrice, department from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=".$coupID." 
				and h.type in ('BOTH','DISCOUNT')
				and l.total >0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$valQ = str_replace("dbo.","",$valQ);
			$valR = $transDB->query($valQ);
			$valW = $transDB->fetch_row($valR);
			$value = $valW[0]*$infoW["discountValue"];
			$dept = $valW[1];
			break;
		case "P": // discount price
			// query to get the item's department and current value
			// current value minus the discount price is how much to
			// take off
			$value = $infoW["discountValue"];
			$deptQ = "select department,(total/quantity) as value from localtemptrans
				as l left join opdata.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=".$coupID."
				and h.type in ('BOTH','DISCOUNT')
				and l.total >0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$deptQ = str_replace("dbo.","",$deptQ);
			$deptR = $transDB->query($deptQ);
			$row = $transDB->fetch_row($deptR);
			$dept = $row[0];
			$value = $row[1] - $value;
			break;
		case "FD": // flat discount for departments
			// simply take off the requested amount
			// scales with quantity for by-weight items
			$value = $infoW["discountValue"];
			$valQ = "select department,quantity from localtemptrans
				as l left join opdata.dbo.houseCouponItems
				as h on l.department = h.upc
				where h.coupID=".$coupID."
				and h.type in ('BOTH','DISCOUNT')
				and l.total > 0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$valQ = str_replace("dbo.","",$valQ);
			$valR = $transDB->query($valQ);
			$row = $transDB->fetch_row($valR);
			$value = $row[1] * $value;
			break;
		case "FI": // flat discount for items
			// simply take off the requested amount
			// scales with quantity for by-weight items
			$value = $infoW["discountValue"];
			$valQ = "select l.upc,quantity from localtemptrans
				as l left join opdata.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=".$coupID."
				and h.type in ('BOTH','DISCOUNT')
				and l.total > 0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$valQ = str_replace("dbo.","",$valQ);
			$valR = $transDB->query($valQ);
			$row = $transDB->fetch_row($valR);
			$value = $row[1] * $value;
			break;
		case "F": // completely flat; no scaling for weight
			$value = $infoW["discountValue"];
			break;
		case "%": // percent discount on all items
			getsubtotals();
			$value = $infoW["discountValue"]*$IS4C_LOCAL->get("discountableTotal");
			break;
		}

		$dept = $infoW["department"];

		addhousecoupon($upc,$dept,-1*$value);
		return lastpage();
	}

	function memberCard($upc){
		$key = substr($upc,-10);
		$hashkey = md5($key);

		$db = pDataConnect();
		$query = "select card_no from customerCards where hashkey='$hashkey'";
		$result = $db->query($query);

		if ($db->num_rows($result) < 1){
			return boxMsg("Invalid card");
		}

		$row = $db->fetch_array($result);
		return memberID($row[0]);
	}

	function add_deposit($upc){
		global $IS4C_LOCAL;

		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

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

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>product number</i></td>
				<td>Try to ring up the specified product.
				Coupon handling is included here</td>
			</tr>
			</table>";
	}
}

?>
