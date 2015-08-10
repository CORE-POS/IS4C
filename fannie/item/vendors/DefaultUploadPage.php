<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DefaultUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Load Vendor Prices";
    public $header = "Upload Vendor price file";
    public $themed = true;

    public $description = '[Vendor Catalog Import] is the default tool for loading or updating vendor item data
    via spreadsheet.';

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

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
            'display_name' => 'Case Qty +',
            'default' => 5,
            'required' => false,
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Unit Size',
            'default' => 6,
            'required' => false
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Case Cost (Reg) +',
            'default' => 7,
            'required' => false
        ),
        'saleCost' => array(
            'name' => 'saleCost',
            'display_name' => 'Case Cost (Sale)',
            'default' => 8,
            'required' => false
        ),
        'unitCost' => array(
            'name' => 'unitCost',
            'display_name' => 'Unit Cost (Reg) +',
            'default' => 9,
            'required' => false
        ),
        'unitSaleCost' => array(
            'name' => 'unitSaleCost',
            'display_name' => 'Unit Cost (Sale)',
            'default' => 10,
            'required' => false
        ),
        'vDept' => array(
            'name' => 'vDept',
            'display_name' => 'Vendor Department',
            'default' => 11,
            'required' => false
        ),
    );

    protected $use_splits = true;
    protected $use_js = true;

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
        $REG_UNIT = $this->get_column_index('unitCost');
        $NET_UNIT = $this->get_column_index('unitSaleCost');
        $SRP = $this->get_column_index('srp');

        // PLU items have different internal UPCs
        // map vendor SKUs to the internal PLUs
        $SKU_TO_PLU_MAP = array();
        $skusP = $dbc->prepare('SELECT sku, upc FROM vendorSKUtoPLU WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            $SKU_TO_PLU_MAP[$skusW['sku']] = $skusW['upc'];
        }

        $itemP = $dbc->prepare("
            INSERT INTO vendorItems (
                brand, 
                sku,
                size,
                upc,
                units,
                cost,
                description,
                vendorDept,
                vendorID,
                saleCost,
                modified,
                srp
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )");
        $srpP = false;
        if ($dbc->tableExists('vendorSRPs')) {
            $srpP = $dbc->prepare_statement("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");
        }
        $pm = new ProductsModel($dbc);

        foreach($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$UPC])) continue;

            // grab data from appropriate columns
            $sku = $data[$SKU];
            $brand = ($BRAND === false) ? $vendorName : substr($data[$BRAND], 0, 50);
            $description = substr($data[$DESCRIPTION], 0, 50);
            if ($QTY === false) {
                $qty = 1.0;
            } else {
                $qty = $data[$QTY];
                if (!is_numeric($qty)) {
                    $qty = 1.0;
                }
            }
            $size = ($SIZE1 === false) ? '' : substr($data[$SIZE1], 0, 25);
            $upc = $data[$UPC];
            $upc = str_replace(' ', '', $upc);
            $upc = str_replace('-', '', $upc);
            if (strlen($upc) > 13) {
                $upc = substr($upc, -13);
            } else {
                $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            }
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            if ($_SESSION['vUploadCheckDigits'])
                $upc = '0'.substr($upc,0,12);
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $upc = $SKU_TO_PLU_MAP[$sku];
            }
            $category = ($CATEGORY === false) ? 0 : $data[$CATEGORY];

            $reg_unit = '';
            if ($REG_UNIT !== false) {
                $reg_unit = trim($data[$REG_UNIT]);
                $reg_unit = $this->priceFix($reg_unit);
            }
            if (!is_numeric($reg_unit) && $REG_COST !== false) {
                $reg = trim($data[$REG_COST]);
                $reg = $this->priceFix($reg);
                if (is_numeric($reg)) {
                    $reg_unit = $reg / $qty;
                }
            }

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg_unit) || !is_numeric($reg_unit))
                continue;

            $net_unit = '';
            if ($NET_UNIT !== false) {
                $net_unit = trim($data[$NET_UNIT]);
                $net_unit = $this->priceFix($net_unit);
            }
            if (!is_numeric($net_unit) && $NET_COST !== false) {
                $net = trim($data[$NET_COST]);
                $net = $this->priceFix($net);
                if (is_numeric($net)) {
                    $net_unit = $net / $qty;
                }
            }
            // blank spreadsheet cell
            if (empty($net_unit)) {
                $net_unit = 0.00;
            }
            $srp = ($SRP === false) ? 0.00 : trim($data[$SRP]);

            if ($net_unit == $reg_unit) {
                $net_unit = 0.00; // not really a sale
            }

            // syntax fixes. kill apostrophes in text fields,
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $srp = $this->priceFix($srp);
            if (!is_numeric($srp)) {
                $srp = 0;
            }

            $brand = str_replace("'","",$brand);
            $description = str_replace("'","",$description);

            $args = array(
                $brand, 
                $sku, 
                $size,
                $upc,
                $qty,
                $reg_unit,
                $description,
                $category,
                $VENDOR_ID,
                $net_unit,
                date('Y-m-d H:i:s'),
                $srp
            );
            $dbc->execute($itemP, $args);

            if ($srpP) {
                $dbc->exec_statement($srpP,array($VENDOR_ID,$upc,$srp));
            }

            if ($_SESSION['vUploadChangeCosts']) {
                $pm->reset();
                $pm->upc($upc);
                $pm->default_vendor_id($VENDOR_ID);
                foreach ($pm->find('store_id') as $obj) {
                    $obj->cost($reg_unit);
                    $obj->save();
                }
            }
        }

        return true;
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
        return '<input type="checkbox" name="rm_cds" value="1" checked /> Remove check digits<br />
                <input type="checkbox" name="up_costs" value="1" checked /> Update product costs';
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= sprintf('<p><a class="btn btn-default" 
            href="%sbatches/UNFI/RecalculateVendorSRPs.php?id=%d">Update SRPs</a></p>',
            $this->config->get('URL'), $_SESSION['vid']);

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
            return '<div class="alert alert-danger">Error: No Vendor Selected</div>';
        }
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vp = $dbc->prepare_statement('SELECT vendorName FROM vendors WHERE vendorID=?');
        $vr = $dbc->exec_statement($vp,array($vid));
        if ($dbc->num_rows($vr)==0){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Found</div>';
        }
        $vrow = $dbc->fetch_row($vr);
        $_SESSION['vid'] = $vid;
        return '<div class="well"><legend>Instructions</legend>
            Upload a price file for <i>'.$vrow['vendorName'].'</i> ('.$vid.'). File must be
            CSV. Files &gt; 2MB may be zipped.</div>';
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

    private function pricefix($str)
    {
        $str = str_replace('$', '', $str);
        $str = str_replace(',', '', $str);

        return $str;
    }

    public function helpContent()
    {
        return '
        <p>
            <ul>
                <li>Only CSV files are supported. This works most reliably for large data sets.</li>
                <li>Maximum file size is usually 2MB. CSV files may be zipped to reduce
                    file size.</li>
                <li>The purpose of the preview screen is to specify the format of your
                    file. It shows the first five rows of data with dropdowns above each
                    column. Use the dropdowns to specify what (if any) data is present in 
                    each column. For example, if UPCs are in the 3rd column, set the dropdown
                    for the third column to UPC.</li>
                <li>Large files may take awhile to process. Give it 5 or 10 minutes before
                    deciding it didn\'t work.</li>
            </ul>
        </p>';
    }
}

FannieDispatch::conditionalExec(false);

