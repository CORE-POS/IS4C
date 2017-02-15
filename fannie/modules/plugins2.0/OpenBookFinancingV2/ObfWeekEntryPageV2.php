<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class ObfWeekEntryPageV2 extends FannieRESTfulPage 
{
    protected $title = 'OBF: Weeks';
    protected $header = 'OBF: Weeks';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Week Entry] sets labor amounts and sales goals by week.';
    public $themed = true;
    protected $lib_class = 'ObfLibV2';

    public function javascript_content()
    {
        ob_start();
        ?>
        function getPrevYear(datestr) {
            var forward = new Date(datestr);
            forward.setYear(forward.getFullYear() - 1);

            var backward = new Date(datestr);
            backward.setYear(backward.getFullYear() - 1);

            var i = 0;
            var out = '';
            while(true) {
                if (forward.getDay() == 0) {
                    out += forward.getFullYear() + '-';
                    if (forward.getMonth()+1 < 10)
                        out += '0';
                    out += (forward.getMonth()+1) + '-';
                    if (forward.getDate() < 10)
                        out += '0';
                    out += forward.getDate();
                    break; 
                } else {
                    forward.setDate(forward.getDate() + 1);
                }

                if (backward.getDay() == 0) {
                    out += backward.getFullYear() + '-';
                    if (backward.getMonth()+1 < 10)
                        out += '0';
                    out += (backward.getMonth()+1) + '-';
                    if (backward.getDate() < 10)
                        out += '0';
                    out += backward.getDate();
                    break; 
                } else {
                    backward.setDate(backward.getDate() - 1);
                }

                if (i++ > 7) break;
            }
            $('#date2').val(out);
        }
        <?php
        return ob_get_clean();
    }

    public function post_handler()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();

        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');

        $end_ts = strtotime($date1);
        $prev_ts = strtotime($date2);

        $model = $lib_class::getWeek($dbc);
        $model->startDate(date('Y-m-d', mktime(0, 0, 0, date('n', $end_ts), date('j', $end_ts)-6, date('Y', $end_ts))));
        $model->endDate(date('Y-m-d', $end_ts));
        $exists = $model->find();
        if (is_array($exists) && count($exists) == 1) {
            $match = $exists[0];
            $model->obfWeekID($match->obfWeekID());
        }
        $model->previousYear(date('Y-m-d', mktime(0, 0, 0, date('n', $prev_ts), date('j', $prev_ts)-6, date('Y', $prev_ts))));
        $model->obfQuarterID(FormLib::get('quarter'));
        $model->obfLaborQuarterID(FormLib::get('labor-quarter'));
        $model->growthTarget(FormLib::get('growthTarget', 0.00) / 100.00);

        $new_id = $model->save();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $new_id);

        return false;
    }

    public function post_id_handler()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();

        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');

        $end_ts = strtotime($date1);
        $prev_ts = strtotime($date2);

        $model = $lib_class::getWeek($dbc);
        $model->obfWeekID($this->id);
        $model->startDate(date('Y-m-d', mktime(0, 0, 0, date('n', $end_ts), date('j', $end_ts)-6, date('Y', $end_ts))));
        $model->endDate(date('Y-m-d', $end_ts));
        $model->previousYear(date('Y-m-d', mktime(0, 0, 0, date('n', $prev_ts), date('j', $prev_ts)-6, date('Y', $prev_ts))));
        $model->obfQuarterID(FormLib::get('quarter'));
        $model->obfLaborQuarterID(FormLib::get('labor-quarter'));
        $model->growthTarget(FormLib::get('growthTarget', 0.00) / 100.00);

        $model->save();

        $hours = FormLib::get('hours', array());
        $wages = FormLib::get('wages', array());
        $weeks = FormLib::get('weekID', array());
        $cats = FormLib::get('catID', array());
        $goals = FormLib::get('lgrowth', array());
        $splh = FormLib::get('lsplh', array());
        $sales = FormLib::get('sales', array());
        $model = $lib_class::getLabor($dbc);
        for($i=0;$i<count($cats);$i++) {
            $model->reset();
            $model->obfCategoryID($cats[$i]);
            if (!isset($weeks[$i])) {
                continue;
            }
            $model->obfWeekID($weeks[$i]);
            $model->hours( isset($hours[$i]) ? $hours[$i] : 0 );
            $model->wages( isset($wages[$i]) ? $wages[$i] : 0 );
            $model->growthTarget( isset($goals[$i]) ? $goals[$i] / 100.00 : 0 );
            $model->splhTarget( isset($splh[$i]) ? $splh[$i] : 0 );
            $model->forecastSales(sprintf('%d', isset($sales[$i]) ? $sales[$i] : 0));
            $model->save();
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $this->id);

        return false;
    }

    private $weekModel;

    public function get_id_view()
    {
        if ($this->id != 0) {
            $class = $this->lib_class;
            $dbc = $class::getDB();
            $this->weekModel = $class::getWeek($dbc);
            $this->weekModel->obfWeekID($this->id);
            $this->weekModel->load();
        }

        return $this->get_view();
    }
    
    public function get_view()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();

        $model = $lib_class::getWeek($dbc);
        if (!is_object($this->weekModel)) {
            $this->weekModel = $lib_class::getWeek($dbc);
            $quarterID = '';
            $laborID = '';
            foreach ($this->weekModel->find('endDate', true) as $w) {
                $quarterID = $w->obfQuarterID();
                $laborID = $w->obfLaborQuarterID();
                break;
            }
            $this->weekModel->obfQuarterID($quarterID);
            $this->weekModel->obfLaborQuarterID($laborID);
        }
        $select = '<select class="form-control"
                    onchange="location=\'' . $_SERVER['PHP_SELF'] . '?id=\' + this.value;">';
        $select .= '<option value="">New Entry</option>';
        foreach($model->find('obfWeekID', true) as $week) {
            $ts = strtotime($week->startDate());
            $end = date('Y-m-d', mktime(0, 0, 0, date('n', $ts), date('j', $ts)+6, date('Y', $ts)));
            $select .= sprintf('<option %s value="%d">%s</option>',
                            ($this->weekModel->obfWeekID() == $week->obfWeekID() ? 'selected' : ''),
                            $week->obfWeekID(), $end);
        }
        $select .= '</select>';

        $ret = '<div class="form-group form-inline">
                <lablel>Week Ending</label>: ' . $select
                . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<button type="button" class="btn btn-default"
                    onclick="location=\'index.php\';return false;">Home</button>'
                . '</div>';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Week End Date</th><th>Previous Year End Date</th>
                <th>Sales Growth Target</th><th>Sales Period</th>
                <th>Labor Period</th></tr>';

        $end1 = '';
        if ($this->weekModel->startDate() != '') {
            $ts = strtotime($this->weekModel->startDate());
            $end1 = date('Y-m-d', mktime(0, 0, 0, date('n', $ts), date('j', $ts)+6, date('Y', $ts)));
        }
        $end2 = '';
        if ($this->weekModel->previousYear() != '') {
            $ts = strtotime($this->weekModel->previousYear());
            $end2 = date('Y-m-d', mktime(0, 0, 0, date('n', $ts), date('j', $ts)+6, date('Y', $ts)));
        }
        $ret .= '<tr>';
        $ret .= '<td><input type="text" class="form-control date-field" name="date1" id="date1"
                        value="' . $end1 . '" required
                        onchange="getPrevYear(this.value);" /></td>';
        $ret .= '<td><input type="text" class="form-control date-field" name="date2" id="date2"
                        value="' . $end2 . '" required /></td>';
        $ret .= '<td><div class="input-group">
                <input type="number" min="-100" max="100" step="0.01"
                    class="form-control" name="growthTarget" 
                    value="' . sprintf('%.2f', $this->weekModel->growthTarget() * 100) . '" />
                <span class="input-group-addon">%</span></div></td>';
        $ret .= '<td><select name="quarter" class="form-control">';
        $quarters = $lib_class::getQuarter($dbc);
        foreach ($quarters->find('obfQuarterID', true) as $q) {
            $ret .= sprintf('<option %s value="%d">%s %s</option>',
                        ($q->obfQuarterID() == $this->weekModel->obfQuarterID() ? 'selected' : ''),
                        $q->obfQuarterID(), $q->name(), $q->year());
        }
        $ret .= '</select></td>';
        $ret .= '<td><select name="labor-quarter" class="form-control">';
        $quarters = $lib_class::getQuarter($dbc);
        foreach ($quarters->find('obfQuarterID', true) as $q) {
            $ret .= sprintf('<option %s value="%d">%s %s</option>',
                        ($q->obfQuarterID() == $this->weekModel->obfLaborQuarterID() ? 'selected' : ''),
                        $q->obfQuarterID(), $q->name(), $q->year());
        }
        $ret .= '</select></td>';
        $ret .= '</tr>';
        
        $ret .= '</table>';

        if ($this->weekModel->load()) { // week record exists
            $ret .= sprintf('<input type="hidden" name="id" value="%d" />',
                        $this->weekModel->obfWeekID());

            $ret .= '<hr />';
            $ret .= '<table class="table">';
            $ret .= '<tr><th>Group</th><th>Hours</th><th>Wages</th>
                    <th>Growth Goal</th><th>SPLH Goal</th><th>Sales Forecast</th></tr>';
            $categories = $lib_class::getCategory($dbc);
            $labor = $lib_class::getLabor($dbc);
            foreach($categories->find() as $obj) {
                $labor->reset();
                $labor->obfWeekID($this->weekModel->obfWeekID());
                $labor->obfCategoryID($obj->obfCategoryID());
                $labor->load();
                if ($labor->growthTarget() == 0) {
                    $labor->growthTarget($obj->growthTarget());
                }
                if ($labor->splhTarget() == 0) {
                    $labor->splhTarget($obj->salesPerLaborHourTarget());
                }
                $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td><input type="text" class="form-control" name="hours[]" value="%.2f" /></td>
                            <td><div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" class="form-control" name="wages[]" value="%.2f" />
                            </div></td>
                            <td><div class="input-group">
                                <input type="text" class="form-control" name="lgrowth[]" value="%.2f" />
                                <span class="input-group-addon">%%</span>
                            </div></td>
                            <td><input type="text" class="form-control" name="lsplh[]" value="%d" /></td>
                            <td><div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" class="form-control" name="sales[]" %s value="%s" />
                            </div></td>
                            <input type="hidden" name="weekID[]" value="%d" />
                            <input type="hidden" name="catID[]" value="%d" />
                            </tr>',
                            $obj->name(),
                            $labor->hours(),
                            $labor->wages(),
                            $labor->growthTarget() * 100,
                            $labor->splhTarget(),
                            ($obj->hasSales() ? '' : 'disabled'),
                            ($obj->hasSales() ? round($labor->forecastSales()) : 'n/a'),
                            $labor->obfWeekID(),
                            $labor->obfCategoryID()
                );
            }
            $ret .= '</table>';
        }

        $ret .= '<p><button type="submit" class="btn btn-default">Save</button></p>';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

