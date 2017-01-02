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
        $last_year = '2014-06-01';
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
                               SET memType=5,
                                Discount=10
                               WHERE Type=\'PC\' 
                                AND memType IN (1,5)
                                AND CardNo IN (' . $in . ')');
        $dbc->execute($redo, $mems);
        $undo = $dbc->prepare('UPDATE custdata 
                               SET memType=1,
                                Discount=0
                               WHERE Type=\'PC\'
                                AND memType IN (5)
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

        $res = $dbc->query('SELECT DISTINCT c.CardNo FROM custdata AS c WHERE Type=\'PC\' AND c.CardNo NOT IN (
            SELECT cardNo FROM CustomerNotifications WHERE source=\'WFC.OAM\'
        )');
        $insP = $dbc->prepare('INSERT INTO CustomerNotifications (cardNo, source, type, message) VALUES (?, \'WFC.OAM\', \'blueline\', \'\')');
        while ($row = $dbc->fetchRow($res)) {
            $dbc->execute($insP, array($row['CardNo']));
        }

        $checkP = $dbc->prepare("SELECT
            card_no
            FROM is4c_trans.dlog_90_view
            WHERE trans_type='T'
                AND description='REBATE CHECK'
                AND tdate > '2016-10-31'
                AND card_no NOT IN (15590)
            GROUP BY card_no
            HAVING SUM(total) <> 0");
        $checkR = $dbc->execute($checkP);
        $upP = $dbc->prepare('UPDATE CustomerNotifications SET message=\'\' WHERE cardNo=? AND source=\'WFC.OAM\'');
        while ($row = $dbc->fetchRow($checkR)) {
            $dbc->execute($upP, array($row['card_no']));
        }

        // lookup OAM usage in the last month
        $usageP = $dbc->prepare("SELECT card_no 
                                FROM is4c_trans.dlog_90_view
                                WHERE upc IN ('0049999900131', 'PATREBDISC')
                                GROUP BY card_no
                                HAVING SUM(total) <> 0");
        $usageR = $dbc->execute($usageP);
        $upP = $dbc->prepare('UPDATE CustomerNotifications SET message=\'\' WHERE cardNo=? AND source=\'WFC.OAM\'');
        while ($row = $dbc->fetchRow($usageR)) {
            $dbc->execute($upP, array($row['card_no']));
        }

        // grant coupon to all members
        /*
        $dbc->query("UPDATE custdata AS c SET memCoupons=1 WHERE Type='PC'");

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
        */
    }
}

