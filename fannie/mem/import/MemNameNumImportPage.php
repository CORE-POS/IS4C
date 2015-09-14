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

class MemNameNumImportPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Member Tools";
    protected $header = "Import Member Names &amp; Numbers";
    public $themed = true;

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

    private $stats = array('imported'=>0, 'errors'=>array());
    
    function process_file($linedata)
    {
        global $FANNIE_OP_DB, $FANNIE_NAMES_PER_MEM;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $mn_index = $this->get_column_index('memnum');
        $fn_index = $this->get_column_index('fn');
        $ln_index = $this->get_column_index('ln');
        $t_index = $this->get_column_index('memtype');

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

        foreach ($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $cardno = $line[$mn_index];
            if (!is_numeric($cardno)) {
                continue; // skip bad record
            }

            $json = array(
                'cardNo' => $cardno,
                'customerTypeID' => ($t_index !== false ? $line[$t_index] : 0),
                'contactAllowed' => 1,
                'chargeBalance' => 0,
                'chargeLimit' => 0,
                'customers' => array(),
            );
            $customer = array();

            $customer['firstName'] = $line[$fn_index];
            $customer['lastName'] = $line[$ln_index];

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

            $json['memberStatus'] = $type;
            $customer['discount'] = $discount;
            $customer['staff'] = $staff;
            $customer['lowIncomeBenefits'] = $SSI;

            // determine person number
            if ($FANNIE_NAMES_PER_MEM == 1) {
                $customer['accountHolder'] = 1;
            } else {
                $account = \COREPOS\Fannie\API\member\MemberREST::get($cardno);
                if ($account) {
                    $customer['accountHolder'] = 0;
                } else {
                    $customer['accountHolder'] = 1;
                }
            }

            $json['customers'][] = $customer;
        
            $resp = \COREPOS\Fannie\API\member\MemberREST::post($cardno, $json);
            if ($resp['errors'] > 0) {
                $this->stats['errors'][] = "Error importing member $cardno ({$line[$fn_index]} {$line[$ln_index]})";
            } else {
                $this->stats['imported']++;
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

