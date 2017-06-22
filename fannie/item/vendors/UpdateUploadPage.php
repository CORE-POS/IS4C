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

class UpdateUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Update Vendor Catalog";
    public $header = "Update Vendor Catalog";
    public $themed = true;

    public $description = '[Vendor Catalog Update] updates existing information in the vendor catalog
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
            'display_name' => 'Description',
            'default' => 3,
            'required' => false
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
            'display_name' => 'Case Cost (Reg)',
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
            'display_name' => 'Unit Cost (Reg)',
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
    protected $use_js = false;

    private function validateVendorID($dbc)
    {
        if (!isset($this->session->vid)){
            throw new Exception('Missing vendor setting');
        }
        $VENDOR_ID = $this->session->vid;

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
        $existsP = $dbc->prepare("SELECT upc FROM vendorItems WHERE upc=? AND vendorID=?");
        $costP = $dbc->prepare('UPDATE products SET cost=?, modified=' . $dbc->now() . ' WHERE upc=? AND default_vendor_id=?');
        $updatedUPCs = array();

        $dbc->startTransaction();
        foreach($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = $data[$indexes['sku']];
            $brand = ($indexes['brand'] === false) ? $vendorName : substr($data[$indexes['brand']], 0, 50);
            $description = ($indexes['desc'] === false) ? '' : substr($data[$indexes['desc']], 0, 50);
            $description = preg_replace('/[^\x20-\x7E]/','', $description);
            if ($indexes['qty'] === false) {
                $qty = 1.0;
            } else {
                $qty = $data[$indexes['qty']];
                if (!is_numeric($qty)) {
                    $qty = 1.0;
                }
            }
            $size = ($indexes['size'] === false) ? '' : substr($data[$indexes['size']], 0, 25);
            $upc = $data[$indexes['upc']];
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
            if ($this->session->vUploadCheckDigits)
                $upc = '0'.substr($upc,0,12);
            $category = ($indexes['vDept'] === false) ? 0 : $data[$indexes['vDept']];

            $reg_unit = '';
            if ($indexes['unitCost'] !== false) {
                $reg_unit = trim($data[$indexes['unitCost']]);
                $reg_unit = $this->priceFix($reg_unit);
            }
            if (!is_numeric($reg_unit) && $indexes['cost'] !== false) {
                $reg = trim($data[$indexes['cost']]);
                $reg = $this->priceFix($reg);
                if (is_numeric($reg)) {
                    $reg_unit = $reg / $qty;
                }
            }

            $net_unit = '';
            if ($indexes['unitSaleCost'] !== false) {
                $net_unit = trim($data[$indexes['unitSaleCost']]);
                $net_unit = $this->priceFix($net_unit);
            }
            if (!is_numeric($net_unit) && $indexes['saleCost'] !== false) {
                $net = trim($data[$indexes['saleCost']]);
                $net = $this->priceFix($net);
                if (is_numeric($net)) {
                    $net_unit = $net / $qty;
                }
            }
            if (empty($net_unit)) {
                $net_unit = 0.00;
            }
            $srp = ($indexes['srp'] === false) ? 0.00 : trim($data[$indexes['srp']]);

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

            if ($dbc->getValue($existsP, array($upc, $VENDOR_ID))) {
                $query = 'UPDATE vendorItems SET ';
                $args = array();
                if ($indexes['brand'] !== false && !empty($brand)) {
                    $query .= 'brand=?, ';
                    $args[] = $brand;
                }
                if ($indexes['desc'] !== false) {
                    $query .= 'description=?, ';
                    $args[] = $description;
                }
                if ($indexes['qty'] !== false && !empty($qty)) {
                    $query .= 'units=?, ';
                    $args[] = $qty;
                }
                if ($indexes['unitCost'] !== false || $indexes['cost'] !== false) {
                    $query .= 'cost=?, ';
                    $args[] = $reg_unit;
                }
                if ($indexes['unitSaleCost'] !== false || $indexes['saleCost'] !== false) {
                    $query .= 'saleCost=?, ';
                    $args[] = $net_unit;
                }
                if ($indexes['vDept'] !== false && !empty($category)) {
                    $query .= 'vendorDept=?, ';
                    $args[] = $category;
                }
                if ($indexes['srp'] !== false) {
                    $query .= 'srp=?, ';
                    $args[] = $srp;
                }
                $query .= ' modified=\'' . date('Y-m-d H:i:s') . '\' 
                        WHERE upc=? AND vendorID=?';
                $args[] = $upc;
                $args[] = $VENDOR_ID;
                $prep = $dbc->prepare($query);
                $dbc->execute($prep, $args);
            } elseif (is_numeric($reg_unit) && $reg_unit <> 0) {
                $args = array(
                    $brand, $sku, $size, $upc,
                    $qty, $reg_unit, $description, $category,
                    $VENDOR_ID, $net_unit, date('Y-m-d H:i:s'), $srp
                );
                $dbc->execute($itemP, $args);

                if ($srpP) {
                    $dbc->execute($srpP,array($VENDOR_ID,$upc,$srp));
                }
            }

            if ($this->session->vUploadChangeCosts && $reg_unit) {
                $dbc->execute($costP, array($reg_unit, $upc, $VENDOR_ID));
                $updatedUPCs[] = $upc;
            }
        }

        if (count($updatedUPCs) > 0) {
            $updateModel = new ProdUpdateModel($dbc);
            $updateModel->logManyUpdates($updatedUPCs, ProdUpdateModel::UPDATE_EDIT);
        }
        $dbc->commitTransaction();

        return true;
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

        $this->session->vUploadCheckDigits = FormLib::get('rm_cds') !== '' ? true : false;
        $this->session->vUploadChangeCosts = FormLib::get('up_costs') !== '' ? true : false;
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
            $this->config->get('URL'), $this->session->vid);

        unset($this->session->vid);
        unset($this->session->vUploadCheckDigits);
        unset($this->session->vUploadChangeCosts);

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
        $this->session->vid = $vid;
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

    private function priceFix($str)
    {
        $str = str_replace('$', '', $str);
        $str = str_replace(',', '', $str);

        return $str;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->preview_content()));
    }
}

FannieDispatch::conditionalExec();

