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
            'display_name' => 'Vendor Subcategory',
            'default' => 11,
            'required' => false
        ),
    );

    protected $use_splits = true;
    protected $use_js = true;

    private function validateVendorID($dbc)
    {
        if (!isset($_SESSION['vid'])){
            throw new Exception('Missing vendor setting');
        }
        $VENDOR_ID = $_SESSION['vid'];

        $prep = $dbc->prepare("SELECT vendorID,vendorName FROM vendors WHERE vendorID=?");
        $row = $dbc->getRow($prep,array($VENDOR_ID));
        if ($row === false) {
            throw new Exception('Missing vendor setting');
        }

        return array($VENDOR_ID, $row['vendorName']);
    }

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        try {
            list($VENDOR_ID, $vendorName) = $this->validateVendorID($dbc);
        } catch (Exception $ex) {
            $this->error_details = $ex->getMessage();
            return false;
        }

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
                brand, sku, size, upc, 
                units, cost, description, vendorDept, 
                vendorID, saleCost, modified, srp
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?
            )");
        $srpP = false;
        if ($dbc->tableExists('vendorSRPs')) {
            $srpP = $dbc->prepare("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");
        }
        $pmodel = new ProductsModel($dbc);

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
                $brand, $sku, $size, $upc,
                $qty, $reg_unit, $description, $category,
                $VENDOR_ID, $net_unit, date('Y-m-d H:i:s'), $srp
            );
            $dbc->execute($itemP, $args);

            if ($srpP) {
                $dbc->execute($srpP,array($VENDOR_ID,$upc,$srp));
            }

            if ($_SESSION['vUploadChangeCosts']) {
                $this->updateCost($pmodel, $upc, $VENDOR_ID, $reg_unit);
            }
        }

        return true;
    }

    private function updateCost($pmodel, $upc, $vendorID, $cost)
    {
        $pmodel->reset();
        $pmodel->upc($upc);
        $pmodel->default_vendor_id($vendorID);
        foreach ($pmodel->find('store_id') as $obj) {
            $obj->cost($cost);
            $obj->save();
        }
    }

    /* clear tables before processing */
    function split_start()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        try {
            list($VENDOR_ID, $vendorName) = $this->validateVendorID($dbc);
        } catch (Exception $ex) {
            $this->error_details = $ex->getMessage();
            return false;
        }

        $delP = $dbc->prepare("DELETE FROM vendorItems WHERE vendorID=?");
        $dbc->execute($delP,array($VENDOR_ID));
        $delP = $dbc->prepare("DELETE FROM vendorSRPs WHERE vendorID=?");
        $dbc->execute($delP,array($VENDOR_ID));

        $_SESSION['vUploadCheckDigits'] = FormLib::get('rm_cds') !== '' ? true : false;
        $_SESSION['vUploadChangeCosts'] = FormLib::get('up_costs') !== '' ? true : false;
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
        $vid = FormLib::get('vid');
        if ($vid === ''){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Selected</div>';
        }
        $dbc = $this->connection;
        $vendP = $dbc->prepare('SELECT vendorName FROM vendors WHERE vendorID=?');
        $vName = $dbc->getValue($vendP,array($vid));
        if ($vName === false) {
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Found</div>';
        }
        $_SESSION['vid'] = $vid;
        return '<div class="well"><legend>Instructions</legend>
            Upload a price file for <i>'.$vName.'</i> ('.$vid.'). File must be
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
        <p>Importing a vendor catalog replaces the existing catalog with data from
        a spreadsheet. The minimum required fields (columns) are:
            <ul>
                <li>SKU (the vendor\'s item number or other unique identifer)</li>
                <li>UPC</li>
                <li>Description</li>
                <li>Unit Cost (Reg) <strong>or</strong> both Case Cost (Reg) and Case Qty</li>
            </ul>
        </p>
        <p>
        If the vendor does not have SKUs, simply making a duplicate of the UPC column and using
        the same value for both is probably the easiest approach. The cost stored in vendor catalogs
        should always be unit cost. If the spreadsheet only contains case costs it must also have
        a case quantity column so the unit cost can be calculated (as case cost divided by case quantity).
        </p>
        <p>
        The following additional fields (columns) are optional:
            <ul>
                <li>Brand. If omitted, the vendor\'s name will go into the brand field.</li>
                <li>Unit Size. If omitted, the field will remain blank.</li>
                <li>Unit Cost (sale) <strong>or</strong> Case Cost (sale). This is used to store a temporary,
                    promotional cost. Again case cost must be used in conjunction with case quantity.</li>
                <li>Vendor subcategory. If omitted, all items will be assigned subcategory number one.</li>
            </ul>
        </p>
        <p><strong>General import tips</strong>
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

