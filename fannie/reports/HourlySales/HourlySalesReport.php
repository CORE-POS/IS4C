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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class HourlySalesReport extends FannieReportPage 
{
    public $description = '[Hourly Sales] lists sales per hour over a given date range.';
    public $report_set = 'Sales Reports';
    public $themed = true;

    protected $title = "Fannie : Hourly Sales Report";
    protected $header = "Hourly Sales";

    protected $required_fields = array('date1', 'date2');

    protected $sortable = false;
    protected $no_sort_but_style = true;
    protected $new_tablesorter = true;

    public function preprocess()
    {
        parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->add_script('../../src/javascript/d3.js/d3.v3.min.js');
            $this->add_script('../../src/javascript/d3.js/charts/singleline/singleline.js');
            $this->add_css_file('../../src/javascript/d3.js/charts/singleline/singleline.css');
        }

        return true;
    }

    public function report_description_content()
    {
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $deptMulti = FormLib::get('departments', array());
        $weekday = FormLib::get('weekday', 0);
        $buyer = FormLib::get('buyer', '');
    
        $ret = array();
        if ($buyer === '') {
            $ret[] = 'Department '.$deptStart.' to '.$deptEnd;
        } else if ($buyer == -1) {
            $ret[] = 'All Super Departments';
        } else if ($buyer == -2) {
            $ret[] = 'All Retail Super Departments';
        } else {
            $ret[] = 'Super Department '.$buyer;
        }

        if ($weekday == 1) {
            $ret[] = 'Grouped by weekday';
        }

        if ($this->report_format == 'html') {
            $ret[] = sprintf(' <a href="../HourlyTrans/HourlyTransReport.php?%s">Transaction Counts for Same Period</a>', 
                            filter_input(INPUT_SERVER, 'QUERY_STRING'));
        }

        return $ret;
    }

    public function report_content() {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div id="chartArea" style="border: 1px solid black;padding: 2em;">';
            $default .= 'Graph: <select onchange="showGraph(this.value);">';
            for ($i=count($this->report_headers)-1; $i >= 1; $i--) {
                $default .= sprintf('<option value="%d">%s</option>',
                                $i, $this->report_headers[$i]);
            }
            $default .= '</select>';
            $default .= '<div id="chartDiv"></div>';
            $default .= '</div>';

            $this->add_onload_command('showGraph('.(count($this->report_headers)-1).')');
        }

        return $default;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $deptMulti = FormLib::get('departments', array());
        $weekday = FormLib::get('weekday', 0);
        $store = FormLib::get('store', 0);
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer == -2) {
                $where .= ' AND s.superID != 0 ';
            } elseif ($buyer != -1) {
                $where .= ' AND s.superID=? ';
                $args[] = $buyer;
            }
        }
        if ($buyer != -1) {
            list($conditional, $args) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $args);
            $where .= $conditional;
        }
        $args[] = $store;

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

        $query = "SELECT $date_selector, $hour as hour, 
                    sum(d.total) AS ttl, avg(d.total) as avg
                  FROM $dlog AS d ";
        // join only needed with specific buyer
        // or all retail
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON d.department=s.dept_ID ';
        } elseif ($buyer !== '' && $buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON d.department=s.dept_ID ';
        }
        $query .= "WHERE d.trans_type IN ('I','D')
                    AND d.tdate BETWEEN ? AND ?
                    AND $where ";
        if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
            $query .= ' AND d.department NOT IN (993, 998, 703) ';
        }
        $query .= " AND " . DTrans::isStoreID($store, 'd') . "
                   GROUP BY $date_selector, $hour
                   ORDER BY $date_selector, $hour";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($query, $args);

        $dataset = array();
        $minhour = 24;
        $maxhour = 0;
        while($row = $dbc->fetch_row($result)) {
            $hour = (int)$row['hour'];

            $date = '';
            if ($weekday == 1) {
                $date = $day_names[$row[0]];
            } else {
                $date = sprintf('%d/%d/%d', $row[1], $row[2], $row[0]);
            }
            
            if (!isset($dataset[$date])) {
               $dataset[$date] = array(); 
            }

            $dataset[$date][$hour] = $row['ttl'];

            if ($hour < $minhour) {
                $minhour = $hour;
            }
            if ($hour > $maxhour) {
                $maxhour = $hour;
            }
        }

        if (isset($dataset['Sun'])) {
            $sunday = $dataset['Sun'];
            unset($dataset['Sun']);
            $dataset['Sun'] = $sunday;
        }

        /**
          # of columns is dynamic depending on the
          date range selected
        */
        $this->report_headers = array('Day');
        foreach($dataset as $day => $info) {
            $this->report_headers[] = $day; 
        }
        $this->report_headers[] = 'Total';

        $data = array();
        /**
          # of rows is dynamic depending when
          the store was open
        */
        for($i=$minhour; $i<=$maxhour; $i++) {
            $record = array();
            $sum = 0;

            if ($i < 12) {
                $record[] = str_pad($i,2,'0',STR_PAD_LEFT).':00 AM';
            } else if ($i == 12) {
                $record[] = $i.':00 PM';
            } else {
                $record[] = str_pad(($i-12),2,'0',STR_PAD_LEFT).':00 PM';
            }

            // each day's sales for the given hour
            foreach($dataset as $day => $info) {
                $sales = isset($info[$i]) ? $info[$i] : 0;
                $record[] = sprintf('%.2f', $sales);
                $sum += $sales;
            }

            $record[] = sprintf('%.2f', $sum);
            $data[] = $record;
        }
        
        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }

        $ret = array('Totals');
        for($i=1; $i<count($data[0]); $i++) {
            $ret[] = 0.0;
        }

        foreach($data as $row) {
            for($i=1; $i < count($row); $i++) {
                $ret[$i] += $row[$i];
            }
        }

        for($i=1; $i<count($ret); $i++) {
            $ret[$i] = sprintf('%.2f', $ret[$i]); 
        }

        return $ret;
    }

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return;
        }

        ob_start();
        ?>
function showGraph(i) {
    $('#chartDiv').html('');

    var ymin = 999999999;
    var ymax = 0;
    var xmin = 999999999;
    var xmax = 0;

    var ydata = Array();
    $('td.reportColumn'+i).each(function(){
        var y = Number($(this).html());
        ydata.push(y);
        if (y > ymax) {
            ymax = y;
        }
        if (y < ymin) {
            ymin = y;
        }
    });

    var xdata = Array();
    $('td.reportColumn0').each(function(){
        var hour = $(this).html().trim().substring(0,2);
        if (hour.charAt(0) == '0') {
            hour = hour.charAt(1);
        }
        hour = Number(hour);
        if ($(this).html().indexOf('PM') != -1 && hour < 12) {
            hour += 12;
        }
        xdata.push(hour);

        if (hour > xmax) {
            xmax = hour;
        }
        if (hour < xmin) {
            xmin = hour;
        }
    });

    var data = Array();
    for (var i=0; i < xdata.length; i++) {
        data.push(Array(xdata[i], ydata[i]));
    }

    singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
}
        <?php
        return ob_get_clean();
    }

    public function form_content()
    {
        ob_start();
        ?>
<form method="get" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <?php echo FormLib::standardDepartmentFields('buyer'); ?>
        <div class="form-group">
            <label class="col-sm-4 control-label">
                Group by weekday?
                <input type=checkbox name=weekday value=1>
            </label>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Save to Excel
                <input type=checkbox name=excel id=excel value=1>
            </label>
            <label class="col-sm-4 control-label">Store</label>
            <div class="col-sm-4">
                <?php $ret=FormLib::storePicker();echo $ret['html']; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type=text id=date2 name=date2 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>                            
        </div>
    </div>
</div>
    <p>
        <button type=submit name=submit value="Submit" class="btn btn-default btn-core">Submit</button>
        <button type=reset name=reset class="btn btn-default btn-reset"
            onclick="$('#super-id').val('').trigger('change');">Start Over</button>
    </p>
</form>
        <?php
        $this->addOnloadCommand("\$('#subdepts').closest('.form-group').hide();");

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This report shows hourly sales over a range of dates.
            The rows are always hours. The columns are either calendar
            dates or named weekdays (e.g., Monday, Tuesday) if grouping
            by week day.</p>
            <p>If a <em>Buyer/Dept</em> option is used, the result will
            be sales from that super department. Otherwise, the result
            will be sales from the specified department range. Note there
            are a couple special options in the <em>Buyer/Dept</em> list:
            <em>All</em> is simply all sales and <em>All Retail</em> is
            everything except for super department #0 (zero).</p>';
    }
}

FannieDispatch::conditionalExec();

