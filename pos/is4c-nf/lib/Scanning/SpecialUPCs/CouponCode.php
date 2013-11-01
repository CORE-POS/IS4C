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

/**
  @class CouponCode
  Handle standard manufacturer coupons

  This module looks for UPCs with prefix
   - 005 UPC-12 coupon 
   - 099 EAN-13 coupon

  It extracts the manufacturer prefix from
  the UPC and validates that a matching item
  is in the transaction

  It looks up the coupon code to calculate
  a discount value and adds the coupon to
  the transaction
*/
class CouponCode extends SpecialUPC 
{

    private $ean;

    public function isSpecial($upc)
    {
        $this->ean = false;    
        if (substr($upc,0,3) == "005") {
            return true;
        } elseif (substr($upc,0,3) == "099") {
            $this->ean = true;
            return true;
        }

        return false;
    }

    public function handle($upc,$json)
    {
        global $CORE_LOCAL;

        $man_id = substr($upc, 3, 5);
        $fam = substr($upc, 8, 3);
        $val = substr($upc, -2);

        $db = Database::pDataConnect();
        $query = "select Value,Qty from couponcodes where Code = '".$val."'";
        $result = $db->query($query);
        $num_rows = $db->num_rows($result);

        if ($num_rows == 0) {
            $json['output'] = DisplayLib::boxMsg(_("coupon type unknown")."<br />"._("enter coupon manually"));
            return $json;
        }

        $query2 = "SELECT reason, threshold FROM disableCoupon WHERE upc='$upc'";
        $result2 = $db->query($query2);
        if ($result2 && $db->num_rows($result2) > 0) {
            $row = $db->fetch_row($result2);
            if ($row['threshold'] <= 0) {
                $json['output'] = DisplayLib::boxMsg(_("coupon disabled")."<br />".$row['reason']);
                return $json;
            } else {
                $transDB = Database::tDataConnect();
                $q = "SELECT SUM(quantity) FROM localtemptrans WHERE upc='$upc'";
                $r = $transDB->query($q);
                if ($transDB->num_rows($r) > 0) {
                    $w = $transDB->fetch_row($r);
                    $qty = $w[0];
                    if ($qty >= $row['threshold']) {
                        $json['output'] = DisplayLib::boxMsg(_('coupon already applied'));
                        return $json;
                    }
                }
            }
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
            $value = MiscLib::truncate2($value);
            $CORE_LOCAL->set("couponupc",$upc);
            $CORE_LOCAL->set("couponamt",$value);

            $dept = 0;
            $db = Database::tDataConnect();
            $query = "select department from localtemptrans WHERE
                substring(upc,4,5)='$man_id' group by department
                order by count(*) desc";
            $result = $db->query($query);
            if ($db->num_rows($result) > 0) {
                $row = $db->fetch_row($result);
                $dept = $row['department'];
            }

            TransRecord::addCoupon($upc, $dept, $value);
            $json['output'] = DisplayLib::lastpage();

            return $json;
        }

        // validate coupon
        $db = Database::tDataConnect();
        $fam = substr($fam, 0, 2);

        /* the idea here is to track exactly which
           items in the transaction a coupon was 
           previously applied to
        */
        $query = "select max(t.unitPrice) as unitPrice,
            max(t.department) as department,
            max(t.ItemQtty) as itemQtty,
            sum(case when c.quantity is null then 0 else c.quantity end) as couponQtty,
            max(case when c.quantity is not null then 0 else t.foodstamp end) as foodstamp,
            max(case when c.quantity is not null then 0 else t.tax end) as tax,
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
        if ($this->ean) {
            $query .= " or substring(t.upc,3,5)='$man_id'";
        }
        $query .= ") and t.trans_status <> 'C'
            group by t.trans_id
            order by t.unitPrice desc";
        $result = $db->query($query);
        $num_rows = $db->num_rows($result);

        /* no item w/ matching manufacturer */
        if ($num_rows == 0) {
            $json['output'] = DisplayLib::boxMsg(_("product not found")."<br />"._("in transaction"));
            return $json;
        }

        /* count up per-item quantites that have not
           yet had a coupon applied to them */
        $available = array();
        $emp_no=$transno=$dept=$foodstamp=$tax=-1;
        $act_qty = 0;
        while($row = $db->fetch_array($result)) {
            if ($row["itemQtty"] - $row["couponQtty"] > 0) {
                $id = $row["trans_id"];
                $available["$id"] = array(0,0);
                $available["$id"][0] = $row["unitPrice"];
                $available["$id"][1] += $row["itemQtty"];
                $available["$id"][1] -= $row["couponQtty"];
                $act_qty += $available["$id"][1];
            }
            if ($emp_no == -1) {
                $emp_no = $row["emp_no"];
                $transno = $row["trans_no"];
                $dept = $row["department"];
                $foodstamp = $row["foodstamp"];
                $tax = $row['tax'];
            }
        }

        /* every line has maximum coupons applied */
        if (count($available) == 0) {
            $json['output'] = DisplayLib::boxMsg(_("Coupon already applied")."<br />"._("for this item"));
            return $json;
        }

        /* insufficient number of matching items */
        if ($qty > $act_qty) {
            $json['output'] = DisplayLib::boxMsg(sprintf(_("coupon requires %d items"),$qty)."<br />".
                        sprintf(_("there are only %d item(s)"),$act_qty)."<br />"._("in this transaction"));
            return $json;
        }
        

        /* free item, multiple choices
           needs work, obviously */
        if ($value == 0 && count($available) > 1) {
            // decide which item(s)
            // manually by cashier maybe?
        }

        /* log the item(s) this coupon is
           being applied to */
        $applied = 0;
        foreach(array_keys($available) as $id) {
            if ($value == 0) {
                $value = -1 * $available["$id"][0];
            }
            if ($qty <= $available["$id"][1]) {
                $q = "INSERT INTO couponApplied 
                    (emp_no,trans_no,quantity,trans_id)
                    VALUES (
                    $emp_no,$transno,$qty,$id)";
                $r = $db->query($q);
                $applied += $qty;
            } else {
                $q = "INSERT INTO couponApplied 
                    (emp_no,trans_no,quantity,trans_id)
                    VALUES (
                    $emp_no,$transno,".
                    $available["$id"][1].",$id)";
                $r = $db->query($q);
                $applied += $available["$id"][1];
            }

            if ($applied >= $qty) {
                break;
            }
        }

        $value = MiscLib::truncate2($value);
        $json['udpmsg'] = 'goodBeep';
        TransRecord::addCoupon($upc, $dept, $value, $foodstamp, $tax);
        $json['output'] = DisplayLib::lastpage();
        $json['redraw_footer'] = True;

        return $json;
    }

}

