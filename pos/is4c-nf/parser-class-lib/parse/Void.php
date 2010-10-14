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
if (!function_exists("tDataConnect")) include_once($IS4C_PATH."lib/connect.php");
if (!function_exists("boxMsg")) include_once($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("lastpage")) include_once($IS4C_PATH."lib/listitems.php");
if (!function_exists("checkstatus")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("addItem")) include_once($IS4C_PATH."lib/additem.php");
if (!function_exists("nullwrap")) include_once($IS4C_PATH."lib/lib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class Void extends Parser {
	function check($str){
		if (substr($str,0,2) == "VD" && strlen($str) <= 15)
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL;
		$ret = $this->default_json();
		if (strlen($str) > 2)
			$ret['output'] = $this->voidupc(substr($str,2));
		elseif ($IS4C_LOCAL->get("currentid") == 0) 
			$ret['output'] = boxMsg("No Item on Order");
		else {
			$str = $IS4C_LOCAL->get("currentid");

			checkstatus($str);

			if ($IS4C_LOCAL->get("voided") == 2) {
				$ret['output'] = $this->voiditem($str -1);
			}
			elseif ($IS4C_LOCAL->get("voided") == 3 || $IS4C_LOCAL->get("voided") == 6 || $IS4C_LOCAL->get("voided") == 8) 
				$ret['output'] = boxMsg("Cannot void this entry");
			elseif ($IS4C_LOCAL->get("voided") == 4 || $IS4C_LOCAL->get("voided") == 5) 
				percentDiscount(0);
			elseif ($IS4C_LOCAL->get("voided") == 10) {
				reverseTaxExempt();
			}
			elseif ($IS4C_LOCAL->get("transstatus") == "V") {
				$ret['output'] = boxMsg("Item already voided");
				$IS4C_LOCAL->set("transstatus","");
			}
			else 
				$ret['output'] = $this->voiditem($str);
		}
		if (empty($ret['output'])){
			$ret['output'] = lastpage();
			$ret['redraw_footer'] = True;
			$ret['udpmsg'] = 'goodBeep';
		}
		else {
			$ret['udpmsg'] = 'errorBeep';
		}
		return $ret;
	}

	function voiditem($item_num) {
		global $IS4C_LOCAL;

		if ($item_num) {
			$query = "select upc, quantity, ItemQtty, foodstamp, total, voided from localtemptrans where "
				."trans_id = ".$item_num;

			$db = tDataConnect();
			$result = $db->query($query);
			$num_rows = $db->num_rows($result);

			if ($num_rows == 0) return boxMsg("Item not found");
			else {
				$row = $db->fetch_array($result);

				if ((!$row["upc"] || strlen($row["upc"]) < 1) && $row["voided"] == 1) 

					return boxMsg("Item already voided");
				elseif (!$row["upc"] || strlen($row["upc"]) < 1) 
					return $this->voidid($item_num);
				elseif ($IS4C_LOCAL->get("discounttype") == 3) 
					return $this->voidupc($row["quantity"]."*".$row["upc"],$item_num);
				else  
					return $this->voidupc($row["ItemQtty"]."*".$row["upc"],$item_num);
			}
		}
		return "";
	}	

	function voidid($item_num) {
		global $IS4C_LOCAL;

		$query = "select upc,VolSpecial,quantity,trans_subtype,unitPrice,
			discount,memDiscount,discountable,scale,numflag,charflag,
			foodstamp,discounttype,total,cost,description,trans_type,
			department,regPrice,tax,volDiscType,volume
		       	from localtemptrans where trans_id = ".$item_num;
		$db = tDataConnect();
		$result = $db->query($query);
		$row = $db->fetch_array($result);

		$upc = $row["upc"];
		$VolSpecial = $row["VolSpecial"];
		$quantity = -1 * $row["quantity"];

		$total = -1 * $row["total"];
		if ($row["trans_subtype"] == "FS") 
			$total = -1 * $row["unitPrice"];

		$CardNo = $IS4C_LOCAL->get("memberID");
		$discount = -1 * $row["discount"];
		$memDiscount = -1 * $row["memDiscount"];
		$discountable = $row["discountable"];
		$unitPrice = $row["unitPrice"];
		$scale = nullwrap($row["scale"]);
		$cost = isset($row["cost"])?-1*$row["cost"]:0;
		$numflag = isset($row["numflag"])?$row["numflag"]:0;
		$charflag = isset($row["charflag"])?$row["charflag"]:0;

		$foodstamp = 0;
		if ($row["foodstamp"] != 0) $foodstamp = 1;

		$discounttype = nullwrap($row["discounttype"]);

		if ($IS4C_LOCAL->get("tenderTotal") < 0 && $foodstamp = 1 && (-1 * $total) > $IS4C_LOCAL->get("fsEligible")) {
			return boxMsg("Item already paid for");
		}
		elseif ($IS4C_LOCAL->get("tenderTotal") < 0 && (-1 * $total) > $IS4C_LOCAL->get("runningTotal") - $IS4C_LOCAL->get("taxTotal")) {
			return boxMsg("Item already paid for");
		}

		$update = "update localtemptrans set voided = 1 where trans_id = ".$item_num;
		$db->query($update);
		addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], $quantity, $unitPrice, $total, $row["regPrice"], $scale, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $quantity, $row["volDiscType"], $row["volume"], $VolSpecial, 0, 0, 1, $cost, $numflag, $charflag);
		if ($row["trans_type"] != "T") {
			$IS4C_LOCAL->set("ttlflag",0);
		}
		else ttl();

		return "";
	}

	function voidupc($upc,$item_num,$silent=False) {
		global $IS4C_LOCAL;

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
			$weight = $IS4C_LOCAL->get("weight");
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
				$scaleprice = $IS4C_LOCAL->get("scaleprice");
				$deliflag = 1;
			}
		}

		if ($upc == "stop") return inputUnknown();

		$db = tDataConnect();

		$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
			."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
			."' and unitPrice = ".$scaleprice." and discounttype <> 3 group by upc";
		if ($IS4C_LOCAL->get("discounttype") == 3) {
			$query = "select sum(quantity) as voidable, max(scale), as scale, "
				."max(volDiscType) as volDiscType from localtemptrans where "
				."upc = '".$upc."' and discounttype = 3 and unitPrice = "
				.$IS4C_LOCAL->get("caseprice")." group by upc";
		}
		elseif ($deliflag == 0) {
			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, "
				."max(scale) as scale, max(volDiscType) as volDiscType from "
				."localtemptrans where upc = '".$upc
				."' and discounttype <> 3 group by upc, discounttype";
		}
		elseif ($IS4C_LOCAL->get("ddNotify") == 1) {
			$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity,"
				."max(scale) as scale, max(volDiscType) as volDiscType from "
				."localtemptrans where upc = '".$upc."' and discounttype <> 3 "
				."and discountable = ".$IS4C_LOCAL->get("discountable")." group by upc, "
				."discounttype, discountable";
		}

		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
		if ($num_rows == 0 ){
			return boxMsg("Item $upc not found");
		}

		$row = $db->fetch_array($result);

		if (($row["scale"] == 1) && $weight > 0) {
			$quantity = $weight - $IS4C_LOCAL->get("tare");
			$IS4C_LOCAL->set("tare",0);
		}

		$volDiscType = $row["volDiscType"];
		$voidable = nullwrap($row["voidable"]);

		$VolSpecial = 0;
		$volume = 0;
		$scale = nullwrap($row["scale"]);

		if ($voidable == 0 && $quantity == 1) return boxMsg("Item already voided");
		elseif ($voidable == 0 && $quantity > 1) return boxMsg("Items already voided");
		elseif ($scale == 1 && $quantity < 0) return boxMsg("tare weight cannot be greater than item weight");
		elseif ($voidable < $quantity && $row["scale"] == 1) {
			$message = "Void request exceeds<BR>weight of item rung in<P><B>You can void up to "
				.$row["voidable"]." lb</B>";
			return boxMsg($message);
		}
		elseif ($voidable < $quantity) {
			$message = "Void request exceeds<BR>number of items rung in<P><B>You can void up to "
				.$row["voidable"]."</B>";
			return boxMsg($message);
		}

		unset($result);
		//----------------------Void Item------------------
			$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
				numflag,charflag,unitPrice,discounttype,regPrice,discount,
				memDiscount,discountable,description,trans_type,trans_subtype,
				department,tax,VolSpecial
				from localtemptrans where upc = '".$upc."' and unitPrice = "
			     .$scaleprice." and trans_id=$item_num";
		if ($IS4C_LOCAL->get("discounttype") == 3) {
			$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
				numflag,charflag,unitPrice,discounttype,regPrice,discount,
				memDiscount,discountable,description,trans_type,trans_subtype,
				department,tax,VolSpecial
				from localtemptrans where upc = '".$upc
				."' and discounttype = 3 and unitPrice = ".$IS4C_LOCAL->get("caseprice")
			        ." and trans_id=$item_num";
		}
		elseif ($deliflag == 0) {
			$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
				numflag,charflag,unitPrice,discounttype,regPrice,discount,
				memDiscount,discountable,description,trans_type,trans_subtype,
				department,tax,VolSpecial
			       	from localtemptrans where upc = '".$upc
				."' and discounttype <> 3"
			        ." and trans_id=$item_num";
		}

		$IS4C_LOCAL->set("discounttype",9);

		$result = $db->query($query_upc);
		$row = $db->fetch_array($result);

		$ItemQtty = $row["ItemQtty"];
		$foodstamp = nullwrap($row["foodstamp"]);
		$discounttype = nullwrap($row["discounttype"]);
		$mixMatch = nullwrap($row["mixMatch"]);
		$cost = isset($row["cost"])?-1*$row["cost"]:0;
		$numflag = isset($row["numflag"])?$row["numflag"]:0;
		$charflag = isset($row["charflag"])?$row["charflag"]:0;
	
		$unitPrice = $row["unitPrice"];
		if (($IS4C_LOCAL->get("isMember") != 1 && $row["discounttype"] == 2) || 
		    ($IS4C_LOCAL->get("isStaff") == 0 && $row["discounttype"] == 4)) 
			$unitPrice = $row["regPrice"];
		elseif ((($IS4C_LOCAL->get("isMember") == 1 && $row["discounttype"] == 2) || 
		    ($IS4C_LOCAL->get("isStaff") != 0 && $row["discounttype"] == 4)) && 
		    ($row["unitPrice"] == $row["regPrice"])) {
			$db_p = pDataConnect();
			$query_p = "select special_price from products where upc = '".$upc."'";
			$result_p = $db_p->query($query_p);
			$row_p = $db_p->fetch_array($result_p);
			
			$unitPrice = $row_p["special_price"];
		
		}
				
		$discount = -1 * $row["discount"];
		$memDiscount = -1 * $row["memDiscount"];
		$discountable = $row["discountable"];

		if ($IS4C_LOCAL->get("ddNotify") == 1) 
			$discountable = $IS4C_LOCAL->get("discountable");

		//----------------------mix match---------------------
		if ($volDiscType >= 1 && $volDiscType != 3) {
			$db_mm = tDataConnect();
			$query_mm = "select sum(ItemQtty) as mmqtty from localtemptrans "
				    ."where mixMatch = ".$mixMatch;
					
			$result_mm = $db_mm->query($query_mm);
			$row_mm = $db_mm->fetch_array($result_mm);
	
			$mmqtty = nullwrap($row_mm["mmqtty"]);
	
			$db_pq = pDataConnect();
			$query_pq = "select normal_price,groupprice,quantity,specialquantity,
				specialgroupprice
			       	from products where upc = '".$upc."'";
			$result_pq = $db_pq->query($query_pq);
			$row_pq = $db_pq->fetch_array($result_pq);
	
			$unitPrice = $row_pq["normal_price"];
			$VolSpecial = nullwrap($row_pq["groupprice"]);
			$volume = nullwrap($row_pq["quantity"]);
			if ($discounttype == 1) {
				$volume = nullwrap($row_pq['specialquantity']);
				$VolSpecial = nullwrap($row_pq["specialgroupprice"]);
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
				addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], -1* $volmulti, $VolSpecial, -1 * $volmulti * $VolSpecial, $VolSpecial, 0, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, -1 * $volmulti * $volume, $volDiscType, $volume, $VolSpecial, $mixMatch, -1 * $volume * $volmulti, 1, $cost, $numflag, $charflag);
				$quantity = $vmremainder;
			}
			if ($vmremainder > $mmremainder) {
				$voladj = $row["VolSpecial"] - ($unitPrice * ($volume - 1));
				addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], -1, $voladj, -1 * $voladj, $voladj, 0, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, -1, $volDiscType, $volume, $VolSpecial, $mixMatch, -1 * $volume, 1, $cost, $numflag, $charflag);
				$quantity = $quantity - 1;
			}
		}
	
		$quantity = -1 * $quantity;
		$total = $quantity * $unitPrice;
	
		$CardNo = $IS4C_LOCAL->get("memberID");
		
		$discounttype = nullwrap($row["discounttype"]);
		if ($discounttype == 3) 
			$quantity = -1 * $ItemQtty;

		if ($IS4C_LOCAL->get("tenderTotal") < 0 && $foodstamp == 1 && 
		   (-1 * $total) > $IS4C_LOCAL->get("fsEligible")) {
			return boxMsg("Item already paid for");
		}
		elseif ($IS4C_LOCAL->get("tenderTotal") < 0 && (-1 * $total) > 
			$IS4C_LOCAL->get("runningTotal") - $IS4C_LOCAL->get("taxTotal")) {
			return boxMsg("Item already paid for");
		}
		elseif ($quantity != 0) {
			addItem($upc, $row["description"], $row["trans_type"], $row["trans_subtype"], "V", $row["department"], $quantity, $unitPrice, $total, $row["regPrice"], $scale, $row["tax"], $foodstamp, $discount, $memDiscount, $discountable, $discounttype, $quantity, $volDiscType, $volume, $VolSpecial, $mixMatch, 0, 1, $cost, $numflag, $charflag);

			if ($row["trans_type"] != "T") {
				$IS4C_LOCAL->set("ttlflag",0);
				$IS4C_LOCAL->set("discounttype",0);
			}

			$db = pDataConnect();
			$chk = $db->query("SELECT deposit FROM products WHERE upc='$upc'");
			if ($db->num_rows($chk) > 0){
				$dpt = array_pop($db->fetch_row($chk));
				if ($dpt > 0){
					$dupc = (int)$dpt;
					return $this->voidupc((-1*$quantity)."*".$dupc,True);
				}
			}
		}
		return "";
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
