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
        $prefix = CoreLocal::get('houseCouponPrefix');
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
        TransRecord::addhousecoupon($upc, $add['department'], -1 * $add['value'], $add['description']);

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
        $dbc = Database::pDataConnect();
        $hctable = $dbc->table_definition('houseCoupons');
        $infoQ = "SELECT endDate," 
                    . $dbc->identifier_escape('limit') . ",
                    discountType, 
                    department,
                    discountValue, 
                    minType, 
                    minValue, 
                    memberOnly, 
                    CASE 
                        WHEN endDate IS NULL THEN 0 
                        ELSE ". $dbc->datediff('endDate', $dbc->now()) . " 
                    END AS expired";
        // new(ish) columns 16apr14
        if (isset($hctable['description'])) {
            $infoQ .= ', description';
        } else {
            $infoQ .= ', \'\' AS description';
        }
        if (isset($hctable['startDate'])) {
            $infoQ .= ", CASE 
                          WHEN startDate IS NULL THEN 0 
                          ELSE ". $dbc->datediff('startDate', $dbc->now()) . " 
                        END as preStart";
        } else {
            $infoQ .= ', 0 AS preStart';
        }
        $infoQ .= " FROM  houseCoupons 
                    WHERE coupID=" . ((int)$id);
        $infoR = $dbc->query($infoQ);
        if ($dbc->num_rows($infoR) == 0) {
            return false;
        }

        return $dbc->fetch_row($infoR);
    }

    private function errorOrQuiet($msg, $quiet)
    {
            if ($quiet) {
                return false;
            } else {
                return DisplayLib::boxMsg(
                    $msg,
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            }
    }

    private function isMember()
    {
        $is_mem = false;
        if (CoreLocal::get('isMember') == 1) {
            $is_mem = true;
        } elseif (CoreLocal::get('memberID') == CoreLocal::get('visitingMem')) {
            $is_mem = true;
        } elseif (CoreLocal::get('memberID') == '0') {
            $is_mem = false;
        }

        return $is_mem;
    }

    /**
      Validate coupon exists, is not expired, and
      transaction meets required qualifications
      @param $id [int] coupon ID
      @param $quiet [boolean] just return false rather than
        an error message on failure
      @return [boolean] true or [string] error message
    */
    public function checkQualifications($id, $quiet=false)
    {
        $infoW = $this->lookupCoupon($id);
        if ($infoW === false) {
            return $this->errorOrQuiet(_("coupon not found"), $quiet);
        }

        if ($infoW["expired"] < 0) {
            $expired = substr($infoW["endDate"], 0, strrpos($infoW["endDate"], " "));
            return $this->errorOrQuiet(_("coupon expired "). $expired, $quiet);
        } elseif ($infoW['preStart'] > 0) {
            return $this->errorOrQuiet(_("coupon not available yet "). $expired, $quiet);
        }

        /* check for member-only, longer use tracking
           available with member coupons */
        if ($infoW["memberOnly"] == 1 && !$this->isMember()) {
            if ($quiet) {
                return false;
            } else {
                return DisplayLib::boxMsg(
                    _("Apply member number first"),
                    _('Member only coupon'),
                    false,
                    array_merge(array('Member Search [ID]' => 'parseWrapper(\'ID\');'), DisplayLib::standardClearButton())
                );
            }
        }

        /* verify the minimum purchase has been made */
        $transDB = Database::tDataConnect();
        $coupID = $id;
        switch ($infoW["minType"]) {
            case "Q": // must purchase at least X
            case "Q+": // must purchase more than X
                $minQ = "select case when sum(ItemQtty) is null
                    then 0 else sum(ItemQtty) end
                    " . $this->baseSQL($transDB, $coupID, 'upc');
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($infoW['minType'] == 'Q+' && $validQtty <= $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                } elseif ($infoW['minType'] == 'Q' && $validQtty < $infoW['minValue']) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                }
                break;
            case 'D': // must purchase at least amount in $ from department
            case 'D+': // must purchase more than amount in $ from department
                $minQ = "select case when sum(total) is null
                    then 0 else sum(total) end
                    " . $this->baseSQL($transDB, $coupID, 'department');
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($infoW['minType'] == 'D+' && $validQtty <= $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                } elseif ($infoW['minType'] == 'D' && $validQtty < $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                }
                break;
            case 'C': // must purchase at least amount in qty (count) from department
            case 'C+': // must purchase more than amount in qty (count) from department
                $minQ = "select case when sum(ItemQtty) is null
                    then 0 else sum(ItemQtty) end
                    " . $this->baseSQL($transDB, $coupID, 'department') . "
                    AND l.trans_type IN ('I','D')";
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];
                if ($infoW['minType'] == 'C+' && $validQtty <= $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                } elseif ($infoW['minType'] == 'C' && $validQtty < $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                }
                break;
            case 'M': // must purchase at least X qualifying items
                  // and some quantity corresponding discount items
                $minQ = "select case when sum(ItemQtty) is null then 0 else
                    sum(ItemQtty) end
                    " . $this->baseSQL($transDB, $coupID, 'upc') . "
                    and h.type = ";
                $minR = $transDB->query($minQ . "'QUALIFIER'");
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];

                $min2R = $transDB->query($minQ . "'DISCOUNT'");
                $min2W = $transDB->fetch_row($min2R);
                $validQtty2 = $min2W[0];

                if ($validQtty < $infoW["minValue"] || $validQtty2 <= 0) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                }
                break;
            case 'MX': // must purchase at least $ from qualifying departments
                       // and some quantity discount items
                       // (mix "cross")
                $minQ = "select case when sum(total) is null
                    then 0 else sum(total) end
                    " . $this->baseSQL($transDB, $coupID, 'department') . "
                        AND h.type='QUALIFIER'";
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validQtty = $minW[0];

                $min2Q = "select case when sum(ItemQtty) is null then 0 else
                    sum(ItemQtty) end
                    " . $this->baseSQL($transDB, $coupID, 'upc') . "
                    and h.type = 'DISCOUNT'";
                $min2R = $transDB->query($min2Q);
                $min2W = $transDB->fetch_row($min2R);
                $validQtty2 = $min2W[0];

                if ($validQtty < $infoW["minValue"] || $validQtty2 <= 0) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                }
                break;
            case '$': // must purchase at least $ total items
            case '$+': // must purchase more than $ total items
                $minQ = "SELECT sum(total) FROM localtemptrans
                    WHERE trans_type IN ('I', 'D', 'M')";
                $minR = $transDB->query($minQ);
                $minW = $transDB->fetch_row($minR);
                $validAmt = $minW[0];
                if ($infoW['minType'] == '$+' && $validAmt <= $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                } elseif ($infoW['minType'] == '$' && $validAmt < $infoW["minValue"]) {
                    return $this->errorOrQuiet(_('coupon requirements not met'), $quiet);
                }
                break;
            case '': // no minimum
            case ' ':
                break;
            default:
                return $this->errorOrQuiet(_('unknown minimum type ') . $infoW['minType']);
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
        $infoW = $this->lookupCoupon($id);
        if ($infoW === false) {
            return $this->errorOrQuiet(_('coupon not found'), false);
        }

        $prefix = CoreLocal::get('houseCouponPrefix');
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
        $limitW = $transDB->fetch_row($limitR);
        $times_used = $limitW[0];
        if ($times_used >= $infoW["limit"]) {
            return $this->errorOrQuiet(_('coupon already applied'), false);
        }

        /**
          For members, enforce limits against longer
          transaction history
        */
        if ($infoW["memberOnly"] == 1 && CoreLocal::get("standalone")==0 
            && CoreLocal::get('memberID') != CoreLocal::get('visitingMem')) {
            $mDB = Database::mDataConnect();

            // Lookup usage of this coupon by this member
            // Subquery is to combine today (dlog)
            // with previous days (dlog_90_view)
            // Potential replacement for houseCouponThisMonth
            $monthStart = date('Y-m-01 00:00:00');
            $altQ = "SELECT SUM(s.quantity) AS quantity,
                        MAX(tdate) AS lastUse
                     FROM (
                        SELECT upc, card_no, quantity, tdate
                        FROM dlog
                        WHERE
                            trans_type='T'
                            AND trans_subtype='IC'
                            AND upc='$upc'
                            AND card_no=" . ((int)CoreLocal::get('memberID')) . "
    
                        UNION ALL

                        SELECT upc, card_no, quantity, tdate
                        FROM dlog_90_view
                        WHERE
                            trans_type='T'
                            AND trans_subtype='IC'
                            AND upc='$upc'
                            AND card_no=" . ((int)CoreLocal::get('memberID')) . "
                            AND tdate >= '$monthStart'
                     ) AS s
                     GROUP BY s.upc, s.card_no";

            $mRes = $mDB->query("SELECT quantity 
                               FROM houseCouponThisMonth
                               WHERE card_no=" . CoreLocal::get("memberID") . " and
                               upc='$upc'");
            if ($mDB->num_rows($mRes) > 0) {
                $mRow = $mDB->fetch_row($mRes);
                $uses = $mRow['quantity'];
                if ($uses >= $infoW["limit"]) {
                    return $this->errorOrQuiet(_('coupon already used<br />on this membership'), false);
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
        switch ($infoW["discountType"]) {
            case "Q": // quantity discount
                // discount = coupon's discountValue
                // times the cheapeast coupon item
                $valQ = "select unitPrice, department 
                    " . $this->baseSQL($transDB, $coupID, 'upc') . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc ";
                $valR = $transDB->query($valQ);
                $valW = $transDB->fetch_row($valR);
                $value = $valW[0] * $infoW["discountValue"];
                break;
            case 'BG': // BOGO
                $valQ = 'SELECT SUM(l.total) '
                        . $this->baseSQL($transDB, $coupID, 'upc') . "
                        and h.type in ('BOTH', 'DISCOUNT')";
                $valP = $transDB->prepare($valQ);
                $value = $transDB->getValue($valP);
                $value = MiscLib::truncate2($value/2);
                if ($value > 0 && $value > $infoW['discountValue']) {
                    $value = $infoW['discountValue'];
                }
                break;
            case "P": // discount price
                // query to get the item's department and current value
                // current value minus the discount price is how much to
                // take off
                $value = $infoW["discountValue"];
                $deptQ = "select department, (total/quantity) as value 
                    " . $this->baseSQL($transDB, $coupID, 'upc') . "
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
                $valQ = "select department, quantity 
                    " . $this->baseSQL($transDB, $coupID, 'department') . "
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
                $valQ = "select department, l.total 
                    " . $this->baseSQL($transDB, $coupID, 'department') . "
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
                $valQ = "select sum(quantity) 
                    " . $this->baseSQL($transDB, $coupID, 'department') . "
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
                $valQ = "select l.upc, quantity 
                    " . $this->baseSQL($transDB, $coupID, 'upc') . "
                    and h.type in ('BOTH', 'DISCOUNT')
                    and l.total > 0
                    order by unitPrice asc";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row[1] * $value;
                break;
            case 'PI': // per-item discount
                    // take of the request amount times the
                    // number of matching items.
                $value = $infoW["discountValue"];
                $valQ = "
                    SELECT 
                       SUM(CASE WHEN ItemQtty IS NULL THEN 0 ELSE ItemQtty END) AS qty
                    " . $this->baseSQL($transDB, $coupID, 'upc'); 
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row['qty'] * $value;
                break;
            case "F": // completely flat; no scaling for weight
                $value = $infoW["discountValue"];
                break;
            case "%": // percent discount on all items
                Database::getsubtotals();
                $value = $infoW["discountValue"] * CoreLocal::get("discountableTotal");
                break;
            case "%B": // better percent discount applies
                Database::getsubtotals();
                $coupon_discount = (int)($infoW['discountValue']*100);
                if ($coupon_discount <= CoreLocal::get('percentDiscount')) {
                    // customer's discount is better than coupon discount; skip
                    // applying coupon
                    $value = 0;
                } else {
                    // coupon discount is better than customer's discount
                    // apply coupon & zero out customer's discount
                    $value = $infoW["discountValue"] * CoreLocal::get("discountableTotal");
                    CoreLocal::set('percentDiscount', 0);
                    $transDB->query('UPDATE localtemptrans SET percentDiscount=0');
                }
                break;
            case "%D": // percent discount on all items in give department(s)
                $valQ = "select sum(total) 
                    " . $this->baseSQL($transDB, $coupID, 'department') . "
                    and h.type in ('BOTH', 'DISCOUNT')";
                $valR = $transDB->query($valQ);
                $row = $transDB->fetch_row($valR);
                $value = $row[0] * $infoW["discountValue"];
                break;
            case "%E": // better percent discount applies to specified department only
                Database::getsubtotals();
                $coupon_discount = (int)($infoW['discountValue']*100);
                if ($coupon_discount <= CoreLocal::get('percentDiscount')) {
                    // customer's discount is better than coupon discount; skip
                    // applying coupon
                    $value = 0;
                } else {
                    // coupon discount is better than customer's discount
                    // apply coupon & exclude those items from customer's discount
                    $valQ = "select sum(total) 
                        " . $this->baseSQL($transDB, $coupID, 'department') . "
                        and h.type in ('BOTH', 'DISCOUNT')";
                    $valR = $transDB->query($valQ);
                    $row = $transDB->fetch_row($valR);
                    $value = $row[0] * $infoW["discountValue"];                 

                    $clearQ = "
                        UPDATE localtemptrans AS l 
                            INNER JOIN " . CoreLocal::get('pDatabase') . $transDB->sep() . "houseCouponItems AS h ON l.department = h.upc
                        SET l.discountable=0
                        WHERE h.coupID = " . $coupID . "
                            AND h.type IN ('BOTH', 'DISCOUNT')";
                    $clearR = $transDB->query($clearR);
                }
                break;
            case 'PD': // modify customer percent discount
                   // rather than add line-item
                $couponPD = $infoW['discountValue'] * 100;
                DiscountModule::updateDiscount(new DiscountModule($couponPD, 'HouseCoupon'));
                // still need to add a line-item with the coupon UPC to the
                // transaction to track usage
                $value = 0;
                $description = $couponPD . ' % Discount Coupon';
                break;
            case 'OD': // override customer percent discount
                   // rather than add line-item
                $couponPD = $infoW['discountValue'] * 100;
                DiscountModule::updateDiscount(new DiscountModule(0, 'custdata'));
                DiscountModule::updateDiscount(new DiscountModule($couponPD, 'HouseCoupon'));
                // still need to add a line-item with the coupon UPC to the
                // transaction to track usage
                $value = 0;
                $description = $couponPD . ' % Discount Coupon';
                break;
        }

        return array('value' => $value, 'department' => $infoW['department'], 'description' => $description);
    }

    /**
      This FROM/WHERE is super repetitive
    */
    private function baseSQL($dbc, $coupID, $mode='upc')
    {
        $ret = '
            FROM localtemptrans AS l
                INNER JOIN ' . CoreLocal::get('pDatabase') . $dbc->sep() . 'houseCouponItems AS h 
                ON h.upc=' . ($mode=='upc' ? 'l.upc' : 'l.department') . '
            WHERE h.coupID=' . ((int)$coupID);
        return $ret;
    }
}

