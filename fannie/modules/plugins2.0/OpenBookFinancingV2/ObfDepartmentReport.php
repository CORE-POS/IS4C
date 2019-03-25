<?php

use COREPOS\Fannie\API\lib\Stats;
use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class ObfDepartmentReport extends FannieRESTfulPage
{
    protected $header = 'OBF Snapshot';
    protected $title = 'OBF Snapshot';

    protected function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['ObfDatabaseV2'] . $this->connection->sep();
        $week = FormLib::get('week');
        $windowSize = 5;

        $weeks = array($week - $windowSize + 1, $week);
        $cacheP = $this->connection->prepare("
            SELECT 
                SUM(actualSales) AS actual,
                SUM(lastYearSales) AS ly,
                SUM(hours) AS hours,
                MAX(w.startDate) AS start,
                MAX(w.endDate) AS end,
                s.obfWeekID,
                MAX(m.superID) AS superID,
                MAX(s.obfCategoryID) AS catID,
                m.super_name
            FROM {$prefix}ObfSalesCache AS s
                LEFT JOIN superDeptNames AS m ON s.superID=m.superID
                LEFT JOIN {$prefix}ObfLabor AS l ON s.obfWeekID=l.obfWeekID AND s.obfCategoryID=l.obfCategoryID
                LEFT JOIN {$prefix}ObfWeeks AS w ON s.obfWeekID=w.obfWeekID
            WHERE s.obfWeekID BETWEEN ? AND ?
                AND s.obfCategoryID=?
            GROUP BY s.obfWeekID, m.super_name
            ORDER BY s.obfWeekID");
        $trendP = $this->connection->prepare("
            SELECT actualSales AS actual
            FROM {$prefix}ObfSalesCache
            WHERE obfWeekID BETWEEN ? AND ?
                AND superID=?
                AND obfCategoryID=?
            ORDER BY obfWeekID");
        $hoursP = $this->connection->prepare("
            SELECT hours 
            FROM {$prefix}ObfLabor
            WHERE obfWeekID=?
                AND obfCategoryID=?");

        $lineData = array(
            'actualSales' => array(), 
            'lastYearSales' => array(),
            'trendSales' => array(),
            'smoothSales' => array(),
        );
        $categorySales = array();
        $hours = array();
        $lyHours = array();

        $cacheR = $this->connection->execute($cacheP, array($weeks[0], $weeks[1], $this->id));
        $lastActual = false;
        while ($cacheW = $this->connection->fetchRow($cacheR)) {
            $weekID = $cacheW['obfWeekID'];
            list($start,) = explode(' ', $cacheW['start']);
            list($end,) = explode(' ', $cacheW['end']);
            $dates = substr($start, 5) . ' - ' . substr($end, 5);
            if (!isset($lineData['actualSales'][$dates])) {
                $lineData['actualSales'][$dates] = 0;
            }
            if (!isset($lineData['lastYearSales'][$dates])) {
                $lineData['lastYearSales'][$dates] = 0;
            }
            if (!isset($lineData['trendSales'][$dates])) {
                $lineData['trendSales'][$dates] = 0;
            }
            $lineData['actualSales'][$dates] += $cacheW['actual'];
            $lineData['lastYearSales'][$dates] += $cacheW['ly'];
            if ($cacheW['actual'] > 0) {
                $lastActual = $weekID;
            }

            $trendR = $this->connection->execute($trendP, array($lastActual-13, $lastActual-1, $cacheW['superID'], $cacheW['catID']));
            $trend = array();
            $tCount = 0;
            $smooth = array();
            while ($trendW = $this->connection->fetchRow($trendR)) {
                $trend[] = array($tCount, $trendW['actual']);
                $smooth[] = $trendW['actual'];
                $tCount++;
            }
            $trend = Stats::removeOutliers($trend);
            $exp = Stats::exponentialFit($trend);
            $plotted = $tCount + ($weekID - $lastActual);
            $lineData['trendSales'][$dates] += exp($exp->a) * exp($exp->b * $plotted);

            // when out of data points extend exponential smoothing
            // by assuming the previous value is correct
            $alpha = 0.6;
            $smoothed = Stats::expSmoothing($smooth, $alpha);
            for ($i=0; $i<($weekID - $lastActual); $i++) {
                $smooth[] = exp($exp->a) * exp($exp->b * ($tCount + $i));
                $alpha *= 0.7;
                $smoothed = Stats::expSmoothing($smooth, 0.4);
            }
            $lineData['smoothSales'][$dates] += $smoothed;

            $super = $cacheW['super_name'];
            if (!isset($categorySales[$super])) {
                $categorySales[$super] = 0;
            }
            $categorySales[$super] += $cacheW['actual'];

            if (!isset($hours[$dates])) {
                $hours[$dates] = $cacheW['hours'];
                $lyHours[$dates] = $this->connection->getValue($hoursP, array($weekID-52, $cacheW['catID']));
            }
        }

        $weekDates = $this->connection->prepare("SELECT startDate, endDate FROM {$prefix}ObfWeeks WHERE obfWeekID=?");
        $weekDates = $this->connection->getRow($weekDates, array($week));
        $weekDates['startDate'] = date('Y-m-d', strtotime($weekDates['startDate']));
        $weekDates['endDate'] = date('Y-m-d', strtotime($weekDates['endDate']));
        $store = $this->connection->prepare("SELECT storeID FROM {$prefix}ObfCategories WHERE obfCategoryID=?");
        $store = $this->connection->getValue($store, array($this->id));
        $opDB = $this->config->get('OP_DB') . $this->connection->sep();
        $deptP = $this->connection->prepare("SELECT dept_ID 
            FROM {$prefix}ObfCategorySuperDeptMap AS o 
                INNER JOIN {$opDB}superdepts AS s ON o.superID=s.superID 
            WHERE o.obfCategoryID=?
            GROUP BY dept_ID");
        $depts = array();
        $deptR = $this->connection->execute($deptP, array($this->id));
        while ($row = $this->connection->fetchRow($deptR)) {
            $depts[] = $row['dept_ID'];
        }
        $args = array_map(function ($i) { return (int)$i; }, $args);
        $stamp = strtotime($weekDates['startDate']);
        $lastWeek = array(
            'startDate' => date('Y-m-d', mktime(0,0,0,date('n',$stamp),date('j',$stamp)-7,date('Y',$stamp))),
            'endDate' => date('Y-m-d', mktime(0,0,0,date('n',$stamp),date('j',$stamp)-1,date('Y',$stamp))),
        );
        $dlog = DTransactionsModel::selectDlog($lastWeek['startDate'], $weekDates['endDate']);
        list($dIn, $args) = $this->connection->safeInClause($depts);
        $bestQ = "SELECT d.upc,
            SUM(total) AS ttl,
            MAX(d.description) AS saleDesc
            FROM {$dlog} AS d
                " . DTrans::joinProducts('d') . "
                LEFT JOIN {$opDB}productUser AS u ON u.upc=d.upc
            WHERE d.department IN ({$dIn})
                AND d.store_id=?
                AND d.tdate BETWEEN ? AND ?
                AND d.trans_type IN ('I','D')
                AND d.charflag <> 'SO'
            GROUP BY d.upc
            ORDER BY SUM(total) DESC";
        $bestQ = $this->connection->addSelectLimit($bestQ, 5);
        $bestP = $this->connection->prepare($bestQ);
        $bestArgs = $args;
        $bestArgs[] = $store;
        $bestArgs[] = $weekDates['startDate'] . ' 00:00:00';
        $bestArgs[] = $weekDates['endDate'] . ' 23:59:59';
        $bestR = $this->connection->execute($bestP, $bestArgs);
        $best = array();
        while ($bestW = $this->connection->fetchRow($bestR)) {
            $best[] = $bestW;
        }

        $gainLossQ = "SELECT d.upc,
            SUM(CASE WHEN d.tdate BETWEEN ? AND ? THEN total ELSE 0 END) as thisWeekTTL,
            SUM(CASE WHEN d.tdate BETWEEN ? AND ? THEN total ELSE 0 END) as lastWeekTTL,
            SUM(CASE WHEN d.tdate BETWEEN ? AND ? THEN total ELSE 0 END)
                - SUM(CASE WHEN d.tdate BETWEEN ? AND ? THEN total ELSE 0 END) as diffTTL,
            MAX(d.description) AS saleDesc
            FROM {$dlog} AS d
                " . DTrans::joinProducts('d') . "
                LEFT JOIN {$opDB}productUser AS u ON u.upc=d.upc
            WHERE d.department IN ({$dIn})
                AND d.store_id=?
                AND d.tdate BETWEEN ? AND ?
                AND d.trans_type IN ('I','D')
                AND d.charflag <> 'SO'
            GROUP BY d.upc
            HAVING thisWeekTTL > 0 AND lastWeekTTL > 0";
        $glArgs = array(
            $weekDates['startDate'] . ' 00:00:00',
            $weekDates['endDate'] . ' 23:59:59',
            $lastWeek['startDate'] . ' 00:00:00',
            $lastWeek['endDate'] . ' 23:59:59',
            $weekDates['startDate'] . ' 00:00:00',
            $weekDates['endDate'] . ' 23:59:59',
            $lastWeek['startDate'] . ' 00:00:00',
            $lastWeek['endDate'] . ' 23:59:59',
        );
        foreach ($args as $a) {
            $glArgs[] = $a;
        }
        $glArgs[] = $store;
        $glArgs[] = $lastWeek['startDate'] . ' 00:00:00';
        $glArgs[] = $weekDates['endDate'] . ' 23:59:59';
        $gainQ = $gainLossQ . ' ORDER BY diffTTL DESC';
        $gainQ = $this->connection->addSelectLimit($gainQ, 5);
        $gainP = $this->connection->prepare($gainQ);
        $gainR = $this->connection->execute($gainP, $glArgs);
        $gain = array();
        while ($row = $this->connection->fetchRow($gainR)) {
            $gain[] = $row;
        }
        $lossQ = $gainLossQ . ' ORDER BY diffTTL ASC';
        $lossQ = $this->connection->addSelectLimit($lossQ, 5);
        $lossP = $this->connection->prepare($lossQ);
        $lossR = $this->connection->execute($lossQ, $glArgs);
        $loss = array();
        while ($row = $this->connection->fetchRow($lossR)) {
            $loss[] = $row;
        }

        $dates = array_keys($lineData['lastYearSales']);
        $ret = '';
        $ret .= sprintf('<p>
            <a href="?id=%d&week=%d">Previous Week</a>
            |
            <a href="?id=%d&week=%d">Next Week</a>
            </p>', $this->id, $week-1, $this->id, $week+1);
        $ret .= '<div class="row"><div class="col-sm-6">';
        $ret .= '<table class="table table-bordered table-striped"><thead><tr><th>&nbsp;</th>';
        foreach ($dates as $date) {
            $ret .= "<th>{$date}</th>";
        }
        $ret .= '</tr></thead><tbody>';

        $ret .= '<tr><th>Actual Sales</th>';
        foreach ($lineData['actualSales'] as $actual) {
            $ret .= '<td>' . number_format($actual) . '</td>';
        }
        $ret .= '</tr>';

        $ret .= '<tr><th>Last Year Sales</th>';
        foreach ($lineData['lastYearSales'] as $actual) {
            $ret .= '<td>' . number_format($actual) . '</td>';
        }
        $ret .= '</tr>';

        $ret .= '<tr><th>Trend Sales</th>';
        foreach ($lineData['trendSales'] as $actual) {
            $ret .= '<td>' . number_format($actual) . '</td>';
        }
        $ret .= '</tr>';
        $ret .= '<tr><th>Smoothed Sales</th>';
        foreach ($lineData['smoothSales'] as $actual) {
            $ret .= '<td>' . number_format($actual) . '</td>';
        }
        $ret .= '</tr>';
        $ret .= '</table>';
        $ret .= '</div><div class="col-sm-5"><canvas id="salesLines"></canvas></div></div>';

        $ret .= '<div class="row"><div class="col-sm-6">';
        $ret .= '<table class="table table-bordered table-striped"><thead><tr><th>Category</th><th>Sales</th></tr></thead><tbody>';
        foreach ($categorySales as $name => $sales) {
            $ret .= '<tr><td>' . $name . '</td><td>' . number_format($sales) . '</td></tr>';
        }
        $ret .= '</tbody></table>';
        $ret .= '</div><div class="col-sm-5"><canvas id="salesPie"></canvas></div></div>';

        $ret .= '<div class="row"><div class="col-sm-6">';
        $ret .= '<table class="table table-bordered table-striped"><thead><tr><th>&nbsp;</th>';
        foreach (array_keys($hours) as $date) {
            $ret .= "<th>{$date}</th>";
        }
        $ret .= '</tr></thead><tbody><tr><th>Hours</th>';
        foreach ($hours as $h) {
            $ret .= '<td>' . number_format($h) . '</td>';
        }
        $ret .= '</tr>';
        $ret .= '</tr></thead><tbody><tr><th>Last Year</th>';
        foreach ($lyHours as $h) {
            $ret .= '<td>' . number_format($h) . '</td>';
        }
        $ret .= '<tr><th>SPLH</th>';
        $splhData = array();
        foreach ($hours as $dates => $h) {
            $ret .= sprintf('<td>%.2f</td>', $lineData['actualSales'][$dates] / $h);
            if ($lineData['actualSales'][$dates]) {
                $splhData[] = array('x' => $dates, 'y' => $lineData['actualSales'][$dates] / $h);
            }
        }
        $ret .= '</tr><tr><th>SPLH Last Year</th>';
        $lySplhData = array();
        foreach ($lyHours as $dates => $h) {
            $ret .= sprintf('<td>%.2f</td>', $lineData['lastYearSales'][$dates] / $h);
            if ($lineData['actualSales'][$dates]) {
                $lySplhData[] = array('x' => $dates, 'y' => $lineData['lastYearSales'][$dates] / $h);
            }
        }
        $ret .= '</tr></tbody></table>';
        $ret .= '<table class="table table-bordered table-striped"><thead><tr><th>Top Sellers</th><th>$</th></tr></thead><tbody>';
        foreach ($best as $b) {
            $ret .= sprintf('<tr><td>%s %s</td><td>%.2f</td></tr>',
                $b['upc'], $b['saleDesc'], $b['ttl']);
        }
        $ret .= '</tbody></table>';
        $ret .= '<table class="table table-bordered table-striped"><thead><tr><th>Gainers</th><th>Last Week</th>
            <th>This Week</th><th>Diff</th></tr></thead><tbody>';
        foreach ($gain as $b) {
            $ret .= sprintf('<tr><td>%s %s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
                $b['upc'], $b['saleDesc'], $b['lastWeekTTL'], $b['thisWeekTTL'], ($b['thisWeekTTL'] - $b['lastWeekTTL']));
        }
        $ret .= '</tbody></table>';
        $ret .= '<table class="table table-bordered table-striped"><thead><tr><th>Droppers</th><th>Last Week</th>
            <th>This Week</th><th>Diff</th></tr></thead><tbody>';
        foreach ($loss as $b) {
            $ret .= sprintf('<tr><td>%s %s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
                $b['upc'], $b['saleDesc'], $b['lastWeekTTL'], $b['thisWeekTTL'], ($b['thisWeekTTL'] - $b['lastWeekTTL']));
        }
        $ret .= '</tbody></table>';
        $ret .= '</div><div class="col-sm-5"><canvas id="hoursLine"></canvas>
            <br /><canvas id="splhLine"></canvas><br />
            <br /><canvas id="bestBar"></canvas><br />
            <br /><canvas id="gainBar"></canvas><br />
            <br /><canvas id="lossBar"></canvas><br />
            </div></div>';

        $lineLabels = json_encode(array_keys($lineData['lastYearSales']));
        $actualData = $this->jsonify($lineData['actualSales']);
        $lyData = $this->jsonify($lineData['lastYearSales']);
        $trendData = $this->jsonify($lineData['smoothSales']);
        $pieData = json_encode(array_values($categorySales));
        $pieLabels = json_encode(array_keys($categorySales));
        $hoursData = $this->jsonify($hours);
        $lyHoursData = $this->jsonify($lyHours);
        $splhData = json_encode($splhData);
        if ($splhData === false) {
            $splhData = '[]';
        }
        $lySplhData = json_encode($lySplhData);
        $bestLabels = json_encode(array_map(function ($i) { return $i['saleDesc']; }, $best));
        $bestData = json_encode(array_map(function($i) { return $i['ttl']; }, $best));
        $gainLabels = json_encode(array_map(function ($i) { return $i['saleDesc']; }, $gain));
        $gainLW = json_encode(array_map(function ($i) { return $i['lastWeekTTL']; }, $gain));
        $gainTW = json_encode(array_map(function ($i) { return $i['thisWeekTTL']; }, $gain));
        $lossLabels = json_encode(array_map(function ($i) { return $i['saleDesc']; }, $loss));
        $lossLW = json_encode(array_map(function ($i) { return $i['lastWeekTTL']; }, $loss));
        $lossTW = json_encode(array_map(function ($i) { return $i['thisWeekTTL']; }, $loss));
        $ret .= <<<HTML
<script type="text/javascript">
function drawCharts() {
    var ctx = document.getElementById('salesLines').getContext('2d');
    var line = new Chart(ctx, {
        type: 'line',
        responsive: false,
        data: {
            datasets: [
                {
                    data: {$actualData},
                    fill: false,
                    label: 'Actual Sales',
                    backgroundColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc",
                    borderColor: "#3366cc"
                },
                {
                    data: {$lyData},
                    fill: false,
                    label: 'Last Year Sales',
                    backgroundColor: "#dc3912",
                    pointBackgroundColor: "#dc3912",
                    pointBorderColor: "#dc3912",
                    borderColor: "#dc3912"
                },
                {
                    data: {$trendData},
                    fill: false,
                    label: 'Trend Sales',
                    backgroundColor: "#ff9900",
                    pointBackgroundColor: "#ff9900",
                    pointBorderColor: "#ff9900",
                    borderColor: "#ff9900"
                }
            ],
            labels: {$lineLabels}
        }
    });

    var ctx2 = document.getElementById('salesPie').getContext('2d');
    var pie = new Chart(ctx2, {
        type: 'pie',
        responsive: false,
        data: {
            datasets: [{
                data: {$pieData},
                backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"]
            }],
            labels: {$pieLabels}
        }
    });

    var ctx3 = document.getElementById('hoursLine').getContext('2d');
    var line = new Chart(ctx3, {
        type: 'line',
        responsive: false,
        data: {
            datasets: [
                {
                    data: {$hoursData},
                    fill: false,
                    label: 'Hours',
                    backgroundColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc",
                    borderColor: "#3366cc"
                },
                {
                    data: {$lyHoursData},
                    fill: false,
                    label: 'Last Year',
                    backgroundColor: "#dc3912",
                    pointBackgroundColor: "#dc3912",
                    pointBorderColor: "#dc3912",
                    borderColor: "#dc3912"
                }
            ],
            labels: {$lineLabels}
        }
    });

    var ctx4 = document.getElementById('splhLine').getContext('2d');
    var line = new Chart(ctx4, {
        type: 'line',
        responsive: false,
        data: {
            datasets: [
                {
                    data: {$splhData},
                    fill: false,
                    label: 'SPLH',
                    backgroundColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc",
                    borderColor: "#3366cc"
                },
                {
                    data: {$lySplhData},
                    fill: false,
                    label: 'Last Year',
                    backgroundColor: "#dc3912",
                    pointBackgroundColor: "#dc3912",
                    pointBorderColor: "#dc3912",
                    borderColor: "#dc3912"
                },
            ],
            labels: {$lineLabels}
        }
    });

    var ctx5 = document.getElementById('bestBar').getContext('2d');
    var bar = new Chart(ctx5, {
        type: 'bar',
        data: {
            labels: {$bestLabels},
            datasets: [{
                label: 'Top Sellers',
                data: {$bestData},
                backgroundColor: "#3366cc",
                pointBackgroundColor: "#3366cc",
                pointBorderColor: "#3366cc",
                borderColor: "#3366cc"
            }]
        }
    });

    var ctx6 = document.getElementById('gainBar').getContext('2d');
    var bar = new Chart(ctx6, {
        type: 'bar',
        data: {
            labels: {$gainLabels},
            datasets: [
                {
                    label: 'This Week',
                    data: {$gainTW},
                    backgroundColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc",
                    borderColor: "#3366cc"
                },
                {
                    label: 'Last Week',
                    data: {$gainLW},
                    backgroundColor: "#dc3912",
                    pointBackgroundColor: "#dc3912",
                    pointBorderColor: "#dc3912",
                    borderColor: "#dc3912"
                }
            ]
        }
    });

    var ctx7 = document.getElementById('lossBar').getContext('2d');
    var bar = new Chart(ctx7, {
        type: 'bar',
        data: {
            labels: {$lossLabels},
            datasets: [
                {
                    label: 'This Week',
                    data: {$lossTW},
                    backgroundColor: "#3366cc",
                    pointBackgroundColor: "#3366cc",
                    pointBorderColor: "#3366cc",
                    borderColor: "#3366cc"
                },
                {
                    label: 'Last Week',
                    data: {$lossLW},
                    backgroundColor: "#dc3912",
                    pointBackgroundColor: "#dc3912",
                    pointBorderColor: "#dc3912",
                    borderColor: "#dc3912"
                }
            ]
        }
    });
}
</script>
HTML;

        $this->addScript($this->config->get('URL') . 'src/javascript/Chart.min.js');
        $this->addOnloadCommand('drawCharts();');

        return $ret;
    }

    private function jsonify($arr)
    {
        $json = array();
        foreach ($arr as $k => $v) {
            if ($v) {
                $json[] = array('x' => $k, 'y'=> $v);
            }
        }
        return json_encode($json);
    }
}

FannieDispatch::conditionalExec();

