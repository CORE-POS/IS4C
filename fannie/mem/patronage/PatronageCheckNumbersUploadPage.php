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

class PatronageCheckNumbersUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Import Redeemed Check Numbers";

    public $description = '[Import Redeemed Check Numbers] takes a spreadsheet of cashed check
    numbers and updates patronage records to reflect checks that are cashed.';
    public $themed = true;

    protected $preview_opts = array(
        'check_no' => array(
            'name' => 'check_no',
            'display_name' => 'Check Number',
            'default' => 0,
            'required' => true
        ),
        'tdate' => array(
            'name' => 'tdate',
            'display_name' => 'Date',
            'default' => 1,
            'required' => false
        ),
    );

    private $stats = array('imported'=>0, 'errors'=>array());
    
    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $cn_index = $this->get_column_index('check_no');
        $td_index = $this->get_column_index('tdate');

        $p = new PatronageModel($dbc);
        foreach ($linedata as $line) {

            $check_no = $line[$cn_index];
            if (!is_numeric($check_no)) continue; // skip bad record

            $p->check_number($check_no);
            $matches = $p->find();
            if (count($matches) == 0) {
                $this->stats['errors'][] = 'No check on file with #' . $check_no;
                continue;
            }

            // there shouldn't be more than one match
            // but loop anyway
            foreach ($matches as $obj) {
                if ($obj->cashed_date() != '') {
                    // if check has already been marked as cash, do not
                    // update the date. just leave it as is and
                    // count the record as imported successfully
                    $this->stats['imported']++;
                } else {
                    // tag the record with today's date OR the
                    // spreadsheet-supplied date
                    $tdate = date('Y-m-d');
                    if ($td_index !== false && $line[$td_index] != '') {
                        $stamp = strtotime($line[$td_index]);
                        if ($stamp) {
                            $tdate = date('Y-m-d', $stamp);
                        }
                    }
                    $obj->cashed_date($tdate);
                    $updated = $obj->save();
                    if ($updated) {
                        $this->stats['imported']++;
                    } else {
                        $this->stats['errors'][] = $dbc->error();
                    }
                }
            }
        }

        return true;
    }
    
    function form_content(){
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing check numbers and optionally
        the date those checks were cashed.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        $ret = '
            <p>Import Complete</p>
            <div class="alert alert-success">' . $this->stats['imported'] . ' records imported</div>';
        if ($this->stats['errors']) {
            $ret .= '<div class="alert alert-error"><ul>';
            foreach ($this->stats['errors'] as $error) {
                $ret .= '<li>' . $error . '</li>';
            }
            $ret .= '</ul></div>';
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

