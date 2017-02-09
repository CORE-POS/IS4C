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

namespace COREPOS\pos\lib\Scanning\SpecialUPCs;
use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;

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
        $upcPrefix = '005';
        if ($this->session->get('UpcIncludeCheckDigits') == 1) {
            $upcPrefix = '05';
        }
        $eanPrefix = '099';
        if ($this->session->get('EanIncludeCheckDigits') == 1) {
            $eanPrefix = '99';
        }

        $this->ean = false;
        if (substr($upc,0,strlen($upcPrefix)) == $upcPrefix) {
            return true;
        } elseif (substr($upc,0,strlen($eanPrefix)) == $eanPrefix) {
            $this->ean = true;
            return true;
        }

        return false;
    }

    private function upcToParts($upc)
    {
        /**
          Adjust string index of pieces
          based on whether check digits
          have been included
        */
        $manIdStart = 3;
        $famStart = 8;
        $valStart = 11;
        if ( ($this->ean && $this->session->get('EanIncludeCheckDigits') == 1) ||
             (!$this->ean && $this->session->get('UpcIncludeCheckDigits') == 1)
           ) {
            $manIdStart = 2;
            $famStart = 9;
            $valStart = 10;
        }

        $manId = substr($upc, $manIdStart, 5);
        $fam = substr($upc, $famStart, 3);
        $val = substr($upc, $valStart, 2);

        return array($manId, $fam, $val, $manIdStart);
    }

    private function getValue($val)
    {
        $dbc = Database::pDataConnect();
        $query = "select Value,Qty from couponcodes where Code = '".$val."'";
        $result = $dbc->query($query);
        return $dbc->fetchRow($result);
    }
    
    private function checkLimits($upc, $json)
    {
        $dbc = Database::pDataConnect();
        $query2 = "SELECT reason, threshold FROM disableCoupon WHERE upc='$upc'";
        $result2 = $dbc->query($query2);
        if ($result2 && $dbc->numRows($result2) > 0) {
            $row = $dbc->fetchRow($result2);
            if ($row['threshold'] <= 0) {
                $json['output'] = DisplayLib::boxMsg(
                    $row['reason'],
                    _("coupon disabled"),
                    false,
                    DisplayLib::standardClearButton()
                );
                return $json;
            }

            $transDB = Database::tDataConnect();
            $qtyQ = "SELECT SUM(quantity) FROM localtemptrans WHERE upc='$upc'";
            $qtyR = $transDB->query($qtyQ);
            if ($transDB->numRows($qtyR) > 0) {
                $qtyW = $transDB->fetchRow($qtyR);
                $qty = $qtyW[0];
                if ($qty >= $row['threshold']) {
                    $json['output'] = DisplayLib::boxMsg(
                        _('coupon already applied'),
                        '',
                        false,
                        DisplayLib::standardClearButton()
                    );
                    return $json;
                }
            }
        }

        return true;
    }

    public function handle($upc,$json)
    {
        list($manId, $fam, $val, $manIdStart) = $this->upcToParts($upc);

        $valueInfo = $this->getValue($val);
        if (!$valueInfo) {
            $json['output'] = DisplayLib::boxMsg(
                _("coupon type unknown")."<br />"._("enter coupon manually"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        }
        $value = $valueInfo["Value"];
        $qty = $valueInfo["Qty"];

        $chk = $this->checkLimits($upc, $json);
        if ($chk !== true) {
            return $chk;
        }

        if ($fam == "992") {
            // 992 basically means blanket accept
            // Old method of asking cashier to assign a department
            // just creates confusion
            // Instead I just try to guess, otherwise use zero
            // (since that's what would happen anyway when the
            // confused cashier does a generic coupon tender)
            $value = MiscLib::truncate2($value);
            $this->session->set("couponupc",$upc);
            $this->session->set("couponamt",$value);

            $dept = 0;
            $dbc = Database::tDataConnect();
            // SQL strings are indexed starting w/ one instead of zero
            // hence $manIdStart+1
            $query = "select department from localtemptrans 
                WHERE substring(upc," . ($manIdStart+1) . ",5)='$manId' 
                GROUP BY department
                ORDER BY count(*) desc";
            $result = $dbc->query($query);
            if ($dbc->numRows($result) > 0) {
                $row = $dbc->fetchRow($result);
                $dept = $row['department'];
            }

            TransRecord::addCoupon($upc, $dept, $value);
            $json['output'] = DisplayLib::lastpage();

            return $json;
        }

        // validate coupon
        $dbc = Database::tDataConnect();

        $matchLength = 5;
        $matchOn = $manId;
        if ($this->session->get('EnforceFamilyCode')) {
            $matchLength = 8;
            $matchOn = $manId  . $fam;
        }

        /* the idea here is to track exactly which
           items in the transaction a coupon was 
           previously applied to

           SQL strings are indexed starting w/ one instead of zero
           hence $manIdStart+1
        */
        $query = "select max(t.unitPrice) as unitPrice,
            max(t.department) as department,
            max(t.ItemQtty) as itemQtty,
            sum(case when c.quantity is null then 0 else c.quantity end) as couponQtty,
            max(case when c.quantity is not null then 0 else t.foodstamp end) as foodstamp,
            max(case when c.quantity is not null then 0 else t.tax end) as tax,
            max(case when c.quantity is not null then 0 else t.discountable end) as discountable,
            max(t.emp_no) as emp_no,
            max(t.trans_no) as trans_no,
            t.trans_id from
            localtemptrans as t left join couponApplied as c
            on t.emp_no=c.emp_no and t.trans_no=c.trans_no
            and t.trans_id=c.trans_id
            where (substring(t.upc," . ($manIdStart+1) . ", {$matchLength})='{$matchOn}'";
        /* not right per the standard, but organic valley doesn't
         * provide consistent manufacturer ids in the same goddamn
         * coupon book */
        if ($this->ean) {
            $query .= " or substring(t.upc," . $manIdStart . ", {$matchLength})='{$matchOn}'";
        }
        $query .= ") and t.trans_status <> 'C'
            group by t.trans_id
            order by t.unitPrice desc";
        $result = $dbc->query($query);
        $numRows = $dbc->numRows($result);

        /* no item w/ matching manufacturer */
        if ($numRows == 0) {
            $json['output'] = DisplayLib::boxMsg(
                _("product not found")."<br />"._("in transaction"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        }

        /* count up per-item quantites that have not
           yet had a coupon applied to them */
        $available = array();
        $empno=$transno=$dept=$foodstamp=$tax=$discountable=-1;
        $actQty = 0;
        while($row = $dbc->fetchRow($result)) {
            if ($row["itemQtty"] - $row["couponQtty"] > 0) {
                $transId = $row["trans_id"];
                $available[$transId] = array(0,0);
                $available[$transId][0] = $row["unitPrice"];
                $available[$transId][1] += $row["itemQtty"];
                $available[$transId][1] -= $row["couponQtty"];
                $actQty += $available[$transId][1];
            }
            if ($empno == -1) {
                $empno = $row["emp_no"];
                $transno = $row["trans_no"];
                $dept = $row["department"];
                $foodstamp = $row["foodstamp"];
                $tax = $row['tax'];
                $discountable = $row['discountable'];
            }
        }

        /* every line has maximum coupons applied */
        if (count($available) == 0) {
            $json['output'] = DisplayLib::boxMsg(
                _("Coupon already applied")."<br />"._("for this item"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        }

        /* insufficient number of matching items */
        if ($qty > $actQty) {
            $msg = sprintf(_("coupon requires %d items"),$qty) . "<br />"
                 . sprintf(_("there are only %d item(s)"),$actQty) . "<br />"
                 . _("in this transaction");
            $json['output'] = DisplayLib::boxMsg($msg, '', false, DisplayLib::standardClearButton());
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
        foreach(array_keys($available) as $transId) {
            if ($value == 0) {
                $value = -1 * $available[$transId][0];
            }
            if ($qty <= $available[$transId][1]) {
                $query = "INSERT INTO couponApplied 
                    (emp_no,trans_no,quantity,trans_id)
                    VALUES (
                    $empno,$transno,$qty,$transId)";
                $dbc->query($query);
                $applied += $qty;
            } else {
                $query = "INSERT INTO couponApplied 
                    (emp_no,trans_no,quantity,trans_id)
                    VALUES (
                    $empno,$transno,".
                    $available[$transId][1].",$transId)";
                $dbc->query($query);
                $applied += $available[$transId][1];
            }

            if ($applied >= $qty) {
                break;
            }
        }

        $value = MiscLib::truncate2($value);
        $json['udpmsg'] = 'goodBeep';
        $status = array('tax'=>$tax, 'foodstamp'=>$foodstamp, 'discountable'=>$discountable);
        TransRecord::addCoupon($upc, $dept, $value, $status);
        $json['output'] = DisplayLib::lastpage();
        $json['redraw_footer'] = True;

        return $json;
    }

}

