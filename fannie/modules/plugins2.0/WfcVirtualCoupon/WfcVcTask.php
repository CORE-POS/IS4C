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

        $chkMsgP = $dbc->prepare("
            SELECT card_no
            FROM custReceiptMessage
            WHERE card_no=?
                AND msg_text like '%Access Discount%'"
        );
        $chkNotP = $dbc->prepare("
            SELECT cardNo
            FROM CustomerNotifications
            WHERE cardNo=?
                AND message like '%Access Discount%'
                AND type=?
                AND source='WfcVcTask'
        ");
        $upMsgP = $dbc->prepare("
            UPDATE custReceiptMessage
            SET msg_text=?
            WHERE card_no=?
                AND msg_text LIKE '%Access Discount%'");
        $upNotP = $dbc->prepare("
            UPDATE CustomerNotifications
            SET message=?
            WHERE cardNo=?
                AND message LIKE '%Access Discount%'
                AND type=?
                AND source='WfcVcTask'
        ");
        $insMsgP = $dbc->prepare("
            INSERT INTO custReceiptMessage
                (card_no, msg_text)
                VALUES (?, ?)");
        $insNotP = $dbc->prepare("
            INSERT INTO CustomerNotifications
                (cardNo, source, type, message)
            VALUES
                (?, 'WfcVcTask', ?, ?)");

        $last_year = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y')-1));
        $dlog_ly = DTransactionsModel::selectDlog($last_year, date('Y-m-d'));
        $accessQ = 'SELECT card_no, MAX(tdate) AS tdate
                    FROM ' . $dlog_ly . '
                    WHERE trans_type=\'I\'
                        AND upc=\'ACCESS\'
                        AND tdate >= ?
                        AND card_no NOT IN (9, 11)
                    GROUP BY card_no
                    HAVING SUM(quantity) > 0';
        $accessP = $dbc->prepare($accessQ);
        $accessR = $dbc->execute($accessP, array($last_year));
        $mems = array();
        $in = '';
        $notification = new CustomerNotificationsModel($dbc);
        while ($accessW = $dbc->fetch_row($accessR)) {
            /**
              Setup receipt notifications. This uses both old-style custReceiptMessage
              and new-style CustomerNotifications. This notification is always
              present.
            */
            $mems[] = $accessW['card_no'];
            $in .= '?,';
            $expires = new DateTime($accessW['tdate']);
            $expires->add(new DateInterval('P1Y'));
            $text = 'Access Discount valid until ' . $expires->format('Y-m-d');
            $text = "\n" . str_repeat('-', 40) . "\n" . $text . "\n" . str_repeat('-', 40) . "\n";
            $msg = $dbc->getValue($chkMsgP, $accessW['card_no']);
            if ($msg) {
                $dbc->execute($upMsgP, array($text, $accessW['card_no']));
            } else {
                $dbc->execute($insMsgP, array($accessW['card_no'], $text));
            }
            $msg = $dbc->getValue($chkNotP, array($accessW['card_no'], 'receipt'));
            if ($msg) {
                $dbc->execute($upNotP, array($text, $accessW['card_no'], 'receipt'));
            } else {
                $res = $dbc->execute($insNotP, array($accessW['card_no'], 'receipt', $text));
            }

            /**
              Set a blueline notification is things are expiring soon
            */
            $now = new DateTime(date('Y-m-d'));
            $expires->sub(new DateInterval('P1M'));
            $notification->reset();
            $notification->cardNo($accessW['card_no']);
            $notification->source('WfcVcTaskABL');
            $notification->type('blueline');
            $exists = $notification->find();
            $notice = $now >= $expires ? '&#x1f6aa;' : '';
            if (count($exists) > 0) {
                $obj = $exists[0];
                $obj->message($notice);
                $obj->save();
            } else {
                $notification->message($notice);
                $notification->save();
            }
        }
        $in = substr($in, 0, strlen($in)-1);

        if (count($mems) == 0) {
            $mems = array(-1);
            $in = '?';
        }

        $delMsgP = $dbc->prepare("
            DELETE FROM custReceiptMessage
            WHERE msg_text LIKE '%Access Discount%'
                AND card_no NOT IN ({$in})");
        $dbc->execute($delMsgP, $mems);

        $upP = $dbc->prepare("
            UPDATE CustomerNotifications
            SET message=''
            WHERE source='WfcVcTaskABL'
                AND cardNo NOT IN ({$in})");
        $dbc->execute($upP, $mems);
        $upP = $dbc->prepare("
            UPDATE CustomerNotifications
            SET message=''
            WHERE source='WfcVcTask'
                AND message LIKE '%Access Discount%'
                AND type='receipt'
                AND cardNo NOT IN ({$in})");
        $dbc->execute($upP, $mems);

        $redo = $dbc->prepare('UPDATE custdata 
                               SET memType=5,
                                Discount=10,
                                SSI=1
                               WHERE Type=\'PC\' 
                                AND (memType IN (1,5) OR memType IS NULL)
                                AND CardNo IN (' . $in . ')');
        $dbc->execute($redo, $mems);
        $undo = $dbc->prepare('UPDATE custdata 
                               SET memType=1,
                                Discount=0,
                                SSI=0
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

        $coupons = array(
            '0049999900142' => array('2017-01-01', '2017-01-15'),
            '0049999900143' => array('2017-01-16', '2017-01-31'),
            '0049999900144' => array('2017-02-01', '2017-02-15'),
            '0049999900145' => array('2017-02-16', '2017-02-28'),
            '0049999900146' => array('2017-03-01', '2017-03-15'),
            '0049999900147' => array('2017-03-16', '2017-03-31'),
        );
        $today = new DateTime(date('Y-m-d'));
        $currentUPC = false;
        foreach ($coupons as $upc => $dates) {
            $start = new DateTime($dates[0]);
            $end = new DateTime($dates[1]);
            if ($today >= $start && $today <= $end) {
                $currentUPC = $upc;
                break;
            }
        }

        if ($currentUPC) {
            $dbc->query("UPDATE CustomerNotifications SET message='OAM' WHERE source='WFC.OAM'");
            // lookup OAM usage in the last month
            $usageP = $dbc->prepare("SELECT card_no 
                                    FROM is4c_trans.dlog_90_view
                                    WHERE upc = ?
                                    GROUP BY card_no
                                    HAVING SUM(total) <> 0");
            $usageR = $dbc->execute($usageP, array($currentUPC));
            $upP = $dbc->prepare('UPDATE CustomerNotifications SET message=\'\' WHERE cardNo=? AND source=\'WFC.OAM\'');
            while ($row = $dbc->fetchRow($usageR)) {
                $dbc->execute($upP, array($row['card_no']));
            }
        }

        if ($today >= new DateTime('2017-04-01')) {
            $dbc->query("UPDATE CustomerNotifications SET message='' WHERE source='WFC.OAM'");
            $assignP = $dbc->prepare("UPDATE CustomerNotifications
                SET message='&#x1F49A;' WHERE source='WFC.OAM' AND cardNo=?");
            $usageP = $dbc->prepare("SELECT card_no 
                                    FROM is4c_trans.dlog_90_view
                                    WHERE upc = '0049999900176'
                                        AND card_no=?
                                    GROUP BY card_no
                                    HAVING SUM(total) <> 0");

            $earnR = $dbc->query('SELECT numflag
                FROM is4c_trans.dlog_90_view
                WHERE upc=\'0049999900173\'
                GROUP BY numflag
                HAVING SUM(total) <> 0');
            while ($row = $dbc->fetchRow($earnR)) {
                $used = $dbc->getValue($usageP, array($row['numflag']));
                if (!$used) {
                    $dbc->execute($assignP, array($row['numflag']));
                }
            }
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

