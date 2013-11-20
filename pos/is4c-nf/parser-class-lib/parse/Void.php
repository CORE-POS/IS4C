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

class Void extends Parser {

	function check($str){
		if (substr($str,0,2) == "VD" && strlen($str) <= 15)
			return True;
		return False;
	}

	private $discounttype = 0;
	private $discountable = 0;
	private $caseprice = 0;
	private $scaleprice = 0;

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
	
		if (is_numeric($CORE_LOCAL->get('VoidLimit')) && $CORE_LOCAL->get('VoidLimit') > 0){
			Database::getsubtotals();
			if ($CORE_LOCAL->get('voidTotal') > $CORE_LOCAL->get('VoidLimit') && $CORE_LOCAL->get('voidOverride') != 1){
				$CORE_LOCAL->set('strRemembered', $CORE_LOCAL->get('strEntered'));
				$CORE_LOCAL->set('voidOverride', 0);
				$ret['main_frame'] = MiscLib::base_url().'gui-modules/adminlogin.php?class=Void';
				return $ret;
			}
		}


		if (strlen($str) > 2)
			$ret['output'] = $this->voidupc(substr($str,2));
		elseif ($CORE_LOCAL->get("currentid") == 0) 
			$ret['output'] = DisplayLib::boxMsg(_("No Item on Order"));
		else {
			$str = $CORE_LOCAL->get("currentid");

			$status = PrehLib::checkstatus($str);
			$this->discounttype = $status['discounttype'];
			$this->discountable = $status['discountable'];
			$this->caseprice = $status['caseprice'];
			$this->scaleprice = $status['scaleprice'];

			if ($status['voided'] == 2) {
				$ret['output'] = $this->voiditem($str -1);
			}
			elseif ($status['voided'] == 3 || $status['voided'] == 6 || $status['voided'] == 8) 
				$ret['output'] = DisplayLib::boxMsg(_("Cannot void this entry"));
			elseif ($status['voided'] == 4 || $status['voided'] == 5) 
				PrehLib::percentDiscount(0);
			elseif ($status['voided'] == 10) {
				TransRecord::reverseTaxExempt();
			}
			elseif ($status['status'] == "V") {
				$ret['output'] = DisplayLib::boxMsg(_("Item already voided"));
			}
			else 
				$ret['output'] = $this->voiditem($str);
		}
		if (empty($ret['output'])){
			$ret['output'] = DisplayLib::lastpage();
			$ret['redraw_footer'] = True;
			$ret['udpmsg'] = 'goodBeep';
		}
		else {
			$ret['udpmsg'] = 'errorBeep';
		}
		return $ret;
	}

	function voiditem($item_num) {
		global $CORE_LOCAL;

		if ($item_num) {
			$query = "select upc, quantity, ItemQtty, foodstamp, discountable,
				total, voided, charflag, discounttype from localtemptrans where "
				."trans_id = ".$item_num;

			$db = Database::tDataConnect();
			$result = $db->query($query);
			$num_rows = $db->num_rows($result);

			if ($num_rows == 0) return DisplayLib::boxMsg(_("Item not found"));
			else {
				$row = $db->fetch_array($result);

				$this->discounttype = $row['discounttype'];
				$this->discountable = $row['discountable'];

				if ((!$row["upc"] || strlen($row["upc"]) < 1) && $row["voided"] == 1) 

					return DisplayLib::boxMsg(_("Item already voided"));
				elseif (!$row["upc"] || strlen($row["upc"]) < 1 || $row['charflag'] == 'SO') 
					return $this->voidid($item_num);
				elseif ($row["discounttype"] == 3){
					return $this->voidupc($row["quantity"]."*".$row["upc"],$item_num);
				}
				else  
					return $this->voidupc($row["ItemQtty"]."*".$row["upc"],$item_num);
			}
		}
		return "";
	}	

	function voidid($item_num) {
		global $CORE_LOCAL;

		$query = "select upc,VolSpecial,quantity,trans_subtype,unitPrice,
			discount,memDiscount,discountable,scale,numflag,charflag,
			foodstamp,discounttype,total,cost,description,trans_type,
			department,regPrice,tax,volDiscType,volume,mixMatch,matched
		       	from localtemptrans where trans_id = ".$item_num;
		$db = Database::tDataConnect();
		$result = $db->query($query);
		$row = $db->fetch_array($result);

		$upc = $row["upc"];
		$VolSpecial = $row["VolSpecial"];
		$quantity = -1 * $row["quantity"];

		$total = -1 * $row["total"];
		if ($row["trans_subtype"] == "FS") 
			$total = -1 * $row["unitPrice"];

		$CardNo = $CORE_LOCAL->get("memberID");
		$discount = -1 * $row["discount"];
		$memDiscount = -1 * $row["memDiscount"];
		$discountable = $row["discountable"];
		$unitPrice = $row["unitPrice"];
		$scale = MiscLib::nullwrap($row["scale"]);
		$cost = isset($row["cost"])?-1*$row["cost"]:0;
		$numflag = isset($row["numflag"])?$row["numflag"]:0;
		$charflag = isset($row["charflag"])?$row["charflag"]:0;
		$mm = $row['mixMatch'];
		$matched = $row['matched'];

		$foodstamp = 0;
		if ($row["foodstamp"] != 0) $foodstamp = 1;

		$discounttype = MiscLib::nullwrap($row["discounttype"]);

		if ($CORE_LOCAL->get("tenderTotal") < 0 && (-1 * $total) > $CORE_LOCAL->get("runningTotal") - $CORE_LOCAL->get("taxTotal")) {
			$cash = $db->query("SELECT total FROM localtemptrans WHERE trans_subtype='CA' AND total <> 0");
			if ($db->num_rows($cash) > 0)	
				return DisplayLib::boxMsg("Item already paid for");
		}

		$update = "update localtemptrans set voided = 1 where trans_id = ".$item_num;
		$db->query($update);
		TransRecord::addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], $quantity, $unitPrice, $total, $row["regPrice"], $scale, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $quantity, $row["volDiscType"], $row["volume"], $VolSpecial, $mm, $matched, 1, $cost, $numflag, $charflag);
		if ($row["trans_type"] != "T") {
			$CORE_LOCAL->set("ttlflag",0);
		}
		else PrehLib::ttl();

		return "";
	}

	function voidupc($upc,$item_num=-1,$silent=False) {
		global $CORE_LOCAL;

		$lastpageflag = 1;
		$deliflag = 0;
		$quantity = 0;

		if (strpos($upc, "*") && (strpos($upc, "**") || strpos($upc, "*") == 0 || 
		    strpos($upc, "*") == strlen($upc)-1))
			$upc = "stop";

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
			$weight = $CORE_LOCAL->get("weight");
		}

		$scaleprice = 0;
		if (is_numeric($upc)) {
			$upc = substr("0000000000000".$upc, -13);
			if (substr($upc, 0, 3) == "002" && substr($upc, -5) != "00000") {
				$scaleprice = substr($upc, 10, 4)/100;
				$upc = substr($upc, 0, 8)."0000";
				$deliflag = 1;
			}
			elseif (substr($upc, 0, 3) == "002" && substr($upc, -5) == "00000") {
				$scaleprice = $this->scaleprice;
				$deliflag = 1;
			}
		}

		if ($upc == "stop") return DisplayLib::inputUnknown();

		$db = Database::tDataConnect();

		$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
			."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
			."' and unitPrice = ".$scaleprice." and discounttype <> 3 group by upc";
		if ($this->discounttype == 3) {
			$query = "select sum(quantity) as voidable, max(scale), as scale, "
				."max(volDiscType) as volDiscType from localtemptrans where "
				."upc = '".$upc."' and discounttype = 3 and unitPrice = "
				.$this->caseprice." group by upc";
		}
		elseif ($deliflag == 0) {
			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, "
				."max(scale) as scale, max(volDiscType) as volDiscType from "
				."localtemptrans where upc = '".$upc
				."' and discounttype <> 3 group by upc, discounttype";
		}
		elseif ($CORE_LOCAL->get("ddNotify") == 1) {
			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity,"
				."max(scale) as scale, max(volDiscType) as volDiscType from "
				."localtemptrans where upc = '".$upc."' and discounttype <> 3 "
				."and discountable = ".$this->discountable." group by upc, "
				."discounttype, discountable";
		}

		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
		if ($num_rows == 0 ){
			return DisplayLib::boxMsg(_("Item not found").": ".$upc);
		}

		$row = $db->fetch_array($result);

		if (($row["scale"] == 1) && $weight > 0) {
			$quantity = $weight - $CORE_LOCAL->get("tare");
			$CORE_LOCAL->set("tare",0);
		}

		$volDiscType = $row["volDiscType"];
		$voidable = MiscLib::nullwrap($row["voidable"]);

		$VolSpecial = 0;
		$volume = 0;
		$scale = MiscLib::nullwrap($row["scale"]);

		if ($voidable == 0 && $quantity == 1) return DisplayLib::boxMsg(_("Item already voided"));
		elseif ($voidable == 0 && $quantity > 1) return DisplayLib::boxMsg(_("Items already voided"));
		elseif ($scale == 1 && $quantity < 0) return DisplayLib::boxMsg(_("tare weight cannot be greater than item weight"));
		elseif ($voidable < $quantity && $row["scale"] == 1) {
			$message = _("Void request exceeds")."<br />"._("weight of item rung in")."<p><b>".
				sprintf(_("You can void up to %.2f lb"),$row['voidable'])."</b>";
			return DisplayLib::boxMsg($message);
		}
		elseif ($voidable < $quantity) {
			$message = _("Void request exceeds")."<br />"._("number of items rung in")."<p><b>".
				sprintf(_("You can void up to %d"),$row['voidable'])."</b>";
			return DisplayLib::boxMsg($message);
		}

		unset($result);
		//----------------------Void Item------------------
		$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
				numflag,charflag,unitPrice,total,discounttype,regPrice,discount,
				memDiscount,discountable,description,trans_type,trans_subtype,
				department,tax,VolSpecial,trans_id
				from localtemptrans where upc = '".$upc."' and unitPrice = "
			     .$scaleprice." and trans_id=$item_num";
		if ($this->discounttype == 3) {
			$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
				numflag,charflag,unitPrice,total,discounttype,regPrice,discount,
				memDiscount,discountable,description,trans_type,trans_subtype,
				department,tax,VolSpecial,trans_id
				from localtemptrans where upc = '".$upc
				."' and discounttype = 3 and unitPrice = ".$this->caseprice
			        ." and trans_id=$item_num";
		}
		elseif ($deliflag == 0) {
			$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
				numflag,charflag,unitPrice,total,discounttype,regPrice,discount,
				memDiscount,discountable,description,trans_type,trans_subtype,
				department,tax,VolSpecial,trans_id
			       	from localtemptrans where upc = '".$upc
				."' and discounttype <> 3"
			        ." and trans_id=$item_num";
		}
		if ($item_num == -1)
			$query_upc = str_replace(" trans_id=$item_num"," voided=0",$query_upc);

		$result = $db->query($query_upc);
		$row = $db->fetch_array($result);

		$ItemQtty = $row["ItemQtty"];
		$foodstamp = MiscLib::nullwrap($row["foodstamp"]);
		$discounttype = MiscLib::nullwrap($row["discounttype"]);
		$mixMatch = MiscLib::nullwrap($row["mixMatch"]);
		$item_num = $row['trans_id'];
		$cost = isset($row["cost"])?-1*$row["cost"]:0;
		$numflag = isset($row["numflag"])?$row["numflag"]:0;
		$charflag = isset($row["charflag"])?$row["charflag"]:0;
	
		$unitPrice = $row["unitPrice"];
		if (($CORE_LOCAL->get("isMember") != 1 && $row["discounttype"] == 2) || 
		    ($CORE_LOCAL->get("isStaff") == 0 && $row["discounttype"] == 4)) 
			$unitPrice = $row["regPrice"];
		elseif ((($CORE_LOCAL->get("isMember") == 1 && $row["discounttype"] == 2) || 
		    ($CORE_LOCAL->get("isStaff") != 0 && $row["discounttype"] == 4)) && 
		    ($row["unitPrice"] == $row["regPrice"])) {
			$db_p = Database::pDataConnect();
			$query_p = "select special_price from products where upc = '".$upc."'";
			$result_p = $db_p->query($query_p);
			$row_p = $db_p->fetch_array($result_p);
			
			$unitPrice = $row_p["special_price"];
		
		}
				
		$discount = -1 * $row["discount"];
		$memDiscount = -1 * $row["memDiscount"];
		$discountable = $row["discountable"];

		//----------------------mix match---------------------
		if ($volDiscType >= 1 && $volDiscType != 3) {
			$db_mm = Database::tDataConnect();
			$query_mm = "select sum(ItemQtty) as mmqtty from localtemptrans "
				    ."where mixMatch = ".$mixMatch;
					
			$result_mm = $db_mm->query($query_mm);
			$row_mm = $db_mm->fetch_array($result_mm);
	
			$mmqtty = MiscLib::nullwrap($row_mm["mmqtty"]);
	
			$db_pq = Database::pDataConnect();
			$query_pq = "select normal_price,groupprice,quantity,specialquantity,
				specialgroupprice
			       	from products where upc = '".$upc."'";
			$result_pq = $db_pq->query($query_pq);
			$row_pq = $db_pq->fetch_array($result_pq);
	
			$unitPrice = $row_pq["normal_price"];
			$VolSpecial = MiscLib::nullwrap($row_pq["groupprice"]);
			$volume = MiscLib::nullwrap($row_pq["quantity"]);
			if ($discounttype == 1) {
				$volume = MiscLib::nullwrap($row_pq['specialquantity']);
				$VolSpecial = MiscLib::nullwrap($row_pq["specialgroupprice"]);
			}
					
			$volmulti = (int) ($quantity/$volume);
			$vmremainder = $quantity % $volume;
			$mm = (int) ($mmqtty/$volume);
			$mmremainder = $mmqtty % $volume;
			if ($mixMatch == 0) {
				$mm = (int) ($voidable/$volume);
				$mmremainder = $voidable % $volume;
			}

			if ($volmulti > 0) {
				TransRecord::addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], -1* $volmulti, $VolSpecial, -1 * $volmulti * $VolSpecial, $VolSpecial, 0, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, -1 * $volmulti * $volume, $volDiscType, $volume, $VolSpecial, $mixMatch, -1 * $volume * $volmulti, 1, $cost, $numflag, $charflag);
				$quantity = $vmremainder;
			}
			if ($vmremainder > $mmremainder) {
				$voladj = $row["VolSpecial"] - ($unitPrice * ($volume - 1));
				TransRecord::addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], -1, $voladj, -1 * $voladj, $voladj, 0, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, -1, $volDiscType, $volume, $VolSpecial, $mixMatch, -1 * $volume, 1, $cost, $numflag, $charflag);
				$quantity = $quantity - 1;
			}
		}
	
		$quantity = -1 * $quantity;
		$total = $quantity * $unitPrice;
		if ($row['unitPrice'] == 0) $total = $quantity * $row['total'];
		else if ($row['total'] != $total) $total = -1*$row['total'];
	
		$CardNo = $CORE_LOCAL->get("memberID");
		
		$discounttype = MiscLib::nullwrap($row["discounttype"]);
		if ($discounttype == 3) 
			$quantity = -1 * $ItemQtty;

		if ($CORE_LOCAL->get("tenderTotal") < 0 && (-1 * $total) > $CORE_LOCAL->get("runningTotal") - $CORE_LOCAL->get("taxTotal")) {
			$cash = $db->query("SELECT total FROM localtemptrans WHERE trans_subtype='CA' AND total <> 0");
			if ($db->num_rows($cash) > 0)	
                return DisplayLib::boxMsg(_("Item already paid for"));
		}
		elseif ($quantity != 0) {
			$update = "update localtemptrans set voided = 1 where trans_id = ".$item_num;
			$db->query($update);
			TransRecord::addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], $quantity, $unitPrice, $total, $row["regPrice"], $scale, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $quantity, $volDiscType, $volume, $VolSpecial, $mixMatch, 0, 1, $cost, $numflag, $charflag);

			if ($row["trans_type"] != "T") {
				$CORE_LOCAL->set("ttlflag",0);
				$CORE_LOCAL->set("discounttype",0);
			}

			$db = Database::pDataConnect();
			$chk = $db->query("SELECT deposit FROM products WHERE upc='$upc'");
			if ($db->num_rows($chk) > 0){
				$dpt = array_pop($db->fetch_row($chk));
				if ($dpt <= 0) return ''; // no deposit found
				$db = Database::tDataConnect();
				$dupc = str_pad((int)$dpt,13,'0',STR_PAD_LEFT);
				$id = $db->query(sprintf("SELECT trans_id FROM localtemptrans
					WHERE upc='%s' AND voided=0 AND quantity=%d",
					$dupc,(-1*$quantity)));
				if ($db->num_rows($id) > 0){	
					$trans_id = array_pop($db->fetch_row($id));
					return $this->voidupc((-1*$quantity)."*".$dupc,$trans_id,True);
				}
			}
		}
		return "";
	}

	public static $adminLoginMsg = 'Void Limit Exceeded. Login to continue.';
	
	public static $adminLoginLevel = 30;

	public static function adminLoginCallback($success){
		global $CORE_LOCAL;
		if ($success){
			$CORE_LOCAL->set('voidOverride', 1);
			$CORE_LOCAL->set('msgrepeat', 1);
			return True;
		}
		else{
			$CORE_LOCAL->set('voidOverride', 0);
			return False;
		}
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>VD<i>ringable</i></td>
				<td>Void <i>ringable</i>, which
				may be a product number or an
				open department ring</td>
			</tr>
			</table>";
	}
}


?>
