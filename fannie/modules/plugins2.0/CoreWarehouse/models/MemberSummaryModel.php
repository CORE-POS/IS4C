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

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

/**
  @class MemberSummaryModel
*/
class MemberSummaryModel extends CoreWarehouseModel
{
    protected $name = "MemberSummary";
    protected $preferred_db = 'plugin:WarehouseDatabase';

    protected $columns = array(
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'firstVisit' => array('type'=>'DATETIME'),
    'lastVisit' => array('type'=>'DATETIME'),
    'totalSpending' => array('type'=>'MONEY'),
    'totalSpendingRank' => array('type'=>'INT'),
    'averageSpending' => array('type'=>'MONEY'),
    'averageSpendingRank' => array('type'=>'INT'),
    'totalItems' => array('type'=>'DOUBLE'),
    'averageItems' => array('type'=>'DOUBLE'),
    'totalVisits' => array('type'=>'INT'),
    'totalVisitsRank' => array('type'=>'INT'),
    'spotlightStart' => array('type'=>'DATETIME'),
    'spotlightEnd' => array('type'=>'DATETIME'),
    'spotlightTotalSpending' => array('type'=>'MONEY'),
    'spotlightAverageSpending' => array('type'=>'MONEY'),
    'spotlightTotalItems' => array('type'=>'DOUBLE'),
    'spotlightAverageItems' => array('type'=>'DOUBLE'),
    'spotlightTotalVisits' => array('type'=>'INT'),
    'yearStart' => array('type'=>'DATETIME'),
    'yearEnd' => array('type'=>'DATETIME'),
    'yearTotalSpending' => array('type'=>'MONEY'),
    'yearTotalSpendingRank' => array('type'=>'INT'),
    'yearAverageSpending' => array('type'=>'MONEY'),
    'yearAverageSpendingRank' => array('type'=>'INT'),
    'yearTotalItems' => array('type'=>'DOUBLE'),
    'yearAverageItems' => array('type'=>'DOUBLE'),
    'yearTotalVisits' => array('type'=>'INT'),
    'yearTotalVisitsRank' => array('type'=>'INT'),
    'oldlightTotalSpending' => array('type'=>'MONEY'),
    'oldlightAverageSpending' => array('type'=>'MONEY'),
    'oldlightTotalItems' => array('type'=>'DOUBLE'),
    'oldlightAverageItems' => array('type'=>'DOUBLE'),
    'oldlightTotalVisits' => array('type'=>'INT'),
    'longlightTotalSpending' => array('type'=>'MONEY'),
    'longlightAverageSpending' => array('type'=>'MONEY'),
    'longlightTotalItems' => array('type'=>'DOUBLE'),
    'longlightAverageItems' => array('type'=>'DOUBLE'),
    'longlightTotalVisits' => array('type'=>'INT'),
    'homeStoreID' => array('type'=>'INT'),
    'homeStorePercent' => array('type'=>'DOUBLE'),
    'storeCouponPercent' => array('type'=>'DOUBLE'),
    );

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);

        $today = time();
        $lastmonth = mktime(0, 0, 0, date('n',$today)-1, 1, date('Y',$today));
        $spotlight_months = array();
        for ($i=0; $i<2; $i++) {
            $spotlight_months[] = mktime(0, 0, 0, date('n',$lastmonth)-$i, 1, date('Y',$lastmonth));
        }
        $lastyear = mktime(0, 0, 0, date('n',$lastmonth)-11, 1, date('Y',$lastmonth));

        $basicQ = '
            SELECT card_no,
                MIN(date_id) AS firstVisit,
                MAX(date_id) AS lastVisit,
                SUM(retailTotal) AS totalSpending,
                AVG(retailTotal/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageSpending,
                SUM(retailQuantity) AS totalItems,
                AVG(retailQuantity/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageItems,
                SUM(transCount) AS totalVisits
            FROM sumMemSalesByDay
            WHERE date_id BETWEEN ? AND ?
            GROUP BY card_no';
        $basicP = $dbc->prepare($basicQ);

        $all_time_args = array(
            0,
            date('Ymt', $lastmonth),
        );

        $dbc->query('TRUNCATE TABLE MemberSummary');

        $insQ = '
            INSERT INTO MemberSummary
            (card_no, firstVisit, lastVisit, totalSpending,
            averageSpending, totalItems, averageItems,
            totalVisits) '
            . $basicQ;
        $insP = $dbc->prepare($insQ);

        $basicR = $dbc->execute($insP, $all_time_args);

        $spotlight_args = array(
            date('Ym01', $spotlight_months[count($spotlight_months)-1]),
            date('Ymt', $spotlight_months[0]),
        );
        $spotlight_start = date('Y-m-01', $spotlight_months[count($spotlight_months)-1]);
        $spotlight_end = date('Y-m-t', $spotlight_months[0]);
        $basicR = $dbc->execute($basicP, $spotlight_args);
        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET spotlightStart=?,
                spotlightEnd=?,
                spotlightTotalSpending=?,
                spotlightAverageSpending=?,
                spotlightTotalItems=?,
                spotlightAverageItems=?,
                spotlightTotalVisits=?
            WHERE card_no=?');
        $dbc->startTransaction();
        while ($spotlight = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $spotlight_start,
                $spotlight_end,
                $spotlight['totalSpending'],
                $spotlight['averageSpending'],
                $spotlight['totalItems'],
                $spotlight['averageItems'],
                $spotlight['totalVisits'],
                $spotlight['card_no'],
            ));
        }
        $dbc->commitTransaction();

        $this->lastYearStats($dbc);

        $oldlight = array(strtotime($spotlight_args[0]), strtotime($spotlight_args[1]));
        $oldlight_args = array(
            date('Ym01', mktime(0,0,0,date('n',$oldlight[0]),1,date('Y',$oldlight[0])-1)),
            date('Ymt', mktime(0,0,0,date('n',$oldlight[1]),1,date('Y',$oldlight[1])-1)),
        );
        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET oldlightTotalSpending=?,
                oldlightAverageSpending=?,
                oldlightTotalItems=?,
                oldlightAverageItems=?,
                oldlightTotalVisits=?
            WHERE card_no=?');
        $basicR = $dbc->execute($basicP, $oldlight_args);
        $dbc->startTransaction();
        while ($old = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $old['totalSpending'],
                $old['averageSpending'],
                $old['totalItems'],
                $old['averageItems'],
                $old['totalVisits'],
                $old['card_no'],
            ));
        }
        $dbc->commitTransaction();

        $longSQL = '';
        $long_args = array($spotlight_args[0]);
        foreach ($spotlight_months as $m) {
            $longSQL .= '?,';
            $long_args[] = date('m', $m);
        }
        $longSQL = substr($longSQL, 0, strlen($longSQL)-1);

        $basicQ = '
            SELECT card_no,
                MIN(date_id) AS firstVisit,
                MAX(date_id) AS lastVisit,
                SUM(retailTotal) AS totalSpending,
                AVG(retailTotal/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageSpending,
                SUM(retailQuantity) AS totalItems,
                AVG(retailQuantity/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageItems,
                SUM(transCount) AS totalVisits
            FROM sumMemSalesByDay
            WHERE date_id < ?
                AND SUBSTRING(CONVERT(date_id,CHAR),5,2) IN (' . $longSQL . ')
            GROUP BY card_no';
        $basicP = $dbc->prepare($basicQ);

        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET longlightTotalSpending=?,
                longlightAverageSpending=?,
                longlightTotalItems=?,
                longlightAverageItems=?,
                longlightTotalVisits=?
            WHERE card_no=?');
        $basicR = $dbc->execute($basicP, $long_args);
        $dbc->startTransaction();
        while ($long = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $long['totalSpending'],
                $long['averageSpending'],
                $long['totalItems'],
                $long['averageItems'],
                $long['totalVisits'],
                $long['card_no'],
            ));
        }
        $dbc->commitTransaction();

        // do ranks

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY totalSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET totalSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY averageSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET averageSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY totalVisits DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET totalVisitsRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY yearTotalSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearTotalSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY yearAverageSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearAverageSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY yearTotalVisits DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearTotalVisitsRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }
        $dbc->commitTransaction();

        $this->storePreference($dbc);
    }

    /**
     * Add new accounts from the current month that
     * aren't in the existing stats table
     */
    private function initMissing($dbc)
    {
        $dateArgs = array(
            date('Ym01'),
            date('Ymd'),
        );

        $initP = $dbc->prepare('
            INSERT INTO MemberSummary
            (card_no, firstVisit, lastVisit, totalSpending,
            averageSpending, totalItems, averageItems,
            totalVisits)
            SELECT card_no,
                MIN(date_id) AS firstVisit,
                MAX(date_id) AS lastVisit,
                SUM(retailTotal) AS totalSpending,
                AVG(retailTotal/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageSpending,
                SUM(retailQuantity) AS totalItems,
                AVG(retailQuantity/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageItems,
                SUM(transCount) AS totalVisits
            FROM sumMemSalesByDay
            WHERE date_id BETWEEN ? AND ?
                AND card_no NOT IN (SELECT card_no FROM MemberSummary)
            GROUP BY card_no');
        $dbc->execute($initP, $dateArgs);
    }

    /**
     * Update the last-year fields in the
     * stats table
     */
    private function lastYearStats($dbc)
    {
        $year_ago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
        $yesterday = strtotime('yesterday');
        $year_args = array(
            date('Ymd', $year_ago),
            date('Ymd', $yesterday),
        );
        $basicQ = '
            SELECT card_no,
                MIN(date_id) AS firstVisit,
                MAX(date_id) AS lastVisit,
                SUM(retailTotal) AS totalSpending,
                AVG(retailTotal/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageSpending,
                SUM(retailQuantity) AS totalItems,
                AVG(retailQuantity/(CASE WHEN transCount=0 THEN 1 ELSE transCount END)) AS averageItems,
                SUM(transCount) AS totalVisits
            FROM sumMemSalesByDay
            WHERE date_id BETWEEN ? AND ?
            GROUP BY card_no';
        $basicP = $dbc->prepare($basicQ);
        $basicR = $dbc->execute($basicP, $year_args);
        $year_start = $year_args[0];
        $year_end = $year_args[1];
        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearStart=?,
                yearEnd=?,
                yearTotalSpending=?,
                yearAverageSpending=?,
                yearTotalItems=?,
                yearAverageItems=?,
                yearTotalVisits=?
            WHERE card_no=?');
        $dbc->startTransaction();
        while ($year = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $year_start,
                $year_end,
                $year['totalSpending'],
                $year['averageSpending'],
                $year['totalItems'],
                $year['averageItems'],
                $year['totalVisits'],
                $year['card_no'],
            ));
        }
        $dbc->commitTransaction();
    }

    /**
     * Update just the store preference fields
     */
    private function storePreference($dbc)
    {
        $year_ago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
        $yesterday = strtotime('yesterday');
        $year_args = array(
            date('Ymd', $year_ago),
            date('Ymd', $yesterday),
        );
        $storeP = $dbc->prepare("SELECT
            SUM(transCount) AS ttl,
            SUM(CASE WHEN store_id=1 THEN transCount ELSE 0 END) AS hs,
            SUM(CASE WHEN store_id=2 THEN transCount ELSE 0 END) AS den,
            card_no
            FROM sumMemSalesByDay
            WHERE date_id BETWEEN ? AND ?
            GROUP BY card_no");
        $res = $dbc->execute($storeP, $year_args);
        $homeP = $dbc->prepare("UPDATE MemberSummary
            SET homeStoreID=?, homeStorePercent=?
            WHERE card_no=?");
        $dbc->startTransaction();
        while ($counts = $dbc->fetchRow($res)) {
            if ($counts['hs'] > $counts['den']) {
                $homeID = 1;
                $homePercent = $counts['hs'] / $counts['ttl'];
            } else {
                $homeID = 2;
                $homePercent = $counts['den'] / $counts['ttl'];
            }
            $dbc->execute($homeP, array($homeID, $homePercent, $counts['card_no']));
        }
        $dbc->commitTransaction();
    }

    private function couponUsage($dbc)
    {
        $year_ago = mktime(0, 0, 0, date('n'), date('j'), date('Y')-1);
        $yesterday = strtotime('yesterday');
        $year_args = array(
            date('Ymd', $year_ago),
            date('Ymd', $yesterday),
        );
        $coupP = $dbc->prepare("
            SELECT card_no,
                SUM(CASE WHEN usedCoupon=1 THEN 1 ELSE 0 END) as coupons,
                COUNT(*) AS transactions
            FROM transactionSummary
            WHERE date_id BETWEEN ? AND ?
            GROUP BY card_no");
        $res = $dbc->execute($coupP, $year_args);
        $setP = $dbc->prepare("UPDATE MemberSummary
            SET storeCouponPercent=?
            WHERE card_no=?");
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $dbc->execute($setP, array($row['coupons'] / $row['transactions'], $row['card_no']));
        }
        $dbc->commitTransaction();
    }

    /**
     * Refresh just a subset of stats instead of
     * doing the full reload. This makes some data fresher
     * but takes a lot less time. Updates:
     *  - last year spending, items, etc
     *  - store preferences
     */
    public function refreshStats()
    {
        echo "Add missing accounts\n";
        $this->initMissing($this->connection);
        echo "Re-calculate stats\n";
        $this->lastYearStats($this->connection);
        echo "Update store preferences\n";
        $this->storePreference($this->connection);
        echo "Update coupon usage\n";
        $this->couponUsage($this->connection);
    }
}

if (php_sapi_name() == 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $config = FannieConfig::factory();
    $settings = $config->get('PLUGIN_SETTINGS');
    $dbc = FannieDB::get($settings['WarehouseDatabase']);
    $obj = new MemberSummaryModel($dbc);
    $obj->refreshStats();
}

