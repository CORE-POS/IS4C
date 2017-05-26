<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class TableExportReport extends FannieReportPage 
{
    protected $header = 'View/Export Table';
    protected $title = 'View/Export Table';
    protected $required_fields = array('id');
    public $description = '[View/Export Table] shows the entire contents of a given database table. 
        For very large tables, it instead shows the first ten thousand records.';
    public $report_set = 'System';

    private $hard_limit = 10000;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $class = $this->form->id;
        if (!class_exists($class)) {
            echo 'no class';
            return array();
        }
        if (!is_subclass_of($class, 'BasicModel')) {
            echo 'bad class ' . $class;
            return array();
        }

        $obj = new $class($dbc);
        $this->report_headers = array();
        foreach ($obj->getColumns() as $c => $info) {
            $this->report_headers[] = $c;
        }

        $db_name = $obj->preferredDB();
        if ($db_name == 'op') {
            $dbc->selectDB($this->config->get('OP_DB'));
        } elseif ($db_name == 'trans') {
            $dbc->selectDB($this->config->get('TRANS_DB'));
        } elseif ($db_name == 'archive') {
            $dbc->selectDB($this->config->get('ARCHIVE_DB'));
        } elseif (substr($db_name, 0, 7) == 'plugin:') {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $pluginDB = substr($db_name, 7);
            if (isset($settings[$pluginDB])) {
                $dbc->selectDB($settings[$pluginDB]);
            } else {
                return array();
            }
        } else {
            return array();
        }

        $noLimit = FormLib::get('noLimit', false);

        $row_count = 0;
        $columns = $obj->getColumns();
        if ($noLimit == false) {
            $obj->setFindLimit($this->hard_limit);
        }
        $data = array();
        foreach ($obj->find() as $row) {
            $record = array();
            foreach ($columns as $c => $info) {
                $record[] = $row->$c();
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $ret = <<<HTML
<form method="get">
    <div class="form-group">
        <label>Table</label>
        <select name="id" class="form-control">
            {{ models }}
        </select>
    </div>
    <div class="form-group">
        <label>Format</label>
        <select name="excel" class="form-control">
            <option value="">Web Page (HTML)</option>
            <option value="csv" selected>CSV</option>
            <option value="txt" selected>TXT</option>
        </select>
    </div>
    <div class="form-group">
        <label>
            <input type="checkbox" name="noLimit" value="1" />
            Export more than 10,000 records
        </label>
        <em>Checking this and choosing a very large table may crash your browser.</em>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
</form>
HTML;
        $models = FannieAPI::listModules('BasicModel');
        sort($models);
        $opts = array_reduce($models, function($c, $i) { return $c . '<option>' . $i . '</option>'; }); 
        $ret = str_replace('{{ models }}', $opts, $ret);

        return $ret;
    }

    public function helpContent()
    {
        return '
            <p>
            Display the exact contents of a selected database table.
            This is primarily an administrator oriented report. The report
            is currently capped at ten thousand records to avoid crashing
            the browser.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->form = new COREPOS\common\mvc\ValueContainer();
        $this->form->id = 'EmployeesModel';
        $this->hard_limit = 1;
        $phpunit->assertInternalType('array', $this->fetch_report_data());
    }
}

FannieDispatch::conditionalExec();
