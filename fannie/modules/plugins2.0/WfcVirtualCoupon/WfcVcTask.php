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
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
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

        $last_year = date('Y-m-d', mktime(0, 0, 0, date('n') - 2, date('j'), date('Y')-1));
        $dlog_ly = DTransactionsModel::selectDlog($last_year, date('Y-m-d'));
        $accessQ = 'SELECT card_no, MAX(tdate) AS tdate
                    FROM ' . $dlog_ly . '
                    WHERE upc=\'ACCESS\'
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

        $todoP = $dbc->prepare("SELECT CardNo FROM custdata WHERE Type='PC' AND memType=5 AND CardNo NOT IN ({$in}) GROUP BY CardNo");
        $todo = $dbc->getAllValues($todoP, $mems);
        $undo = $dbc->prepare('UPDATE custdata 
                               SET memType=1,
                                Discount=0,
                                SSI=0
                               WHERE Type=\'PC\'
                                AND memType IN (5)
                                AND CardNo NOT IN (' . $in . ')');
        $dbc->execute($undo, $mems);

        $callbacks = FannieConfig::config('MEMBER_CALLBACKS');
        foreach ($callbacks as $cb) {
            $obj = new $cb();
            $obj->run($todo);
            $obj->run($mems);
        }

        $record = DTrans::defaults();
        $record['emp_no'] = 1001;
        $record['register_no'] = 30;
        $record['trans_no'] = DTrans::getTransNo($dbc, 1001, 30);
        $record['trans_id'] = 1;
        $record['trans_type'] = 'I';
        $record['upc'] = 'ACCESSEXP';
        $record['description'] = 'ACCESS EXPIRED';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        foreach ($todo as $c) {
            $record['card_no'] = $c;
            $pInfo = DTrans::parameterize($record, 'datetime', $dbc->now());
            $insP = $dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') 
                . " ({$pInfo['columnString']}) VALUES ({$pInfo['valueString']})");
            $dbc->execute($insP, $pInfo['arguments']);
            $record['trans_id']++;
        }

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
        $dbc->startTransaction();
        $insP = $dbc->prepare('INSERT INTO CustomerNotifications (cardNo, source, type, message) VALUES (?, \'WFC.OAM\', \'blueline\', \'\')');
        while ($row = $dbc->fetchRow($res)) {
            $dbc->execute($insP, array($row['CardNo']));
        }
        $dbc->commitTransaction();

        $curP = $dbc->prepare('SELECT * FROM WfcOamSchedule WHERE ? BETWEEN startDate AND endDate');
        $curRow = $dbc->getRow($curP, array(date('Y-m-d')));

        if ($curRow) {
            $setP = $dbc->prepare("UPDATE CustomerNotifications SET message=? WHERE source='WFC.OAM'");
            $dbc->execute($setP, array($curRow['msg']));
            // lookup OAM usage in the last month
            $usageP = $dbc->prepare("SELECT card_no 
                                    FROM trans_archive.dlogBig
                                    WHERE upc = ?
                                        AND tdate BETWEEN ? AND ?
                                    GROUP BY card_no, upc
                                    HAVING SUM(quantity) <> 0");
            $usageArgs = array($curRow['upc'], $curRow['startDate'], str_replace('00:00:00', '23:59:59', $curRow['endDate']));
            $usageR = $dbc->execute($usageP, $usageArgs);
            $dbc->startTransaction();
            $upP = $dbc->prepare('UPDATE CustomerNotifications SET message=\'\' WHERE cardNo=? AND source=\'WFC.OAM\'');
            while ($row = $dbc->fetchRow($usageR)) {
                $dbc->execute($upP, array($row['card_no']));
            }
            $dbc->commitTransaction();

            // remove coupon from non-owner accounts
            $dbc->query("UPDATE CustomerNotifications AS n
                INNER JOIN custdata AS c ON n.cardNo=c.CardNo
                SET n.message=''
                WHERE n.source='WFC.OAM'
                    AND c.Type <> 'PC'");
        } else {
            $dbc->query("UPDATE CustomerNotifications SET message='' WHERE source='WFC.OAM'");
        }

        /** friend coupon
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
         */

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

