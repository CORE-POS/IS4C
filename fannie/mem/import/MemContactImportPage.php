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

class MemContactImportPage extends FannieUploadPage {
    protected $title = "Fannie :: Member Tools";
    protected $header = "Import Member Contact Info";

    public $description = '[Member Contact Info] uploads members\' address, phone number, and
    email. Member numbers must already exist.';

    protected $preview_opts = array(
        'memnum' => array(
            'name' => 'memnum',
            'display_name' => 'Member Number',
            'default' => 0,
            'required' => True
        ),
        'street' => array(
            'name' => 'street',
            'display_name' => 'Street Address',
            'default' => 1,
            'required' => False
        ),
        'street2' => array(
            'name' => 'street2',
            'display_name' => '2nd Address Line',
            'default' => 2,
            'required' => False
        ),
        'city' => array(
            'name' => 'city',
            'display_name' => 'City',
            'default' => 3,
            'required' => False
        ),
        'state' => array(
            'name' => 'state',
            'display_name' => 'State',
            'default' => 4,
            'required' => False,
        ),
        'zip' => array(
            'name' => 'zip',
            'display_name' => 'Zip Code',
            'default' => 5,
            'required' => False,
        ),
        'ph1' => array(
            'name' => 'ph1',
            'display_name' => 'Phone #',
            'default' => 6,
            'required' => False,
        ),
        'ph2' => array(
            'name' => 'ph2',
            'display_name' => 'Alt. Phone #',
            'default' => 7,
            'required' => False,
        ),
        'email' => array(
            'name' => 'email',
            'display_name' => 'Email',
            'default' => 8,
            'required' => False,
        )
    );

    private $details = '';

    function MemContactImportPage()
    {
        global $FANNIE_COUNTRY;
        $country = (isset($FANNIE_COUNTRY)&&!empty($FANNIE_COUNTRY))?$FANNIE_COUNTRY:"US";
        if ($country == 'CA') {
            $this->preview_opts['state']['display_name'] = 'Province';
            $this->preview_opts['zip']['display_name'] = 'Postal Code';
        }
    }
    
    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $mn_index = $this->get_column_index('memnum');
        $st_index = $this->get_column_index('street');
        $st2_index = $this->get_column_index('street2');
        $city_index = $this->get_column_index('city');
        $state_index = $this->get_column_index('state');
        $zip_index = $this->get_column_index('zip');
        $ph_index = $this->get_column_index('ph1');
        $ph2_index = $this->get_column_index('ph2');
        $email_index = $this->get_column_index('email');

        foreach($linedata as $line){
            // get info from file and member-type default settings
            // if applicable
            $cardno = $line[$mn_index];
            if (!is_numeric($cardno)) continue; // skip bad record
            $street = ($st_index !== False) ? $line[$st_index] : "";
            $street2 = ($st2_index !== False) ? $line[$st2_index] : "";
            $city = ($city_index !== False) ? $line[$city_index] : "";
            $state = ($state_index !== False) ? $line[$state_index] : "";
            $zip = ($zip_index !== False) ? $line[$zip_index] : "";
            $ph1 = ($ph_index !== False) ? $line[$ph_index] : "";
            $ph2 = ($ph2_index !== False) ? $line[$ph2_index] : "";
            $email = ($email_index !== False) ? $line[$email_index] : "";

            // combine multi-line addresses
            $full_street = !empty($street2) ? $street."\n".$street2 : $street;

            $model = new MeminfoModel($dbc);
            $model->card_no($cardno);
            if (!empty($full_street)) $model->street($full_street);
            if (!empty($city)) $model->city($city);
            if (!empty($state)) $model->state($state);
            if (!empty($zip)) $model->zip($zip);
            if (!empty($ph1)) $model->phone($ph1);
            if (!empty($email)) $model->email_1($email);
            if (!empty($ph2)) $model->email_2($ph2);
            $try = $model->save();

            if ($try === False){
                $this->details .= "<b>Error importing member $cardno</b><br />";
            }
            else {
                $this->details .= "Imported contact info for member $cardno<br />";
            }

        }
        return True;
    }
    
    function form_content(){
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing member numbers, address, phone number(s),
        and emails. All fields are optional except member number.
        <br />A preview helps you to choose and map spreadsheet fields to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    function results_content(){
        return $this->details .= 'Import completed successfully';
    }
}

FannieDispatch::conditionalExec(false);
