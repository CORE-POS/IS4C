<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class SmoothedModel
*/
class SmoothedModel extends CoreWarehouseModel
{
    protected $name = "Smoothed";
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'movement' => array('type'=>'DOUBLE'),
    'mark' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function reload($trans_db,$start_month,$start_year,$end_month=False,$end_year=False)
    {
        if (!$end_month) {
            $end_month = $start_month;
        }
        if (!$end_year) {
            $end_year = $start_year;
        }
        $startTS = mktime(0, 0, 0, $start_month, 1, $start_year);
        $endTS = mktime(0, 0, 0, $end_month, 1, $end_year);
        while ($startTS <= $endTS) {
            $this->refresh_data($trans_db, date('n', $startTS), date('Y', $startTS));
            $startTS = mktime(0, 0, 0, date('n', $startTS)+1, 1, date('Y', $startTS));
        }
    }

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        if ($day) {
            $str = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $this->smoothDay($str);
        } else {
            $ts = mktime(0, 0, 0, $month, 1, $year);
            while (date('n', $ts) == $month && date('Y', $ts) == $year) {
                $str = date('Y-m-d', $ts);
                $this->smoothDay($str);
                $ts = mktime(0, 0, 0, date('n',$ts), date('j', $ts)+1, date('Y', $ts));
            }
        }
    }

    private function smoothDay($date)
    {
        /**
         * Skip future dates to avoid piling on zero-sales days
         */
        if (strtotime($date) >= strtotime(date('Y-m-d'))) {
            return;
        }
        echo "Reload date: $date\n";
        $dateID = date('Ymd', strtotime($date));
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);
        $dbc->query("UPDATE Smoothed SET mark=1");

        $dlog = DTransactionsModel::selectDlog($date);
        $findP = $dbc->prepare("SELECT movement FROM Smoothed WHERE upc=? AND storeID=?");
        $upP = $dbc->prepare("UPDATE Smoothed SET movement=?, mark=0 WHERE upc=? AND storeID=?");
        $insP = $dbc->prepare("INSERT INTO Smoothed (upc, storeID, movement, mark) VALUES (?, ?, ?, 0)");

        /**
         * Pass one:
         * Gets UPCs with sales and updates them
         */
        echo "Reloading UPCs\n";
        $upcP = $dbc->prepare("SELECT upc, store_id, " . DTrans::sumQuantity() . " AS qty
            FROM {$dlog}
            WHERE tdate BETWEEN ? AND ?
                AND trans_type='I'
                AND trans_status <> 'R'
                AND charflag <> 'SO'
            GROUP BY upc, store_id");
        $upcR = $dbc->execute($upcP, array($date, $date . ' 23:59:59'));
        echo $dbc->numRows($upcR) . "\n";
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($upcR)) {
            $current = $dbc->getValue($findP, array($row['upc'], $row['store_id']));
            if ($current !== false) {
                $next = $current + (($row['qty'] - $current) * 0.90);
                $dbc->execute($upP, array($next, $row['upc'], $row['store_id']));
            } else {
                $next = $row['qty'];
                $dbc->execute($insP, array($row['upc'], $row['store_id'], $next));
            }
        }
        $dbc->commitTransaction();

        /**
         * Pass two:
         * Gets like codes with sales and updates them
         */
        echo "Reloading LCs\n";
        $lcR = $dbc->query("SELECT likeCode FROM " . FannieDB::fqn('likeCodes', 'op'));
        $itemP = $dbc->prepare("SELECT upc FROM " . FannieDB::fqn('upcLike', 'op') . " WHERE likeCode=?");
        $dbc->startTransaction();
        while ($lcW = $dbc->fetchRow($lcR)) {
            $upcs = $dbc->getAllValues($itemP, array($lcW['likeCode']));
            list($inStr, $args) = $dbc->safeInClause($upcs, array($date, $date . ' 23:59:59'));
            $upcP = $dbc->prepare("SELECT store_id, " . DTrans::sumQuantity() . " AS qty
                FROM {$dlog}
                WHERE tdate BETWEEN ? AND ?
                    AND upc IN ({$inStr})
                    AND trans_type='I'
                    AND trans_status <> 'R'
                    AND charflag <> 'SO'
                GROUP BY store_id");
            $upcR = $dbc->execute($upcP, $args);
            $lcUPC = 'LC' . $lcW['likeCode'];
            while ($row = $dbc->fetchRow($upcR)) {
                $current = $dbc->getValue($findP, array($lcUPC, $row['store_id']));
                if ($current !== false) {
                    $next = $current + (($row['qty'] - $current) * 0.90);
                    $dbc->execute($upP, array($next, $lcUPC, $row['store_id']));
                } else {
                    $next = $row['qty'];
                    $dbc->execute($insP, array($lcUPC, $row['store_id'], $next));
                }
            }
        }
        $dbc->commitTransaction();

        /**
         * Pass three:
         * Anything still marked has zero sales on the day
         */
        echo "Handling zeroes\n";
        $res = $dbc->query("SELECT upc, storeID FROM Smoothed WHERE mark=1");
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $current = $dbc->getValue($findP, array($row['upc'], $row['storeID']));
            $next = $current + ((0 - $current) * 0.90);
            $dbc->execute($upP, array($next, $row['upc'], $row['storeID']));
        }
        $dbc->commitTransaction();

        /**
         * Should be redundant but don't want mistakes
         * bleeding forward
         */
        $dbc->query("UPDATE Smoothed SET mark=0");
    }
}

