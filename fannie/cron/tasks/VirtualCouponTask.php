<?php
/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class VirtualCouponTask extends FannieTask
{

    public $name = 'Virtual Coupons Task';

    public $description = 'Removes virtual coupons assigned to members
based on expiration and/or use.';

    public $default_schedule = array(
        'min' => 36,
        'hour' => 0,
        'day' => '1',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        // delete expired entries
        $expireR = $dbc->query("
            DELETE FROM HouseVirtualCoupons
            WHERE end_date < " . $dbc->curdate()
        );

        /**
          Pull all current coupons and
          query transaction history to see if the particular
          member has used that coupon. If so, delete the
          entry.
        */
        $delP = $dbc->prepare("
            DELETE FROM HouseVirtualCoupons
            WHERE card_no=?
                AND coupID=?
        ");
        $currentP = $dbc->prepare("
            SELECT card_no, coupID, start_date, end_date
            FROM HouseVirtualCoupons
            WHERE start_date >= ?
        ");
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $currentR = $dbc->execute($currentQ, array($yesterday));
        while ($row = $dbc->fetchRow($currentR)) {
            $dlog = DTransactionsModel::selectDlog($row['start_date'], $row['end_date']);
            $upc = '00499999' . str_pad($row['coupID'], 5, '0', STR_PAD_LEFT);
            $chkP = $dbc->prepare("
                SELECT SUM(total) AS ttl
                FROM {$dlog}
                WHERE tdate BETWEEN ? AND ?
                    AND upc=?
                    AND card_no=?");
            $args = array(
                $row['start_date'] . ' 00:00:00',
                $row['end_date'] . ' 23:59:59',
                $upc,
                $row['card_no'],
            );
            $val = $dbc->getValue($chkP, $args);
            if ($val !== false && abs($val) > 0) {
                $dbc->execute($delP, array($row['card_no'], $row['coupID']));
            }
        }
    }
}

