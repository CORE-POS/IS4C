<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class HourlyMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie :  Movement Report";
    protected $header = "Movement Report";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Hourly Movement] shows movement for items by hour reduced';
    public $report_set = 'Movement Reports';
    protected $report_headers = array('Hour');

    public function report_description_content()
    {
        if ($this->report_format != 'html') {
            return array();
        }

        $url = $this->config->get('URL');
        $this->addScript($url . 'src/javascript/jquery.js');
        $this->addScript($url . 'src/javascript/jquery-ui.js');
        $this->addCssFile($url . 'src/javascript/jquery-ui.css');

        $dates_form = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
        foreach ($_GET as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store' && $key != 'mode' && $key != 'weekday') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'date1' && $key != 'date2' && $key != 'store' && $key != 'mode' && $key != 'weekday') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        $stores = FormLib::storePicker();
        $mode = FormLib::get('mode', 'Dollars');
        $weekday = FormLib::get('weekday', false);
        $dates_form .= '
            <label>Start Date</label>
            <input class="date-field" type="text" name="date1" value="' . FormLib::get('date1') . '" /> 
            <label>End Date</label>
            <input class="date-field" type="text" name="date2" value="' . FormLib::get('date2') . '" /> 
            <input type="hidden" name="excel" value="" id="excel" />
            <select name="mode">
                <option value="Dollars" ' . ($mode == 'Dollars' ? 'selected' : '') . '>In Dollars</option>
                <option value="Quantity" ' . ($mode != 'Dollars' ? 'selected' : '') . '>In Quantity</option>
            </select>
            <select name="weekday">
                <option value="0" ' . ($weekday ? '' : 'selected') . '>By Date</option>
                <option value="1" ' . ($weekday ? 'selected' : '') . '>By Weekday</option>
            </select>
            ' . $stores['html'] . '
            <button type="submit" onclick="$(\'#excel\').val(\'\');return true;">Change Dates</button>
            <button type="submit" onclick="$(\'#excel\').val(\'csv\');return true;">Download</button>
            </form>
            <style type="text/css">
            .ui-datepicker {
                z-index: 999 !important;
            }
            </style>';

        $this->add_onload_command("\$('.date-field').datepicker({dateFormat:'yy-mm-dd'});");
        
        return array($dates_form);
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = '';
        $weekday = FormLib::get('weekday', false);
        $mode = FormLib::get('mode', 'Dollars');
        $from_where = FormLib::standardItemFromWhere();
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
        $query = "
            SELECT $date_selector, $hour as hour,
                " . DTrans::sumQuantity('t')." as qty,
                SUM(t.total) AS total
            " . $from_where['query'] . "
            GROUP BY $date_selector, $hour
            ORDER BY $date_selector, $hour";

        $prep = $dbc->prepare($query);
        try {
            $result = $dbc->execute($prep, $from_where['args']);
        } catch (Exception $ex) {
            // MySQL 5.6 doesn't GROUP BY correctly
            return array();
        }
        $data = array();
        $colsAdded = array();
        $minHour = 23;
        $maxHour = 0;
        while ($row = $dbc->fetchRow($result)) {
            $colName = !$weekday ? date('Y-m-d', mktime(0, 0, 0, $row[1], $row[2], $row[0])) : $day_names[$row[0]];
            if (!isset($colsAdded[$colName])) {
                $colsAdded[$colName] = array();
                $this->report_headers[] = $colName;
            }
            $colsAdded[$colName][$row['hour']] = sprintf('%.2f', $row['total']);
            if ($mode == 'Quantity') {
                $colsAdded[$colName][$row['hour']] = sprintf('%.2f', $row['qty']);
            }
            if ($row['hour'] > $maxHour) {
                $maxHour = $row['hour'];
            }
            if ($row['hour'] < $minHour) {
                $minHour = $row['hour'];
            }
        }
        for ($i=$minHour; $i<=$maxHour; $i++) {
            $record = array($i . ':00');
            foreach ($colsAdded as $colName => $col) {
                $record[] = isset($col[$i]) ? $col[$i] : 0;
            }
            $data[] = $record;
        }

        return $data;
    }

    private function percent($a, $b)
    {
        if ($b == 0) return 0;
        return sprintf('%.2f', 100 * ($a/$b));
    }

    public function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        ob_start();
        ?>
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="row">
            <?php echo FormLib::standardItemFields(); ?>
            <?php echo FormLib::standardDateFields(); ?>
        </div>
        <div class="row form-horizontal">
            <div class="form-group">
                <label class="col-sm-1 control-label">Results as</label>
                <div class="col-sm-2">
                    <select name="mode" class="form-control">
                        <option>Dollars</option>
                        <option>Quantity</option>
                    </select>
                </div>
                <label class="col-sm-1 control-label">
                    <input type="checkbox" name="weekday" value="1" />
                    By weekday
                </label>
            </div>
            <div class="form-group">
                <label class="col-sm-1 control-label">Store</label>
                <div class="col-sm-2">
                    <?php $s = FormLib::storePicker(); echo $s['html']; ?>
                </div>
                <label class="col-sm-1 control-label">
                    <input type="checkbox" name="excel" value="csv" />
                    Excel
                </label>
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-default btn-core">Get Report</button>
            <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            This tool performs a variety of different movement reports.
            Select the set of items by department, brand, vendor, or
            like code using the appropriate tabs. 
            </p>
            <p>
            Identifying items by department provides a super department
            choice and/or a range of departments and/or a range of
            sub departments. Identifying by brand allows either a text
            brand name or a numeric UPC prefix. Vendor simply allows
            a choice of vendor. Like code provides a range of like
            codes.
            </p>
            <p>
            After choosing a method for identifying a set of items
            and defining a date range, there is also an option to choose
            how the report is summed. PLU will show one row for each
            item sold. Date will show one row for each day in the period.
            Department will show one row for each department that
            has sales. Weekday is similar to date but combines each Monday,
            Tuesday, etc into a single row.
            </p>'; 
    }
}

FannieDispatch::conditionalExec();


