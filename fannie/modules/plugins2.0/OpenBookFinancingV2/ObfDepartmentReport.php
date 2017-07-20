<?php

use COREPOS\Fannie\API\lib\Stats;

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
        );
        $categorySales = array();
        $hours = array();
        $lyHours = array();

        $cacheR = $this->connection->execute($cacheP, array($weeks[0], $weeks[1], $this->id));
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

            $trendR = $this->connection->execute($trendP, array($weekID-13, $weekID-1, $cacheW['superID'], $cacheW['catID']));
            $trend = array();
            $tCount = 0;
            while ($trendW = $this->connection->fetchRow($trendR)) {
                $trend[] = array($tCount, $trendW['actual']);
                $tCount++;
            }
            $trend = Stats::removeOutliers($trend);
            $exp = Stats::exponentialFit($trend);
            $lineData['trendSales'][$dates] += exp($exp->a) * exp($exp->b * $tCount);

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
        $ret .= '</div><div class="col-sm-5"><canvas id="hoursLine"></canvas>
            <br /><canvas id="splhLine"></canvas></div></div>';

        $lineLabels = json_encode(array_keys($lineData['lastYearSales']));
        $actualData = $this->jsonify($lineData['actualSales']);
        $lyData = $this->jsonify($lineData['lastYearSales']);
        $trendData = $this->jsonify($lineData['trendSales']);
        $pieData = json_encode(array_values($categorySales));
        $pieLabels = json_encode(array_keys($categorySales));
        $hoursData = $this->jsonify($hours);
        $lyHoursData = $this->jsonify($lyHours);
        $splhData = json_encode($splhData);
        $lySplhData = json_encode($lySplhData);
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
                },
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

