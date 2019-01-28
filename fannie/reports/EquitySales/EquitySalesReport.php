<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EquitySalesReport extends FannieReportPage
{
    public $description = '[Equity Sales Report] View or compare equity sales time range(s).';
    public $report_set = 'Equity Sales';
    public $themed = true;

    protected $title = "Fannie : Equity Sales Report";
    protected $header = "Equity Sales Report";

    protected $report_headers = array('Starting Dates');
    protected $required_fields = array('date1', 'date2');

    protected $sortable = false;
    protected $no_sort_but_style = true;
    protected $new_tablesorter = true;

    public function preprocess()
    {
        parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->addScript('../../src/javascript/Chart.min.js');
            $this->addScript('../../src/javascript/CoreChart.js');
        }

        return true;
    }

    public function report_content() {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div class="row">
                <div class="col-sm-10"><canvas id="dailyCanvas"></canvas></div>
                </div><div class="row">
                <div class="col-sm-10"><canvas id="totalCanvas"></canvas></div>
                </div>';

            $this->addOnloadCommand('chartAll('.(count($this->report_headers)-1).')');
        }

        return $default;
    }

    public function report_description_content()
    {
        $ret = array();
        $ret[] = "
            <button class='btn btn-default RunNew' data-toggle='collapse' data-target='#ifc'>Run New Dates</button>
            <div class='inner-form-contents collapse' id='ifc'>{$this->form_content()}</div>
        ";
        return $ret;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $data = array();
        $store = FormLib::get('store');
        $equityType = FormLib::get('equityType');
        $maxDepth = FormLib::get('depth');
        $depth = 2;
        for ($i=1; $i<13; $i++) {
            ${'date'.$i} = FormLib::get('date'.$i, false);
        }

        /*
            View which Equity Departments:
            1:A, 2:B, 3:A+B (total)
        */
        $includeDepts = "SUM(d.total) AS Equity";
        if ($equityType == 1) {
            $includeDepts = "SUM(CASE WHEN d.department=992 THEN total ELSE 0 END) AS Equity";
        } elseif ($equityType == 2) {
            $includeDepts = "SUM(CASE WHEN d.department=991 THEN total ELSE 0 END) AS Equity";
        }

        $begin = new DateTime($date1); 
        $end = new DateTime($date2);
        $diff = $begin->diff($end);
        $span = $diff->days;
        $end = $end->add(new DateInterval('P1D'));

        $date_selector = ($span < 60) ? 'year(tdate), month(tdate), day(tdate)' : "year(tdate), month(tdate)";
        $day_names = array();
        if ($weekday == 1) {
            $date_selector = $dbc->dayofweek('tdate');

            $timestamp = strtotime('next Sunday');
            for ($i = 1; $i <= 7; $i++) {
                $day_names[$i] = strftime('%a', $timestamp);
                $timestamp = strtotime('+1 day', $timestamp);
            }
        }
        $hour = $dbc->hour('tdate');

        $store_selector = ($store != 0) ? "AND d.store_id = ?" : "AND 0 = ?";

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = "
            SELECT
                $date_selector,
                CONCAT(year(tdate), '-', month(tdate), '-', day(tdate)) as date,
                sum(d.total) AS ttl,
                avg(d.total) AS avg,
                $includeDepts
            FROM $dlog AS d
            WHERE d.department IN (991,992)
                AND d.tdate BETWEEN ? AND ?
                AND d.emp_no <> 1001
                $store_selector
            GROUP BY $date_selector
            ORDER BY $date_selector";
        $args = array($date1.' 00:00:00', $date2.' 23:59:59', $store);
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);
        $temp = array();
        while ($row = $dbc->fetchRow($result)) {
            unset($temp);
            $date = $row['date'];
            $date = new DateTime($date);
            $date = $date->format('Y-m-d');
            $temp[] = $date;
            $temp[] = sprintf('%.2f', $row['Equity']);
            $data[] = $temp;
        }

        if ($span < 60) {
            $this->addMissingDates($begin, $end, $data);
            $this->report_headers[] = "<span class='lightweight'>$date1 | $date2</span>";
        } else {
            $this->report_headers[0] = 'Month';
            $hdate = new DateTime($date1);
            $hdate = $hdate->format('Y');
            $this->report_headers[] = "<span class='lightweight'>$hdate</span>";
        }
        
        //additional queries to find comparable data
        $temp = array();
        $depthRanges = array(
            1 => array(1,2),
            2 => array(3,4),
            3 => array(5,6),
            4 => array(7,8),
            5 => array(9,10),
            6 => array(11,12)
        );
        while ($depth <= $maxDepth) {
            $range1 = $depthRanges[$depth][0];
            $range2 = $depthRanges[$depth][1];
            $tDate1 = ${"date$range1"};
            $tDate2 = ${"date$range2"};
            unset($temp);
            $temp = array();
            $query = "
                SELECT
                    $date_selector,
                    CONCAT(year(tdate), '-', month(tdate), '-', day(tdate)) as date,
                    sum(d.total) AS ttl,
                    avg(d.total) AS avg,
                    $includeDepts
                FROM $dlog AS d
                WHERE d.department IN (991,992)
                    AND d.tdate BETWEEN ? AND ?
                    AND d.emp_no <> 1001
                    $store_selector
                GROUP BY $date_selector
                ORDER BY $date_selector";
            $args = array($tDate1.' 00:00:00', $tDate2.' 23:59:59', $store);
            $prep = $dbc->prepare($query);
            $result = $dbc->execute($prep,$args);
            $tempB = array();
            $tempC = array();
            while ($row = $dbc->fetchRow($result)) {
                unset($tempB);
                $date = $row['date'];
                $date = new DateTime($date);
                $date = $date->format('Y-m-d');
                $tempB[] = $date;
                $tempB[] = sprintf('%.2f', $row['Equity']);
                $tempC[] = $tempB;
            }
            if ($span < 60) {
                $begin = new DateTime($tDate1); 
                $end = new DateTime($tDate2);
                $end = $end->add(new DateInterval('P1D'));
                $this->addMissingDates($begin, $end, $tempC);
            }

            // plug temp values into data
            foreach ($tempC as $k => $row) {
                $data[$k][$depth] = $row[1];
                $data[$k][3+$depth] = $row[3];
            }

            if ($span < 60) {
                $this->report_headers[] = "<span class='lightweight'>$tDate1 | $tDate2</span>";
            } else {
                $hdate = new DateTime($tDate1);
                $hdate = $hdate->format('Y');
                $this->report_headers[] = "<span class='lightweight'>$hdate</span>";
            }
            $tDate1 = $compDate1;
            $tDate2 = $compDate2;
            $depth++;
        }

        if ($span > 60) {
            foreach ($data as $k => $v) {
                $x = substr($data[$k][0], 5, 2);
                $y = new DateTime($data[$k][0]);
                $y->format('F');
                $data[$k][0] = $y->format('F');
            }
        }

        return $data;
    }

    private function addMissingDates($begin, $end, &$array)
    {
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        foreach ($period as $dt) {
            $curDate = $dt->format("Y-m-d");
            $res = $this->searchForDate($curDate, $array);
            if ($res) $array[] = array($res, 0);
        }
    }

    private function searchForDate($date, &$array) {
        foreach ($array as $key => $val) {
            if ($val[0] === $date) {
                return null;
            }
        }
        return $date;
    }

    private function usortByDates(&$array)
    {
        usort($array, function($a, $b) {
            return strtotime($a[0]) - strtotime($b[0]);
        });
    }

    private function rowToRecord($row)
    {
        $maxDepth = FormLib::get('depth');
        $ret = array();
        $ret[] = sprintf('%d/%d/%d', $row[1], $row[2], $row[0]);
        $ret[] = sprintf('%.2f', $row['Equity']);

        return $ret;
    }

    public function calculate_footers($data)
    {
        $maxDepth = FormLib::get('depth');
        $ret = array('Total');
        $sums = array();
        for ($i=1; $i<=$maxDepth; $i++) {
            $sums[] = 0;
        }
        $i = 0;
        foreach ($data as $row) {
            foreach ($sums as $k => $v) {
                $sums[$k] += $row[$k+1];
            }
            $i++;
        }

        foreach ($sums as $k => $v) {
            $ret[] = $sums[$k];
        }
        return $ret;
    }

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return;
        }

        return <<<JAVASCRIPT
function chartAll(totalCol) {
    var xLabels = $('td.reportColumn0').toArray().map(x => x.innerHTML.trim());
    var totals = $('td.reportColumn' + totalCol).toArray().map(x => Number(x.innerHTML.trim()));
    var daily = [];
    var dailyLabels = [];
    for (var i=1; i<=totalCol; i++) {
        dailyLabels.push($('th.reportColumn'+i).first().text().trim());
        var yData = $('td.reportColumn' + i).toArray().map(x => Number(x.innerHTML.trim()));
        daily.push(yData);
    }

    CoreChart.lineChart('dailyCanvas', xLabels, daily, dailyLabels);
}

$(function(){
    $('#addDateRange').click(function(){
        $('.row').each(function(){
            var visible = $(this).is(':visible');
            if (visible == false) {
                var depth = $('#depth').val();
                $('#depth').val(parseInt(depth,10)+1);
                $(this).css('display','block').show();;
                //(depth);
                return false;
            }
        });
    });
});

$('.prevBtn').on('click',function(){
    var fullname = $(this).attr('id');
    var type = fullname.substring(4,fullname.length-1);
    var start = fullname.substring(fullname.length-1);
    var date1 = $('#date'+(start-2)).val();
    date1 = new Date(date1);
    date2 = new Date(date1);
    date3 = new Date(date1);
    switch (type) {
        case 'Week': 
            date1.setDate(date1.getDate() - 6);
            var d = date1.getDate();
            var m = date1.getMonth()+1;
            var y = date1.getFullYear();
            var newdate = y+'-'+m+'-'+d;
            break;
        case 'Month': 
            date2.setMonth(date2.getMonth() - 1);
            var d = date2.getDate();
            var m = date2.getMonth()+1; var y = date2.getFullYear();
            var newdate = y+'-'+m+'-'+d;
            break;
        case 'Year': 
            date3.setFullYear(date3.getFullYear() - 1);
            var d = date3.getDate();
            var m = date3.getMonth()+1;
            var y = date3.getFullYear();
            var newdate = y+'-'+m+'-'+d;
            break;
    }
    var newend = $('#date'+(start-2)).val();
    newend = new Date(newend);
    newend.setDate(newend.getDate()-1);
    d = newend.getDate()+1;
    m = newend.getMonth()+1;
    y = newend.getFullYear();
    newend = y+'-'+m+'-'+d;
    var end = parseInt(start,10) + 1;
    $('#date'+start).val(newdate);
    $('#date'+end).val(newend);
});
JAVASCRIPT;
    }

    public function form_content()
    {
        $storepicker = FormLib::storePicker();
        $store = FormLib::get('store');
        $datepicker = FormLib::date_range_picker();
        $equityType = FormLib::get('equityType');

        $equityoptions = array('', 'View Equity A', 'View Equity B', 'View A&B Combined',);
        $equitypicker = '<select class="form-control" name="equityType">';
        for ($i=1; $i<4; $i++) {
            $option = $equityoptions[$i];
            $sel = ($equityType == $i) ? 'selected' : '';
            $equitypicker .= "<option value=$i $sel>$option</option>"; 
        }
        $equitypicker .= "</select>";

        $nums = range('1','10');
        $wordyNums = array(1=>'1st', 2=>'2nd', 3=>'3rd', 4=>'Fourth', 5=>'Fifth', 6=>'Sixth');
        $depthcontent = '';
        foreach ($nums as $num) {
            $s = ($num == 1) ? 'selected' : '';
            $depthcontent .= "<option value='$num' $s>$num</option>"; 
        }
        foreach ($_GET as $k => $v) {
            ${$k} = $v; 
        }

        $formInput = '';
        $d1 = 1;
        $d2 = 2;
        for ($i=1; $i<7; $i++) {
            if ($i == 1) {
                $formInput .= sprintf('
            <div class="panel panel-default">
                <div class="panel-heading"><b>%s</b> Date Range</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="text" name="date1" id="date1" class="form-control date-field" value="%s"required/>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="text" name="date2" id="date2" class="form-control date-field" value="%s" required/>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Store</label>
                                %s
                            </div>
                            <div class="form-group">
                                <label>Equity Types</label>
                                %s
                            </div>
                        </div>
                        <div class="col-md-12">
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <a class="btn btn-default btn-xs" id="addDateRange"><b>+</b> Add Another Range to Compare</a>
                            </div>
                        </div>
                    </div>
                    ',
                    $wordyNums[$i],
                    $date1,
                    $date2,
                    $storepicker['html'],
                    $equitypicker
                );
            } else {
                $d1 += 2; $d2 += 2;
                $hide = ($i > 1) ? 'collapse' : '';
                $require = ($i < 2) ? 'required' : '';
                $formInput .= sprintf ('
        <div class="box">
            <div class="row %s">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading"><b>%s</b> Date Range</div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="text" name="date%d" id="date%d" value="%s" class="form-control date-field" %s/>
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="text" name="date%d" id="date%d" value="%s" class="form-control date-field" %s/>
                                    </div>
                                    <table><tr>
                                        <!--
                                        <td class="btn btn-default btn-xs prevBtn" id="prevWeek%d">Previous Week</p></td>
                                        <td class="btn btn-default btn-xs prevBtn" id="prevMonth%d">Previous Month</p></td>
                                        <td class="btn btn-default btn-xs prevBtn" id="prevYear%d">Previous Year<p></td></tr>
                                        -->
                                    </table>
                                </div>
                                <div class="col-md-6">
                                </div>
                                <div class="col-md-12"></div>
                            </div>
                            <div class="col-md-6">
                            </div>
                        </div>
                    </div>
                </div>
            </div>',
                    $hide, 
                    $wordyNums[$i],
                    $d1, $d1, 
                    ${"date$d1"},
                    $require, $d2, $d2, 
                    ${"date$d2"},
                    $require, $d1, $d1, $d1
                );
            }

        if ($i == 1) {
            $formInput .= <<<HTML
            <div class="col-md-6">
                <div class="col-md-12">
                    <div class="form-group">
                        $datepicker
                    </div>
                </div>
            </div>
        </div>
HTML;
        }
        $formInput .= <<<HTML
    </div>
HTML;

        }

        return <<<HTML
<form method="get" id="form1">
    <input type="hidden" name="depth" id="depth" value="1"/>
    <div>
    $formInput
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Generate New Report</button>
    </div>
</form>
HTML;
    }

    public function css_content()
    {
        return <<<CSS
.form-group  {
    margin: 15px;
}
.btn-primary {
    margin-bottom: 15px;
}
button.RunNew {
    border: 5px solid lightgrey;
    border-radius: 0px;
}
.inner-form-contents {
    border: 5px solid lightgrey;
    background: lightgrey;
    display: block;
    display: none;
    overflow: auto;
}
.box {
    max-width: 350px;
    float: left;
    padding-right: 15px;
}
span.lightweight {
    font-weight: normal;
}
.btn-p {
    padding-top: 10px;
    padding-left: 20px;
}
CSS;
    }

    public function helpContent()
    {
        return '<p>
            Compare equity sales for <i>n</i> time periods.
            <ul>
                <li><strong>Start Date, End Date</strong>: Choose a 
                    range of time with which to check sales.</li>
                <li><strong>Store</strong>: Choose a store to report 
                    sales for.</li>
                <li><strong>Equity Sales</strong>: Choose which transaction 
                    types to include.</li>
                <li><strong>+ Add Another Range to Compare</strong>: Click 
                    this button to add [an] additional date range(s).
                    Ranges will line up starting by each <i>Start Date</i> chosen.
                <li><strong>"X-Axis" Table-labels:</strong> 
                    <ul>
                        <li>If less than 2 months are selected, data will be
                            displayed by date.</li> 
                        <li>If 2 months or more are chosen, data will be 
                            displayed by Month.</li>
                    </ul>
            </ul>
        </p>';
    }
}

FannieDispatch::conditionalExec();

