<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

class ItemPD extends Parser {
	function check($str){
		if (substr($str,-2) == "PD" && is_numeric(substr($str,0,strlen($str)-2)))
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		if ($CORE_LOCAL->get("currentid") == 0) 
			$ret['output'] = DisplayLib::boxMsg(_("No Item on Order"));
		else {
			$str = $CORE_LOCAL->get("currentid");
			$pd = substr($str,0,strlen($str)-2);

			$ret['output'] = $this->discountitem($str,$pd);
		}
		if (empty($ret['output'])){
			$ret['output'] = DisplayPage::lastpage();
			$ret['redraw_footer'] = True;
			$ret['udpmsg'] = 'goodBeep';
		}
		else {
			$ret['udpmsg'] = 'errorBeep';
		}
		return $ret;
	}

	function discountitem($item_num,$pd) {
		global $CORE_LOCAL;

		if ($item_num) {
			$query = "select upc, quantity, ItemQtty, foodstamp, total, voided, charflag from localtemptrans where "
				."trans_id = ".$item_num;

			$db = Database::tDataConnect();
			$result = $db->query($query);
			$num_rows = $db->num_rows($result);

			if ($num_rows == 0) return DisplayLib::boxMsg(_("Item not found"));
			else {
				$row = $db->fetch_array($result);

				if (!$row["upc"] || strlen($row["upc"]) < 1 || $row['charflag'] == 'SO') 
					return DisplayLib::boxMsg("Not a valid item");
				else  
					return $this->discountupc($row["ItemQtty"]."*".$row["upc"],$item_num,$pd);
			}
		}
		return "";
	}	

	function discountupc($upc,$item_num=-1,$pd=0) {
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
				$deliflag = 1;
			}
		}

		if ($upc == "stop") return DisplayLib::inputUnknown();

		$db = Database::tDataConnect();

		$query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
			."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
			."' and unitPrice = ".$scaleprice." and discounttype <> 3 group by upc";

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

		//----------------------Void Item------------------
		$query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
			numflag,charflag,unitPrice,discounttype,regPrice,discount,
			memDiscount,discountable,description,trans_type,trans_subtype,
			department,tax,VolSpecial
			from localtemptrans where upc = '".$upc."' and unitPrice = "
		     .$scaleprice." and trans_id=$item_num";

		$result = $db->query($query_upc);
		$row = $db->fetch_array($result);

		$ItemQtty = $row["ItemQtty"];
		$foodstamp = MiscLib::nullwrap($row["foodstamp"]);
		$discounttype = MiscLib::nullwrap($row["discounttype"]);
		$mixMatch = MiscLib::nullwrap($row["mixMatch"]);
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

		$CardNo = $CORE_LOCAL->get("memberID");
		
		$discounttype = MiscLib::nullwrap($row["discounttype"]);
		if ($discounttype == 3) 
			$quantity = -1 * $ItemQtty;

		elseif ($quantity != 0) {
			TransRecord::addRecord(array(
                'upc' => $upc, 
                'description' => $row["description"], 
                'trans_type' => $row["trans_type"], 
                'trans_subtype' => $row["trans_subtype"], 
                'trans_status' => "V", 
                'department' => $row["department"], 
                'quantity' => $quantity, 
                'unitPrice' => $unitPrice, 
                'total' => $total, 
                'regPrice' => $row["regPrice"], 
                'scale' => $scale, 
                'tax' => $row["tax"], 
                'foodstamp' => $foodstamp, 
                'discount' => $discount, 
                'memDiscount' => $memDiscount, 
                'discountable' => $discountable, 
                'discounttype' => $discounttype, 
                'quantity' => $quantity, 
                'volDiscType' => $volDiscType, 
                'volume' => $volume, 
                'VolSpecial' => $VolSpecial, 
                'mixMatch' => $mixMatch, 
                'voided' => 1, 
                'cost' => $cost, 
                'numflag' => $numflag, 
                'charflag' => $charflag
            ));

			if ($row["trans_type"] != "T") {
				$CORE_LOCAL->set("ttlflag",0);
			}

			$db = Database::pDataConnect();
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
