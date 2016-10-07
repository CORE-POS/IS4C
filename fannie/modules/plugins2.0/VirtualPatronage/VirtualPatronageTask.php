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
class VirtualPatronageTask extends FannieTask 
{
    public $name = 'Manage Virtual Patronage';

    public $description = 'Examine transaction data and mark virtual patronage
    vouchers as redeemed and/or expired.';

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        $date = date('Y-m-d', strtotime('yesterday'));
        $dlog = DTransactionsModel::get($date);

        $markP = $dbc->prepare("
            UPDATE VirtualVouchers
            SET redeemed=1,
                redeemDate=?,
                redeemTrans=?,
                redeemedAs=?
            WHERE cardNo=?
                AND redeemed=0
                AND expired=0");

        /**
           Get usages that were not voided
           and record their details
        */
        $res = $dbc->query("
            SELECT card_no,
                MAX(tdate) AS tdate,
                trans_num,
                MAX(charflag) AS charflag
            FROM {$dlog}
            WHERE trans_type='T'
                AND trans_subtype='VV'
            GROUP BY card_no,
                trans_num
            HAVING SUM(CASE WHEN trans_status='V' THEN -1 ELSE 1 END) <> 0
        ");
        while ($row = $dbc->fetchRow($res)) {
            $type = 'VOUCHER';
            switch ($row['charflag']) {
                case 'CK':
                    $type = 'CHECK';
                    break;
                case 'DN':
                    $type = 'DONATION';
                    break;
            }

            $dbc->execute($markP, array(
                $row['tdate'],
                $row['trans_num'],
                $type,
                $row['card_no'],
            ));
        }

        // manage expirations
        $dbc->query("
            UPDATE VirtualVouchers
            SET expired=1
            WHERE expireDate IS NOT NULL
                AND redeemed=0
                AND expireDate < " . $dbc->curdate()
        );

        /**
          Set up notifications for remaining vouchers
        */
        $dbc->selectDB($this->config->get('OP_DB'));
        $dbc->query("DELETE FROM CustomerNotifications WHERE source='VirtualPatronage'");
        $cnObj = new CustomerNotificationsModel($dbc);
        $res = $dbc->query("
            SELECT card_no,
                amount
            FROM VirtualVouchers
            WHERE redeemed=0
                AND expired=0
        ");
        while ($row = $dbc->fetchRow($res)) {
            $cnObj->reset();
            $cnObj->cardNo($row['card_no']);
            $cnObj->source('VirtualPatronage');
            $cnObj->message(sprintf('Patronage voucher available for $%.2f', $row['amount']));
            $cnObj->type('memlist');
            $cnObj->save();
        }
    }
}

