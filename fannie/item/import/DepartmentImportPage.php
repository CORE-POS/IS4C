<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class DepartmentImportPage extends FannieUploadPage {
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Departments";

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

    
    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $dn_index = $this->get_column_index('dept_no');
        $desc_index = $this->get_column_index('desc');
        $margin_index = $this->get_column_index('margin');
        $tax_index = $this->get_column_index('tax');
        $fs_index = $this->get_column_index('fs');

        // prepare statements
        $marP = $dbc->prepare_statement("INSERT INTO deptMargin (dept_ID,margin) VALUES (?,?)");

        $scP = $dbc->prepare_statement("INSERT INTO deptSalesCodes (dept_ID,salesCode) VALUES (?,?)");

        $model = new DepartmentsModel($dbc);

        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $dept_no = $line[$dn_index];
            $desc = $line[$desc_index];
            $margin = ($margin_index !== False) ? $line[$margin_index] : 0;
            if ($margin > 1) $margin /= 100.00;
            $tax = ($tax_index !== False) ? $line[$tax_index] : 0;
            $fs = ($fs_index !== False) ? $line[$fs_index] : 0;

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
            $model->save();

            $insR = $dbc->exec_statement($insP,array($dept_no,$desc,$tax,$fs));

            if ($dbc->tableExists('deptMargin')) {
                $insR = $dbc->exec_statement($marP,array($dept_no, $margin));
            }

            if ($dbc->tableExists('deptSalesCodes')) {
                $insR = $dbc->exec_statement($scP,array($dept_no, $dept_no));
            }
        }
        return True;
    }
    
    function form_content(){
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing departments numbers, descriptions, margins,
        and optional tax/foodstamp settings. Unless you know better, use zero and
        one for tax and foodstamp columns.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    function results_content(){
        return 'Import completed successfully';
    }
}

FannieDispatch::conditionalExec(false);

