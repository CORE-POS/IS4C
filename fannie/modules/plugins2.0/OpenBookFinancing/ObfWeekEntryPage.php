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
class ObfWeekEntryPage extends FannieRESTfulPage 
{
    protected $title = 'OBF: Weeks';
    protected $header = 'OBF: Weeks';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Week Entry] sets labor amounts and sales goals by week.';

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
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');

        $end_ts = strtotime($date1);
        $prev_ts = strtotime($date2);

        $model = new ObfWeeksModel($dbc);
        $model->startDate(date('Y-m-d', mktime(0, 0, 0, date('n', $end_ts), date('j', $end_ts)-6, date('Y', $end_ts))));
        $model->endDate(date('Y-m-d', $end_ts));
        $model->previousYear(date('Y-m-d', mktime(0, 0, 0, date('n', $prev_ts), date('j', $prev_ts)-6, date('Y', $prev_ts))));
        $model->obfQuarterID(FormLib::get('quarter'));
        $model->growthTarget(FormLib::get('growthTarget', 0.00) / 100.00);

        $new_id = $model->save();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $new_id);

        return false;
    }

    public function post_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');

        $end_ts = strtotime($date1);
        $prev_ts = strtotime($date2);

        $model = new ObfWeeksModel($dbc);
        $model->obfWeekID($this->id);
        $model->startDate(date('Y-m-d', mktime(0, 0, 0, date('n', $end_ts), date('j', $end_ts)-6, date('Y', $end_ts))));
        $model->endDate(date('Y-m-d', $end_ts));
        $model->previousYear(date('Y-m-d', mktime(0, 0, 0, date('n', $prev_ts), date('j', $prev_ts)-6, date('Y', $prev_ts))));
        $model->obfQuarterID(FormLib::get('quarter'));
        $model->growthTarget(FormLib::get('growthTarget', 0.00) / 100.00);

        $model->save();

        $hours = FormLib::get('hours', array());
        $wages = FormLib::get('wages', array());
        $weeks = FormLib::get('weekID', array());
        $cats = FormLib::get('catID', array());
        $goals = FormLib::get('labor', array());
        $alloc = FormLib::get('alloc', array());
        $sales = FormLib::get('sales', array());
        $model = new ObfLaborModel($dbc);
        for($i=0;$i<count($cats);$i++) {
            $model->reset();
            $model->obfCategoryID($cats[$i]);
            if (!isset($weeks[$i])) {
                continue;
            }
            $model->obfWeekID($weeks[$i]);
            $model->hours( isset($hours[$i]) ? $hours[$i] : 0 );
            $model->wages( isset($wages[$i]) ? $wages[$i] : 0 );
            $model->laborTarget( isset($goals[$i]) ? $goals[$i] / 100.00 : 0 );
            $model->hoursTarget( isset($alloc[$i]) ? $alloc[$i] : 0 );
            $model->forecastSales(sprintf('%d', isset($sales[$i]) ? $sales[$i] : 0));
            $model->save();
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $this->id);

        return false;
    }

    private $weekModel;

    public function get_id_view()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        if ($this->id != 0) {
            $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
            $this->weekModel = new ObfWeeksModel($dbc);
            $this->weekModel->obfWeekID($this->id);
            $this->weekModel->load();
        }

        return $this->get_view();
    }
    
    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $model = new ObfWeeksModel($dbc);
        if (!is_object($this->weekModel)) {
            $this->weekModel = new ObfWeeksModel($dbc);
        }
        $select = '<select onchange="location=\'' . $_SERVER['PHP_SELF'] . '?id=\' + this.value;">';
        $select .= '<option value="">New Entry</option>';
        foreach($model->find('obfWeekID', true) as $week) {
            $ts = strtotime($week->startDate());
            $end = date('Y-m-d', mktime(0, 0, 0, date('n', $ts), date('j', $ts)+6, date('Y', $ts)));
            $select .= sprintf('<option %s value="%d">%s</option>',
                            ($this->weekModel->obfWeekID() == $week->obfWeekID() ? 'selected' : ''),
                            $week->obfWeekID(), $end);
        }
        $select .= '</select>';

        $ret = '<b>Week Ending</b>: ' . $select
                . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<button onclick="location=\'ObfIndexPage.php\';return false;">Home</button>'
                . '<br /><br />';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        $ret .= '<tr><th>Week End Date</th><th>Previous Year End Date</th>
                <th>Sales Growth Target</th><th>Quarter</th></tr>';

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
        $ret .= '<td><input type="text" size="12" name="date1" id="date1"
                        value="' . $end1 . '"
                        onchange="getPrevYear(this.value);" /></td>';
        $this->add_onload_command("\$('#date1').datepicker();\n");
        $ret .= '<td><input type="text" size="12" name="date2" id="date2"
                        value="' . $end2 . '" /></td>';
        $ret .= '<td><input type="text" size="6" name="growthTarget" 
                        value="' . sprintf('%.2f', $this->weekModel->growthTarget() * 100) . '" />%</td>';
        $this->add_onload_command("\$('#date2').datepicker();\n");
        $ret .= '<td><select name="quarter">';
        $quarters = new ObfQuartersModel($dbc);
        foreach ($quarters->find('obfQuarterID', true) as $q) {
            $ret .= sprintf('<option %s value="%d">%s %s</option>',
                        ($q->obfQuarterID() == $this->weekModel->obfQuarterID() ? 'selected' : ''),
                        $q->obfQuarterID(), $q->name(), $q->year());
        }
        $ret .= '</select></td>';
        $ret .= '</tr>';
        
        $ret .= '</table>';

        if ($this->weekModel->load()) { // week record exists
            $ret .= sprintf('<input type="hidden" name="id" value="%d" />',
                        $this->weekModel->obfWeekID());

            $ret .= '<hr />';
            $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
            $ret .= '<tr><th>Group</th><th>Hours</th><th>Wages</th>
                    <th>Labor Goal</th><th>Allocated Hours</th><th>Sales Forecast</th></tr>';
            $categories = new ObfCategoriesModel($dbc);
            $labor = new ObfLaborModel($dbc);
            foreach($categories->find() as $obj) {
                $labor->reset();
                $labor->obfWeekID($this->weekModel->obfWeekID());
                $labor->obfCategoryID($obj->obfCategoryID());
                $labor->load();
                if ($labor->laborTarget() == 0) {
                    $labor->laborTarget($obj->laborTarget());
                }
                if ($labor->averageWage() == 0) {
                    $labor->averageWage($obj->averageWage());
                }
                if ($labor->hoursTarget() == 0) {
                    $labor->hoursTarget($obj->hoursTarget());
                }
                $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td><input type="text" size="8" name="hours[]" value="%.2f" /></td>
                            <td><input type="text" size="8" name="wages[]" value="%.2f" /></td>
                            <td><input type="text" size="8" name="labor[]" value="%.2f" />%%</td>
                            <td><input type="text" size="8" name="alloc[]" value="%d" /></td>
                            <td>$<input type="text" size="8" name="sales[]" %s value="%s" /></td>
                            <input type="hidden" name="weekID[]" value="%d" />
                            <input type="hidden" name="catID[]" value="%d" />
                            </tr>',
                            $obj->name(),
                            $labor->hours(),
                            $labor->wages(),
                            $labor->laborTarget() * 100,
                            $labor->hoursTarget(),
                            ($obj->hasSales() ? '' : 'disabled'),
                            ($obj->hasSales() ? round($labor->forecastSales()) : 'n/a'),
                            $labor->obfWeekID(),
                            $labor->obfCategoryID()
                );
            }
            $ret .= '</table>';
        }

        $ret .= '<input type="submit" value="Save" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

