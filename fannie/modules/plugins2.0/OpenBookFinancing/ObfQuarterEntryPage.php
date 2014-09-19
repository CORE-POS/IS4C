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
    protected $title = 'OBF: Quarters';
    protected $header = 'OBF: Quarters';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Quarter Entry] sets sales and labor goals by quarter.';

    public function javascript_content()
    {
        ob_start();
        ?>
        <?php
        return ob_get_clean();
    }

    public function post_handler()
    {
        $this->id = '';
        return $this->post_id_handler();
    }

    public function post_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $model = new ObfQuartersModel($dbc);
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
        global $FANNIE_PLUGIN_SETTINGS;
        if ($this->id != 0) {
            $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
            $this->currentModel = new ObfQuartersModel($dbc);
            $this->currentModel->obfQuarterID($this->id);
            $this->currentModel->load();
        }

        return $this->get_view();
    }

    private $currentModel;
    
    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $model = new ObfQuartersModel($dbc);
        if (!is_object($this->currentModel)) {
            $this->currentModel = new ObfQuartersModel($dbc);
        }
        $select = '<select name="id" onchange="location=\'' . $_SERVER['PHP_SELF'] . '?id=\' + this.value;">';
        $select .= '<option value="">New Entry</option>';
        $first = true;
        foreach($model->find('obfWeekID', true) as $obj) {
            $select .= sprintf('<option %s value="%d">%s</option>',
                            ($this->currentModel->obfQuarterID() == $obj->obfQuarterID() ? 'selected' : ''),
                            $obj->obfQuarterID(), $obj->name() . ' ' . $obj->year());
        }
        $select .= '</select>';

        $ret = '<b>Quarter</b>: ' . $select 
                . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<button onclick="location=\'ObfIndexPage.php\';return false;">Home</button>'
                . '<br /><br />';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        $ret .= '<tr><th>Name</th><th>Year</th>
                <th># of Weeks</th><th>Sales Goal ($)</th>
                <th>Labor Goal ($)</th></tr>';

        $ret .= '<tr>';
        $ret .= '<td><input type="text" size="12" name="name" id="name"
                        value="' . $this->currentModel->name() . '"
                        onchange="getPrevYear(this.value);" /></td>';
        $ret .= '<td><input type="text" size="4" name="year" id="year"
                        value="' . $this->currentModel->year() . '" /></td>';
        $ret .= sprintf('<td><input type="text" size="3" name="weeks"
                            value="%d" /></td>', $this->currentModel->weeks());
        $ret .= sprintf('<td><input type="text" size="10" name="sales"
                            value="%.2f" /></td>', $this->currentModel->salesTarget());
        $ret .= sprintf('<td><input type="text" size="10" name="labor"
                            value="%.2f" /></td>', $this->currentModel->laborTarget());
        $ret .= '</tr>';
        
        $ret .= '</table>';

        $ret .= '<input type="submit" value="Save" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

