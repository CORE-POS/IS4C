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
		else if (substr($str,0,4) == "GS1~")
			return True;
		return False;
	}

	function parse($str){
		if (substr($str,0,4) == "GS1~")
			$str = $this->fixGS1($str);

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

		/* extract scale-sticker prices */
		$scaleprice = 0;
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

		// wfc uses deposit field to link another upc
		if (isset($row["deposit"]) && $row["deposit"] > 0){
			$dupc = (int)$row["deposit"];
			$this->add_deposit($dupc);
		}

		$upc = $row["upc"];
		$row['numflag'] = isset($row["local"])?$row["local"]:0;

		/* do tax shift */
		$tax = $row['tax'];
		if ($IS4C_LOCAL->get("toggletax") != 0) {
			$tax = ($tax==0) ? 1 : 0;
			$IS4C_LOCAL->set("toggletax",0);
		}
		$row['tax'] = $tax;

		/* do foodstamp shift */
		$foodstamp = $row["foodstamp"];
		if ($IS4C_LOCAL->get("togglefoodstamp") != 1){
			$IS4C_LOCAL->set("togglefoodstamp",0);
			$foodstamp = ($foodstamp==0) ? 1 : 0;
		}
		$row['foodstamp'] = $foodstamp;

		/* do discount shifts */
		$discountable = $row["discount"];
		if ($IS4C_LOCAL->get("toggleDiscountable") == 1) {
			$IS4C_LOCAL->set("toggleDiscountable",0);
			$discountable = ($discountable == 0) ? 1 : 0;
		}
		$row['discount'] = $discountable;

		/*
			BEGIN: figure out discounts by type
		*/

		/* get discount object */
		$discounttype = nullwrap($row["discounttype"]);
		$DTClasses = $IS4C_LOCAL->get("DiscountTypeClasses");
		if (!class_exists($DTClasses[$discounttype]))
			include($IS4C_PATH."lib/Scanning/DiscountTypes/".$DTClasses[$discounttype].".php");
		$DiscountObject = new $DTClasses[$discounttype];

		/* add in sticker price and calculate a quantity
		   if the item is stickered, scaled, and on sale */
		if (substr($upc,0,3) == "002"){
			if ($DiscountObject->isSale() && $scale == 1)
				$quantity = truncate2($scaleprice / $row["normal_price"]);
			$row['normal_price'] = $scaleprice;
		}

		// don't know what this is - wedge?
		if ($IS4C_LOCAL->get("nd") == 1 && $discountable == 7) {
			$discountable = 3;
			$IS4C_LOCAL->set("nd",0);
		}

		/*
			END: figure out discounts by type
		*/

		/* get price method object  & add item*/
		$pricemethod = nullwrap($row["pricemethod"]);
		if ($DiscountObject->isSale())
			$pricemethod = nullwrap($row["specialpricemethod"]);
		$PMClasses = $IS4C_LOCAL->get("PriceMethodClasses");
		if (!class_exists($PMClasses[$pricemethod]))
			include($IS4C_PATH."lib/Scanning/PriceMethods/".$PMClasses[$pricemethod].".php");
		$PriceMethodObject = new $PMClasses[$pricemethod];
		$PriceMethodObject->addItem($row, $quantity, $DiscountObject);	

		// cleanup, reset flags and beep
		if ($quantity != 0) {
			// ddNotify is legacy/unknown. likely doesn't work
			if ($IS4C_LOCAL->get("ddNotify") == 1 && $IS4C_LOCAL->get("itemPD") == 10) {
				$IS4C_LOCAL->set("itemPD",0);
				$discountable = 7;
			}
			$intvoided = 0;
			if ($IS4C_LOCAL->get("ddNotify") == 1 && $discountable == 7) 
				$intvoided = 22;

			$IS4C_LOCAL->set("msgrepeat",0);
			$IS4C_LOCAL->set("qttyvalid",0);

			$ret['udpmsg'] = 'goodBeep';
		}

		// probably pointless, see what happens without it
		//if ($tax != 1) $IS4C_LOCAL->set("voided",0);

		/* add discount notifications lines, if applicable */
		$DiscountObject->addDiscountLine();

		/* reset various flags and variables */
		if ($IS4C_LOCAL->get("tare") != 0) $IS4C_LOCAL->set("tare",0);
		$IS4C_LOCAL->set("ttlflag",0);
		$IS4C_LOCAL->set("fntlflag",0);
		$IS4C_LOCAL->set("quantity",0);
		$IS4C_LOCAL->set("itemPD",0);
		$IS4C_LOCAL->set("voided",0);
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

	function fixGS1($str){
		// remove GS1~ prefix + two additional characters
		$str = substr($str,6);

		// check application identifier

		// coupon; return whole thing
		if (substr($str,0,4) == "8110")
			return $str;

		// GTIN-14; return w/o check digit,
		// ignore any other fields for now
		if (substr($str,0,1) == "10")
			return substr($str,2,13);
		
		// application identifier not recognized
		// will likely cause no such item error
		return $str; 
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
