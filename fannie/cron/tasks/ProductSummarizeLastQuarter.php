<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB;

        $today = mktime();
        $this_monday = $today;
        while(date('N', $this_monday) != 1) {
            $this_monday = mktime(0, 0, 0, date('n', $this_monday), date('j', $this_monday) - 1, date('Y', $this_monday));
        }
        $last_monday = mktime(0, 0, 0, date('n', $this_monday), date('j', $this_monday) - 7, date('Y', $this_monday));

        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        echo $this->cronMsg('Determining applicable weeks');
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

        $addP = $dbc->prepare('INSERT INTO productWeeklyLastQuarter 
                            (upc, weekLastQuarterID, quantity, total,
                            percentageStoreSales, percentageSuperDeptSales,
                            percentageDeptSales)
                            VALUES
                            (?, ?, ?, ?,
                            ?, ?,
                            ?)');
        $products = $FANNIE_OP_DB . $dbc->sep() . 'products';
        $supers = $FANNIE_OP_DB . $dbc->sep() . 'MasterSuperDepts';
        $store_sales = 0.0;
        $super_sales = array();
        $dept_sales = array();
        $dbc->query('TRUNCATE TABLE productWeeklyLastQuarter');
        foreach($weeks as $weekID => $limits) {
            $upcs = array();
            echo $this->cronMsg('Processing week #'.$weekID);
            $dlog = DTransactionsModel::selectDlog(date('Y-m-d', $limits[0]), date('Y-m-d', $limits[1]));
            $dataP = $dbc->prepare("SELECT d.upc, SUM(total) as ttl, "
                                . DTrans::sumQuantity('d') . " as qty,
                                MAX(p.department) as dept, MAX(s.superID) as superDept
                                FROM $dlog AS d INNER JOIN $products AS p
                                ON d.upc = p.upc
                                LEFT JOIN $supers AS s
                                ON p.department = s.dept_ID
                                WHERE tdate BETWEEN ? AND ?
                                GROUP BY d.upc");
            $args = array(date('Y-m-d 00:00:00', $limits[0]), date('Y-m-d 23:59:59', $limits[1]));
            $result = $dbc->execute($dataP, $args);
            // accumulate all info for the week
            // in order to calculate percentages
            while($row = $dbc->fetch_row($result)) {
                // normally miskeys that were voided
                // and not useful information
                if ($row['ttl'] == 0) continue;

                $upcs[$row['upc']] = array(
                    'ttl' => $row['ttl'],
                    'qty' => $row['qty'],
                    'dept' => $row['dept'],
                    'super' => $row['superDept'],
                );

                $store_sales += $row['ttl'];

                if (!isset($super_sales[$row['superDept']])) {
                    $super_sales[$row['superDept']] = 0.0;
                }
                $super_sales[$row['superDept']] += $row['ttl'];

                if (!isset($dept_sales[$row['dept']])) {
                    $dept_sales[$row['dept']] = 0.0;
                }
                $dept_sales[$row['dept']] += $row['ttl'];
            }

            // add entries for this week's items
            foreach($upcs as $upc => $info) {
                $d_ttl = $dept_sales[$info['dept']];
                $s_ttl = $super_sales[$info['super']];

                $args = array(
                    $upc,
                    $weekID,
                    $info['qty'],
                    $info['ttl'],
                    $store_sales == 0 ? 0.0 : $info['ttl'] / $store_sales,
                    $s_ttl == 0 ? 0.0 : $info['ttl'] / $s_ttl,
                    $d_ttl == 0 ? 0.0 : $info['ttl'] / $d_ttl,
                );
                $dbc->execute($addP, $args);
            }
        } // end loop on weeks

        // now do weighted averages
        echo $this->cronMsg('Calculating weighted averages');
        $dbc->query('TRUNCATE TABLE productSummaryLastQuarter');
        $dbc->query('INSERT INTO productSummaryLastQuarter
                   (upc, qtyThisWeek, totalThisWeek, qtyLastQuarter,
                    totalLastQuarter, percentageStoreSales,
                    percentageSuperDeptSales, percentageDeptSales)
                   SELECT upc, 
                   SUM(CASE WHEN weekLastQuarterID=0 THEN quantity ELSE 0 END) as qtyThisWeek,
                   SUM(CASE WHEN weekLastQuarterID=0 THEN total ELSE 0 END) as totalThisWeek,
                   SUM(CASE WHEN weekLastQuarterID<>0 THEN quantity ELSE 0 END) as qtyLastQuarter,
                   SUM(CASE WHEN weekLastQuarterID<>0 THEN total ELSE 0 END) as totalLastQuarter,
                   SUM((14-weekLastQuarterID) * percentageStoreSales) / SUM(14-weekLastQuarterID),
                   SUM((14-weekLastQuarterID) * percentageSuperDeptSales) / SUM(14-weekLastQuarterID),
                   SUM((14-weekLastQuarterID) * percentageDeptSales) / SUM(14-weekLastQuarterID)
                   FROM productWeeklyLastQuarter
                   GROUP BY upc');
    }
}

