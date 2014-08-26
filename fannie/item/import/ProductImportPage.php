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

class ProductImportPage extends FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Products";

    public $description = '[Product Import] loads or updates product data via spreadsheet. Used
    primarily for intial database population.';

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC',
            'default' => 0,
            'required' => true
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 1,
            'required' => true
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Price',
            'default' => 2,
            'required' => true
        ),
        'dept' => array(
            'name' => 'dept',
            'display_name' => 'Department #',
            'default' => 3,
            'required' => false
        )
    );

    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $defaults_table = array();
        $defQ = $dbc->prepare_statement("SELECT dept_no,dept_tax,dept_fs,dept_discount FROM departments");
        $defR = $dbc->exec_statement($defQ);
        while($defW = $dbc->fetch_row($defR)){
            $defaults_table[$defW['dept_no']] = array(
                'tax' => $defW['dept_tax'],
                'fs' => $defW['dept_fs'],
                'discount' => $defW['dept_discount']
            );
        }

        $upc_index = $this->get_column_index('upc');
        $desc_index = $this->get_column_index('desc');
        $price_index = $this->get_column_index('price');
        $dept_index = $this->get_column_index('dept');

        $ret = true;
        $linecount = 0;
        $checks = (FormLib::get_form_value('checks')=='yes') ? true : false;
        $skipExisting = FormLib::get('skipExisting', 1);
        $model = new ProductsModel($dbc);
        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $upc = $line[$upc_index];
            $desc = $line[$desc_index];
            $price =  $line[$price_index];  
            $price = str_replace('$', '', $price);
            $price = trim($price);
            $dept = ($dept_index !== false) ? $line[$dept_index] : 0;
            $tax = 0;
            $fs = 0;
            $discount = 1;
            if ($dept_index !== false){
                if (isset($defaults_table[$dept]['tax']))
                    $tax = $defaults_table[$dept]['tax'];
                if (isset($defaults_table[$dept]['discount']))
                    $discount = $defaults_table[$dept]['discount'];
                if (isset($defaults_table[$dept]['fs']))
                    $fs = $defaults_table[$dept]['fs'];
            }

            // upc cleanup
            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows

            if ($checks) {
                $upc = substr($upc,0,strlen($upc)-1);
            }
            $upc = BarcodeLib::padUPC($upc);

            if (strlen($desc) > 35) $desc = substr($desc,0,35);     

            $model->reset();
            $model->upc($upc);
            if ($model->load() && $skipExisting) {
                continue;
            }
            $model->description($desc);
            $model->normal_price($price);
            $model->department($dept);
            $model->tax($tax);
            $model->foodstamp($fs);
            $model->discount($discount);
            // fully init new record
            $model->pricemethod(0);
            $model->special_price(0);
            $model->specialpricemethod(0);
            $model->specialquantity(0);
            $model->specialgroupprice(0);
            $model->advertised(0);
            $model->tareweight(0);
            $model->start_date('');
            $model->end_date('');
            $model->discounttype(0);
            $model->wicable(0);
            $model->inUse(1);
            $try = $model->save();

            if ($try === false) {
                $ret = false;
                $this->error_details = 'There was an error importing UPC '.$upc;
            }

            if ($linecount++ % 100 == 0) {
                set_time_limit(30);
            }
        }

        return $ret;
    }

    function form_content()
    {
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs, descriptions, prices,
        and optional department numbers
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    function preview_content()
    {
        return '<input type="checkbox" name="checks" value="yes" />
            Remove check digits from UPCs
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="checkbox" name="skipExisting" value="1" checked />
            Skip Existing Items
            ';
    }

    function results_content()
    {
        return 'Import completed successfully';
    }
}

FannieDispatch::conditionalExec(false);

