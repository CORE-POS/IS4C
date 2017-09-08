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

    protected function getPlanSales($catID)
    {
        $ttl = 0;
        foreach ($this->PLAN_SALES_Q1_2018 as $k => $v) {
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

        $weekP = $this->connection->prepare("SELECT * FROM {$prefix}ObfWeeks WHERE obfWeekID=?");
        $week = $this->connection->getRow($weekP, array($this->id));

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

        $ret = '<table class="table small table-bordered table-striped">';
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
                $plan = $this->getPlanSales($catID);
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
                        $plan = $this->getPlanSales($catID);
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
                $plan = $this->getPlanSales($catID);
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
                        $planSales = $this->getPlanSales($catID);
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
        <label>Week</label>
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

