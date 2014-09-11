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
     4Sep2012 Eric Lee Change $header to Sub-Departments.
              Add some notes to the initial page.
*/
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SubdeptImportPage extends FannieUploadPage {

    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Sub-Departments";

    public $description = '[Subdepartment Import] loads subdept data from a spreadsheet.';

    protected $preview_opts = array(
        'sn' => array(
            'name' => 'sn',
            'display_name' => 'SubDept #',
            'default' => 0,
            'required' => True
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Name',
            'default' => 1,
            'required' => True
        ),
        'dn' => array(
            'name' => 'dn',
            'display_name' => 'Dept #',
            'default' => 2,
            'required' => True
        )
    );

    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $sn_index = $this->get_column_index('sn');
        $desc_index = $this->get_column_index('desc');
        $dn_index = $this->get_column_index('dn');

        $insP = $dbc->prepare_statement("INSERT INTO subdepts (subdept_no,subdept_name,dept_ID)
                    VALUES (?,?,?)");

        foreach($linedata as $line){
            // get info from file and member-type default settings
            // if applicable
            $dept_no = $line[$dn_index];
            $desc = $line[$desc_index];
            $subdept_no = $line[$sn_index];

            if (!is_numeric($subdept_no)) continue; // skip header/blank rows

            if (strlen($desc) > 30) $desc = substr($desc,0,30);

            $insR = $dbc->exec_statement($insP,array($subdept_no,$desc,$dept_no));
        }
        return True;
    }
    
    function form_content(){
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing subdept numbers, names, and what department
        number they belong to.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    function results_content(){
        return 'Import completed successfully';
    }
}

FannieDispatch::conditionalExec(false);

