<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Show total sales by hour for today from dlog.
 * Offer dropdown of superdepartments and, on-select, display the same report for
 *  that superdept only.
 * This page extends FanniePage because it is simpler than most reports
 *  and would be encumbered by the FannieReportPage structure.
*/

use COREPOS\Fannie\API\data\DataCache;
use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../config.php');
include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');

class SalesTodayReport2 extends \COREPOS\Fannie\API\FannieReportTool 
{
    public $description = '[Today\'s Sales] shows current day totals by hour.';
    public $report_set = 'Sales Reports';

    protected $selected = -1;
    protected $store = 0;
    protected $name = "";
    protected $supers = array();

    public function preprocess()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $this->form = new COREPOS\common\mvc\FormValueContainer();
        try {
            $this->store = $this->form->store;
        } catch (Exception $ex) { 
            $this->store = Store::getIdByIp();
        }


        $this->title = "Fannie : Today's Sales";
        $this->header = '';

        $this->addScript($this->config->get('URL').'src/javascript/Chart.min.js');
        $this->addScript('stChart.js?date=20171121');

        return true;

    // preprocess()
    }

    public function body_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $transDB = $this->config->get('TRANS_DB') . $dbc->sep();

        // hourly sales today
        $query = "
            SELECT SUM(total) AS ttl,
                ". $dbc->hour('tdate') . " AS hr
            FROM {$transDB}dlog AS d
                LEFT JOIN MasterSuperDepts AS t ON d.department = t.dept_ID
            WHERE t.superID > 0 
                AND " . DTrans::isStoreID($this->store, 'd') . "
                AND d.trans_type IN ('I','D','M')
            GROUP BY " . $dbc->hour('tdate') . "
            ORDER BY " . $dbc->hour('tdate');
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($this->store));
        $today = array();
        while ($row = $dbc->fetchRow($res)) {
            $today[] = $row;
        }

        $cache = DataCache::check('SameWeekdaySales');
        if (!is_array($cache)) {
            $cache = array();
        }
        if (isset($cache[$this->store])) {
            $avg = $cache[$this->store]['avg'];
            $lastWeek = $cache[$this->store]['lastweek'];
        } else {
            $query = "
                SELECT SUM(total) AS ttl,
                    ". $dbc->hour('tdate') . " AS hr
                FROM {$transDB}dlog_90_view AS d
                    LEFT JOIN MasterSuperDepts AS t ON d.department = t.dept_ID
                WHERE t.superID > 0 
                    AND " . DTrans::isStoreID($this->store, 'd') . "
                    AND d.trans_type IN ('I','D','M')
                    AND d.tdate BETWEEN ? AND ?
                GROUP BY " . $dbc->hour('tdate') . "
                ORDER BY " . $dbc->hour('tdate');
            $prep = $dbc->prepare($query);
            $lastWeek = array();
            $avg = array();
            for ($i=1; $i<=5; $i++) {
                $date = date('Y-m-d', strtotime($i*7 . ' days ago'));
                $res = $dbc->execute($prep, array($this->store, $date . ' 00:00:00', $date . ' 23:59:59'));
                while ($row = $dbc->fetchRow($res)) {
                    if ($i == 1) {
                        $lastWeek[] = array('hr' => $row['hr'], 'ttl' => $row['ttl']);
                    }
                    if (!isset($avg[$row['hr']])) {
                        $avg[$row['hr']] = array('hr' => $row['hr'], 'ttl'=>0);
                    }
                    $avg[$row['hr']]['ttl'] += $row['ttl'];
                }
            }
            $avg = array_map(function ($i) { return array('hr'=>$i['hr'], 'ttl'=>$i['ttl']/5); }, array_values($avg));
            $cache[$this->store] = array('avg'=>$avg, 'lastweek'=>$lastWeek);
            DataCache::freshen($cache, 'day', 'SameWeekdaySales');
        }
        include(__DIR__ . '/feed.php');
        $feedCache = '/tmp/feed.ics';
        if (!file_exists($feedCache) || (time() - filemtime($feedCache)) > (60*60)) {
            $fp = fopen($feedCache, 'w');
            $ch = curl_init($feed);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $success = curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
        $cal = '';
        try {
            $fp = fopen($feedCache, 'r');
            $document = Sabre\VObject\Reader::read($fp, Sabre\VObject\Reader::OPTION_FORGIVING);
            $events = $document->getBaseComponents('VEvent');
            $todayDT = new DateTime(date('Y-m-d'));
            $counter = 0;
            foreach ($events as $event) {
                $start = $event->DTSTART->getDateTime();
                if ($start < $todayDT) continue;
                $end = $event->DTEND->getDateTime();

                $startDay = $start->format('D M jS');
                $startTime = $start->format('g:ia');
                $endDay = $end->format('D M jS');
                $endTime = $end->format('g:ia');

                $highlight = '';
                if ($startDay == $todayDT->format('D M jS')) {
                    $highlight = 'class="alert-info"';
                }

                $cal .= '<p ' . $highlight . '>';
                $event->SUMMARY = str_replace('Copy: ', '', $event->SUMMARY);
                $cal .= '<strong>' . $event->SUMMARY . '</strong><br />';

                if ($startDay == $endDay) {
                    $cal .= $startDay . ' ' . $startTime . ' - ' . $endTime . '<br />';
                } else {
                    $cal .= $startDay . ' ' . $startTime . ' - ' . $endDay . ' ' . $endTime . '<br />';
                }
                if ($event->LOCATION) {
                    $cal .= 'Location: ' . $event->LOCATION . '<br />';
                }
                if ($event->DESCRIPTION) {
                    //$cal .= nl2br(trim($event->DESCRIPTION)) . '<br />';
                }
                $cal .= '</p>';

                $counter++;
                if ($counter >= 3) break;
            }
        } catch (Exception $ex) {
            // calendar was unavailable
        }

        ob_start();
        echo '<div class="row">';
        echo '<div class="col-sm-6">';
        echo "<div class=\"text-center \"><h1>Today's <span style=\"color:green;\">$this->name</span> Sales!</h1>";
        echo "<table class=\"table table-bordered no-bs-table\">"; 
        echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
        $sum = 0;
        foreach ($today as $row) {
            printf("<tr class=\"datarow\"><td class=\"x-data\">%d</td><td class=\"y-data text-right\">%.2f</td></tr>",
                $row[1], $row[0]);
            $sum += $row[0];
        }
        echo "<tr><th width=60px class='text-left'>Total</th><td class='text-right'>";
        echo number_format($sum,2);
        echo "</td></tr></table>";

        $stores = FormLib::storePicker();
        echo '<div class="form-group form-inline">For: '
            . $stores['html'] . 
            '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-sm-5">';
        if ($cal) {
            $cal = '<h2>Upcoming Events</h2>' . $cal;
        }
        //echo $cal;
        echo '</div>';
        echo '</div>';

        echo '<div id="chartDiv"><canvas id="chartCanvas"></canvas></div>';

        $mapper = function($i) { return array('x'=>(int)$i['hr'], 'y'=>$i['ttl']); };
        $filter = function ($i) { return $i['x'] >= 9; };
        $points = array(
            'avg' => array_map($mapper, $avg),
            'today' => array_map($mapper, $today),
            'lastWeek' => array_map($mapper, $lastWeek),
        );
        $points['avg'] = array_values(array_filter($points['avg'], $filter));
        $points['today'] = array_values(array_filter($points['today'], $filter));
        $points['lastWeek'] = array_values(array_filter($points['lastWeek'], $filter));
        $labels = json_encode(array_map(function ($i) { return $i['x']; }, $points['avg']));
        $points = json_encode($points);
        $dayName = json_encode(date('D'));
        $this->addOnloadCommand("stChart.lineChart('chartCanvas', {$labels}, {$points}, {$dayName});");
        $this->addOnloadCommand("\$('select[name=store]').change(function() { location='?store='+this.value; });");

        echo '</div>';

        return ob_get_clean();
    // body_content()
    }

    public function css_content()
    {
        return <<<CSS
.no-bs-table {
    width: auto !important;
    margin-left: auto;
    margin-right: auto;
}
CSS;
    }

    public function helpContent()
    {
        return '<p>Hourly Sales for the current day. The drop down menu
            can switch the report to a single super department.</p>';
    }

// SalesTodayReport
}

FannieDispatch::conditionalExec();

