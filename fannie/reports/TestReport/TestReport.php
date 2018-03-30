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

class TestReport extends FannieReportPage
{
    public $description = '[Test Report] is not a "real" report.';
    public $report_set = 'Testing';
    public $themed = true;

    protected $title = "Fannie : Test Report";
    protected $header = "Test Report";

    protected $report_headers = array('Selected Date Range','Selected Range Sales');
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
        $ret[] = $this->fetch_adjacent_period();
        return $ret;
    }

    private function fetch_adjacent_period()
    {
        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        $temp1 = new DateTime($date1);
        $temp2 = new DateTime($date2);
        $diff = $temp1->diff($temp2, true);
        $days = $diff->format('%a');

        $formContents = array('store','equityType','compare');
        foreach ($formContents as $input) {
            ${$input} = FormLib::get($input);
        }

        $actions = array(
            'increment' => array(
                'action' => '+',
                'glyph' => 'right',
            ),
            'decrement' => array(
                'action' => '-',
                'glyph' => 'left',
            ),
        );
        foreach ($actions as $k => $row) {
            if ($days == 0) {
                $temp1->modify($row['action'].'1 Day');
                $temp2->modify($row['action'].'1 Day');
                $newDate1 = $temp1->format('Y-m-d');
                $newDate2 = $temp2->format('Y-m-d');
            } elseif ($days == 6) {
                $temp1->modify($row['action'].'1 Week');
                $temp2->modify($row['action'].'1 Week');
                $newDate1 = $temp1->format('Y-m-d');
                $newDate2 = $temp2->format('Y-m-d');
            } else {
                // assumes user in month view
                $temp1->modify($row['action'].'1 Month');
                $temp2->modify($row['action'].'31 Days');
                $newDate1 = $temp1->format('Y-m-d');
                $newDate2 = $temp2->format('Y-m-t');
            }
            ${'form'.$k} = '<form method="get" style="display: inline-block;">';
            foreach ($formContents as $input) {
                ${'form'.$k} .= "<input type='hidden' name='$input' value='${$input}'>";
            }
            ${'form'.$k} .= "<input type='hidden' name='date1' value='$newDate1'>";
            ${'form'.$k} .= "<input type='hidden' name='date2' value='$newDate2'>";
            ${'form'.$k} .= '<button class="btn btn-default btn-xs"><span class="glyphicon glyphicon-chevron-'.$row['glyph'].'"></span></button>';
            ${'form'.$k} .= '</form>';
        }

        return <<<HTML
<div><label>Change Period</label>: $formdecrement | $formincrement</div>
<div class="text-danger">Note: previous period button does not work and I don't know why. -Corey</div>
HTML;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        $store = FormLib::get('store');
        $equityType = FormLib::get('equityType');
        $compare = FormLib::get('compare');
        $maxDepth = FormLib::get('depth');
        $depth = 1;

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

        $date_selector = 'year(tdate), month(tdate), day(tdate)';
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

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $data = array();
        $query = "
            SELECT
                $date_selector,
                sum(d.total) AS ttl,
                avg(d.total) AS avg,
                $includeDepts
            FROM $dlog AS d
            WHERE d.department IN (991,992)
                AND d.tdate BETWEEN ? AND ?
                AND d.emp_no <> 1001
            GROUP BY $date_selector
            ORDER BY $date_selector";
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $prep = $dbc->prepare($query);
        var_dump($prep);
        $result = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }
        
        //additional query(-ies) to find comparable data
        $temp = array();
        $tDate1 = $date1;
        $tDate2 = $date2;
        while ($depth <= $maxDepth) {
            unset($temp);
            $temp = array();
            list($compDate1,$compDate2) = $this->fetch_depth_data($tDate1,$tDate2,$depth);
            $query = "
                SELECT
                    $date_selector,
                    sum(d.total) AS ttl,
                    avg(d.total) AS avg,
                    $includeDepts
                FROM $dlog AS d
                WHERE d.department IN (991,992)
                    AND d.tdate BETWEEN ? AND ?
                    AND d.emp_no <> 1001
                GROUP BY $date_selector
                ORDER BY $date_selector";
            $args = array($compDate1.' 00:00:00', $compDate2.' 23:59:59');
            $prep = $dbc->prepare($query);
            $result = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($result)) {
                $temp[] = $this->rowToRecord($row);
            }

            // plug temp values into data
            foreach ($temp as $k => $row) {
                if ($compare == 1) {
                    $data[$k][0] .= " - $row[0]";
                }
                $data[$k][1+$depth] = $row[1];
                $data[$k][3+$depth] = $row[3];
            }
            if ($compare == 2) {
                $today = new DateTime();
                $today->modify("- $depth Year");
                $this->report_headers[] = "Sales From ".$today->format('Y');
            } else {
                $this->report_headers[] = 'Previous Period';
            }
            $tDate1 = $compDate1;
            $tDate2 = $compDate2;
            $depth++;
        }


        return $data;
    }

    private function fetch_adjacent_data($date1,$date2)
    {
        $temp1 = new DateTime($date1);
        $temp2 = new DateTime($date2);
        $diff = $temp1->diff($temp2, true);
        $days = $diff->format('%a');

        if ($days == 0) {
            $temp1->modify('- 1 Day');
            $temp2->modify('- 1 Day');
            $newDate1 = $temp1->format('Y-m-d');
            $newDate2 = $temp2->format('Y-m-d');
        } elseif ($days == 6) {
            $temp1->modify('- 1 Week');
            $temp2->modify('- 1 Week');
            $newDate1 = $temp1->format('Y-m-d');
            $newDate2 = $temp2->format('Y-m-d');
        } else {
            // assumes we're probably looking at a month
            $temp1->modify('- 1 Month');
            $temp2->modify('- 31 Days');
            $newDate1 = $temp1->format('Y-m-d');
            $newDate2 = $temp2->format('Y-m-t');
        }

        return array($newDate1,$newDate2);
    }

    private function fetch_depth_data($date1,$date2,$depth)
    {
        $temp1 = new DateTime($date1);
        $temp2 = new DateTime($date2);
        $diff = $temp1->diff($temp2, true);
        $days = $diff->format('%a');

        if ($compare == 2) {
            $temp1->modify("- $depth Year");
            $temp2->modify("- $depth Year");
            $newDate1 = $temp1->format('Y-m-d');
            $newDate2 = $temp2->format('Y-m-d');
        } else {
            if ($days == 0) {
                $temp1->modify('- 1 Day');
                $temp2->modify('- 1 Day');
                $newDate1 = $temp1->format('Y-m-d');
                $newDate2 = $temp2->format('Y-m-d');
            } elseif ($days == 6) {
                $temp1->modify('- 1 Week');
                $temp2->modify('- 1 Week');
                $newDate1 = $temp1->format('Y-m-d');
                $newDate2 = $temp2->format('Y-m-d');
            } else {
                //default compare is by month
                $temp1->modify('- 1 Month');
                $temp2->modify('- 31 Days');
                $newDate1 = $temp1->format('Y-m-d');
                $newDate2 = $temp2->format('Y-m-t');
            }
        }
        
        return array($newDate1,$newDate2);
    }

    private function rowToRecord($row)
    {
        $compare = FormLib::get('compare');
        $maxDepth = FormLib::get('depth');
        $ret = array();
        if ($compare == 1) {
            $ret[] = sprintf('%d/%d/%d', $row[1], $row[2], $row[0]);
            $ret[] = sprintf('%.2f', $row['Equity']);
        } else {
            $ret[] = sprintf('%d/%d/%d', $row[1], $row[2], $row[0]);
            $ret[] = sprintf('%.2f', $row['Equity']);
        }

        return $ret;
    }

    public function calculate_footers($data)
    {
        $maxDepth = FormLib::get('depth');
        $ret = array('Total');
        $sums = array();
        for ($i=0; $i<=$maxDepth; $i++) {
            $sums[] = 0;
        }
        //$sums = array(0, 0, 0, 0);
        $i = 0;
        foreach ($data as $row) {
            foreach ($sums as $k => $v) {
                $sums[$k] += $row[$k+1];
            }
            /*$sums[0] += $row[1];
            $sums[1] += $row[2];
            $sums[2] += $row[3];
            $sums[3] += $row[4];*/
            $i++;
        }
        //$sums[2] = ($sums[2] / $i);
        //$sums[3] = ($sums[3] / $i);

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
                $(this).css('display','block').show();;
                return false;
            }
        });
    });
});

$('.prevBtn').on('click',function(){
    var fullname = $(this).attr('id');
    var type = fullname.substring(4,fullname.length-1);
    var start = fullname.substring(fullname.length-1);
});
JAVASCRIPT;
    }

    public function form_content()
    {
        $ret = FormLib::storePicker();
        $datepicker = FormLib::date_range_picker();
        $equitypicker = <<<HTML
<select class="form-control" name="equityType">
    <option value="1">View Equity A</option>
    <option value="2">View Equity B</option>
    <option value="3">View A&B Combined</option>
</select>
HTML;
        $nums = range('1','10');
        $wordyNums = array(1=>'First', 2=>'Second', 3=>'Third', 4=>'Fourth', 5=>'Fifth', 6=>'Sixth');
        $depthcontent = '';
        foreach ($nums as $num) {
            $s = ($num == 1) ? 'selected' : '';
            $depthcontent .= "<option value='$num' $s>$num</option>"; 
        }

        $formInput = '';
        $d1 = 1;
        $d2 = 2;
        for ($i=1; $i<7; $i++) {
            if ($i == 1) {
                $formInput .= sprintf('
            <div class="panel panel-default">
                <div class="panel-heading"><i>%s</i> Date Range</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="text" name="date1" id="date1" class="form-control date-field" required/>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="text" name="date2" id="date2" class="form-control date-field" required/>
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
                            <div class="form-group">
                                <label title="Date range must be greater than 1 month">Label Data In</label>
                                <select name="viewBy" id="viewBy" class="form-control">
                                    <option value="day" selected>Days</option>
                                    <option value="month">Months</option>
                                </select>
                            </div>
                        </div>
                    </div>',
                    $wordyNums[$i],
                    $ret['html'],
                    $equitypicker
                );
            } else {
                $d1 += 2; $d2 += 2;
                $hide = ($i > 1) ? 'collapse' : '';
                $require = ($i < 2) ? 'required' : '';
                $formInput .= sprintf ('
            <div class="row %s">
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading"><i>%s</i> Date Range</div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="text" name="date%d" id="date%d" class="form-control date-field" %s/>
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="text" name="date%d" id="date%d" class="form-control date-field" %s/>
                                    </div>
                                    <table><tr>
                                        <td class="btn btn-default btn-xs prevBtn" id="prevWeek%d">Previous Week</p></td>
                                        <td class="btn btn-default btn-xs prevBtn" id="prevMonth%d">Previous Month</p></td>
                                        <td class="btn btn-default btn-xs prevBtn" id="prevYear%d">Previous Year<p></td></tr>
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
                </div>',
                    $hide, $wordyNums[$i],
                    $d1, $d1, $d2, $d2, $require, $require, $d1, $d1, $d1
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
    $formInput
    <a href='#' id="addDateRange"><b>+</b> Add Another Range to Compare</a>
    <p></p>
    <p>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </p>
</form>
HTML;
    }

    public function css_content()
    {
        return <<<CSS
.btn-p {
    padding-top: 10px;
    padding-left: 20px;
}
CSS;
    }

    public function helpContent()
    {
        return '<p>Show Equity sales by day, week, month
            or year and compare against selected period.';
    }
}

FannieDispatch::conditionalExec();

