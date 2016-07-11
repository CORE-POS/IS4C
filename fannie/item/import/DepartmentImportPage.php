<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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
/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
     6Mar2013 Andy Theuninck re-do as class
     4Sep2012 Eric Lee Add some notes to the initial page.
*/
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DepartmentImportPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Departments";

    protected $must_authenticate = true;
    protected $auth_classes = array('departments', 'admin');

    public $description = '[Department Import] load POS departments from a spreadsheet.';

    protected $preview_opts = array(
        'dept_no' => array(
            'name' => 'dept_no',
            'display_name' => 'Dept #',
            'default' => 0,
            'required' => True
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Name',
            'default' => 1,
            'required' => True
        ),
        'margin' => array(
            'name' => 'margin',
            'display_name' => 'Margin',
            'default' => 2,
            'required' => False
        ),
        'tax' => array(
            'name' => 'tax',
            'display_name' => 'Tax',
            'default' => 3,
            'required' => False
        ),
        'fs' => array(
            'name' => 'fs',
            'display_name' => 'FS',
            'default' => 4,
            'required' => False
        )
    );

    private $stats = array('imported'=>0, 'errors'=>array());
    
    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        // prepare statements
        $marP = $dbc->prepare("INSERT INTO deptMargin (dept_ID,margin) VALUES (?,?)");
        $scP = $dbc->prepare("INSERT INTO deptSalesCodes (dept_ID,salesCode) VALUES (?,?)");
        $model = new DepartmentsModel($dbc);

        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $dept_no = $line[$indexes['dept_no']];
            $desc = $line[$indexes['desc']];
            $margin = ($indexes['margin'] !== False) ? $line[$indexes['margin']] : 0;
            if ($margin > 1) $margin /= 100.00;
            $tax = ($indexes['tax'] !== False) ? $line[$indexes['tax']] : 0;
            $fs = ($indexes['fs'] !== False) ? $line[$indexes['fs']] : 0;

            if (!is_numeric($dept_no)) continue; // skip header/blank rows

            if (strlen($desc) > 30) $desc = substr($desc,0,30);

            $model->reset();
            $model->dept_no($dept_no);
            $model->dept_name($desc);
            $model->dept_tax($tax);
            $model->dept_fs($fs);
            $model->dept_limit(50);
            $model->dept_minimum(0.01);
            $model->dept_discount(1);
            $model->dept_see_id(0);
            $model->modified(date('Y-m-d H:i:s'));
            $model->modifiedby(1);
            $model->margin($margin);
            $model->salesCode($dept_no);
            $imported = $model->save();

            if ($imported) {
                $this->stats['imported']++;
            } else {
                $this->stats['errors'][] = 'Error imported department #' . $dept_no;
            }

            if ($dbc->tableExists('deptMargin')) {
                $insR = $dbc->execute($marP,array($dept_no, $margin));
            }

            if ($dbc->tableExists('deptSalesCodes')) {
                $insR = $dbc->execute($scP,array($dept_no, $dept_no));
            }
        }

        return true;
    }
    
    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing departments numbers, descriptions, margins,
        and optional tax/foodstamp settings. Unless you know better, use zero and
        one for tax and foodstamp columns.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        $ret = '
            <p>Import Complete</p>
            <div class="alert alert-success">' . $this->stats['imported'] . ' departments imported</div>';
        if ($this->stats['errors']) {
            $ret .= '<div class="alert alert-error"><ul>';
            foreach ($this->stats['errors'] as $error) {
                $ret .= '<li>' . $error . '</li>';
            }
            $ret .= '</ul></div>';
        }

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $this->stats = array('imported'=>0, 'errors'=>array('foo'));
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
        $data = array(1000, 'test dept', '0.5', 0, 1);
        $indexes = array('dept_no'=>0, 'desc'=>1, 'margin'=>2, 'tax'=>3, 'fs'=>4);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();

