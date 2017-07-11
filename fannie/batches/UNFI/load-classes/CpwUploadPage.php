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

class CpwUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - CPW Prices";
    public $header = "Upload CPW price file";

    public $description = '[CPW Catalog Import] specialized vendor import tool. Column choices
    default to UNFI price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 6,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 1,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 2,
            'required' => true
        ),
        'qty' => array(
            'display_name' => 'Case+Unit Size',
            'default' => 3,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 4,
            'required' => true
        ),
    );

    protected $vendor_name = 'CPW';

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
        $skusP = $dbc->prepare('SELECT sku, upc FROM VendorAliases WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            if (!isset($SKU_TO_PLU_MAP[$skusW['sku']])) {
                $SKU_TO_PLU_MAP[$skusW['sku']] = array();
            }
            $SKU_TO_PLU_MAP[$skusW['sku']][] = $skusW['upc'];
        }

        $itemP = $dbc->prepare("
            INSERT INTO vendorItems 
                (brand, sku, size, upc, units, cost, description,
                 vendorDept, vendorID, saleCost, modified, srp) 
                VALUES 
                (
                 '',    ?,   ?,    ?,   ?,     ?,    ?,
                 0,          ?,        0,        ?,        0)
        ");
        $updated_upcs = array();
        $prodP = $dbc->prepare('UPDATE products SET modified=?, cost=? WHERE upc=?');
        $existsP = $dbc->prepare('SELECT 1 FROM vendorItems WHERE upc=? AND vendorID=?');
        $updateP = $dbc->prepare("
            UPDATE vendorItems
            SET description=?,
                sku=?,
                cost=?,
                units=?,
                size=?
                modified=?
            WHERE upc=?
                AND vendorID=?");

        $dbc->startTransaction();
        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = $data[$indexes['sku']];
            $description = $data[$indexes['desc']];
            $upc = $data[$indexes['upc']];
            $upc = str_replace('-', '', $upc);
            $upc = substr($upc, 0, strlen($upc)-1);
            $upc = BarcodeLib::padUPC($upc);
            $aliases = array($upc);
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $aliases = array_merge($aliases, $SKU_TO_TO_PLU_MAP[$sku]);
            }
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000" || !preg_match('/^[0-9]+$/', $upc))
                continue;
            elseif (!preg_match('/^[0-9]+$/', $sku))
                continue;
            $reg = trim($data[$indexes['cost']]);
            $sizeInfo = $data[$indexes['qty']];
            if (trim($sizeInfo) == '') {
                continue; // usually means not available
            }
            if (strstr($sizeInfo, '/')) {
                list($qty,$size) = explode('/', $sizeInfo, 2);
            } elseif (strstr($sizeInfo, '#')) {
                $qty = trim($sizeInfo, '# ');
                $size = '#';
            } elseif (strstr($sizeInfo, 'lb')) {
                $qty = trim(str_replace('lb', '', $sizeInfo));
                $size = '#';
            } elseif (strstr($sizeInfo, 'ct')) {
                $qty = trim(str_replace('ct', '', $sizeInfo));
                $size = '1 ct';
            }

            // syntax fixes. 
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $reg = $this->sanitizePrice($reg);

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg) || $reg == 0) {
                continue;
            }

            // need unit cost, not case cost
            $reg_unit = $reg / $qty;

            foreach ($aliases as $alias) {
                $dbc->execute($prodP, array(date('Y-m-d H:i:s'), $reg_unit, $alias));
                $updated_upcs[] = $alias;
            }

            if ($dbc->getValue($existsP, array($upc, $VENDOR_ID))) {
                $args = array(
                    $description,
                    $sku,
                    $reg_unit,
                    $qty,
                    $size,
                    date('Y-m-d H:i:s'),
                    $upc,
                    $VENDOR_ID);
                $dbc->execute($updateP, $args);
            } else {
                $args = array(
                    $sku,
                    $size,
                    $upc,
                    $qty,
                    $reg_unit,
                    $description,
                    $VENDOR_ID,
                    date('Y-m-d H:i:s'),
                );
                $dbc->execute($itemP,$args);
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

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SEVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

