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

        $last_year = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y')-1));
        $dlog_ly = DTransactionsModel::selectDlog($last_year, date('Y-m-d'));
        $accessQ = 'SELECT card_no
                    FROM ' . $dlog_ly . '
                    WHERE trans_type=\'I\'
                        AND upc=\'ACCESS\'
                        AND tdate >= ?
                    GROUP BY card_no
                    HAVING SUM(quantity) > 0';
        $accessP = $dbc->prepare($accessQ);
        $accessR = $dbc->execute($accessP, array($last_year));
        $mems = array();
        $in = '';
        while ($accessW = $dbc->fetch_row($accessR)) {
            $mems[] = $accessW['card_no'];
            $in .= '?,';
        }
        $in = substr($in, 0, strlen($in)-1);

        if (count($mems) == 0) {
            $mems = array(-1);
            $in = '?';
        }

        $redo = $dbc->prepare('UPDATE custdata 
                               SET memType=CASE WHEN memType=3 THEN 6 ELSE 5 END
                               WHERE Type=\'PC\' 
                                AND memType NOT IN (5,6)
                                AND CardNo IN (' . $in . ')');
        $dbc->execute($redo, $mems);
        $undo = $dbc->prepare('UPDATE custdata 
                               SET memType=CASE WHEN memType=6 THEN 3 ELSE 1 END
                               WHERE memType IN (5,6) 
                                AND CardNo NOT IN (' . $in . ')');
        $dbc->execute($undo, $mems);

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
        $dbc->query("UPDATE custdata AS c SET memCoupons=2 WHERE Type='PC' AND memType IN (5,6)");

        // lookup OB usage in the last month
        $usageP = $dbc->prepare("SELECT card_no 
                                FROM $dlog
                                WHERE upc='0049999900001'
                                    AND tdate BETWEEN ? AND ?
                                GROUP BY card_no
                                HAVING SUM(total) <> 0");
        $usageR = $dbc->execute($usageP, array($start . ' 00:00:00', $end . ' 23:59:59'));
        $no_ob = array();

        // remove coupon from members that have used it
        $removeP = $dbc->prepare('UPDATE custdata AS c SET memCoupons=memCoupons-1 WHERE CardNo=?');
        while($usageW = $dbc->fetch_row($usageR)) {
            $dbc->execute($removeP, array($usageW['card_no']));
            $no_ob[$usageW['card_no']] = true;
        }

        // lookup access usage in the last month
        $usageP = $dbc->prepare("SELECT card_no 
                                FROM $dlog
                                WHERE upc='0049999900002'
                                    AND tdate BETWEEN ? AND ?
                                GROUP BY card_no
                                HAVING SUM(total) <> 0");
        $usageR = $dbc->execute($usageP, array($start . ' 00:00:00', $end . ' 23:59:59'));
        $no_ac = array();

        // remove coupon from members that have used it
        $removeP = $dbc->prepare('UPDATE custdata AS c SET memCoupons=memCoupons-1 WHERE CardNo=? AND memType IN (5,6)');
        while($usageW = $dbc->fetch_row($usageR)) {
            $dbc->execute($removeP, array($usageW['card_no']));
            $no_ac[$usageW['card_no']] = true;
        }

        $coupon_blueline = $dbc->concat(
                        $dbc->convert('c.CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        "' Coup(OB)'",
                        ''
        );
        $dbc->query("UPDATE custdata AS c SET blueLine=$coupon_blueline WHERE Type='PC' AND memCoupons > 0");

        $coupon_blueline = $dbc->concat(
                        $dbc->convert('c.CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        "' Coup(0)'",
                        ''
        );
        $dbc->query("UPDATE custdata AS c SET blueLine=$coupon_blueline WHERE Type='PC' AND memCoupons = 0");

        // more detail needed for access members
        $both_blueline = $dbc->concat(
                        $dbc->convert('CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        "' Coup(OB AC)'",
                        ''
        );
        $ob_blueline = $dbc->concat(
                        $dbc->convert('CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        "' Coup(OB)'",
                        ''
        );
        $ac_blueline = $dbc->concat(
                        $dbc->convert('CardNo', 'CHAR'),
                        "' '",
                        'LastName',
                        "' Coup(AC)'",
                        ''
        );
        $accessR = $dbc->query("SELECT CardNo FROM custdata WHERE memType IN (5,6) AND personNum=1 AND memCoupons > 0");
        while($accessW = $dbc->fetch_row($accessR)) {
            if (isset($no_ob[$accessW['CardNo']]) && !isset($no_ac[$accessW['CardNo']])) {
                $dbc->query("UPDATE custdata SET blueLine=$ac_blueline WHERE CardNo=" . $accessW['CardNo']);
            } else if (!isset($no_ob[$accessW['CardNo']]) && isset($no_ac[$accessW['CardNo']])) {
                $dbc->query("UPDATE custdata SET blueLine=$ob_blueline WHERE CardNo=" . $accessW['CardNo']);
            } else {
                $dbc->query("UPDATE custdata SET blueLine=$both_blueline WHERE CardNo=" . $accessW['CardNo']);
            }
        }
    }
}

