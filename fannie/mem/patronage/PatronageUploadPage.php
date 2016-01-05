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

class PatronageUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Import Member Patronage";

    public $description = '[Import Patronage] loads in member purchase and patronage
    distribution data for a given year.';

    protected $preview_opts = array(
        'memnum' => array(
            'display_name' => 'Member Number',
            'default' => 0,
            'required' => true
        ),
        'gross' => array(
            'display_name' => 'Gross Purchases',
            'default' => 1,
            'required' => true
        ),
        'discount' => array(
            'display_name' => 'Discounts',
            'default' => 2,
            'required' => true
        ),
        'reward' => array(
            'display_name' => 'Rewards',
            'default' => 3,
            'required' => true
        ),
        'net' => array(
            'display_name' => 'Net Purchases',
            'default' => 4,
            'required' => true
        ),
        'pat' => array(
            'display_name' => 'Total Patronage',
            'default' => 5,
            'required' => true
        ),
        'cash' => array(
            'display_name' => 'Paid Out Patronage',
            'default' => 6,
            'required' => true
        ),
        'equity' => array(
            'display_name' => 'Retained Patronage',
            'default' => 7,
            'required' => true
        ),
        'fy' => array(
            'display_name' => 'Fiscal Year',
            'default' => 8,
            'required' => true
        ),
    );

    private $stats = array('imported'=>0, 'errors'=>array());
    
    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $mn_index = $this->get_column_index('memnum');
        $gross_index = $this->get_column_index('gross');
        $discount_index = $this->get_column_index('discount');
        $reward_index = $this->get_column_index('reward');
        $net_index = $this->get_column_index('net');
        $pat_index = $this->get_column_index('pat');
        $cash_index = $this->get_column_index('cash');
        $equity_index = $this->get_column_index('equity');
        $fy_index = $this->get_column_index('fy');

        // prepare statements
        $insP = $dbc->prepare("INSERT INTO patronage (cardno,purchase,discounts,rewards,net_purch,tot_pat,
            cash_pat,equit_pat,FY) VALUES (?,?,?,?,?,?,?,?,?)");
        foreach ($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $cardno = $line[$mn_index];
            if (!is_numeric($cardno)) continue; // skip bad record

            $args = array(
                $cardno,
                $line[$gross_index],
                $line[$discount_index],
                $line[$reward_index],
                $line[$net_index],
                $line[$pat_index],
                $line[$cash_index],
                $line[$equity_index],
                $line[$fy_index],
            );
            $insR = $dbc->execute($insP, $args);
            if ($insR) {
                $this->stats['imported']++;
            } else {
                $this->stats['errors'][] = $dbc->error();
            }
        }

        return true;
    }
    
    function form_content(){
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing member purchase information
        and patronage distribution amounts.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        return $this->simpleStats($this->stats);
    }
}

FannieDispatch::conditionalExec();

