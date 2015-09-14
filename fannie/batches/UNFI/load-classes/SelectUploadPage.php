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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SelectUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {

    public $title = "Fannie - Select Nutrition Prices";
    public $header = "Upload Select Nutrition price file";
    public $themed = true;

    public $description = '[Select Nutrition Catalog Import] specialized vendor import tool. Column choices
    default to Select price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC *',
            'default' => 14,
            'required' => True
        ),
        'srp' => array(
            'name' => 'srp',
            'display_name' => 'SRP *',
            'default' => 16,
            'required' => True
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand *',
            'default' => 2,
            'required' => True
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description *',
            'default' => 6,
            'required' => True
        ),
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU *',
            'default' => 1,
            'required' => true
        ),
        'qty' => array(
            'name' => 'qty',
            'display_name' => 'Case Qty *',
            'default' => 3,
            'required' => True
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Unit Size',
            'default' => 4,
            'required' => False
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Case Cost (Reg) *',
            'default' => 8,
            'required' => True
        ),
        'saleCost' => array(
            'name' => 'saleCost',
            'display_name' => 'Case Cost (Sale)',
            'default' => 12,
            'required' => false
        ),
        'cat' => array(
            'name' => 'cat',
            'display_name' => 'Select Category # *',
            'default' => 5,
            'required' => True
        ),
        'flags' => array(
            'name' => 'flags',
            'display_name' => 'Flags',
            'default' => 20,
            'required' => false
        ),
    );

    protected $use_splits = false;
    protected $use_js = true;

    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $idP = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorName='SELECT' ORDER BY vendorID");
        $idR = $dbc->exec_statement($idP);
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $idW = $dbc->fetchRow($idR);
        $VENDOR_ID = $idW['vendorID'];

        $SKU = $this->get_column_index('sku');
        $BRAND = $this->get_column_index('brand');
        $DESCRIPTION = $this->get_column_index('desc');
        $QTY = $this->get_column_index('qty');
        $SIZE1 = $this->get_column_index('size');
        $UPC = $this->get_column_index('upc');
        $CATEGORY = $this->get_column_index('cat');
        $REG_COST = $this->get_column_index('cost');
        $NET_COST = $this->get_column_index('saleCost');
        $SRP = $this->get_column_index('srp');
        $FLAGS = $this->get_column_index('flags');

        // PLU items have different internal UPCs
        // map vendor SKUs to the internal PLUs
        $SKU_TO_PLU_MAP = array();
        $skusP = $dbc->prepare_statement('SELECT sku, upc FROM vendorSKUtoPLU WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            $SKU_TO_PLU_MAP[$skusW['sku']] = $skusW['upc'];
        }

        $extraP = $dbc->prepare_statement("update prodExtra set cost=? where upc=?");
        $prodP = $dbc->prepare('
            UPDATE products
            SET cost=?,
                numflag= numflag | ? | ?,
                modified=' . $dbc->now() . '
            WHERE upc=?
                AND default_vendor_id=?');
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
        $updated_upcs = array();
        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();

        foreach($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$UPC])) continue;

            // grab data from appropriate columns
            $sku = ($SKU !== false) ? $data[$SKU] : '';
            $sku = str_replace('-', '', $sku);
            $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
            $brand = $data[$BRAND];
            $description = $data[$DESCRIPTION];
            $qty = $data[$QTY];
            $size = ($SIZE1 !== false) ? $data[$SIZE1] : '';
            $prodInfo = ($FLAGS !== false) ? $data[$FLAGS] : '';
            $flag = 0;
            $upc = substr($data[$UPC],0,13);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $upc = $SKU_TO_PLU_MAP[$sku];
                if (substr($size, -1) == '#' && substr($upc, 0, 3) == '002') {
                    $qty = trim($size, '# ');
                    $size = '#';
                } elseif (substr($size, -2) == 'LB' && substr($upc, 0, 3) == '002') {
                    $qty = trim($size, 'LB ');
                    $size = 'LB';
                }
            }
            $category = $data[$CATEGORY];
            $reg = trim($data[$REG_COST]);
            $net = ($NET_COST !== false) ? trim($data[$NET_COST]) : 0.00;
            // blank spreadsheet cell
            if (empty($net)) {
                $net = 0;
            }
            $srp = trim($data[$SRP]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg) or empty($srp))
                continue;

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

            // sale price isn't really a discount
            if ($reg == $net) {
                $net = 0;
            }

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg) or !is_numeric($srp)) {
                continue;
            }

            $srp = $rounder->round($srp);

            // set organic flag on OG1 (100%) or OG2 (95%)
            $organic_flag = 0;
            if (strstr($prodInfo, 'OG2') || strstr($prodInfo, 'OG1')) {
                $organic_flag = 17;
            }
            // set gluten-free flag on g
            $gf_flag = 0;
            if (strstr($prodInfo, 'g')) {
                $gf_flag = 18;
            }

            // need unit cost, not case cost
            $reg_unit = $reg / $qty;
            $net_unit = $net / $qty;

            $dbc->exec_statement($extraP, array($reg_unit,$upc));
            $dbc->exec_statement($prodP, array($reg_unit,$organic_flag,$gf_flag,$upc,$VENDOR_ID));
            $updated_upcs[] = $upc;

            $args = array(
                $brand, 
                $sku === false ? '' : $sku, 
                $size === false ? '' : $size,
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
            $dbc->execute($itemP,$args);

            if ($srpP) {
                $dbc->exec_statement($srpP,array($VENDOR_ID,$upc,$srp));
            }
        }

        $updateModel = new ProdUpdateModel($dbc);
        $updateModel->logManyUpdates($updated_upcs, ProdUpdateModel::UPDATE_EDIT);

        return true;
    }

    /* clear tables before processing */
    function split_start(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $idP = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorName='SELECT' ORDER BY vendorID");
        $idR = $dbc->exec_statement($idP);
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $idW = $dbc->fetchRow($idR);
        $VENDOR_ID = $idW['vendorID'];

        $viP = $dbc->prepare_statement("DELETE FROM vendorItems WHERE vendorID=?");
        $vsP = $dbc->prepare_statement("DELETE FROM vendorSRPs WHERE vendorID=?");
        $dbc->exec_statement($viP,array($VENDOR_ID));
        $dbc->exec_statement($vsP,array($VENDOR_ID));
    }

    function preview_content(){
        return '';  
        return '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
    }

    function results_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.$_SERVER['PHP_SELF'].'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

