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
class ObfQuarterEntryPage extends FannieRESTfulPage 
{
    public function preprocess()
    {
        if (!headers_sent()) {
            header('Location: ../OpenBookFinancingV2/ObfQuarterEntryPageV2.php');
        }
        return false;
    }

    protected $title = 'OBF: Quarters';
    protected $header = 'OBF: Quarters';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Quarter Entry] sets sales and labor goals by quarter.';
    public $themed = true;
    protected $lib_class = 'ObfLib';

    public function post_handler()
    {
        $this->id = '';
        return $this->post_id_handler();
    }

    public function post_id_handler()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();

        $model = $lib_class::getQuarter($dbc);
        if ($this->id !== '') {
            $model->obfWeekID($this->id);
        }
        $model->name(FormLib::get('name'));
        $model->year(FormLib::get('year'));
        $model->weeks(FormLib::get('weeks'));
        $model->salesTarget(FormLib::get('sales'));
        $model->laborTarget(FormLib::get('labor'));

        $save_result = $model->save();
        if ($save_result !== false && $this->id === '') {
            $this->id = $save_result;
        }

        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $this->id);

        return false;
    }


    public function get_id_view()
    {
        if ($this->id != 0) {
            $lib_class = $this->lib_class;
            $dbc = $lib_class::getDB();
            $this->currentModel = $lib_class::getQuarter($dbc);
            $this->currentModel->obfQuarterID($this->id);
            $this->currentModel->load();
        }

        return $this->get_view();
    }

    private $currentModel;
    
    public function get_view()
    {
        $lib_class = $this->lib_class;
        $dbc = $lib_class::getDB();

        $model = $lib_class::getQuarter($dbc);
        if (!is_object($this->currentModel)) {
            $this->currentModel = $lib_class::getQuarter($dbc);
        }
        $select = '<select name="id" class="form-control" 
                    onchange="location=\'' . $_SERVER['PHP_SELF'] . '?id=\' + this.value;">';
        $select .= '<option value="">New Entry</option>';
        $first = true;
        foreach($model->find('obfWeekID', true) as $obj) {
            $select .= sprintf('<option %s value="%d">%s</option>',
                            ($this->currentModel->obfQuarterID() == $obj->obfQuarterID() ? 'selected' : ''),
                            $obj->obfQuarterID(), $obj->name() . ' ' . $obj->year());
        }
        $select .= '</select>';

        $ret = '<div class="form-group form-inline">
                <label>Quarter</label>: ' . $select 
                . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<button type="button" class="btn btn-default"
                    onclick="location=\'index.php\';return false;">Home</button>'
                . '</div>';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Name</th><th>Year</th>
                <th># of Weeks</th><th>Sales Goal ($)</th>
                <th>Labor Goal ($)</th></tr>';

        $ret .= '<tr>';
        $ret .= '<td><input type="text" class="form-control" required name="name" id="name"
                        value="' . $this->currentModel->name() . '"
                        onchange="getPrevYear(this.value);" /></td>';
        $ret .= '<td><input type="number" class="form-control" required name="year" id="year"
                        value="' . $this->currentModel->year() . '" /></td>';
        $ret .= sprintf('<td><input type="number" required class="form-control" name="weeks"
                            value="%d" /></td>', $this->currentModel->weeks());
        $ret .= sprintf('<td><div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="number" required class="form-control" name="sales" value="%.2f" />
            </div></td>', $this->currentModel->salesTarget());
        $ret .= sprintf('<td><div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="number" class="form-control" required name="labor" value="%.2f" />
            </div></td>', $this->currentModel->laborTarget());
        $ret .= '</tr>';
        
        $ret .= '</table>';

        $ret .= '<p><button type="submit" class="btn btn-default">Save</button></p>';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

