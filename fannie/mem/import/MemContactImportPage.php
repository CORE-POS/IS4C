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

class MemContactImportPage extends \COREPOS\Fannie\API\FannieUploadPage {
    protected $title = "Fannie :: Member Tools";
    protected $header = "Import Member Contact Info";

    public $description = '[Member Contact Info] uploads members\' address, phone number, and
    email. Member numbers must already exist.';

    protected $preview_opts = array(
        'memnum' => array(
            'display_name' => 'Member Number',
            'default' => 0,
            'required' => true
        ),
        'street' => array(
            'display_name' => 'Street Address',
            'default' => 1,
        ),
        'street2' => array(
            'display_name' => '2nd Address Line',
            'default' => 2,
        ),
        'city' => array(
            'display_name' => 'City',
            'default' => 3,
        ),
        'state' => array(
            'display_name' => 'State',
            'default' => 4,
        ),
        'zip' => array(
            'display_name' => 'Zip Code',
            'default' => 5,
        ),
        'ph1' => array(
            'display_name' => 'Phone #',
            'default' => 6,
        ),
        'ph2' => array(
            'display_name' => 'Alt. Phone #',
            'default' => 7,
        ),
        'email' => array(
            'display_name' => 'Email',
            'default' => 8,
        )
    );

    private $stats = array('imported'=>0, 'errors'=>array());

    function __construct()
    {
        parent::__construct();
        $country = FannieConfig::config('COUNTRY', 'US');
        if ($country == 'CA') {
            $this->preview_opts['state']['display_name'] = 'Province';
            $this->preview_opts['zip']['display_name'] = 'Postal Code';
        }
    }
    
    public function process_file($linedata, $indexes)
    {
        foreach ($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $cardno = $line[$indexes['memnum']];
            if (!is_numeric($cardno)) continue; // skip bad record
            $json = array('cardNo' => $cardno);
            if ($indexes['street'] !== false) {
                $json['addressFirstLine'] = $line[$indexes['street']];
            }
            if ($indexes['street2'] !== false) {
                $json['addressSecondLine'] = $line[$indexes['street2']];
            }
            if ($indexes['city'] !== false) {
                $json['city'] = $line[$indexes['city']];
            }
            if ($indexes['state'] !== false) {
                $json['state'] = $line[$indexes['state']];
            }
            if ($indexes['zip'] !== false) {
                $json['zip'] = $line[$indexes['zip']];
            }
            if ($indexes['ph1'] !== false || $indexes['ph2'] !== false || $indexes['email'] !== false) {
                $json['customers'] = array(array('accountHolder' => 1));
                if ($indexes['ph1'] !== false) {
                    $json['customers'][0]['phone'] = $line[$indexes['ph1']];
                }
                if ($indexes['ph2'] !== false) {
                    $json['customers'][0]['altPhone'] = $line[$indexes['ph2']];
                }
                if ($indexes['email'] !== false) {
                    $json['customers'][0]['email'] = $line[$indexes['email']];
                }
            }

            $resp = \COREPOS\Fannie\API\member\MemberREST::post($cardno, $json);

            if ($resp['errors'] > 0) {
                $this->stats['errors'][] = "Error importing member $cardno";
            } else {
                $this->stats['imported']++;
            }
        }

        return true;
    }
    
    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing member numbers, address, phone number(s),
        and emails. All fields are optional except member number.
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
        $data = array(1, '123 4th st', 'apt1', 'city', 'st', 12345, '867-5309', '', 'not@email');
        $indexes = array('memnum'=>0, 'street'=>1, 'street2'=>2, 'city'=>3, 'state'=>4,
            'zip'=>5, 'ph1'=>6, 'ph2'=>7, 'email'=>8);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();
