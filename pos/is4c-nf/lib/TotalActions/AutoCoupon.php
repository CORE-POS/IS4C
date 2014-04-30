<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class AutoCoupon
  Apply automatic coupons to the transaction
*/
class AutoCoupon extends TotalAction
{
    /**
      Apply action
      @return [boolean] true if the action
        completes successfully (or is not
        necessary at all) or [string] url
        to redirect to another page for
        further decisions/input.
    */
    public function apply()
    {
        global $CORE_LOCAL;
        $db = Database::pDataConnect();

        $coupons = array();
        $hc_table = $db->table_definition('houseCoupons');
        if ($db->table_exists('autoCoupons')) {
            $autoR = $db->query('SELECT coupID, description FROM autoCoupons');
            while($autoW = $db->fetch_row($autoR)) {
                $coupons[$autoW['coupID']] = $autoW['description'];
            }
        }
        if (isset($hc_table['description']) && isset($hc_table['auto'])) {
            $autoR = $db->query('SELECT coupID, description FROM houseCoupons WHERE auto=1');
            while($autoW = $db->fetch_row($autoR)) {
                $coupons[$autoW['coupID']] = $autoW['description'];
            }
        }

        $hc = new HouseCoupon();
        $prefix = $CORE_LOCAL->get('houseCouponPrefix');
        if ($prefix == '') {
            $prefix = '00499999';
        }

        foreach($coupons as $id => $description) {

            if ($hc->checkQualifications($id) !== true) {
                // member or transaction does not meet requirements
                // for auto-coupon purposes, this isn't really an 
                // error. no feedback necessary
                continue;
            }

            // get value of coupon AND value
            // of any previous applications of this coupon
            $add = $hc->getValue($id);
            $upc = $prefix . str_pad($id, 5, '0', STR_PAD_LEFT);
            $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            $current = $db->query('SELECT SUM(-total) AS ttl FROM '
                           .$CORE_LOCAL->get('tDatabase') . $db->sep() . 'localtemptrans
                           WHERE upc=\'' . $upc . '\'');
            $val = 0;
            if ($db->num_rows($current) > 0) {
                $currentW = $db->fetch_row($current);
                $val = $currentW['ttl'];
            }

            $next_val = $add['value'] - $val;
            if ($next_val == 0) {
                // no need to add another line item
                // previous one(s) sum to correct total
                continue;
            }

            TransRecord::addhousecoupon($upc, $add['department'], -1 * $next_val, $description);
        }

        return true;
    }
}

