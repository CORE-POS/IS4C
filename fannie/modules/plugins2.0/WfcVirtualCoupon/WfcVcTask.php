<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class WfcVcTask extends FannieTask 
{
    public $name = 'WFC Virtual Coupon Tracker';

    public $description = 'Tracks usage of WFC virtual coupon and updates custdata.blueLine
    to indicate whether the coupon is available. Replaces older script "nightly.memcoupon.php".';

    public function run()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $dlog = DTransactionsModel::selectDlog($start, $end);

        $default_blueline = $dbc->concat(
                        $dbc->convert('c.CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        ''
        );

        // normalize everyone to zero
        $dbc->query('UPDATE custdata AS c SET memCoupons=0, blueLine=' . $default_blueline);
        // grant coupon to all members
        $dbc->query("UPDATE custdata AS c SET memCoupons=1 WHERE Type='PC'");

        // lookup usage in the last month
        $usageP = $dbc->prepare("SELECT card_no 
                                FROM $dlog
                                WHERE upc='0049999900001'
                                    AND tdate BETWEEN ? AND ?
                                GROUP BY card_no
                                HAVING SUM(total) <> 0");
        $usageR = $dbc->execute($usageP, array($start . ' 00:00:00', $end . ' 23:59:59'));

        // remove coupon from members that have used it
        $removeP = $dbc->prepare('UPDATE custdata AS c SET memCoupons=0 WHERE CardNo=?');
        while($usageW = $dbc->fetch_row($usageR)) {
            $dbc->execute($removeP, array($usageW['card_no']));
        }

        $coupon_blueline = $dbc->concat(
                        $dbc->convert('c.CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        "' Coup('",
                        $dbc->convert('c.memCoupons', 'CHAR'),
                        "')'",
                        ''
        );

        // set member blueLine based on number of coupons
        $dbc->query("UPDATE custdata AS c SET blueLine=$coupon_blueline WHERE Type='PC'");
    }
}

