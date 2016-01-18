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
     4Sep2012 Eric Lee Change $header to Sub-Departments.
              Add some notes to the initial page.
*/
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SubdeptImportPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Sub-Departments";

    protected $must_authenticate = true;
    protected $auth_classes = array('departments', 'admin');

    public $description = '[Subdepartment Import] loads subdept data from a spreadsheet.';

    protected $preview_opts = array(
        'sn' => array(
            'display_name' => 'SubDept #',
            'default' => 0,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Name',
            'default' => 1,
            'required' => true
        ),
        'dn' => array(
            'display_name' => 'Dept #',
            'default' => 2,
            'required' => true
        )
    );

    private $stats = array('imported'=>0, 'errors'=>array());

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $insP = $dbc->prepare("INSERT INTO subdepts (subdept_no,subdept_name,dept_ID)
                    VALUES (?,?,?)");

        foreach ($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $dept_no = $line[$indexes['dn']];
            $desc = $line[$indexes['desc']];
            $subdept_no = $line[$indexes['sn']];

            if (!is_numeric($subdept_no)) continue; // skip header/blank rows

            if (strlen($desc) > 30) $desc = substr($desc,0,30);

            $insR = $dbc->execute($insP,array($subdept_no,$desc,$dept_no));
            if ($insR) {
                $this->stats['imported']++;
            } else {
                $this->stats['errors'][] = 'Error importing sub department #' . $subdept_no;
            }
        }

        return true;
    }
    
    function form_content(){
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing subdept numbers, names, and what department
        number they belong to.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        return $this->simpleStats($this->stats);
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
        $data = array(999, 'test subdept', 1);
        $indexes = array('sn'=>0, 'desc'=>1, 'dn'=>2);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();

