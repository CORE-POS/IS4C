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
		global $CORE_LOCAL;
		$my_url = MiscLib::base_url();
		$ret = $this->default_json();

		/* force cashiers to enter a comment on refunds */
		if ($CORE_LOCAL->get("refund")==1 && $CORE_LOCAL->get("refundComment") == ""){
			$ret['udpmsg'] = 'twoPairs';
			if ($CORE_LOCAL->get("SecurityRefund") > 20){
				$CORE_LOCAL->set("adminRequest",$my_url."gui-modules/refundComment.php");
				$CORE_LOCAL->set("adminRequestLevel",$CORE_LOCAL->get("SecurityRefund"));
				$CORE_LOCAL->set("adminLoginMsg",_("Login to issue refund"));
				$CORE_LOCAL->set("away",1);
				$ret['main_frame'] = $my_url."gui-modules/adminlogin.php";
			}
			else
				$ret['main_frame'] = $my_url.'gui-modules/refundComment.php';
			$CORE_LOCAL->set("refundComment",$CORE_LOCAL->get("strEntered"));
			return $ret;
		}

		$entered = str_replace(".", " ", $entered);

		$quantity = $CORE_LOCAL->get("quantity");
		if ($CORE_LOCAL->get("quantity") == 0 && $CORE_LOCAL->get("multiple") == 0) $quantity = 1;

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
			$scaleprice = MiscLib::truncate2(substr($upc, -4)/100);
			$upc = substr($upc, 0, 8)."00000";
			if ($upc == "0020006000000" || $upc == "0020010000000") $scaleprice *= -1;
		}

		$db = Database::pDataConnect();
		$query = "select inUse,upc,description,normal_price,scale,deposit,
			qttyEnforced,department,local,cost,tax,foodstamp,discount,
			discounttype,specialpricemethod,special_price,groupprice,
			pricemethod,quantity,specialgroupprice,specialquantity,
			mixmatchcode,idEnforced,tareweight
		       	from products where upc = '".$upc."'";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		/* check for special upcs that aren't really products */
		if ($num_rows == 0){
			$objs = $CORE_LOCAL->get("SpecialUpcClasses");
			foreach($objs as $class_name){
				$instance = new $class_name();
				if ($instance->is_special($upc)){
					return $instance->handle($upc,$ret);
				}
			}
			// no match; not a product, not special
			
			/*
			if ($CORE_LOCAL->get("requestType")!="badscan"){
				$CORE_LOCAL->set("requestType","badscan");
				$CORE_LOCAL->set("requestMsg",_("not a valid item").'<br />'._("enter description"));
				$ret['main_frame'] = $my_url.'gui-modules/requestInfo.php';
				return $ret;
			}
			else {
				$ret['output'] = DisplayLib::lastpage();
				TransRecord::addQueued($upc,$CORE_LOCAL->get("requestMsg"),0,'BS');
				$CORE_LOCAL->set("requestMsg","");
				$CORE_LOCAL->set("requestType","");
				return $ret; 
			}
			*/
			//TransRecord::addQueued($upc,'BADSCAN');
			$opts = array('upc'=>$upc,'description'=>'BADSCAN');
			TransRecord::add_log_record($opts);
			$CORE_LOCAL->set("boxMsg",$upc." "._("not a valid item"));
			//$ret['udpmsg'] = 'errorBeep'; // 12/12/12 this seems to stack with DisplayLib::msgbox
			$ret['main_frame'] = $my_url."gui-modules/boxMsg2.php";
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
			if ($CORE_LOCAL->get("warned") == 1 && $CORE_LOCAL->get("warnBoxType") == "inUse"){
				$CORE_LOCAL->set("warned",0);
				$CORE_LOCAL->set("warnBoxType","");
			}	
			else {
				$CORE_LOCAL->set("warned",1);
				$CORE_LOCAL->set("warnBoxType","inUse");
				$CORE_LOCAL->set("strEntered",$row["upc"]);
				$CORE_LOCAL->set("boxMsg","<b>".$row["upc"]." - ".$row["description"]."</b>
					<br />"._("Item not for sale")."
					<br /><font size=-1>"._("enter to continue sale").", "._("clear to cancel")."</font>");
				$ret['main_frame'] = $my_url."gui-modules/boxMsg2.php";
				return $ret;
			}
		}

		/**
		  Detect if a by-weight item has the same weight as the last by-weight
		  item. This can indicate a stuck scale.
		  The giant if determines whether the item is scalable, that we
		  know the weight, and that we know the previous weight (lastWeight)
		
		  Pre-weighed items (upc starts with 002) are ignored because they're not
		  weighed here. Scalable items that cost one cent are ignored as a special
		  case; they're normally entered by keying a quantity multiplier
		*/
		if ($num_rows > 0 && $row['scale'] == 1 
			&& $CORE_LOCAL->get("lastWeight") > 0 && $CORE_LOCAL->get("weight") > 0
			&& abs($CORE_LOCAL->get("weight") - $CORE_LOCAL->get("lastWeight")) < 0.0005
			&& substr($upc,0,3) != "002" && abs($row['normal_price']) > 0.01){
			if ($CORE_LOCAL->get("warned") == 1 && $CORE_LOCAL->get("warnBoxType") == "stuckScale"){
				$CORE_LOCAL->set("warned",0);
				$CORE_LOCAL->set("warnBoxType","");
			}	
			else {
				$CORE_LOCAL->set("warned",1);
				$CORE_LOCAL->set("warnBoxType","stuckScale");
				$CORE_LOCAL->set("strEntered",$row["upc"]);
				$CORE_LOCAL->set("boxMsg","<b>Same weight as last item</b>
					<br><font size=-1>[enter] to confirm correct, [clear] to cancel</font>");
				$ret['main_frame'] = $my_url."gui-modules/boxMsg2.php";
				return $ret;
			}
		}

		if ($row["idEnforced"] > 0){

			$restrictQ = "SELECT upc,dept_ID FROM dateRestrict WHERE
				( upc='{$row['upc']}' AND
				  ( ".$db->datediff($db->now(),'restrict_date')."=0 OR
				    ".$db->dayofweek($db->now())."=restrict_dow
				  ) AND
				  ( (restrict_start IS NULL AND restrict_end IS NULL) OR
				    ".$db->curtime()." BETWEEN restrict_start AND restrict_end
				  )
			 	) OR 
				( dept_ID='{$row['department']}' AND
				  ( ".$db->datediff($db->now(),'restrict_date')."=0 OR
				    ".$db->dayofweek($db->now())."=restrict_dow
				  ) AND
				  ( (restrict_start IS NULL AND restrict_end IS NULL) OR
				    ".$db->curtime()." BETWEEN restrict_start AND restrict_end
				  )
				)";
			$restrictR = $db->query($restrictQ);
			if ($db->num_rows($restrictR) > 0){
				$CORE_LOCAL->set("boxMsg",_("product cannot be sold right now"));
				$ret['main_frame'] = $my_url."gui-modules/boxMsg2.php";
				return $ret;
			}

			if ($CORE_LOCAL->get("cashierAge") < 18 && $CORE_LOCAL->get("cashierAgeOverride") != 1){
				$CORE_LOCAL->set("adminRequest",$my_url."gui-modules/pos2.php");
				$CORE_LOCAL->set("adminRequestLevel",30);
				$CORE_LOCAL->set("adminLoginMsg",_("Login to approve sale"));
				$CORE_LOCAL->set("away",1);
				$CORE_LOCAL->set("cashierAgeOverride",2);
				$ret['main_frame'] = $my_url."gui-modules/adminlogin.php";
				return $ret;
			}

			$msg = $CORE_LOCAL->get("requestMsg");
			if ((is_numeric($msg) && strlen($msg)==8) || $msg == 1){
				$CORE_LOCAL->set("memAge",$msg);
				$CORE_LOCAL->set("requestMsg","");
				$CORE_LOCAL->set("requestType","");
			}

			if ($CORE_LOCAL->get("memAge")=="")
				$CORE_LOCAL->set("memAge",date('Ymd'));
			$diff = time() - ((int)strtotime($CORE_LOCAL->get("memAge")));
			$age = floor($diff / (365*60*60*24));
			if ($age < $row['idEnforced']){
				$ret['udpmsg'] = 'twoPairs';
				$current = date("m/d/y",strtotime($CORE_LOCAL->get("memAge")));
				$CORE_LOCAL->set("requestType","customer age");
				$CORE_LOCAL->set("requestMsg","Type customer birthdate YYYYMMDD<br />(current: $current)");
				$ret['main_frame'] = $my_url.'gui-modules/requestInfo.php';
				return $ret;
			}
		}

		if ($row['tareweight'] > 0){
			$peek = PrehLib::peekItem();
			if (strstr($peek,"** Tare Weight") === False)
				TransRecord::addTare($row['tareweight']*100);
		}

		/* sanity check - ridiculous price 
		   (can break db column if it doesn't fit
		*/
		if (strlen($row["normal_price"]) > 8){
			$ret['output'] = DisplayLib::boxMsg("$upc<br />"._("Claims to be more than $100,000"));
			return $ret;
		}

		$scale = ($row["scale"] == 0) ? 0 : 1;

		/* need a weight with this item
		   retry the UPC in a few milliseconds and see
		*/
		if ($scale != 0 && $CORE_LOCAL->get("weight") == 0 && 
			$CORE_LOCAL->get("quantity") == 0 && substr($upc,0,3) != "002") {

			$CORE_LOCAL->set("SNR",$CORE_LOCAL->get('strEntered'));
			$ret['output'] = DisplayLib::boxMsg(_("please put item on scale"));
			$CORE_LOCAL->set("wgtRequested",0);
			$CORE_LOCAL->set("warned",1);
			//$ret['retry'] = $CORE_LOCAL->get("strEntered");
			
			return $ret;
		}
		$CORE_LOCAL->set("warned",0);
		/* got a scale weight, make sure the tare
		   is valid */
		if ($scale != 0 and substr($upc,0,3) != "002"){
			$quantity = $CORE_LOCAL->get("weight") - $CORE_LOCAL->get("tare");
			if ($CORE_LOCAL->get("quantity") != 0) 
				$quantity = $CORE_LOCAL->get("quantity") - $CORE_LOCAL->get("tare");

			if ($quantity <= 0){
				$ret['output'] = DisplayLib::boxMsg(_("item weight must be greater than tare weight"));
				return $ret;
			}
			$CORE_LOCAL->set("tare",0);
		}

		/* non-scale items need integer quantities */	
		if ($row["scale"] == 0 && (int) $CORE_LOCAL->get("quantity") != $CORE_LOCAL->get("quantity") ) {
			$ret['output'] = DisplayLib::boxMsg(_("fractional quantity cannot be accepted for this item"));
			return $ret;
		}

		/* quantity required for this item. Send to
		   entry page if one wasn't provided */
		$qttyEnforced = $row["qttyEnforced"];
		if (($qttyEnforced == 1) && ($CORE_LOCAL->get("multiple") == 0) && ($CORE_LOCAL->get("msgrepeat") == 0)) {
			$ret['main_frame'] = $my_url."gui-modules/qtty2.php";
			return $ret;
		}
		else
			$CORE_LOCAL->set("qttyvalid",1); // this may be unnecessary

		/* wedge I assume
		   I don't like this being hard-coded, but since these UPCs
		   are entries in products they can't go in a SpecialUPC
		   object (unless SpecialUPC checks take place on every
		   scan, but that's more overhead than I want on such a common
		   operation
		*/
		if ($upc == "0000000008010" && $CORE_LOCAL->get("msgrepeat") == 0) {
			$CORE_LOCAL->set("endorseType","giftcert");
			$CORE_LOCAL->set("tenderamt",$total);
			$CORE_LOCAL->set("boxMsg","<b>".$total." gift certificate</b><br />
				"._("insert document")."<br />"._("press enter to endorse")."
				<p><font size='-1'>"._("clear to cancel")."</font>");
			$ret["main_frame"] = $my_url."gui-modules/boxMsg2.php";
			return $ret;
		}

		/* wedge I assume
		   see 0000000008010 above
		*/
		if ($upc == "0000000008011" && $CORE_LOCAL->get("msgrepeat") == 0) {
			$CORE_LOCAL->set("endorseType","classreg");
			$CORE_LOCAL->set("tenderamt",$total);
			$CORE_LOCAL->set("boxMsg","<b>".$total." class registration</b><br />
				"._("insert form")."<br />"._("press enter to endorse")."
				<p><font size='-1'>"._("clear to cancel")."</font>");
			$ret["main_frame"] = $my_url."gui-modules/boxMsg2.php";
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
		$row['description'] = str_replace("'","",$row['description']);

		/* do tax shift */
		$tax = $row['tax'];
		if ($CORE_LOCAL->get("toggletax") != 0) {
			$tax = ($tax==0) ? 1 : 0;
			$CORE_LOCAL->set("toggletax",0);
		}
		$row['tax'] = $tax;

		/* do foodstamp shift */
		$foodstamp = $row["foodstamp"];
		if ($CORE_LOCAL->get("togglefoodstamp") != 0){
			$CORE_LOCAL->set("togglefoodstamp",0);
			$foodstamp = ($foodstamp==0) ? 1 : 0;
		}
		$row['foodstamp'] = $foodstamp;

		/* do discount shifts */
		$discountable = $row["discount"];
		if ($CORE_LOCAL->get("toggleDiscountable") == 1) {
			$CORE_LOCAL->set("toggleDiscountable",0);
			$discountable = ($discountable == 0) ? 1 : 0;
		}
		$row['discount'] = $discountable;

		/*
			BEGIN: figure out discounts by type
		*/

		/* get discount object */
		$discounttype = MiscLib::nullwrap($row["discounttype"]);
		$DTClasses = $CORE_LOCAL->get("DiscountTypeClasses");
		$DiscountObject = new $DTClasses[$discounttype];

		/* add in sticker price and calculate a quantity
		   if the item is stickered, scaled, and on sale 
		   if it's not scaled or on sale, there's no need
		   to back-calculate weight and adjust so just use
		   sticker price as normal_price
		*/
		if (substr($upc,0,3) == "002"){
			if ($DiscountObject->isSale() && $scale == 1)
				$quantity = MiscLib::truncate2($scaleprice / $row["normal_price"]);
			else
				$row['normal_price'] = $scaleprice;
		}

		// don't know what this is - wedge?
		if ($CORE_LOCAL->get("nd") == 1 && $discountable == 7) {
			$discountable = 3;
			$CORE_LOCAL->set("nd",0);
		}

		/*
			END: figure out discounts by type
		*/

		/* get price method object  & add item*/
		$pricemethod = MiscLib::nullwrap($row["pricemethod"]);
		if ($DiscountObject->isSale())
			$pricemethod = MiscLib::nullwrap($row["specialpricemethod"]);
		$PMClasses = $CORE_LOCAL->get("PriceMethodClasses");
		$PriceMethodObject = new $PMClasses[$pricemethod];
		// prefetch: otherwise object members 
		// pass out of scope in addItem()
		$prefetch = $DiscountObject->priceInfo($row,$quantity);
		$PriceMethodObject->addItem($row, $quantity, $DiscountObject);

		/* add discount notifications lines, if applicable */
		$DiscountObject->addDiscountLine();

		// cleanup, reset flags and beep
		if ($quantity != 0) {
			// ddNotify is legacy/unknown. likely doesn't work
			if ($CORE_LOCAL->get("ddNotify") == 1 && $CORE_LOCAL->get("itemPD") == 10) {
				$CORE_LOCAL->set("itemPD",0);
				$discountable = 7;
			}
			$intvoided = 0;
			if ($CORE_LOCAL->get("ddNotify") == 1 && $discountable == 7) 
				$intvoided = 22;

			$CORE_LOCAL->set("msgrepeat",0);
			$CORE_LOCAL->set("qttyvalid",0);

			$ret['udpmsg'] = 'goodBeep';
		}

		// probably pointless, see what happens without it
		//if ($tax != 1) $CORE_LOCAL->set("voided",0);

		/* reset various flags and variables */
		if ($CORE_LOCAL->get("tare") != 0) $CORE_LOCAL->set("tare",0);
		$CORE_LOCAL->set("ttlflag",0);
		$CORE_LOCAL->set("fntlflag",0);
		$CORE_LOCAL->set("quantity",0);
		$CORE_LOCAL->set("itemPD",0);
		$CORE_LOCAL->set("voided",0);
		Database::setglobalflags(0);

		/* output item list, update totals footer */
		$ret['redraw_footer'] = True;
		$ret['output'] = DisplayLib::lastpage();

		if ($prefetch['unitPrice']==0 && $discounttype == 0){
			$ret['main_frame'] = $my_url.'gui-modules/priceOverride.php';
		}

		return $ret;
	}

	function add_deposit($upc){
		global $CORE_LOCAL;

		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

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
