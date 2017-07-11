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

class UnfiUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - UNFI Prices";
    public $header = "Upload UNFI price file";

    public $description = '[UNFI Catalog Import] specialized vendor import tool. Column choices
    default to UNFI price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 14,
            'required' => True
        ),
        'srp' => array(
            'display_name' => 'SRP *',
            'default' => 16,
            'required' => True
        ),
        'brand' => array(
            'display_name' => 'Brand *',
            'default' => 2,
            'required' => True
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 6,
            'required' => True
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 1,
            'required' => true
        ),
        'qty' => array(
            'display_name' => 'Case Qty *',
            'default' => 3,
            'required' => True
        ),
        'size' => array(
            'display_name' => 'Unit Size',
            'default' => 4,
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 8,
            'required' => True
        ),
        'saleCost' => array(
            'display_name' => 'Case Cost (Sale)',
            'default' => 12,
        ),
        'cat' => array(
            'display_name' => 'UNFI Category # *',
            'default' => 5,
            'required' => True
        ),
        'flags' => array(
            'display_name' => 'Flags',
            'default' => 20,
        ),
    );

    protected $use_splits = true;
    protected $use_js = false;
    protected $vendor_name = 'UNFI';

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array($this->vendor_name));

        return $vid;
    }

    function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        // PLU items have different internal UPCs
        // map vendor SKUs to the internal PLUs
        $SKU_TO_PLU_MAP = array();
        $skusP = $dbc->prepare('SELECT sku, upc, isPrimary, multiplier FROM VendorAliases WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            if (!isset($SKU_TO_PLU_MAP[$skusW['sku']])) {
                $SKU_TO_PLU_MAP[$skusW['sku']] = array();
            }
            $SKU_TO_PLU_MAP[$skusW['sku']][] = $skusW;
        }

        // Repack items that are mapped to bulk items
        $LINKED_MAP = array();
        $linkP = $dbc->prepare('
            SELECT p.upc,
                s.plu
            FROM products AS p 
                INNER JOIN scaleItems AS s ON p.upc=s.linkedPLU
            WHERE p.default_vendor_id=?
            GROUP BY p.upc,
                s.plu');
        $linkR = $dbc->execute($linkP, array($VENDOR_ID));
        while ($linkW = $dbc->fetchRow($linkR)) {
            $LINKED_MAP[$linkW['upc']] = $linkW['plu'];
        }

        $extraP = $dbc->prepare("update prodExtra set cost=? where upc=?");
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
        if (false && $dbc->tableExists('vendorSRPs')) {
            $srpP = $dbc->prepare("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");
        }
        $updated_upcs = array();
        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();

        $dbc->startTransaction();

        foreach($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = ($indexes['sku'] !== false) ? $data[$indexes['sku']] : '';
            $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
            $brand = $data[$indexes['brand']];
            $description = $data[$indexes['desc']];
            $qty = $data[$indexes['qty']];
            $size = ($indexes['size'] !== false) ? $data[$indexes['size']] : '';
            $prodInfo = ($indexes['flags'] !== false) ? $data[$indexes['flags']] : '';
            $flag = 0;
            $upc = substr($data[$indexes['upc']],0,13);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            $aliases = array(array('upc'=>$upc, 'multiplier'=>1, 'isPrimary'=>1));
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $aliases = $SKU_TO_PLU_MAP[$sku];
            }
            $category = $data[$indexes['cat']];
            $reg = trim($data[$indexes['cost']]);
            $net = ($indexes['saleCost'] !== false) ? trim($data[$indexes['saleCost']]) : 0.00;
            // blank spreadsheet cell
            if (empty($net)) {
                $net = 0;
            }
            $srp = trim($data[$indexes['srp']]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg) or empty($srp))
                continue;

            // syntax fixes. 
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $reg = $this->sanitizePrice($reg);
            $net = $this->sanitizePrice($net);
            $srp = $this->sanitizePrice($srp);

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

            list($organic_flag, $gf_flag) = $this->getFlags($prodInfo);

            // need unit cost, not case cost
            $reg_unit = $reg / $qty;
            $net_unit = $net / $qty;

            foreach ($aliases as $alias) {

                if (substr($size, -1) == '#' && substr($alias['upc'], 0, 3) == '002') {
                    $qty = trim($size, '# ');
                    $reg_unit = $reg / $qty;
                    $net_unit = $net / $qty;
                    $size = '#';
                } elseif (substr($size, -2) == 'LB' && substr($alias['upc'], 0, 3) == '002') {
                    $qty = trim($size, 'LB ');
                    $reg_unit = $reg / $qty;
                    $net_unit = $net / $qty;
                    $size = 'LB';
                }

                $dbc->execute($extraP, array($reg_unit*$alias['multiplier'],$alias['upc']));
                $dbc->execute($prodP, array($reg_unit*$alias['multiplier'],$organic_flag,$gf_flag,$alias['upc'],$VENDOR_ID));
                $updated_upcs[] = $alias['upc'];

                $srp = $rounder->round($srp * $alias['multiplier']);

                $args = array(
                    $brand, 
                    $alias['isPrimary'] ? $sku : $alias['upc'],
                    $size === false ? '' : $size,
                    $alias['upc'],
                    $qty,
                    $reg_unit*$alias['multiplier'],
                    $description,
                    $category,
                    $VENDOR_ID,
                    $net_unit*$alias['multiplier'],
                    date('Y-m-d H:i:s'),
                    $srp
                );
                $dbc->execute($itemP,$args);

                if ($srpP) {
                    $dbc->execute($srpP,array($VENDOR_ID,$alias['upc'],$srp));
                }
            }
        }

        $updateModel = new ProdUpdateModel($dbc);
        $updateModel->logManyUpdates($updated_upcs, ProdUpdateModel::UPDATE_EDIT);

        $dbc->commitTransaction();

        return true;
    }

    protected function sanitizePrice($reg)
    {
        $reg = str_replace('$',"",$reg);
        return str_replace(",","",$reg);
    }

    protected function getFlags($prodInfo)
    {
        // set organic flag on OG1 (100%) or OG2 (95%)
        $organic_flag = 0;
        if (strstr($prodInfo, 'O2') || strstr($prodInfo, 'O1')) {
            $organic_flag = (1 << (17 - 1));
        }
        // set gluten-free flag on g
        $gf_flag = 0;
        if (strstr($prodInfo, 'g')) {
            $organic_flag = (1 << (18 - 1));
        }

        return array($organic_flag, $gf_flag);
    }

    /* clear tables before processing */
    function split_start(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        $viP = $dbc->prepare("DELETE FROM vendorItems WHERE vendorID=?");
        $vsP = $dbc->prepare("DELETE FROM vendorSRPs WHERE vendorID=?");
        $dbc->execute($viP,array($VENDOR_ID));
        $dbc->execute($vsP,array($VENDOR_ID));
    }

    function preview_content(){
        return '';  
        return '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SEVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

