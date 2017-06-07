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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProductImportPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Product Tools";
    protected $header = "Import Products";

    public $description = '[Product Import] loads or updates product data via spreadsheet. Used
    primarily for intial database population.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC',
            'default' => 0,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description',
            'default' => 1,
            'required' => true
        ),
        'price' => array(
            'display_name' => 'Price',
            'default' => 2,
            'required' => true
        ),
        'dept' => array(
            'display_name' => 'Department #',
            'default' => 3,
        )
    );

    private $stats = array('imported'=>0, 'errors'=>array());

    private function deptDefaults($dbc)
    {
        $defaults_table = array();
        $defQ = $dbc->prepare("SELECT dept_no,dept_tax,dept_fs,dept_discount FROM departments");
        $defR = $dbc->execute($defQ);
        while($defW = $dbc->fetch_row($defR)){
            $defaults_table[$defW['dept_no']] = array(
                'tax' => $defW['dept_tax'],
                'fs' => $defW['dept_fs'],
                'discount' => $defW['dept_discount']
            );
        }

        return $defaults_table;
    }

    private function getDefaultableSettings($dept, $defaults_table)
    {
        $tax = 0;
        $fstamp = 0;
        $discount = 1;
        if ($dept && isset($defaults_table[$dept])) {
            if (isset($defaults_table[$dept]['tax']))
                $tax = $defaults_table[$dept]['tax'];
            if (isset($defaults_table[$dept]['discount']))
                $discount = $defaults_table[$dept]['discount'];
            if (isset($defaults_table[$dept]['fs']))
                $fstamp = $defaults_table[$dept]['fs'];
        }

        return array($tax, $fstamp, $discount);
    }

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $defaults_table = $this->deptDefaults($dbc);

        $ret = true;
        $linecount = 0;
        $checks = (FormLib::get_form_value('checks')=='yes') ? true : false;
        $skipExisting = FormLib::get('skipExisting', 0);
        $model = new ProductsModel($dbc);
        $dbc->startTransaction();
        foreach($linedata as $line) {
            // get info from file and member-type default settings
            // if applicable
            $upc = $line[$indexes['upc']];
            $desc = $line[$indexes['desc']];
            $price =  $line[$indexes['price']];  
            $price = str_replace('$', '', $price);
            $price = trim($price);
            $dept = ($indexes['dept'] !== false) ? $line[$indexes['dept']] : 0;
            list($tax, $fstamp, $discount) = $this->getDefaultableSettings($dept, $defaults_table);

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
            $model->store_id(1);
            $exists = $model->load();
            if ($exists && $skipExisting) {
                continue;
            }
            $model->description($desc);
            $model->normal_price($price);
            $model->department($dept);
            $model->tax($tax);
            $model->foodstamp($fstamp);
            $model->discount($discount);
            if (!$exists) {
                // fully init new record
                $model->pricemethod(0);
                $model->special_price(0);
                $model->specialpricemethod(0);
                $model->specialquantity(0);
                $model->specialgroupprice(0);
                $model->advertised(0);
                $model->tareweight(0);
                $model->start_date('0000-00-00');
                $model->end_date('0000-00-00');
                $model->discounttype(0);
                $model->wicable(0);
                $model->inUse(1);
                $model->created(date('Y-m-d H:i:s'));
            }
            $try = $model->save();

            if ($try) {
                $this->stats['imported']++;
            } else {
                $this->stats['errors'][] = 'Error importing UPC ' . $upc;
            }
        }
        $dbc->commitTransaction();

        return $ret;
    }

    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing product UPCs, descriptions, prices,
        and optional department numbers
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function preview_content()
    {
        return '<label><input type="checkbox" name="checks" value="yes" />
            Remove check digits from UPCs</label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <label><input type="checkbox" name="skipExisting" value="1" checked />
            Skip Existing Items</label>
            ';
    }

    function results_content()
    {
        return $this->simpleStats($this->stats);
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->results_content()));
        $data = array('9999999999999', 'test item', 9.99, 1);
        $indexes = array('upc'=>0, 'desc'=>1, 'price'=>2, 'dept'=>3);
        $phpunit->assertEquals(true, $this->process_file(array($data), $indexes));
    }
}

FannieDispatch::conditionalExec();

