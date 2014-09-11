<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DefaultUploadPage extends FannieUploadPage {

    public $title = "Fannie - Load Vendor Prices";
    public $header = "Upload Vendor price file";

    public $description = '[Vendor Catalog Import] is the default tool for loading or updating vendor item data
    via spreadsheet.';

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC *',
            'default' => 0,
            'required' => true
        ),
        'srp' => array(
            'name' => 'srp',
            'display_name' => 'SRP',
            'default' => 1,
            'required' => false
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand',
            'default' => 2,
            'required' => false,
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description *',
            'default' => 3,
            'required' => true
        ),
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU *',
            'default' => 4,
            'required' => true
        ),
        'qty' => array(
            'name' => 'qty',
            'display_name' => 'Case Qty *',
            'default' => 5,
            'required' => true
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Unit Size',
            'default' => 6,
            'required' => false
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Case Cost (Reg) *',
            'default' => 7,
            'required' => true
        ),
        'saleCost' => array(
            'name' => 'saleCost',
            'display_name' => 'Case Cost (Sale)',
            'default' => 8,
            'required' => false
        ),
        'vDept' => array(
            'name' => 'vDept',
            'display_name' => 'Vendor Department',
            'default' => 9,
            'required' => false
        ),
    );

    protected $use_splits = true;

    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!isset($_SESSION['vid'])){
            $this->error_details = 'Missing vendor setting';
            return False;
        }
        $VENDOR_ID = $_SESSION['vid'];

        $p = $dbc->prepare_statement("SELECT vendorID,vendorName FROM vendors WHERE vendorID=?");
        $idR = $dbc->exec_statement($p,array($VENDOR_ID));
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $idW = $dbc->fetch_row($idR);
        $vendorName = $idW['vendorName'];

        $SKU = $this->get_column_index('sku');
        $BRAND = $this->get_column_index('brand');
        $DESCRIPTION = $this->get_column_index('desc');
        $QTY = $this->get_column_index('qty');
        $SIZE1 = $this->get_column_index('size');
        $UPC = $this->get_column_index('upc');
        $CATEGORY = $this->get_column_index('vDept');
        $REG_COST = $this->get_column_index('cost');
        $NET_COST = $this->get_column_index('saleCost');
        $SRP = $this->get_column_index('srp');

        $itemP = $dbc->prepare_statement("INSERT INTO vendorItems 
                    (brand,sku,size,upc,units,cost,description,vendorDept,vendorID)
                    VALUES (?,?,?,?,?,?,?,?,?)");
        $vi_def = $dbc->tableDefinition('vendorItems');
        if (isset($vi_def['saleCost'])) {
            $itemP = $dbc->prepare_statement("INSERT INTO vendorItems 
                        (brand,sku,size,upc,units,cost,description,vendorDept,vendorID,saleCost)
                        VALUES (?,?,?,?,?,?,?,?,?,?)");
        }
        $srpP = $dbc->prepare_statement("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");
        $pm = new ProductsModel($dbc);

        foreach($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$UPC])) continue;

            // grab data from appropriate columns
            $sku = $data[$SKU];
            $brand = ($BRAND === false) ? $vendorName : substr($data[$BRAND], 0, 50);
            $description = substr($data[$DESCRIPTION], 0, 50);
            $qty = $data[$QTY];
            $size = ($SIZE1 === false) ? '' : substr($data[$SIZE1], 0, 25);
            $upc = $data[$UPC];
            $upc = str_replace(' ', '', $upc);
            $upc = str_replace('-', '', $upc);
            if (strlen($upc) > 13) {
                $upc = substr($upc, 0, 13);
            } else {
                $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            }
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            if ($_SESSION['vUploadCheckDigits'])
                $upc = '0'.substr($upc,0,12);
            $category = ($CATEGORY === false) ? 0 : $data[$CATEGORY];
            $reg = trim($data[$REG_COST]);
            $net = ($NET_COST !== false) ? trim($data[$NET_COST]) : 0.00;
            // blank spreadsheet cell
            if (empty($net)) {
                $net = 0.00;
            }
            $srp = ($SRP === false) ? 0.00 : trim($data[$SRP]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg))
                continue;

            if ($net == $reg) {
                $net = 0.00; // not really a sale
            }

            // syntax fixes. kill apostrophes in text fields,
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $brand = str_replace("'","",$brand);
            $description = str_replace("'","",$description);
            $reg = str_replace('$',"",$reg);
            $reg = str_replace(",","",$reg);
            $net = str_replace('$',"",$net);
            $net = str_replace(",","",$net);
            $srp = str_replace('$',"",$srp);
            $srp = str_replace(",","",$srp);

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg))
                continue;

            if (!is_numeric($qty)) {
                $qty = 1.0;
            }
            // need unit cost, not case cost
            $reg_unit = $reg / $qty;
            $net_unit = $net / $qty;

            $args = array($brand, $sku, $size,
                    $upc,$qty,$reg_unit,$description,$category,$VENDOR_ID);
            if (isset($vi_def['saleCost'])) {
                $args[] = $net_unit;
            }
            $dbc->exec_statement($itemP,$args);

            if (is_numeric($srp)) {
                $dbc->exec_statement($srpP,array($VENDOR_ID,$upc,$srp));
            }

            if ($_SESSION['vUploadChangeCosts']) {
                $pm->reset();
                $pm->upc($upc);
                if ($pm->load()) {
                    $pm->cost($reg_unit);
                    $pm->save();
                }
            }
        }

        return True;
    }

    /* clear tables before processing */
    function split_start()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!isset($_SESSION['vid'])){
            $this->error_details = 'Missing vendor setting';
            return False;
        }
        $VENDOR_ID = $_SESSION['vid'];

        $p = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorID=?");
        $idR = $dbc->exec_statement($p,array($VENDOR_ID));
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }

        $p = $dbc->prepare_statement("DELETE FROM vendorItems WHERE vendorID=?");
        $dbc->exec_statement($p,array($VENDOR_ID));
        $p = $dbc->prepare_statement("DELETE FROM vendorSRPs WHERE vendorID=?");
        $dbc->exec_statement($p,array($VENDOR_ID));

        if (FormLib::get_form_value('rm_cds') !== '') {
            $_SESSION['vUploadCheckDigits'] = true;
        } else {
            $_SESSION['vUploadCheckDigits'] = false;
        }
        if (FormLib::get('up_costs') !== '') {
            $_SESSION['vUploadChangeCosts'] = true;
        } else {
            $_SESSION['vUploadChangeCosts'] = false;
        }
    }

    function preview_content()
    {
        return '<input type="checkbox" name="rm_cds" checked /> Remove check digits<br />
                <input type="checkbox" name="up_costs" checked /> Update product costs';
    }

    function results_content()
    {
        $ret = "Price data import complete<p />";
        $ret .= '<a href="'.$_SERVER['PHP_SELF'].'">Upload Another</a>';
        unset($_SESSION['vid']);
        unset($_SESSION['vUploadCheckDigits']);
        unset($_SESSION['vUploadChangeCosts']);
        return $ret;
    }

    function form_content()
    {
        global $FANNIE_OP_DB;
        $vid = FormLib::get_form_value('vid');
        if ($vid === ''){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<span style="color:red;">Error: No Vendor Selected</span>';
        }
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vp = $dbc->prepare_statement('SELECT vendorName FROM vendors WHERE vendorID=?');
        $vr = $dbc->exec_statement($vp,array($vid));
        if ($dbc->num_rows($vr)==0){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<span style="color:red;">Error: No Vendor Found</span>';
        }
        $vrow = $dbc->fetch_row($vr);
        $_SESSION['vid'] = $vid;
        return '<fieldset><legend>Instructions</legend>
            Upload a price file for <i>'.$vrow['vendorName'].'</i> ('.$vid.'). File must be
            CSV. Files &gt; 2MB may be zipped.</fieldset><br />';
    }

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli') {
            /* this page requires a session to pass some extra
               state information through multiple requests */
            @session_start();
        }

        return parent::preprocess();
    }
}

FannieDispatch::conditionalExec(false);

