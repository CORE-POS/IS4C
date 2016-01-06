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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EquityHistoryImportPage extends \COREPOS\Fannie\API\FannieUploadPage {
    protected $title = "Fannie :: Member Tools";
    protected $header = "Import Existing Member Equity";

    public $description = '[Equity History Import] loads information about members\' pre-existing
    equity balance. Pre-existing means equity was not purchased using this POS.';
    public $themed = true;

    protected $preview_opts = array(
        'memnum' => array(
            'display_name' => 'Member Number',
            'default' => 0,
            'required' => True
        ),
        'amt' => array(
            'display_name' => 'Equity Amt',
            'default' => 1,
            'required' => True
        ),
        'date' => array(
            'display_name' => 'Date',
            'default' => 2,
        ),
        'transID' => array(
            'display_name' => 'Transaction ID',
            'default' => 3,
        ),
        'dept' => array(
            'display_name' => 'Dept. #',
            'default' => 4,
        )
    );

    private $stats = array('imported'=>0, 'errors'=>array());
    
    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $mn_index = $this->get_column_index('memnum');
        $amt_index = $this->get_column_index('amt');
        $date_index = $this->get_column_index('date');
        $dept_index = $this->get_column_index('dept');
        $trans_index = $this->get_column_index('transID');

        // prepare statements
        $insP = $dbc->prepare("INSERT INTO stockpurchases (card_no,stockPurchase,
                tdate,trans_num,dept) VALUES (?,?,?,?,?)");
        foreach($linedata as $line){
            // get info from file and member-type default settings
            // if applicable
            $cardno = $line[$mn_index];
            if (!is_numeric($cardno)) continue; // skip bad record
            $amt = $line[$amt_index];
            $date = ($date_index !== False) ? $line[$date_index] : '0000-00-00';
            $dept = ($dept_index !== False) ? $line[$dept_index] : 0;   
            $trans = ($trans_index !== False) ? $line[$trans_index] : "";

            $insR = $dbc->execute($insP,array($cardno,$amt,$date,$trans,$dept));
            if ($insR === False){
                $this->stats['errors'][] = "Error importing entry for member $cardno";
            } else {
                $this->stats['imported']++;
            }
        }

        return true;
    }
    
    function form_content(){
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing member numbers and equity purchase amounts.
        Optionally, you can include purchase dates, transaction identifiers, and
        department numbers.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        return $this->simpleStates($this->stats);
    }
}

FannieDispatch::conditionalExec();

