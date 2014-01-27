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
  @class HouseCoupon
  WFC style custom store coupons

  This class looks for UPC prefix 00499999

  The remainder of the UPC is an ID value
  to look up requirement(s) and discount
  via the houseCoupons and houseCouponItems
  tables
*/
class HouseCoupon extends SpecialUPC 
{

    public function isSpecial($upc)
    {
        global $CORE_LOCAL;
        $prefix = $CORE_LOCAL->get('houseCouponPrefix');
        if ($prefix == '') {
            $prefix = '00499999';
        }

		if (substr($upc,0,strlen($prefix)) == $prefix) {
            return true;
        }

        return false;
    }

    public function handle($upc, $json)
    {
        global $CORE_LOCAL;

        $coupID = ltrim(substr($upc, -5), "0");
        $leadDigits = substr($upc, 3, 5);

        $qualified = $this->checkQualifications($coupID);
        if ($qualified !== true) {
            $json['output'] = $qualified;
            return $json;
        }

        $available = $this->checkLimits($coupID);
        if ($available !== true) {
            $json['output'] = $available;
            return $json;
        }

        $add = $this->getValue($coupID);
        if ($add['value'] != 0) {
            TransRecord::addhousecoupon($upc, $add['department'], -1 * $add['value'], $add['description']);
        }
        $json['output'] = DisplayLib::lastpage();
        $json['udpmsg'] = 'goodBeep';
        $json['redraw_footer'] = true;

        return $json;
    }

    /**
      helper - lookup coupon record
    */
    private function lookupCoupon($id)
    {
        $db = Database::pDataConnect();
        $hctable = $db->table_definition('houseCoupons');
        $infoQ = "select endDate," . $db->identifier_escape('limit') .
            ",discountType, department,
            discountValue, minType, minValue, memberOnly, 
            case when endDate is NULL then 0 else 
            ". $db->datediff('endDate', $db->now()) . " end as expired
            from
            houseCoupons WHERE coupID=" . ((int)$id);
        if (isset($hctable['definition'])) {
            $infoQ = str_replace('as expired', 'as expired, description', $infoQ);
        }
        $infoR = $db->query($infoQ);
        if ($db->num_rows($infoR) == 0) {
            return false;
        }

        return $db->fetch_row($infoR);
    }

    /**
      Validate coupon exists, is not expired, and
      transaction meets required qualifications
      @param $id [int] coupon ID
      @return [boolean] true or [string] error message
    */
    public function checkQualifications($id)
    {
        global $CORE_LOCAL;
        $infoW = $this->lookupCoupon($id);
        if ($infoW === false) {
            return DisplayLib::boxMsg(_("coupon not found"));
        }

        if ($infoW["expired"] < 0) {
            $expired = substr($infoW["endDate"], 0, strrpos($infoW["endDate"], " "));
            return DisplayLib::boxMsg(_("coupon expired") . " " . $expired);
        }

        /* check for member-only, longer use tracking
           available with member coupons */
        if ($infoW["memberOnly"] == 1 && ($CORE_LOCAL->get("memberID") == "0" || $CORE_LOCAL->get("isMember") != 1)) {
            return DisplayLib::boxMsg(_("Member only coupon") . "<br />" .
                        _("Apply member number first"));
        }

        /* verify the minimum purchase has been made */
        $transDB = Database::tDataConnect();
        switch($infoW["minType"]) {
            case "Q": // must purchase at least X
                $minQ = "select case when sum(ItemQtty) is null
                    then 0 else sum(ItemQtty) end
                        from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems 
                    as h on l.upc = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($validQtty < $infoW["minValue"]) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case "Q+": // must purchase more than X
                $minQ = "select case when sum(ItemQtty) is null
                    then 0 else sum(ItemQtty) end
                        from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems 
                    as h on l.upc = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($validQtty <= $infoW["minValue"]) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case 'D': // must at least purchase from department
                $minQ = "select case when sum(total) is null
                    then 0 else sum(total) end
                    from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($validQtty < $infoW["minValue"]) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case 'D+': // must more than purchase from department 
                $minQ = "select case when sum(total) is null
                    then 0 else sum(total) end
                    from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($validQtty <= $infoW["minValue"]) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case 'M': // must purchase at least X qualifying items
                  // and some quantity corresponding discount items
                $minQ = "select case when sum(ItemQtty) is null then 0 else
                    sum(ItemQtty) end
                    from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID = $coupID
                    and h.type = 'QUALIFIER'" ;
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];

                $min2Q = "select case when sum(ItemQtty) is null then 0 else
                    sum(ItemQtty) end
                    from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID = $coupID
                    and h.type = 'DISCOUNT'";
                $min2R = $transDB->query($min2Q);
                $min2W = $transDB->fetch_row($minR);
                $validQtty2 = $min2W[0];

                if ($validQtty < $infoW["minValue"] || $validQtty2 <= 0) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case '$': // must purchase at least $ total items
                $minQ = "SELECT sum(total) FROM localtemptrans
                    WHERE trans_type IN ('I', 'D', 'M')";
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validAmt = $minW[0];
                if ($validAmt < $infoW["minValue"]) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case '$+': // must purchase more than $ total items
                $minQ = "SELECT sum(total) FROM localtemptrans
                    WHERE trans_type IN ('I', 'D', 'M')";
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validAmt = $minW[0];
                if ($validAmt <= $infoW["minValue"]) {
                    return DisplayLib::boxMsg(_("coupon requirements not met"));
                }
                break;
            case '': // no minimum
            case ' ':
                break;
            default:
                return DisplayLib::boxMsg(_("unknown minimum type") . " " . $infoW["minType"]);
        }

        return true;
    }

    /**
      Check how many times the coupon has been used and 
      compare against usage limits - e.g., one per transaction,
      one per member, etc. This is a separate method from
      checkQualifications() so that calling code has the option
      of working around limits via voids or amount adjustments
      @param $id [int] coupon ID
      @return [boolean] true or [string] error message
    */
    public function checkLimits($id)
    {
        global $CORE_LOCAL;
        $infoW = $this->lookupCoupon($id);
        if ($infoW === false) {
            return DisplayLib::boxMsg(_("coupon not found"));
        }

        $prefix = $CORE_LOCAL->get('houseCouponPrefix');
        if ($prefix == '') {
            $prefix = '00499999';
        }
        $upc = $prefix . str_pad($id, 5, '0', STR_PAD_LEFT);

        /* check the number of times this coupon
         * has been used in this transaction
         * against the limit */
        $transDB = Database::tDataConnect();
        $limitQ = "select case when sum(ItemQtty) is null
            then 0 else sum(ItemQtty) end
            from localtemptrans where
            upc = '" . $upc . "'" ;
        $limitR = $transDB->query($limitQ);
        $times_used = array_pop($transDB->fetch_row($limitR));
        if ($times_used >= $infoW["limit"]) {
            return DisplayLib::boxMsg(_("coupon already applied"));
        }

        /**
          For members, enforce limits against longer
          transaction history
        */
        if ($infoW["memberOnly"] == 1 && $CORE_LOCAL->get("standalone")==0) {
            $mDB = Database::mDataConnect();
            $mR = $mDB->query("SELECT quantity FROM houseCouponThisMonth
                WHERE card_no=" . $CORE_LOCAL->get("memberID") . " and
                upc='$upc'");
            if ($mDB->num_rows($mR) > 0) {
                $uses = array_pop($mDB->fetch_row($mR));
                if ($infoW["limit"] >= $uses) {
                    return DisplayLib::boxMsg(_("Coupon already used")
                                ."<br />"
                                ._("on this membership"));
                }
            }
        }

        return true;
    }

    
    /**
      Get information about how much the coupon is worth
      @param $id [int] coupon ID
      @return array with keys:
        value => [float] coupon value
        department => [int] department number for the coupon
        description => [string] description for coupon
    */
    public function getValue($id)
    {
        global $CORE_LOCAL;
        $infoW = $this->lookupCoupon($id);
        if ($infoW === false) {
            return array('value' => 0, 'department' => 0, 'description' => '');
        }

        $transDB = Database::tDataConnect();
        /* if we got this far, the coupon
         * should be valid
         */
        $value = 0;
        $coupID = $id;
        $description = isset($infoW['description']) ? $infoW['description'] : '';
        switch($infoW["discountType"]) {
            case "Q": // quantity discount
                // discount = coupon's discountValue
                // times the cheapeast coupon item
                $valQ = "select unitPrice, department from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID=" . $coupID . " 
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc ";
                $valR = $transDB->query($valQ);
                $valW = $transDB->fetch_row($valR);
                $value = $valW[0] * $infoW["discountValue"];
                break;
            case "P": // discount price
                // query to get the item's department and current value
                // current value minus the discount price is how much to
                // take off
                $value = $infoW["discountValue"];
                $deptQ = "select department, (total/quantity) as value from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID=" . $coupID . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc ";
                $deptR = $transDB->query($deptQ);
                $row = $transDB->fetch_row($deptR);
                $value = $row[1] - $value;
                break;
            case "FD": // flat discount for departments
                // simply take off the requested amount
                // scales with quantity for by-weight items
                $value = $infoW["discountValue"];
                $valQ = "select department, quantity from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc ";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row[1] * $value;
                break;
            case "MD": // mix discount for departments
                // take off item value or discount value
                // whichever is less
                $value = $infoW["discountValue"];
                $valQ = "select department, l.total from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by l.total desc ";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = ($row[1] < $value) ? $row[1] : $value;
                break;
            case "AD": // all department discount
                // apply discount across all items
                // scales with quantity for by-weight items
                $value = $infoW["discountValue"];
                $valQ = "select sum(quantity) from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc ";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row[1] * $value;
                break;
            case "FI": // flat discount for items
                // simply take off the requested amount
                // scales with quantity for by-weight items
                $value = $infoW["discountValue"];
                $valQ = "select l.upc, quantity from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID = " . $coupID . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row[1] * $value;
                break;
            case "F": // completely flat; no scaling for weight
                $value = $infoW["discountValue"];
                break;
            case "%": // percent discount on all items
                Database::getsubtotals();
                $value = $infoW["discountValue"] * $CORE_LOCAL->get("discountableTotal");
                break;
            case "%D": // percent discount on all items in give department(s)
                $valQ = "select sum(total) from localtemptrans
                    as l left join " . $CORE_LOCAL->get('pDatabase') . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID . "
                    and h.type in ('BOTH', 'DISCOUNT')";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row[0] * $infoW["discountValue"];
                break;
            case 'PD': // modify customer percent discount
                   // rather than add line-item
                $couponPD = $infoW['discountValue'] * 100;
                $ttlPD = 0;
                Database::getsubtotals();
                $opDB = Database::pDataConnect();
                $custQ = 'SELECT Discount FROM custdata WHERE CardNo='.$CORE_LOCAL->get('memberID');    
                $custR = $opDB->query($custQ);
                // get member's normal discount
                $cust_discount = 0;
                if ($opDB->num_rows($custR) > 0) {
                    $custW = $opDB->fetch_row($custR);
                    $cust_discount = $custW['Discount'];
                }
                // apply discount module
                $handler_class = $CORE_LOCAL->get('DiscountModule');
                if ($handler_class === '') $handler_class = 'DiscountModule';
                elseif (!class_exists($handler_class)) $handler_class = 'DiscountModule';
                if (class_exists($handler_class)) {
                    $module = new $handler_class();
                    $ttlPD = $module->percentage($cust_discount);
                }
                // add coupon's discount
                $ttlPD += $couponPD;
                // apply new discount to session & transaction
                $CORE_LOCAL->set('percentDiscount', $ttlPD);
                $transDB = Database::tDataConnect();
                $transDB->query(sprintf('UPDATE localtemptrans SET percentDiscount=%f',$ttlPD));

                // still need to add a line-item with the coupon UPC to the
                // transaction to track usage
                $value = 0;
                $description = $ttlPD . ' % Discount Coupon';
                break;
        }

        return array('value' => $value, 'department' => $infoW['department'], 'description' => $description);
    }
}

