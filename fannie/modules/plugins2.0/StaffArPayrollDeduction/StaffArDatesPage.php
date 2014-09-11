<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
class StaffArDatesPage extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Payroll Deductions';
    public $description = '[Schedule] sets dates when payments will be applied.';

    public function preprocess()
    {
        $this->title = _('Payroll Deduction Schedule');
        $this->header = _('Payroll Deduction Schedule');
        $this->__routes[] = 'get<add>';
        $this->__routes[] = 'get<delete>';

        return parent::preprocess();
    }

    public function get_add_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);

        $valid_date = strtotime($this->add);
        if ($valid_date) {
            $save_date = date('Y-m-d', $valid_date);
            $model = new StaffArDatesModel($dbc);
            $model->tdate(date('Y-m-d', $valid_date));    
            if (count($model->find()) == 0) {
                $model->save();
            }
        }

        echo $this->dateTable();

        return false;
    }

    public function get_delete_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);
        $model = new StaffArDatesModel($dbc);
        $model->staffArDateID($this->delete);
        $model->delete();

        echo $this->dateTable();

        return false;
    }

    public function get_view()
    {
        global $FANNIE_URL;
        $this->add_script('js/dates.js');

        $ret = '<div id="mainDisplayDiv">';
        $ret .= $this->dateTable();
        $ret .= '</div>';
        $ret .= '<hr />';
        $ret .= '<b>Add Date</b>: <input type="text" id="newDate" />
                <input type="submit" onclick="addDate(); return false;" value="Add" />';
        $this->add_onload_command("\$('#newDate').datepicker();");

        return $ret;
    }

    private function dateTable()
    {
        $ret = '<table cellspacing="0" cellpadding="4" border="1">';
        $dates = $this->getDates();
        foreach($dates as $id => $date) {
            if (strstr($date, ' ')) {
                list($date, $time) = explode(' ', $date, 2);
            }
            $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td><a href="" onclick="removeDate(%d); return false;">Remove</a></td>
                            </tr>',
                            $date, $id
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    private function getDates()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);
        $ret = array();
        $model = new StaffArDatesModel($dbc);
        foreach($model->find('tdate') as $obj) {
            $ret[$obj->staffArDateID()] = $obj->tdate();
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();

