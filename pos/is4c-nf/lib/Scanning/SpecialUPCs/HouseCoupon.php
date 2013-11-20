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
        if (substr($upc, 0, 8) == "00499999") {
            return true;
        }

        return false;
    }

    public function handle($upc, $json)
    {
        global $CORE_LOCAL;

        $coupID = ltrim(substr($upc, -5), "0");
        $leadDigits = substr($upc, 3, 5);

        /* make sure the coupon exists
         * and isn't expired
         */
        $db = Database::pDataConnect();
        $infoQ = "select endDate," . $db->identifier_escape('limit') .
            ",discountType, department,
            discountValue, minType, minValue, memberOnly, 
            case when endDate is NULL then 0 else 
            ". $db->datediff('endDate', $db->now()) . " end as expired
            from
            houseCoupons where coupID = " . $coupID ;
        $infoR = $db->query($infoQ);
        if ($db->num_rows($infoR) == 0) {
            $json['output'] =  DisplayLib::boxMsg(_("coupon not found"));
            return $json;
        }
        $infoW = $db->fetch_row($infoR);
        if ($infoW["expired"] < 0) {
            $expired = substr($infoW["endDate"], 0, strrpos($infoW["endDate"], " "));
            $json['output'] =  DisplayLib::boxMsg(_("coupon expired") . " " . $expired);
            return $json;
        }

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
            $json['output'] =  DisplayLib::boxMsg(_("coupon already applied"));
            return $json;
        }

        /* check for member-only, longer use tracking
           available with member coupons */
        if (($infoW["memberOnly"] == 1) && 
            (($CORE_LOCAL->get("memberID") == "0") ||
               ($CORE_LOCAL->get("isMember") != 1  )
            )
           ) {
            $json['output'] = DisplayLib::boxMsg(_("Member only coupon") . "<br />" .
                        _("Apply member number first"));
            return $json;
        } else if ($infoW["memberOnly"] == 1 && $CORE_LOCAL->get("standalone")==0) {
            $mDB = Database::mDataConnect();
            $mR = $mDB->query("SELECT quantity FROM houseCouponThisMonth
                WHERE card_no=".$CORE_LOCAL->get("memberID")." and
                upc='$upc'");
            if ($mDB->num_rows($mR) > 0) {
                $uses = array_pop($mDB->fetch_row($mR));
                if ($infoW["limit"] >= $uses){
                    $json['output'] = DisplayLib::boxMsg(_("Coupon already used")."<br />".
                                _("on this membership"));
                    return $json;
                }
            }
        }

        /* verify the minimum purchase has been made */
        switch($infoW["minType"]) {
            case "Q": // must purchase at least X
                $minQ = "select case when sum(ItemQtty) is null
                    then 0 else sum(ItemQtty) end
                        from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems 
                    as h on l.upc = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $validQtty = array_pop($transDB->fetch_row($minR));
                if ($validQtty < $infoW["minValue"]) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case "Q+": // must purchase more than X
                $minQ = "select case when sum(ItemQtty) is null
                    then 0 else sum(ItemQtty) end
                        from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems 
                    as h on l.upc = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $validQtty = array_pop($transDB->fetch_row($minR));
                if ($validQtty <= $infoW["minValue"]) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case 'D': // must at least purchase from department
                $minQ = "select case when sum(total) is null
                    then 0 else sum(total) end
                    from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $validQtty = array_pop($transDB->fetch_row($minR));
                if ($validQtty < $infoW["minValue"]) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case 'D+': // must more than purchase from department 
                $minQ = "select case when sum(total) is null
                    then 0 else sum(total) end
                    from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
                    as h on l.department = h.upc
                    where h.coupID = " . $coupID ;
                $minR = $transDB->query($minQ);
                $validQtty = array_pop($transDB->fetch_row($minR));
                if ($validQtty <= $infoW["minValue"]) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case 'M': // must purchase at least X qualifying items
                  // and some quantity corresponding discount items
                $minQ = "select case when sum(ItemQtty) is null then 0 else
                    sum(ItemQtty) end
                    from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID = $coupID
                    and h.type = 'QUALIFIER'" ;
                $minR = $transDB->query($minQ);
                $validQtty = array_pop($transDB->fetch_row($minR));

                $min2Q = "select case when sum(ItemQtty) is null then 0 else
                    sum(ItemQtty) end
                    from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
                    as h on l.upc = h.upc
                    where h.coupID = $coupID
                    and h.type = 'DISCOUNT'";
                $min2R = $transDB->query($min2Q);
                $validQtty2 = array_pop($transDB->fetch_row($min2R));

                if ($validQtty < $infoW["minValue"] || $validQtty2 <= 0) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case '$': // must purchase at least $ total items
                $minQ = "SELECT sum(total) FROM localtemptrans
                    WHERE trans_type IN ('I', 'D', 'M')";
                $minR = $transDB->query($minQ);
                $validAmt = array_pop($transDB->fetch_row($minR));
                if ($validAmt < $infoW["minValue"]) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case '$+': // must purchase more than $ total items
                $minQ = "SELECT sum(total) FROM localtemptrans
                    WHERE trans_type IN ('I', 'D', 'M')";
                $minR = $transDB->query($minQ);
                $validAmt = array_pop($transDB->fetch_row($minR));
                if ($validAmt <= $infoW["minValue"]) {
                    $json['output'] = DisplayLib::boxMsg(_("coupon requirements not met"));
                    return $json;
                }
                break;
            case '': // no minimum
            case ' ':
                break;
            default:
                $json['output'] = DisplayLib::boxMsg(_("unknown minimum type") . " " . $infoW["minType"]);
                return $json;
        }

        /* if we got this far, the coupon
         * should be valid
         */
        $value = 0;
        switch($infoW["discountType"]) {
            case "Q": // quantity discount
                // discount = coupon's discountValue
                // times the cheapeast coupon item
                $valQ = "select unitPrice, department from localtemptrans
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                    as l left join opdata" . $transDB->sep() . "houseCouponItems
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
                $transDB->query(sprintf('UPDATE localtemptrans SET percentDiscount=%f',$ttlPD));

                // still need to add a line-item with the coupon UPC to the
                // transaction to track usage
                $value = 0;
                break;
        }

        $dept = $infoW["department"];
        
        TransRecord::addhousecoupon($upc, $dept, -1 * $value);
        $json['output'] = DisplayLib::lastpage();
        $json['udpmsg'] = 'goodBeep';
        $json['redraw_footer'] = true;

        return $json;
    }

}

