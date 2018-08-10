<?php

use COREPOS\Fannie\API\lib\Stats;
use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class ObfBigBoardReport extends FannieRESTfulPage
{
    protected $header = 'OBF Big Board';
    protected $title = 'OBF Big Board';

    protected $OU_START = 162;
    protected $PLAN_CACHE1 = null;
    protected $PLAN_CACHE2 = null;

    protected function getOuStart($weekID)
    {
        if ($weekID >= 214) {
            return 214;
        } elseif ($weekID >= 201) {
            return 201;
        } elseif ($weekID >= 188) {
            return 188;
        } elseif ($weekID >= 175) {
            return 175;
        }

        return 162;
    }

    protected $PLAN_SALES_Q1_2018 = array(
        '1,6' => 53904.29,      // Hillside Produce
        '2,10' => 12187.19,     // Hillside Deli
        '2,11' => 33128.32,
        '2,16' => 13505.62,
        '3,1' => 25019.71,      // Hillside Grocery
        '3,4' => 60877.32,
        '3,5' => 23046.19,
        '3,7' => 192.84,
        '3,8' => 17028.21,
        '3,9' => 2657.68,
        '3,13' => 14635.17,
        '3,17' => 25688.49,
        '7,6' => 19084.56,      // Denfeld Produce
        '8,10' => 4516.25,      // Denfeld Deli
        '8,11' => 13618.01,
        '8,16' => 5318.20,
        '9,1' => 8168.40,       // Denfeld Grocery
        '9,4' => 24552.79,
        '9,5' => 8522.84,
        '9,7' => 82.03,
        '9,8' => 5726.79,
        '9,9' => 1002.57,
        '9,13' => 4636.12,
        '9,17' => 8414.48,
    );

    protected $PLAN_SALES_Q2_2018 = array(
        '1,6' => 51031.00,      // Hillside Produce
        '2,10' => 11448.32,     // Hillside Deli
        '2,11' => 31119.86,
        '2,16' => 12686.82,
        '3,1' => 26430.32,      // Hillside Grocery
        '3,4' => 64309.57,
        '3,5' => 24345.53,
        '3,7' => 203.71,
        '3,8' => 17988.26,
        '3,9' => 2807.52,
        '3,13' => 15460.30,
        '3,17' => 27136.80,
        '7,6' => 17975.00,      // Denfeld Produce
        '8,10' => 4383.48,      // Denfeld Deli
        '8,11' => 13217.67,
        '8,16' => 5161.85,
        '9,1' => 8470.24,       // Denfeld Grocery
        '9,4' => 25460.06,
        '9,5' => 8837.77,
        '9,7' => 85.06,
        '9,8' => 5938.41,
        '9,9' => 1039.62,
        '9,13' => 4807.43,
        '9,17' => 8725.41,
    );

    protected $PLAN_SALES_Q3_2018 = array(
        '1,6' => 51510.00,      // Hillside Produce
        '2,10' => 11676.94,     // Hillside Deli
        '2,11' => 31742.34,
        '2,16' => 12940.72,
        '3,1' => 25497.83,      // Hillside Grocery
        '3,4' => 62041.83,
        '3,5' => 23487.33,
        '3,7' => 196.81,
        '3,8' => 17353.57,
        '3,9' => 2708.96,
        '3,13' => 14914.74,
        '3,17' => 26179.90,
        '7,6' => 20085.00,      // Denfeld Produce
        '8,10' => 4514.67,      // Denfeld Deli
        '8,11' => 13615.10,
        '8,16' => 5317.08,
        '9,1' => 8949.35,       // Denfeld Grocery
        '9,4' => 26900.87,
        '9,5' => 9338.17,
        '9,7' => 89.81,
        '9,8' => 6274.05,
        '9,9' => 1098.86,
        '9,13' => 5079.05,
        '9,17' => 9218.78,
    );

    protected $PLAN_SALES_Q4_2018 = array(
        '1,6' => 52231.00,      // Hillside Produce
        '2,10' => 11840.47,     // Hillside Deli
        '2,11' => 32186.37,
        '2,16' => 13122.16,
        '3,1' => 25854.77,      // Hillside Grocery
        '3,4' => 62910.11,
        '3,5' => 23815.64,
        '3,7' => 199.76,
        '3,8' => 17596.82,
        '3,9' => 2746.91,
        '3,13' => 15123.69,
        '3,17' => 26546.32,
        '7,6' => 20708.00,      // Denfeld Produce
        '8,10' => 4654.99,      // Denfeld Deli
        '8,11' => 14037.15,
        '8,16' => 5481.86,
        '9,1' => 9226.50,       // Denfeld Grocery
        '9,4' => 27735.16,
        '9,5' => 9627.56,
        '9,7' => 92.79,
        '9,8' => 6468.55,
        '9,9' => 1133.08,
        '9,13' => 5236.49,
        '9,17' => 9504.87,
    );

    private function weekToYM($weekID)
    {
        $prep = $this->connection->prepare('SELECT startDate
            FROM ' . FannieDB::fqn('ObfWeeks', 'plugin:ObfDatabaseV2') . '
            WHERE obfWeekID=?');
        $date = $this->connection->getValue($prep, array($weekID));
        $nowNext = array(0, 0);
        $stamp = strtotime($date);
        $cur = strtotime($date);
        for ($i=0; $i<7; $i++) {
            if (date('n', $cur) == date('n', $stamp)) {
                $nowNext[0]++;
            } else {
                $nowNext[1]++;
            }
            $stamp = mktime(0, 0, 0, date('n', $stamp), date('j', $stamp)+1, date('Y', $stamp));
        }
        if ($nowNext[0] > $nowNext[1]) {
            $stamp = time();
        }

        return array(date('Y', $stamp), date('n', $stamp));
    }

    protected function getPlanSales($catID, $weekID)
    {
        $ttl = 0;
        $plan = $this->PLAN_SALES_Q1_2018;
        if ($weekID >= 218) {
            $prep = $this->connection->prepare("
                SELECT l.obfCategoryID, s.superID, (1+l.growthTarget)*s.lastYearSales AS plan
                FROM " . FannieDB::fqn('ObfLabor', 'plugin:ObfDatabaseV2') . " AS l
                    INNER JOIN " . FannieDB::fqn('ObfCategories', 'plugin:ObfDatabaseV2') . " AS c ON l.obfCategoryID=c.obfCategoryID
                    INNER JOIN " . FannieDB::fqn('ObfSalesCache', 'plugin:ObfDatabaseV2') . " AS s
                        ON c.obfCategoryID=s.obfCategoryID AND l.obfWeekID=s.obfWeekID
                WHERE l.obfWeekID=?");
            $res = $this->connection->execute($prep, array($weekID));
            $ret = array();
            while ($row = $this->connection->fetchRow($res)) {
                $key = $row['obfCategoryID'] . ',' . $row['superID'];
                $ret[$key] = $row['plan'];
            }
            $plan = $ret;
        } elseif ($weekID >= 214) {
            list($year, $month) = $this->weekToYM($weekID);
            $ret = array();
            $prep = $this->connection->prepare('SELECT c.obfCategoryID, m.superID, p.planGoal
                FROM ' . FannieDB::fqn('ObfCategories', 'plugin:ObfDatabaseV2') . ' AS c
                INNER JOIN ' . FannieDB::fqn('ObfCategorySuperDeptMap', 'plugin:ObfDatabaseV2') . ' AS m ON c.obfCategoryID=m.obfCategoryID
                INNER JOIN ' . FannieDB::fqn('ObfPlans', 'plugin:ObfDatabaseV2') . ' AS p ON c.storeID=p.storeID AND m.superID=p.superID
                WHERE c.hasSales=1 and month=? and year=?');
            $res = $this->connection->execute($prep, array($month, $year)); 
            $days = date('t', mktime(0,0,0,$month,1,$year));
            while ($row = $this->connection->fetchRow($res)) {
                $key = $row['obfCategoryID'] . ',' . $row['superID'];
                $ret[$key] = ($row['planGoal'] / $days) * 7;
            }
            $plan = $ret;
        } elseif ($weekID >= 201) {
            return $this->PLAN_SALES_Q4_2018;
        } elseif ($weekID >= 188) {
            $plan = $this->PLAN_SALES_Q3_2018;
        } elseif ($weekID >= 175) {
            $plan = $this->PLAN_SALES_Q2_2018;
        }
        foreach ($plan as $k => $v) {
            if (strpos($k, $catID . ',') === 0) {
                $ttl += $v;
            } 
        }

        return $ttl;
    }

    protected function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['ObfDatabaseV2'] . $this->connection->sep();
        $store = FormLib::get('store', 0);

        $ret = '';
        $ret = sprintf('<p>
            <a href="?id=%d&store=%d">Prev Week</a> |
            <a href="?id=%d&store=%d">Next Week</a> |
            <a href="?id=%d&store=%d">Other Store</a>
            </p>',
            ($this->id-1), $store,
            ($this->id+1), $store,
            $this->id, ($store == 1 ? 2 : 1)
        );

        $weekP = $this->connection->prepare("SELECT * FROM {$prefix}ObfWeeks WHERE obfWeekID=?");
        $week = $this->connection->getRow($weekP, array($this->id));
        $this->OU_START = $this->getOuStart($this->id);

        $curStart = new DateTimeImmutable($week['startDate']);
        $curEnd = new DateTimeImmutable($week['endDate']);

        $actualP = $this->connection->prepare("SELECT SUM(actualSales) FROM {$prefix}ObfSalesCache WHERE obfWeekID BETWEEN ? AND ? AND obfCategoryID=?"); 
        $transP = $this->connection->prepare("SELECT transactions FROM {$prefix}ObfSalesCache WHERE obfWeekID=?");
        $laborP = $this->connection->prepare("SELECT SUM(hours) FROM {$prefix}ObfLabor WHERE obfWeekID BETWEEN ? AND ? AND obfCategoryID=?"); 
        $castP = $this->connection->prepare("SELECT forecastSales FROM {$prefix}ObfLabor WHERE obfWeekID=? AND obfCategoryID=?"); 
        $splhP = $this->connection->prepare("SELECT splhTarget FROM {$prefix}ObfLabor WHERE obfWeekID=? AND obfCategoryID=?");

        $catQ = "SELECT obfCategoryID, name
            FROM {$prefix}ObfCategories
            WHERE hasSales=1";
        $args = array();
        if ($store) {
            $catQ .= " AND storeID=? ";
            $args[] = $store;
        }
        $catQ .= " ORDER BY name";
        $catP = $this->connection->prepare($catQ);
        $catR = $this->connection->execute($catP, $args);

        $totals = array(
            0 => array('actual'=>0, 'forecast'=>0, 'plan'=>0, 'planHours'=>0, 'actualHours'=>0, 'aggOU'=>0, 'hoursOU'=>0),
            1 => array('forecast'=>0, 'plan'=>0, 'planHours'=>0),
            2 => array('forecast'=>0, 'plan'=>0, 'planHours'=>0),
            3 => array('forecast'=>0, 'plan'=>0, 'planHours'=>0),
        );

        $ret .= '<table class="table small table-bordered table-striped">';
        $ret .= '<tr><th>&nbsp;</th>';
        for ($i=3; $i>=0; $i--) {
            $start = $i == 0 ? $curStart : $curStart->add(new DateInterval('P' . ($i*7) . 'D'));
            $end = $i == 0 ? $curEnd : $curEnd->add(new DateInterval('P' . ($i*7) . 'D'));
            $span = $i == 0 ? 4 : 2;
            $ret .= '<th class="text-center" colspan="' . $span . '">'
                . $start->format('m/j')
                . ' - '
                . $end->format('m/j')
                . '</th>';
        }
        $ret .= '<th>&nbsp;</th>';
        $ret .= '</tr>';
        $ret .= '<tr><th>&nbsp;</th>';
        for ($i=3; $i>=0; $i--) {
            $ret .= '<th>Plan</th><th>Cast</th>';
            if ($i == 0) {
                $ret .= '<th>Actual</th><th>O/U</th><th>Agg. O/U</th>';
            }
        }
        $ret .= '</tr>';

        $saleCategories = array();
        $ret .= '<tr><th colspan="12">Sales</th></tr>';
        while ($catW = $this->connection->fetchRow($catR)) {
            $saleCategories[] = $catW;
            $catID = $catW['obfCategoryID'];
            $ret .= '<tr><td>' . $catW['name'] . '</td>';
            for ($i=3; $i>=0; $i--) {
                $weekID = $this->id + $i;
                $plan = $this->getPlanSales($catID, $weekID);
                $cast = $this->connection->getValue($castP, array($weekID, $catID));
                $totals[$i]['plan'] += $plan;
                $totals[$i]['forecast'] += $cast;
                $ret .= '<td class="text-right">' . number_format($plan) . '</td>';
                $ret .= '<td class="text-right">' . number_format($cast) . '</td>';
                if ($i == 0) {
                    $actual = $this->connection->getValue($actualP, array($weekID, $weekID, $catID));
                    $o_u = $actual - $plan;
                    $totals[$i]['actual'] += $actual;
                    $ret .= '<td class="text-right">' . number_format($actual) . '</td>';
                    $ret .= '<td class="text-right">' . number_format($o_u) . '</td>';
                    $aggOU = 0;
                    $sales = $this->connection->getValue($actualP, array($this->OU_START, $weekID, $catID));
                    // this loop is in case getPlanSales() is revised to take
                    // a weekID argument and choose from multiple plans
                    for ($j=$this->OU_START; $j<=$weekID; $j++) {
                        $plan = $this->getPlanSales($catID, $j);
                        $sales -= $plan;
                    }
                    $ret .= '<td class="text-right">' . number_format($sales) . '</td>';
                    $totals[$i]['aggOU'] += $sales;
                }
            }
            $ret .= '</tr>';
        }

        $catQ = str_replace('hasSales=1', 'hasSales=0', $catQ);
        $catP = $this->connection->prepare($catQ);
        $catR = $this->connection->execute($catP, $args);
        while ($catW = $this->connection->fetchRow($catR)) {
            $saleCategories[] = $catW;
        }

        $ret .= '<tr><th colspan="12">Hours</th></tr>';
        foreach ($saleCategories as $catW) {
            $catID = $catW['obfCategoryID'];
            $ret .= '<tr><td>' . $catW['name'] . '</td>';
            $splh = $this->connection->getValue($splhP, array($weekID, $catID));
            for ($i=3; $i>=0; $i--) {
                $weekID = $this->id + $i;
                $plan = $this->getPlanSales($catID, $weekID);
                if ($plan == 0) {
                    $plan = $totals[$i]['plan'];
                }
                $plan /= $splh;
                $ret .= '<td class="text-right">' . number_format($plan) . '</td><td>-</td>';
                $totals[$i]['planHours'] += $plan;
                if ($i == 0) {
                    $actual = $this->connection->getValue($laborP, array($weekID, $weekID, $catID));
                    $o_u = $actual - $plan;
                    $ret .= '<td class="text-right">' . number_format($actual) . '</td>';
                    $ret .= '<td class="text-right">' . number_format($o_u) . '</td>';
                    $totals[$i]['actualHours'] += $actual;
                    $hours = $this->connection->getValue($laborP, array($this->OU_START, $weekID, $catID));
                    for ($j=$this->OU_START; $j<=$weekID; $j++) {
                        $planSales = $this->getPlanSales($catID, $j);
                        if ($planSales == 0) {
                            $planSales = $totals[$i]['plan'];
                        }
                        $weekSPLH = $this->connection->getValue($splhP, array($j, $catID));
                        $hours -= ($planSales / $weekSPLH);
                    }
                    $ret .= '<td class="text-right">' . number_format($hours) . '</td>';
                    $totals[$i]['hoursOU'] += $hours;
                }
            }
        }

        $ret .= '<tr><th colspan="12">Store</th></tr>';
        $ret .= '<tr><td>Sales</td>';
        for ($i=3; $i>=0; $i--) {
            $ret .= '<td class="text-right">' . number_format($totals[$i]['plan']) . '</td>';
            $ret .= '<td class="text-right">' . number_format($totals[$i]['forecast']) . '</td>';
            if ($i == 0) {
                $ret .= '<td class="text-right">' . number_format($totals[$i]['actual']) . '</td>';
                $ret .= '<td class="text-right">' . number_format($totals[$i]['actual'] - $totals[$i]['plan']) . '</td>';
                $ret.= '<td class="text-right">' . number_format($totals[$i]['aggOU']) . '</td>';
            }
        }

        $ret .= '<tr><td>Hours</td>';
        for ($i=3; $i>=0; $i--) {
            $ret .= '<td class="text-right">' . number_format($totals[$i]['planHours']) . '</td><td>-</td>';
            if ($i == 0) {
                $ret .= '<td class="text-right">' . number_format($totals[$i]['actualHours']) . '</td>';
                $ret .= '<td class="text-right">' . number_format($totals[$i]['actualHours'] - $totals[$i]['planHours']) . '</td>';
                $ret .= '<td class="text-right">' . number_format($totals[$i]['hoursOU']) . '</td>';
            }
        }

        $ret .= '<tr><td>Transactions</td>';
        for ($i=0; $i<8; $i++) {
            $ret .= '<td>-</td>';
        }
        $trans = $this->connection->getValue($transP, array($this->id));
        $ret .= '<td class="text-right">' . $trans . '</td><td>-</td><td>-</td></tr>';

        $ret .= '<tr><td>Basket</td>';
        for ($i=0; $i<8; $i++) {
            $ret .= '<td>-</td>';
        }
        $ret .= '<td class="text-right">' . number_format($totals[0]['actual'] / $trans, 2) . '</td><td>-</td><td>-</td></tr>';

        $dlog = DTransactionsModel::selectDlog($curStart->format('Y-m-d'), $curEnd->format('Y-m-d'));
        $ownerP = $this->connection->prepare("SELECT SUM(total) FROM {$dlog} WHERE department=992 AND tdate BETWEEN ? AND ?");
        $oArgs = array($curStart->format('Y-m-d 00:00:00'), $curEnd->format('Y-m-d 23:59:59'));
        $equityA = $this->connection->getValue($ownerP, $oArgs);
        $ret .= '<tr><td>Owners</td>';
        for ($i=0; $i<8; $i++) {
            $ret .= '<td>-</td>';
        }
        $ret .= '<td class="text-right">' . floor($equityA / 20) . '</td><td>-</td><td>-</td></tr>';

        $ret .= '</table>';

        return $ret;
    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['ObfDatabaseV2'] . $this->connection->sep();
        $query = "SELECT obfWeekID, startDate, endDate
            FROM {$prefix}ObfWeeks
            WHERE endDate < ?
            ORDER BY endDate DESC";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array(date('Y-m-d')));
        $opts = '';
        $optCount = 0;
        while ($row = $this->connection->fetchRow($res)) {
            $row['startDate'] = str_replace(' 00:00:00', '', $row['startDate']);
            $row['endDate'] = str_replace(' 00:00:00', '', $row['endDate']);
            $optCount++;
            $opts .= sprintf('<option value="%d">%s - %s</option>', $row['obfWeekID'], $row['startDate'], $row['endDate']);
            if ($optCount > 104) break;
        }

        $stores = FormLib::storePicker();

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Week</label>
        <select name="id" class="form-control">{$opts}</select>
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

