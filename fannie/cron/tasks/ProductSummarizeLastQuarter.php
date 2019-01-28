<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class ProductSummarizeLastQuarter extends FannieTask
{
    public $name = 'Summarize Product Sales for the last quarter';

    public $description = 'Recalculates totals, quantities, and percentage of sales
last thirteen weeks';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private function initWeeks($dbc)
    {
        $today = strtotime('today');
        $this_monday = $today;
        while(date('N', $this_monday) != 1) {
            $this_monday = mktime(0, 0, 0, date('n', $this_monday), date('j', $this_monday) - 1, date('Y', $this_monday));
        }
        $last_monday = mktime(0, 0, 0, date('n', $this_monday), date('j', $this_monday) - 7, date('Y', $this_monday));

        $this->cronMsg('Determining applicable weeks', FannieLogger::INFO);
        $dbc->query('TRUNCATE TABLE weeksLastQuarter');
        $ins = $dbc->prepare('INSERT INTO weeksLastQuarter (weekLastQuarterID, weekStart, weekEnd) VALUES (?, ?, ?)');

        $weeks = array();

        // week zero
        $this_sunday = mktime(0, 0, 0, date('n', $this_monday), date('j', $this_monday) + 6, date('Y', $this_monday));
        $args = array(0, date('Y-m-d 00:00:00', $this_monday), date('Y-m-d 23:59:59', $this_sunday));
        $weeks[] = array($this_monday, $this_sunday);
        $dbc->execute($ins, $args);
        // database may be uncooperative about a zero value in the increment column
        // so make sure its assigned correctly
        $dbc->query('UPDATE weeksLastQuarter SET weekLastQuarterID=0');

        // other weeks
        for ($i=0; $i<13; $i++) {
            $monday = mktime(0, 0, 0, date('n', $last_monday), date('j', $last_monday) - ($i*7), date('Y', $last_monday));
            $sunday = mktime(0, 0, 0, date('n', $monday), date('j', $monday) + 6, date('Y', $monday));
            $args = array($i+1, date('Y-m-d 00:00:00', $monday), date('Y-m-d 23:59:59', $sunday));
            $dbc->execute($ins, $args);
            $weeks[] = array($monday, $sunday);
        }

        return $weeks;
    }

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB;

        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        $weeks = $this->initWeeks($dbc);

        $addP = $dbc->prepare('INSERT INTO productWeeklyLastQuarter 
                            (upc, weekLastQuarterID, quantity, total,
                            percentageStoreSales, percentageSuperDeptSales,
                            percentageDeptSales, storeID, saleQuantity, saleTotal)
                            VALUES
                            (?,   ?,                 ?,        ?,
                            ?,                    ?,
                            ?,                   ?,       ?,            ?)');
        $products = $FANNIE_OP_DB . $dbc->sep() . 'products';
        $supers = $FANNIE_OP_DB . $dbc->sep() . 'MasterSuperDepts';
        $dbc->query('TRUNCATE TABLE productWeeklyLastQuarter');
        $dbc->startTransaction();
        foreach($weeks as $weekID => $limits) {
            $store_sales = array();
            $super_sales = array();
            $dept_sales = array();
            $upcs = array();
            $this->cronMsg('Processing week #'.$weekID, FannieLogger::INFO);
            $dlog = DTransactionsModel::selectDlog(date('Y-m-d', $limits[0]), date('Y-m-d', $limits[1]));
            $dataP = $dbc->prepare("SELECT d.upc, SUM(total) as ttl, "
                                . DTrans::sumQuantity('d') . " as qty,
                                SUM(CASE WHEN d.discounttype > 0 THEN total ELSE 0 END) AS saleTTL,
                                SUM(CASE WHEN d.discounttype = 0 THEN 0
                                    WHEN d.trans_status='M' THEN 0
                                    WHEN d.trans_subtype='OG' THEN 0
                                    WHEN d.unitPrice=0.01 THEN 1
                                    ELSE d.quantity END) AS saleQty,
                                d.store_id,
                                MAX(p.department) as dept, MAX(s.superID) as superDept
                                FROM $dlog AS d 
                                    " . DTrans::joinProducts('d', 'p', 'INNER') . "
                                    LEFT JOIN $supers AS s ON p.department = s.dept_ID
                                WHERE tdate BETWEEN ? AND ?
                                GROUP BY d.upc, d.store_id");
            $args = array(date('Y-m-d 00:00:00', $limits[0]), date('Y-m-d 23:59:59', $limits[1]));
            $result = $dbc->execute($dataP, $args);
            // accumulate all info for the week
            // in order to calculate percentages
            while($row = $dbc->fetch_row($result)) {
                // normally miskeys that were voided
                // and not useful information
                if ($row['ttl'] == 0) continue;

                $upcs[$row['upc'].':'.$row['store_id']] = array(
                    'ttl' => $row['ttl'],
                    'qty' => $row['qty'],
                    'dept' => $row['dept'],
                    'super' => $row['superDept'],
                    'saleTTL' => $row['saleTTL'],
                    'saleQty' => $row['saleQty'],
                );

                if (!isset($store_sales[$row['store_id']])) {
                    $store_sales[$row['store_id']] = 0;
                }
                $store_sales[$row['store_id']] += $row['ttl'];

                if (!isset($super_sales[$row['superDept'] . ':' . $row['store_id']])) {
                    $super_sales[$row['superDept'] . ':' . $row['store_id']] = 0.0;
                }
                $super_sales[$row['superDept'] . ':' . $row['store_id']] += $row['ttl'];

                if (!isset($dept_sales[$row['dept'] . ':' . $row['store_id']])) {
                    $dept_sales[$row['dept'] . ':' . $row['store_id']] = 0.0;
                }
                $dept_sales[$row['dept'] . ':' . $row['store_id']] += $row['ttl'];

                if ($this->test_mode) {
                    break;
                }
            }

            // add entries for this week's items
            foreach($upcs as $key => $info) {
                list($upc, $storeID) = explode(':', $key, 2);
                $d_ttl = $dept_sales[$info['dept'] . ':' . $storeID];
                $s_ttl = $super_sales[$info['super'] . ':' . $storeID];

                $args = array(
                    $upc,
                    $weekID,
                    $info['qty'],
                    $info['ttl'],
                    $store_sales[$storeID] == 0 ? 0.0 : $info['ttl'] / $store_sales[$storeID],
                    $s_ttl == 0 ? 0.0 : $info['ttl'] / $s_ttl,
                    $d_ttl == 0 ? 0.0 : $info['ttl'] / $d_ttl,
                    $storeID,
                    $info['saleQty'],
                    $info['saleTTL'],
                );
                $dbc->execute($addP, $args);
            }

            if ($this->test_mode) {
                break;
            }
        } // end loop on weeks
        $dbc->commitTransaction();

        $this->weightedAverages($dbc);
    }

    private function weightedAverages($dbc)
    {
        // now do weighted averages
        $this->cronMsg('Calculating weighted averages', FannieLogger::INFO);
        $dbc->query('TRUNCATE TABLE productSummaryLastQuarter');
        $res = $dbc->query("SELECT q.upc, storeID, p.department, m.superID
            FROM productWeeklyLastQuarter AS q
                INNER JOIN " . FannieDB::fqn('products', 'op') . " AS p ON q.upc=p.upc AND q.storeID=p.store_id
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON p.department=m.dept_ID
            GROUP BY q.upc, storeID, department, m.superID
            ORDER BY q.storeID");
        $insP = $dbc->prepare("INSERT INTO productSummaryLastQuarter
            (upc, storeID, qtyThisWeek, totalThisWeek, qtyLastQuarter,
            totalLastQuarter, percentageStoreSales, percentageSuperDeptSales,
            percentageDeptSales)
            VALUES (?, ?, 0, 0, ?, ?, ?, ?, ?)");
        $totalsP = $dbc->prepare("SELECT weekLastQuarterID, storeID,
            SUM(total) AS store,
            SUM(CASE WHEN m.superID=? THEN total ELSE 0 END) AS super,
            SUM(CASE WHEN p.department=? THEN total ELSE 0 END) AS dept
            FROM productWeeklyLastQuarter AS q
                INNER JOIN " . FannieDB::fqn('products', 'op') . " AS p ON q.upc=p.upc AND q.storeID=p.store_id
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON p.department=m.dept_ID
            WHERE storeID=?
            GROUP BY weekLastQuarterID, storeID");
        $totalCache = array(
            'store' => array(),
            'super' => array(),
            'dept' => array(),
        );
        $weekP = $dbc->prepare("SELECT * FROM productWeeklyLastQuarter WHERE upc=? AND storeID=? AND weekLastQuarterID=?");
        $rows = $dbc->numRows($res);
        $count = 1;
        $dbc->startTransaction();
        echo "Found $rows\n";
        while ($row = $dbc->fetchRow($res)) {
            echo "$count/$rows\r";
            $upc = $row['upc'];
            $storeID = $row['storeID'];
            $sales = array(
                'store' => 0,
                'super' => 0,
                'dept' => 0,
            );
            $realSales = 0;
            $scaledSales = 0;
            $realQty = 0;
            $factor = 1.0;
            for ($i=1; $i<=13; $i++) {
                $week = $dbc->getRow($weekP, array($upc, $storeID, $i));
                if ($week) {
                    $realSales += $week['total'];
                    $realQty += $week['quantity'];
                    $scaledSales += ($factor * $week['total']);
                }
                if (!isset($totalCache['store'][$storeID])) {
                    $totalCache['store'][$storeID] = array();
                }
                if (!isset($totalCache['super'][$row['superID']])) {
                    $totalCache['super'][$row['superID']] = array();
                }
                if (!isset($totalCache['dept'][$row['department']])) {
                    $totalCache['dept'][$row['department']] = array();
                }
                if (!isset($totalCache['store'][$storeID][$i])) {
                    $totalCache['super'] = array();
                    $totalCache['dept'] = array();
                    $totalR = $dbc->execute($totalsP, array($row['superID'], $row['department'], $storeID));
                    while ($totals = $dbc->fetchRow($totalR)) {
                        $weekID = $totals['weekLastQuarterID'];
                        $totalCache['store'][$storeID][$weekID] = $totals['store'];
                        $totalCache['super'][$row['superID']][$weekID] = $totals['super'];
                        $totalCache['dept'][$row['department']][$weekID] = $totals['dept'];
                    }
                }
                $sales['store'] += $factor * $totalCache['store'][$storeID][$i];
                if (!isset($totalCache['super'][$row['superID']][$i])) {
                    $totalR = $dbc->execute($totalsP, array($row['superID'], $row['department'], $storeID));
                    while ($totals = $dbc->fetchRow($totalR)) {
                        $weekID = $totals['weekLastQuarterID'];
                        $totalCache['super'][$row['superID']][$weekID] = $totals['super'];
                        $totalCache['dept'][$row['department']][$weekID] = $totals['dept'];
                    }
                }
                $sales['super'] += $factor * $totalCache['super'][$row['superID']][$i];
                if (!isset($totalCache['dept'][$row['department']][$i])) {
                    $totalR = $dbc->execute($totalsP, array($row['superID'], $row['department'], $storeID));
                    while ($totals = $dbc->fetchRow($totalR)) {
                        $weekID = $totals['weekLastQuarterID'];
                        $totalCache['dept'][$row['department']][$weekID] = $totals['dept'];
                    }
                }
                $sales['dept'] += $factor * $totalCache['dept'][$row['department']][$i];
                $factor -= 0.05;
            }
            $args = array(
                $upc,
                $storeID,
                $realQty,
                $realSales,
                $sales['store'] != 0 ? $scaledSales / $sales['store'] : 0,
                $sales['super'] != 0 ? $scaledSales / $sales['super'] : 0,
                $sales['dept'] != 0 ? $scaledSales / $sales['dept'] : 0,
            );
            $dbc->execute($insP, $args);

            $count++;
        }
        $dbc->commitTransaction();
        /*
        $dbc->query('INSERT INTO productSummaryLastQuarter
                   (upc, storeID, qtyThisWeek, totalThisWeek, qtyLastQuarter,
                    totalLastQuarter, percentageStoreSales,
                    percentageSuperDeptSales, percentageDeptSales)
                   SELECT upc, 
                   storeID,
                   SUM(CASE WHEN weekLastQuarterID=0 THEN quantity ELSE 0 END) as qtyThisWeek,
                   SUM(CASE WHEN weekLastQuarterID=0 THEN total ELSE 0 END) as totalThisWeek,
                   SUM(CASE WHEN weekLastQuarterID<>0 THEN quantity ELSE 0 END) as qtyLastQuarter,
                   SUM(CASE WHEN weekLastQuarterID<>0 THEN total ELSE 0 END) as totalLastQuarter,
                   SUM(CASE WHEN weekLastQuarterID=1 THEN percentageStoreSales ELSE 0 END),
                   SUM(CASE WHEN weekLastQuarterID=1 THEN percentageSuperDeptSales ELSE 0 END),
                   SUM(CASE WHEN weekLastQuarterID=1 THEN percentageDeptSales ELSE 0 END)
                   FROM productWeeklyLastQuarter
                   GROUP BY upc, storeID');
         */
    }
}

