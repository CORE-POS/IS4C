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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemNameNumImportPage extends FannieUploadPage 
{
    protected $title = "Fannie :: Member Tools";
    protected $header = "Import Member Names &amp; Numbers";

    public $description = '[Member Names and Numbers] loads member names and numbers. This is the
    starting point for importing existing member information. Member numbers need to be established
    first so other information can be associated by number.';

    protected $preview_opts = array(
        'memnum' => array(
            'name' => 'memnum',
            'display_name' => 'Member Number',
            'default' => 0,
            'required' => True
        ),
        'fn' => array(
            'name' => 'fn',
            'display_name' => 'First Name',
            'default' => 1,
            'required' => True
        ),
        'ln' => array(
            'name' => 'ln',
            'display_name' => 'Last Name',
            'default' => 2,
            'required' => True
        ),
        'mtype' => array(
            'name' => 'memtype',
            'display_name' => 'Type',
            'default' => 3,
            'required' => False
        )
    );


    private $details = '';
    
    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $mn_index = $this->get_column_index('memnum');
        $fn_index = $this->get_column_index('fn');
        $ln_index = $this->get_column_index('ln');
        $t_index = $this->get_column_index('mtype');

        $defaults_table = array();
        // get defaults directly from the memtype table if possible
        $mt = $dbc->tableDefinition('memtype');
        $defQ = $dbc->prepare_statement("SELECT memtype,custdataType,discount,staff,ssi from memtype");
        if ($dbc->tableExists('memdefaults') && (!isset($mt['custdataType']) || !isset($mt['discount']) || !isset($mt['staff']) || !isset($mt['ssi']))) {
            $defQ = $dbc->prepare_statement("SELECT memtype,cd_type as custdataType,discount,staff,SSI as ssi from memdefaults");
        }
        $defR = $dbc->exec_statement($defQ);
        while($defW = $dbc->fetch_row($defR)) {
            $defaults_table[$defW['memtype']] = array(
                'type' => $defW['custdataType'],
                'discount' => $defW['discount'],
                'staff' => $defW['staff'],
                'SSI' => $defW['ssi']
            );
        }

        // prepare statements
        $perP = $dbc->prepare_statement("SELECT MAX(personNum) FROM custdata WHERE CardNo=?");
        $dateP = $dbc->prepare_statement('INSERT INTO memDates (card_no) VALUES (?)');
        $model = new CustdataModel($dbc);
        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $cardno = $line[$mn_index];
            if (!is_numeric($cardno)) {
                continue; // skip bad record
            }

            $model->reset();
            $model->CardNo($cardno);

            $model->LastName($line[$ln_index]);
            $model->FirstName($line[$fn_index]);    
            $model->blueLine($cardno.' '.$line[$ln_index]);
            $model->memType(($t_index !== false) ? $line[$t_index] : 0);
            $type = "PC";
            $discount = 0;
            $staff = 0;
            $SSI = 0;
            if ($t_index !== false) {
                if (isset($defaults_table[$mtype]['type'])) {
                    $type = $defaults_table[$mtype]['type'];
                }
                if (isset($defaults_table[$mtype]['discount'])) {
                    $discount = $defaults_table[$mtype]['discount'];
                }
                if (isset($defaults_table[$mtype]['staff'])) {
                    $staff = $defaults_table[$mtype]['staff'];
                }
                if (isset($defaults_table[$mtype]['SSI'])) {
                    $SSI = $defaults_table[$mtype]['SSI'];
                }
            }

            $model->Type($type);
            $model->Discount($discount);
            $model->staff($staff);
            $model->SSI($SSI);

            // determine person number
            $perR = $dbc->exec_statement($perP,array($cardno));
            $pn = 1;
            if ($dbc->num_rows($perR) > 0) {
                $row = $dbc->fetch_row($perR);
                $pn = $row[0] + 1;
            }
            $model->personNum($pn);

            $model->CashBack(0);
            $model->Balance(0);
            $model->memCoupons(0);
        
            $insR = $model->save();
            if ($insR === false) {
                $this->details .= "<b>Error importing member $cardno ($fn $ln)</b><br />";
            } else {
                $this->details .= "Imported member $cardno ($fn $ln)<br />";
            }

            if ($pn == 1) {
                MeminfoModel::update($cardno,array());
                $dbc->exec_statement($dateP,array($cardno));
            }
        }

        return true;
    }
    
    function form_content()
    {
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing member numbers, first &amp; last names,
        and optionally type IDs.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    function results_content()
    {
        return $this->details .= 'Import completed successfully';
    }
}

FannieDispatch::conditionalExec(false);

