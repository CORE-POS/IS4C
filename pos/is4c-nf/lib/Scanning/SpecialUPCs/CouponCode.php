<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("SpecialUPC")) include($CORE_PATH."lib/Scanning/SpecialUPC.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

if (!function_exists('pDataConnect')) include($CORE_PATH."lib/connect.php");
if (!function_exists('boxMsg')) include($CORE_PATH."lib/drawscreen.php");
if (!function_exists('lastpage')) include($CORE_PATH."lib/listitems.php");
if (!function_exists('truncate2')) include($CORE_PATH."lib/lib.php");
if (!function_exists('addcoupon')) include($CORE_PATH."lib/additem.php");

class CouponCode extends SpecialUPC {

var $ean;

	function is_special($upc){
		$this->ean = false;	
		if (substr($upc,0,3) == "005")
			return true;
		elseif (substr($upc,0,3) == "099"){
			$this->ean = true;
			return true;
		}

		return false;
	}

	function handle($upc,$json){
		global $CORE_LOCAL;

		$man_id = substr($upc, 3, 5);
		$fam = substr($upc, 8, 3);
		$val = substr($upc, -2);

		$db = pDataConnect();
		$query = "select Value,Qty from couponcodes where Code = '".$val."'";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		if ($num_rows == 0) {
			$json['output'] = boxMsg("coupon type unknown<br>please enter coupon<br>manually");
			return $json;
		}

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
			$CORE_LOCAL->set("couponupc",$upc);
			$CORE_LOCAL->set("couponamt",$value);

			$dept = 0;
			$db = tDataConnect();
			$query = "select department from localtemptrans WHERE
				substring(upc,4,5)='$man_id' group by department
				order by count(*) desc";
			$result = $db->query($query);
			if ($db->num_rows($result) > 0)
				$dept = array_pop($db->fetch_row($result));

			addcoupon($upc, $dept, $value);
			$json['output'] = lastpage();
			return $json;
		}

		// validate coupon
		$db->close();
		$db = tDataConnect();
		$fam = substr($fam, 0, 2);

		/* the idea here is to track exactly which
		   items in the transaction a coupon was 
		   previously applied to
		*/
		$query = "select max(t.unitPrice) as unitPrice,
			max(t.department) as department,
			max(t.ItemQtty) as itemQtty,
			sum(case when c.quantity is null then 0 else c.quantity end) as couponQtty,
			max(case when c.quantity is null then 0 else t.foodstamp end) as foodstamp,
			max(t.emp_no) as emp_no,
			max(t.trans_no) as trans_no,
			t.trans_id from
			localtemptrans as t left join couponApplied as c
			on t.emp_no=c.emp_no and t.trans_no=c.trans_no
			and t.trans_id=c.trans_id
			where (substring(t.upc,4,5)='$man_id'";
		/* not right per the standard, but organic valley doesn't
		 * provide consistent manufacturer ids in the same goddamn
		 * coupon book */
		if ($this->ean)
			$query .= " or substring(t.upc,3,5)='$man_id'";
		$query .= ") and t.trans_status <> 'C'
			group by t.trans_id
			order by t.unitPrice desc";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		/* no item w/ matching manufacturer */
		if ($num_rows == 0){
			$json['output'] = boxMsg("product not found<br />in transaction");
			return $json;
		}

		/* count up per-item quantites that have not
		   yet had a coupon applied to them */
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

		/* every line has maximum coupons applied */
		if (count($available) == 0) {
			$json['output'] = boxMsg("Coupon already applied<br />for this item");
			return $json;
		}

		/* insufficient number of matching items */
		if ($qty > $act_qty) {
			$json['output'] = boxMsg("coupon requires ".$qty."items<br />there are only ".$act_qty." item(s)<br />in this transaction");
			return $json;
		}
		

		/* free item, multiple choices
		   needs work, obviously */
		if ($value == 0 && count($available) > 1){
			// decide which item(s)
			// manually by cashier maybe?
		}

		/* log the item(s) this coupon is
		   being applied to */
		$applied = 0;
		foreach(array_keys($available) as $id){
			if ($value == 0)
				$value = -1 * $available["$id"][0];
			if ($qty <= $available["$id"][1]){
				$q = "INSERT INTO couponApplied 
					(emp_no,trans_no,quantity,trans_id)
					VALUES (
					$emp_no,$transno,$qty,$id)";
				$r = $db->query($q);
				$applied += $qty;
			}
			else {
				$q = "INSERT INTO couponApplied 
					(emp_no,trans_no,quantity,trans_id)
					VALUES (
					$emp_no,$transno,".
					$available["$id"][1].",$id)";
				$r = $db->query($q);
				$applied += $available["$id"][1];
			}

			if ($applied >= $qty) break;
		}

		$value = truncate2($value);
		addcoupon($upc, $dept, $value, $foodstamp);
		$json['output'] = lastpage();
		return $json;
	}

}

?>
