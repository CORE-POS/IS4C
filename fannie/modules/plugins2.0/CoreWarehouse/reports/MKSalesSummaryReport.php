<?php

use COREPOS\Fannie\API\data\DataCache;

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class MKSalesSummaryReport extends FannieRESTfulPage 
{
    protected $header = 'MK Sales Summary';
    protected $title = 'MK Sales Summary';

    private function getLastYear($endTS)
    {
        $lyTS = mktime(0, 0, 0, date('n', $endTS), date('j', $endTS), date('Y', $endTS)-1);
        $inc = 1;
        if (date('w', $lyTS) < 4) {
            $inc = -1;
        }
        while (date('w', $lyTS) != 0) {
            $lyTS = mktime(0, 0, 0, date('n', $lyTS), date('j', $lyTS) + $inc, date('Y', $lyTS));
        }
        $lyStart = mktime(0, 0, 0, date('n', $lyTS), date('j', $lyTS)-6, date('Y', $lyTS));

        return array($lyStart, $lyTS);
    }

    private function subtractWeek($ts)
    {
        return mktime(0, 0, 0, date('n', $ts), date('j', $ts)-7, date('Y', $ts));
    }

    private function startFY()
    {
        $month = date('n');
        $year = date('Y');
        if ($month <= 6) {
            return mktime(0,0,0, 7, 1, $year-1);
        }
        return mktime(0,0,0, 7, 1, $year);
    }

    public function get_view()
    {
        $cache = unserialize(DataCache::getFile('monthly', 'MKSales'));
        $this->addScript('../../../../src/javascript/Chart.min.js');
        $this->addScript('mk.js');
        if (is_array($cache) && $cache['expires'] > time()) {
            foreach ($cache['commands'] as $c) {
                $this->addOnloadCommand($c);
            }
            return $cache['data'];
        }

        $warehouse = $this->config->get('PLUGIN_SETTINGS');
        $warehouse = $warehouse['WarehouseDatabase'];
        $warehouse .= $this->connection->sep();

        $weeks = array();
        $endTS = strtotime('last sunday');
        $startTS = mktime(0, 0, 0, date('n', $endTS), date('j', $endTS)-6, date('Y', $endTS));
        $startFY = $this->startFY();
        list($lyStart, $lyEnd) = $this->getLastYear($endTS);
        $dates = array();
        while ($startTS >= $startFY) {
            $dates[] = array(
                'thisYear' => array($startTS, $endTS),
                'lastYear' => array($lyStart, $lyEnd),
            );
            $startTS = $this->subtractWeek($startTS);
            $endTS = $this->subtractWeek($endTS);
            $lyStart = $this->subtractWeek($lyStart);
            $lyEnd = $this->subtractWeek($lyEnd);
        }
        $dates = array_reverse($dates);

        $salesP = $this->connection->prepare("
            SELECT store_id,
                SUM(total) AS ttl
            FROM {$warehouse}sumDeptSalesByDay AS d
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON m.dept_ID=d.department
            WHERE date_id BETWEEN ? AND ?
                AND m.superID <> 0
                AND store_id IN (1,2)
            GROUP BY store_id");
        $thisYear = array();
        $lastYear = array();
        foreach ($dates as $d) {
            $salesR = $this->connection->execute($salesP, array(
                date('Ymd', $d['thisYear'][0]),
                date('Ymd', $d['thisYear'][1]),
            ));
            $stores = array();
            while ($salesW = $this->connection->fetchRow($salesR)) {
                $stores[$salesW['store_id']] = $salesW['ttl'];
            }
            $thisYear[] = $stores;

            $salesR = $this->connection->execute($salesP, array(
                date('Ymd', $d['lastYear'][0]),
                date('Ymd', $d['lastYear'][1]),
            ));
            $stores = array();
            while ($salesW = $this->connection->fetchRow($salesR)) {
                $stores[$salesW['store_id']] = $salesW['ttl'];
            }
            $lastYear[] = $stores;
        }
        
        $labels = array_map(function ($i) { return date('n/j/Y', $i['thisYear'][0]); }, $dates);
        $hillsideTY = array_map(function ($i) { return $i[1]; }, $thisYear);
        $hillsideLY = array_map(function ($i) { return $i[1]; }, $lastYear);
        $denfeldTY = array_map(function ($i) { return $i[2]; }, $thisYear);
        $denfeldLY = array_map(function ($i) { return $i[2]; }, $lastYear);
        $totalTY = array_map(function ($i) { return $i[1] + $i[2]; }, $thisYear);
        $totalLY = array_map(function ($i) { return $i[1] + $i[2]; }, $lastYear);
        $lineData = array(
            'labels' => $labels,
            'hillside' => array('thisYear' => $hillsideTY, 'lastYear' => $hillsideLY),
            'denfeld' => array('thisYear' => $denfeldTY, 'lastYear' => $denfeldLY),
            'ttl' => array('thisYear' => $totalTY, 'lastYear' => $totalLY),
        );
        $lineData = json_encode($lineData);

        $barsHillside = array();
        for ($i=0; $i<count($hillsideTY); $i++) {
            $barsHillside[] = $hillsideTY[$i] - $hillsideLY[$i];
        }
        $barsDenfeld = array();
        for ($i=0; $i<count($denfeldTY); $i++) {
            $barsDenfeld[] = $denfeldTY[$i] - $denfeldLY[$i];
        }
        $barLabels = $labels;
        array_unshift($barLabels, 'Total');
        array_unshift($barsHillside, array_sum($barsHillside));
        array_unshift($barsDenfeld, array_sum($barsDenfeld));
        $barData = json_encode(array('labels'=>$barLabels, 'hillside'=>$barsHillside, 'denfeld'=>$barsDenfeld));

        $thisWeek = $dates[count($dates)-1]['thisYear'];
        $salesTables = array(1=>array(), 2=>array());
        $itemTables = array(1=>array(), 2=>array());
        $dailyP = $this->connection->prepare("
            SELECT date_id,
                SUM(total) AS ttl
            FROM {$warehouse}sumDeptSalesByDay AS d
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON m.dept_ID=d.department
            WHERE date_id BETWEEN ? AND ?
                AND m.superID <> 0
                AND store_id=?
            GROUP BY date_id
            ORDER BY SUM(total) DESC");
        $skuP = $this->connection->prepare("
            SELECT d.upc,
                p.description,
                SUM(d.total) AS ttl
            FROM {$warehouse}sumUpcSalesByDay AS d
                INNER JOIN " . FannieDB::fqn('products', 'op') . " AS p ON d.upc=p.upc AND d.store_id=p.store_id
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON m.dept_ID=p.department
            WHERE date_id BETWEEN ? AND ?
                AND m.superID <> 0
                AND d.upc <> '0000000007000'
                AND d.store_id=?
            GROUP BY d.upc, p.description
            ORDER BY SUM(total) DESC LIMIT 10");
        foreach (array_keys($salesTables) as $storeID) {
            $args = array(date('Ymd', $thisWeek[0]), date('Ymd', $thisWeek[1]), $storeID);
            $salesR = $this->connection->execute($dailyP, $args);
            $rank = 1;
            while ($row = $this->connection->fetchRow($salesR)) {
                $record = array($rank, $row['date_id'], $row['ttl']);
                $salesTables[$storeID][] = $record;
                $rank++;
            }
            $rank = 1;
            $itemTables[$storeID] = '';
            $skuR = $this->connection->execute($skuP, $args);
            while ($row = $this->connection->fetchRow($skuR)) {
                $itemTables[$storeID] .= "<tr><td class=\"text-right\">{$rank}</td><td class=\"text-center\">{$row['description']}</td></tr>";
                $rank++;
            }
        }
        $sort = function ($a, $b) {
            if ($a[1] == $b[1]) return 0;
            return $a[1] < $b[1] ? -1 : 1;
        };
        usort($salesTables[1], $sort);
        usort($salesTables[2], $sort);
        $htmlTables = array();
        foreach ($salesTables as $id => $rows) {
            $table = '';
            foreach ($rows as $row) {
                $table .= sprintf('<tr><td>%s</td><td>%s</td><td>$%s</td></tr>',
                    $row[0] < 4 ? $row[0] : '',
                    date('l', strtotime($row[1])),
                    number_format($row[2], 2)
                );
            }
            $htmlTables[$id] = $table;
        }

        $hSales = $hillsideTY[count($hillsideTY)-1];
        $dSales = $denfeldTY[count($denfeldTY)-1];
        $weekSummary = array(
            'hillside' => array(
                'ttl' => number_format($hSales, 2),
                'growth' => sprintf('%.1f', (($hSales - $hillsideLY[count($hillsideLY)-1])/$hSales)*100),
                'orgShare' => sprintf('%.1f', ($hSales/($hSales+$dSales))*100),
            ), 
            'denfeld' => array(
                'ttl' => number_format($dSales, 2),
                'growth' => sprintf('%.1f', (($dSales - $denfeldLY[count($denfeldLY)-1])/$dSales)*100),
                'orgShare' => sprintf('%.1f', ($dSales/($hSales+$dSales))*100),
            ), 
        );

        $commands = array();
        $commands[] = "mk.drawLines({$lineData});";
        $commands[] = "mk.drawBars({$barData});";
        foreach ($commands as $c) {
            $this->addOnloadCommand($c);
        }

        $report = <<<HTML
<div class="row">
    <div class="col-sm-12">
        <canvas id="denfeldLine"></canvas>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-sm-12">
        <canvas id="hillsideLine"></canvas>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-sm-12">
        <canvas id="totalLine"></canvas>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-sm-12">
        <canvas id="barGraph"></canvas>
    </div>
</div>
<div class="row">
    <div class="col-sm-5">
        <h3 class="text-center">Denfeld</h3>
        <table class="table table-bordered table-striped small">
            <tr><th class="text-center" colspan="2">Sales Performance</th><th>Growth</th></tr>
            <tr>
                <td>Total Week</td>
                <td>\${$weekSummary['denfeld']['ttl']}</td>
                <td>{$weekSummary['denfeld']['growth']}%</td>
            </tr>
            <tr>
                <td>% Organization</td>
                <td>{$weekSummary['denfeld']['orgShare']}%</td>
                <td></td>
            </tr>
        </table>
        <table class="table table-bordered table-striped small">
            {$htmlTables[2]}
        </table>
        <table class="table table-bordered table-striped small">
            <tr><th colspan="2" class="text-center">Top 10 $$ SKUs Last Week Denfeld</th></tr>
            {$itemTables[2]}
        </table>
    </div>
    <div class="col-sm-5">
        <h3 class="text-center">Hillside</h3>
        <table class="table table-bordered table-striped small">
            <tr><th class="text-center" colspan="2">Sales Performance</th><th>Growth</th></tr>
            <tr>
                <td>Total Week</td>
                <td>\${$weekSummary['hillside']['ttl']}</td>
                <td>{$weekSummary['hillside']['growth']}%</td>
            </tr>
            <tr>
                <td>% Organization</td>
                <td>{$weekSummary['hillside']['orgShare']}%</td>
                <td></td>
            </tr>
        </table>
        <table class="table table-bordered table-striped small">
            {$htmlTables[1]}
        </table>
        <table class="table table-bordered table-striped small">
            <tr><th colspan="2" class="text-center">Top 10 $$ SKUs Last Week Hillside</th></tr>
            {$itemTables[1]}
        </table>
    </div>
</div>
HTML;
        $cached = DataCache::putFile('monthly', serialize(array('data'=>$report, 'commands'=>$commands, 'expires'=>strtotime('next monday'))), 'MKSales');

        return $report;
    }
}

FannieDispatch::conditionalExec();

