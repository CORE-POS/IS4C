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
if (!function_exists("addItem")) include($IS4C_PATH."lib/additem.php");
if (!function_exists("boxMsg")) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("nullwrap")) include($IS4C_PATH."lib/lib.php");
if (!function_exists("setglobalvalue")) include_once($IS4C_PATH."lib/loadconfig.php");
if (!function_exists("boxMsgscreen")) include_once($IS4C_PATH."lib/clientscripts.php");
if (!function_exists("list_items")) include_once($IS4C_PATH."lib/listitems.php");
if (!function_exists("memberID")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

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
		global $IS4C_LOCAL,$IS4C_PATH;
		$ret = $this->default_json();

		/* force cashiers to enter a comment on refunds */
		if ($IS4C_LOCAL->get("refund")==1 && $IS4C_LOCAL->get("refundComment") == ""){
			$ret['main_frame'] = $IS4C_PATH.'gui-modules/refundComment.php';
			$IS4C_LOCAL->set("refundComment",$IS4C_LOCAL->get("strEntered"));
			return $ret;
		}

		$entered = str_replace(".", " ", $entered);

		$quantity = $IS4C_LOCAL->get("quantity");
		if ($IS4C_LOCAL->get("quantity") == 0 && $IS4C_LOCAL->get("multiple") == 0) $quantity = 1;
		$scaleprice = 0;

		/* exapnd UPC-E */
		if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
			$p6 = substr($entered, -1);
			if ($p6 == 0) $entered = substr($entered, 0, 3)."00000".substr($entered, 3, 3);
			elseif ($p6 == 1) $entered = substr($entered, 0, 3)."10000".substr($entered, 3, 3);
			elseif ($p6 == 2) $entered = substr($entered, 0, 3)."20000".substr($entered, 3, 3);
			elseif ($p6 == 3) $entered = substr($entered, 0, 4)."00000".substr($entered, 4, 2);
			elseif ($p6 == 4) $entered = substr($entered, 0, 5)."00000".substr($entered, 5, 1);
			else $entered = substr($entered, 0, 6)."0000".$p6;
		}

		/* make sure upc length is 13 */
		$upc = "";
		if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) $upc = "0".substr($entered, 0, 12);
		else $upc = substr("0000000000000".$entered, -13);

		if (substr($upc, 0, 3) == "002") {
			$scaleprice = truncate2(substr($upc, -4)/100);
			$upc = substr($upc, 0, 8)."00000";
			if ($upc == "0020006000000" || $upc == "0020010000000") $scaleprice *= -1;
		}

		$db = pDataConnect();
		$query = "select inUse,upc,description,normal_price,scale,deposit,
			qttyEnforced,department,local,cost,tax,foodstamp,discount,
			discounttype,specialpricemethod,special_price,groupprice,
			pricemethod,quantity,specialgroupprice,specialquantity,
			mixmatchcode
		       	from products where upc = '".$upc."'";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		/* check for special upcs that aren't really products */
		if ($num_rows == 0){
			$objs = $IS4C_LOCAL->get("SpecialUpcClasses");
			foreach($objs as $class_name){
				if (!class_exists($class_name))
					include($IS4C_PATH.'lib/Scanning/SpecialUPCs/'.$class_name.'.php');
				$instance = new $class_name();
				if ($instance->is_special($upc)){
					return $instance->handle($upc,$ret);
				}
			}
			// no match; not a product, not special
			$ret['output'] = boxMsg($upc."<br /><b>is not a valid item</b>");
			return $ret; 
		}

		/* product exists
		   BEGIN error checking round #1
		*/
		$row = $db->fetch_array($result);

		/* Implementation of inUse flag
		 *   if the flag is not set, display a warning dialog noting this
		 *   and allowing the sale to be confirmed or canceled
		 */
		if ($row["inUse"] == 0){
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
				$ret['main_frame'] = $IS4C_PATH."gui-modules/boxMsg2.php";
				return $ret;
			}
		}

		/* sanity check - ridiculous price 
		   (can break db column if it doesn't fit
		*/
		if (strlen($row["normal_price"]) > 8){
			$ret['output'] = boxMsg("$upc<br>Claims to be more than $100,000");
			return $ret;
		}

		$scale = ($row["scale"] == 0) ? 0 : 1;

		/* need a weight with this item
		   retry the UPC in a few milliseconds and see
		*/
		if ($scale != 0 && $IS4C_LOCAL->get("weight") == 0 && 
			$IS4C_LOCAL->get("quantity") == 0 && substr($upc,0,3) != "002") {

			$IS4C_LOCAL->set("SNR",1);
			$ret['output'] = boxMsg("please put item on scale");
			$IS4C_LOCAL->set("wgtRequested",0);
			$ret['retry'] = $IS4C_LOCAL->get("strEntered");
			return $ret;
		}

		/* got a scale weight, make sure the tare
		   is valid */
		if ($scale != 0){
			$quantity = $IS4C_LOCAL->get("weight") - $IS4C_LOCAL->get("tare");
			if ($IS4C_LOCAL->get("quantity") != 0) 
				$quantity = $IS4C_LOCAL->get("quantity") - $IS4C_LOCAL->get("tare");

			if ($quantity <= 0){
				$ret['output'] = boxMsg("item weight must be greater than tare weight");
				return $ret;
			}
			$IS4C_LOCAL->set("tare",0);
		}

		/* non-scale items need integer quantities */	
		if ($row["scale"] == 0 && (int) $IS4C_LOCAL->get("quantity") != $IS4C_LOCAL->get("quantity") ) {
			$ret['output'] = boxMsg("fractional quantity cannot be accepted for this item");
			return $ret;
		}

		/* quantity required for this item. Send to
		   entry page if one wasn't provided */
		$qttyEnforced = $row["qttyEnforced"];
		if (($qttyEnforced == 1) && ($IS4C_LOCAL->get("multiple") == 0) && ($IS4C_LOCAL->get("msgrepeat") == 0)) {
			$ret['main_frame'] = $IS4C_PATH."gui-modules/qtty2.php";
			return $ret;
		}
		else
			$IS4C_LOCAL->set("qttyvalid",1); // this may be unnecessary

		/* wedge I assume
		   I don't like this being hard-coded, but since these UPCs
		   are entries in products they can't go in a SpecialUPC
		   object (unless SpecialUPC checks take place on every
		   scan, but that's more overhead than I want on such a common
		   operation
		*/
		if ($upc == "0000000008010" && $IS4C_LOCAL->get("msgrepeat") == 0) {
			$IS4C_LOCAL->set("endorseType","giftcert");
			$IS4C_LOCAL->set("tenderamt",$total);
			$IS4C_LOCAL->set("boxMsg","<b>".$total." gift certificate</b><br />
				insert document<br />press [enter] to endorse
				<p><font size='-1'>[clear] to cancel</font>");
			$ret["main_frame"] = $IS4C_PATH."gui-modules/boxMsg2.php";
			return $ret;
		}

		/* wedge I assume
		   see 0000000008010 above
		*/
		if ($upc == "0000000008011" && $IS4C_LOCAL->get("msgrepeat") == 0) {
			$IS4C_LOCAL->set("endorseType","classreg");
			$IS4C_LOCAL->set("tenderamt",$total);
			$IS4C_LOCAL->set("boxMsg","<b>".$total." class registration</b><br />
				insert form<br />press [enter] to endorse
				<p><font size='-1'>[clear] to cancel</font>");
			$ret["main_frame"] = $IS4C_PATH."gui-modules/boxMsg2.php";
			return $ret;
		}

		/*
		   END error checking round #1
		*/	

		if ($row["deposit"] > 0){
			$dupc = (int)$row["deposit"];
			$this->add_deposit($dupc);
		}

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


		/* do tax shift */
		$tax = $row['tax'];
		if ($IS4C_LOCAL->get("toggletax") != 0) {
			$tax = ($tax==0) ? 1 : 0;
			$IS4C_LOCAL->set("toggletax",0);
		}

		/* do foodstamp shift */
		$foodstamp = $row["foodstamp"];
		if ($IS4C_LOCAL->get("togglefoodstamp") != 1){
			$IS4C_LOCAL->set("togglefoodstamp",0);
			$foodstamp = ($foodstamp==0) ? 1 : 0;
		}

		/* do discount shifts */
		$discounttype = nullwrap($row["discounttype"]);
		$discountable = $row["discount"];
		$discountmethod = nullwrap($row["specialpricemethod"]);
		if ($IS4C_LOCAL->get("toggleDiscountable") == 1) {
			$IS4C_LOCAL->set("toggleDiscountable",0);
			$discountable = ($discountable == 0) ? 1 : 0;
		}

		/* deal with scale (e.g., hobart) stickered items */ 
		if (substr($upc, 0, 3) == "002" && ($scale == 0 || $discounttype == 0)) {
			/* if the item isn't on sale or isn't sold by weight,
			   just use the price from the sticker 
			   and don't worry about exact weight */
			$unitPrice = $scaleprice;
			$regPrice = $unitPrice;
		}
		elseif (substr($upc,0,3) == "002" && $row["scale"] != 0){
			/* if the item is on sale AND sold by weight,
			   use the sticker price to calculate the
			   actual weight */
			$quantity = truncate2($scaleprice / $row["normal_price"]);
			$unitPrice = $row["normal_price"];
			$regPrice = $row["normal_price"];
		}

		/*
			BEGIN: figure out discounts by type
			Set up $discount and $memDiscount,
			adjust unitPrice if needed	
		*/

		$discount = 0;
		$memDiscount = 0;

		// don't know what this is - wedge?
		if ($IS4C_LOCAL->get("nd") == 1 && $discountable == 7) {
			$discountable = 3;
			$IS4C_LOCAL->set("nd",0);
		}

		/* member special pricing
		   2 => special_price is an alternate price
		   4 => special_price is a discount amount
		*/
		if ($discounttype == 2 || $discounttype == 4) {
			$memDiscount = truncate2($row["normal_price"] * $quantity) - truncate2($row["special_price"] * $quantity);
			$discount = 0;
			if ($IS4C_LOCAL->get("isMember") == 1)
				$unitPrice = nullwrap($row["special_price"]);
		}
		/* special price for everyone */
		elseif ($discounttype == 1 && $discountmethod == 0) {
			$discount = ($unitPrice - $row["special_price"]) * $quantity;
			$memDiscount = 0;
			$unitPrice = $row["special_price"];
		}
		/* doing a % discount for members - andy */
		elseif ($discounttype == 3) {
			$discount = 0;
			$memDiscount = truncate2($row['special_price']*$unitPrice);
			if ($IS4C_LOCAL->get("isMember"))
				$unitPrice = nullwrap($unitPrice - $memDiscount);
		}
		/* haven't looked at this in forever; might
		   actually be the same as type #4 */
		elseif ($discounttype == 5){
			$discount = 0;
			$memDiscount = truncate2($row["special_price"]*$quantity);
			if ($IS4C_LOCAL->get("isMember"))
				$unitPrice = nullwrap($unitPrice - $memDiscount);
		}

		/* wedge? */
		if ($IS4C_LOCAL->get("casediscount") > 0 && $IS4C_LOCAL->get("casediscount") <= 100) {
			$casediscount = (100 - $IS4C_LOCAL->get("casediscount"))/100;
			$unitPrice = $casediscount * $unitPrice;
		}

		/*
			END: figure out discounts by type
		*/

		/*
			BEGIN: group pricing
			pricemethods > 0
		*/

		//-------------Mix n Match -------------------------------------
		$matched = 0;

		$VolSpecial = nullwrap($row["groupprice"]);	
		$volDiscType = nullwrap($row["pricemethod"]);
		$volume = nullwrap($row["quantity"]);

		/* use equivalent "special" columns for group sales */
		$isVolumeSale = false;
		$potentialMemSpecial = false;
		if ($row["specialpricemethod"] > 0){
			$VolSpecial = nullwrap($row["specialgroupprice"]);
			$volDiscType = nullwrap($row["specialpricemethod"]);
			$volume = nullwrap($row["specialquantity"]);
			
			$isVolumeSale = true;
			if ($discounttype == 2 && $IS4C_LOCAL->get("isMember") == 1)
				$potentialMemSpecial = true;
		}

		$mixMatch  = $row["mixmatchcode"];
		if ($volDiscType != 0){
			$dbt = tDataConnect();
			/* switch on pricing method */
			switch($volDiscType){
			case 1: // not really sure; historic
			case 2: // X for $Y (e.g., $0.50 each or 3 for $1)
				$queryt = "select sum(ItemQtty - matched) as mmqtty, 
					mixMatch from localtemptrans 
					where trans_status <> 'R' AND 
					mixMatch = '".$mixMatch."' group by mixMatch";
				if (!$row["mixmatchcode"] || $row["mixmatchcode"] == '0') {
					$mixMatch = 0;
					$queryt = "select sum(ItemQtty - matched) as mmqtty from "
						."localtemptrans where trans_status<>'R' AND "
						."upc = '".$row["upc"]."' group by upc";
				}
				$resultt = $dbt->query($queryt);
				$num_rowst = $dbt->num_rows($resultt);

				if ($volDiscType == 1)
					$unitPrice = truncate2($VolSpecial/$volume);  

				$voladj = $VolSpecial - (($volume - 1) * $unitPrice); // one at special price
				$newmm = (int) ($quantity/$volume); // number of complete sets

				$mmqtty = 0;
				if ($num_rowst > 0) {
					$rowt = $dbt->fetch_array($resultt);
					// number not in complete sets in localtemptrans
					$mmqtty = floor($rowt["mmqtty"]); 
				}

				// unmatched items in localtemptrans + any left
				// over from this scan after accounting for
				// new complete sets
				$newmmtotal = $mmqtty + (floor($quantity) % $volume);		 

				/* add complete sets
				   Items on member special are just added at
				   regular price if a member number hasn't
				   been entered yet */
				if ($newmm >= 1) {
					if (!$isVolumeSale || ($isVolumeSale && !$potentialMemSpecial)){
						addItem($upc, $description, "I", "", "", $department, 
							$newmm * $volume, truncate2($VolSpecial), 
							truncate2($newmm * $VolSpecial), 
							truncate2($VolSpecial), $scale, $tax, $foodstamp, 
							$discount, $memDiscount, $discountable, 
							$discounttype, $volume * $newmm, 
							$volDiscType, $volume, $VolSpecial, 
							$mixMatch, $volume * $newmm, 0, 
							truncate2($newmm*$cost),$numflag,$charflag);
					}
					else if ($isVolumeSale && $potentialMemSpecial){
						addItem($upc, $description, "I", "", "", $department, 
							$newmm * $volume, truncate2($unitPrice), 
							truncate2($newmm * $volume * $unitPrice), 
							truncate2($unitPrice), $scale, $tax, $foodstamp, 
							$discount, 
							$newmm * (($volume * $unitPrice) - $VolSpecial), 
							$discountable, 
							$discounttype, $volume * $newmm, 
							$volDiscType, $volume, $VolSpecial, 
							$mixMatch, $volume * $newmm, 0, 
							truncate2($newmm*$cost),$numflag,$charflag);
					}
					$quantity = $quantity - ($newmm*$volume);
					$newmm = 0;
					$IS4C_LOCAL->set("qttyvalid",0);
				}

				/* if this ring completes a set with
				   existing unmatched items in localtemptrans,
				   add an item with volume adjusted price
				   again, member specials are handled
				   differently if a member number hasn't
				   been entered yet */
				if ($newmmtotal >= $volume) {
					if (!$isVolumeSale || ($isVolumeSale && !$potentialMemSpecial)){
						addItem($upc, $description, "I", "", "", $department, 
							1, $voladj, $voladj, $voladj, $scale, $tax, 
							$foodstamp, $discount, $memDiscount, $discountable, 
							$discounttype, 1, $volDiscType, $volume, 
							$VolSpecial, $mixMatch, $volume, 0, 
							$cost, $numflag, $charflag);
					}
					else if ($isVolumeSale && $potentialMemSpecial){
						addItem($upc, $description, "I", "", "", $department, 
							1, $unitPrice, $unitPrice, $unitPrice, $scale, $tax, 
							$foodstamp, $discount, 
							$unitPrice - $voladj, $discountable, 
							$discounttype, 1, $volDiscType, $volume, 
							$VolSpecial, $mixMatch, $volume, 0, 
							$cost, $numflag, $charflag);
					}
					$quantity = $quantity - 1;
					if ($quantity < 0) $quantity = 0; // might happen with scaled item
					$newmmtotal = 0;
					$IS4C_LOCAL->set("qttyvalid",0);
				}
				break; // end case 1,2

			case 3:
			case 4:
				/* not straight-up interchangable
				 * ex: buy item A, get $1 off item B
				 * need strict pairs AB 
				 *
				 * type 3 tries to split the discount amount
				 * across A & B's departments; type 4
				 * does not 
				 */
				$qualMM = abs($mixMatch);
				$discMM = -1*abs($mixMatch);

				// lookup existing qualifiers (i.e., item As)
				// by-weight items are rounded down here
				$q1 = "SELECT floor(sum(ItemQtty)),max(department) 
					FROM localtemptrans WHERE mixMatch='$qualMM' 
					and trans_status <> 'R'";
				$r1 = $dbt->query($q1);
				$quals = 0;
				$dept1 = 0;
				if($dbt->num_rows($r1)>0){
					$row = $dbt->fetch_row($r1);
					$quals = round($row[0]);
					$dept1 = $row[1];	
				}

				// lookup existing discounters (i.e., item Bs)
				// by-weight items are counted per-line here
				//
				// extra checks to make sure the maximum
				// discount on scale items is "free"
				$q2 = "SELECT sum(CASE WHEN scale=0 THEN ItemQtty ELSE 1 END),
					max(department),max(scale),max(total) FROM localtemptrans 
					WHERE mixMatch='$discMM' 
					and trans_status <> 'R'";
				$r2 = $dbt->query($q2);
				$dept2 = 0;
				$discs = 0;
				$discountIsScale = False;
				$scaleDiscMax = 0;
				if($dbt->num_rows($r2)>0){
					$row = $dbt->fetch_row($r2);
					$discs = round($row[0]);
					$dept2 = $row[1];
					if ($row[2]==1) $discountIsScale = True;
					$scaleDiscMax = $row[3];
				}
				if ($quantity != (int)$quantity && $mixMatch < 0){
					$discountIsScale = True;
					$scaleDiscMax = $quantity * $unitPrice;
				}

				// items that have already been used in an AB set
				$q3 = "SELECT sum(matched) FROM localtemptrans WHERE
					mixmatch IN ('$qualMM','$discMM')";
				$r3 = $dbt->query($q3);
				$matches = ($dbt->num_rows($r3)>0)?array_pop($dbt->fetch_array($r3)):0;

				// reduce totals by existing matches
				// implicit: quantity required for B = 1
				// i.e., buy X item A save on 1 item B
				$matches = $matches/$volume;
				$quals -= $matches*($volume-1);
				$discs -= $matches;
				
				// where does the currently scanned item go?
				if ($mixMatch > 0){
					$quals = ($quals >0)?$quals+floor($quantity):floor($quantity);
					$dept1 = $department;
				}
				else {
					// again, scaled items count once per line
					if ($quantity != (int)$quantity)
						$discs = ($discs >0)?$discs+1:1;
					else
						$discs = ($discs >0)?$discs+$quantity:$quantity;
					$dept2 = $department;
				}

				// count up complete sets
				$sets = 0;
				while($discs > 0 && $quals >= ($volume-1) ){
					$discs -= 1;
					$quals -= ($volume -1);
					$sets++;
				}
				
				if ($sets > 0){
					// if the current item is by-weight, quantity
					// decrement has to be corrected, but matches
					// should still be an integer
					$ttlMatches = $sets;
					if($quantity != (int)$quantity) $sets = $quantity;
					$quantity = $quantity - $sets;

					if ($quantity < 0) $quantity = 0;

					$qualDisc = $sets*($volume-1)*($VolSpecial/$volume);
					$discDisc = $sets*($VolSpecial/$volume);
					$maxDiscount = $sets*$VolSpecial;

					if ($scaleDiscMax != 0 && $maxDiscount > $scaleDiscMax){
						$maxDiscount = truncate2($scaleDiscMax);
						$qualDisc = truncate2($scaleDiscMax / 2);
						$discDisc = truncate2($scaleDiscMax / 2);
					}

					/* everything except member specials gets a separate
					   "discount" line
					*/
					if (!$isVolumeSale || ($isVolumeSale && $discounttype != 2)){
						addItem($upc, $description, "I", "", "", $department, 
							$sets, truncate2($unitPrice), 
							truncate2($sets * $unitPrice), 
							truncate2($unitPrice), $scale, $tax, $foodstamp, 
							$discount, $memDiscount, $discountable, 
							$discounttype, $sets, $volDiscType, $volume, 
							$VolSpecial, $mixMatch, $volume*$ttlMatches, 0, 
							truncate2($sets*$cost),$numflag,$charflag);
						/* type 3 => split discount across depts
						 * type 4 => all discount on disc dept
						 */
						if ($volDiscType == 3){
							additemdiscount($dept1,$qualDisc);
							additemdiscount($dept2,$discDisc);
						}
						elseif($volDiscType == 4){
							additemdiscount($dept2,$maxDiscount);
						}
					}
					else if ($isVolumeSale && $discounttype == 2){
						// don't bother trying to split discount
						addItem($upc, $description, "I", "", "", $department, 
							$sets, truncate2($unitPrice), 
							truncate2($sets * $unitPrice), 
							truncate2($unitPrice), $scale, $tax, $foodstamp, 
							$discount, $maxDiscount, $discountable, 
							$discounttype, $sets, $volDiscType, $volume, 
							$VolSpecial, $mixMatch, $volume*$ttlMatches, 0, 
							truncate2($sets*$cost),$numflag,$charflag);
					}
					$IS4C_LOCAL->set("qttyvalid",0);
				}
				break; // end case 3,4

			case 5:
				/* elaborate set matching; can require up to
				   11 separate items
				   Qualifying item(s) have a mixmatch code
				   with a matching 'stem' plus '_qX'
				   (e.g., mmitemstem_q0, mmitemstem_q1, etc)
				   Discount item has the same stem plus '_d'
				   (e.g, mmitemstem_d)
				*/
				$mixMatch  = $row["mixmatchcode"];
				$stem = substr($mixMatch,0,10);	
				$sets = 99;
				// count up total sets
				for($i=0; $i<=$volume; $i++){
					$tmp = $stem."_q".$i;
					if ($volume == $i) $tmp = $stem.'_d';

					$chkQ = "SELECT sum(CASE WHEN scale=0 THEN ItemQtty ELSE 1 END) 
						FROM localtemptrans WHERE mixmatch='$tmp' 
						and trans_status<>'R'";
					$chkR = $dbt->query($chkQ);
					$tsets = array_pop($dbt->fetch_row($chkR));
					if ($tsets == ""){
						$tsets = 0;
					}
					if ($tmp == $mixMatch){
						$tsets += is_int($quantity)?$quantity:1;
					}

					if ($tsets < $sets)
						$sets = $tsets;

					// item not found, no point continuing
					if ($sets == 0) break;
				}

				// count existing sets
				$matches = 0;
				$mQ = "SELECT sum(matched) FROM localtemptrans WHERE
					left(mixmatch,11)='{$stem}_'";
				$mR = $dbt->query($mQ);
				if ($dbt->num_rows($mR) > 0)
					$matches = array_pop($dbt->fetch_row($mR));
				$sets -= $matches;
				
				// this means the current item
				// completes a new set
				if ($sets > 0){
					if($quantity != (int)$quantity) $sets = $quantity;

					// mem specials again; see case 1,2
					if (!$isVolumeSale || ($isVolumeSale && !$potentialMemSpecial)){
						addItem($upc, $description, "I", "", "", $department, 
							$sets, truncate2($unitPrice), 
							truncate2($sets * $unitPrice), 
							truncate2($unitPrice), $scale, $tax, $foodstamp, 
							$discount, $memDiscount, $discountable, 
							$discounttype, $sets, $volDiscType, $volume, 
							$VolSpecial, $mixMatch, $sets, 0, 
							truncate2($sets*$unitPrice),$numflag,$charflag);
						$discount_dept = 0;
						if ($mixMatch == $stem.'_d')
							$discount_dept = $department;
						else {
							$dQ = "SELECT max(department),sum(ItemQtty) 
								FROM localtemptrans
								WHERE mixmatch='{$stem}_d'";
							$dR = $dbt->query($dQ);
							$dW = $dbt->fetch_row($dR);
							$discount_dept = $dW[0];
							$sets = $dW[1];
						}
						additemdiscount($discount_dept,$sets*$VolSpecial);
					}
					else if ($isVolumeSale && $potentialMemSpecial){
						addItem($upc, $description, "I", "", "", $department, 
							$sets, truncate2($unitPrice), 
							truncate2($sets * $unitPrice), 
							truncate2($unitPrice), $scale, $tax, $foodstamp, 
							$discount, $sets*$VolSpecial, $discountable, 
							$discounttype, $sets, $volDiscType, $volume, 
							$VolSpecial, $mixMatch, $sets, 0, 
							truncate2($sets*$unitPrice),$numflag,$charflag);
					}
					$IS4C_LOCAL->set("qttyvalid",0);
					$quantity -= $sets;
				}
				break; // end case 5
			} // end switching on price method
		}

		/*
			END: group pricing
		*/

		$total = truncate2($unitPrice * $quantity);
		$unitPrice = truncate2($unitPrice);

		/* got this far:
		   the item is valid and there's a quantity left
		   to add to the transaction */
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

			addItem($upc, $description, "I", " ", " ", $department, $quantity, $unitPrice, 
				$total, $regPrice, $scale, $tax, $foodstamp, $discount, $memDiscount, 
				$discountable, $discounttype, $qtty, $volDiscType, $volume, $VolSpecial, 
				$mixMatch, $matched, $intvoided, truncate2($quantity*$cost),$numflag,$charflag);
			$IS4C_LOCAL->set("msgrepeat",0);
			$IS4C_LOCAL->set("qttyvalid",0);

			$ret['udpmsg'] = 'goodBeep';
		}

		if ($tax != 1) $IS4C_LOCAL->set("voided",0);

		/* add discount notifications lines, if applicable */
		if ($discounttype == 1 && $discountmethod == 0) {
			$IS4C_LOCAL->set("voided",2);
			adddiscount($discount,$department);
		}
		elseif ($discounttype == 2 && $IS4C_LOCAL->get("isMember") == 1) {
			$IS4C_LOCAL->set("voided",2);
			adddiscount($memDiscount,$department);
		}
		elseif ($discounttype == 3 && $IS4C_LOCAL->get("isMember") == 1) {
			$IS4C_LOCAL->set("voided",2);
			adddiscount($memDiscount,$department);
		}
		elseif ($discounttype == 4 && $IS4C_LOCAL->get("isStaff") != 0) {
			$IS4C_LOCAL->set("voided",2);
			adddiscount($memDiscount,$department);
		}
		else 
			$IS4C_LOCAL->set("voided",0);

		/* reset various flags and variables */
		if ($IS4C_LOCAL->get("tare") != 0) $IS4C_LOCAL->set("tare",0);
		$IS4C_LOCAL->set("ttlflag",0);
		$IS4C_LOCAL->set("fntlflag",0);
		$IS4C_LOCAL->set("quantity",0);
		$IS4C_LOCAL->set("itemPD",0);
		setglobalflags(0);

		/* output item list, update totals footer */
		$ret['redraw_footer'] = True;
		$ret['output'] = lastpage();
		return $ret;
	}

	function add_deposit($upc){
		global $IS4C_LOCAL;

		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

		$db = pDataConnect();
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
