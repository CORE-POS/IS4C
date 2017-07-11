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

class GardenOfLifeUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Garden of Life Prices";
    public $header = "Upload Garden of Life price file";
    public $themed = true;

    public $description = '[Garden of Life Catalog Import] specialized vendor import tool. Column choices
    default to Garden of Life order form layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 7,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 0,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 6,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 8,
            'required' => true
        ),
        'size' => array(
            'display_name' => 'Unit Size',
            'default' => 4,
        ),
        'type' => array(
            'display_name' => 'Unit Type',
            'default' => 5,
            'required' => true
        ),
    );

    protected $use_splits = false;
    protected $use_js = false;

    function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $idP = $dbc->prepare("
            SELECT vendorID 
            FROM vendors 
            WHERE vendorName='GARDEN OF LIFE' 
            ORDER BY vendorID");
        $idR = $dbc->execute($idP);
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return false;
        }
        $idW = $dbc->fetchRow($idR);
        $VENDOR_ID = $idW['vendorID'];

        $extraP = $dbc->prepare("update prodExtra set cost=? where upc=?");
        $prodP = $dbc->prepare('
            UPDATE products
            SET cost=?,
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
                'GARDEN OF LIFE',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                ?,
                0,
                ?,
                0
            )");
        $updated_upcs = array();

        $CARTON_PATTERN = '/\s*\((\d+) .+ per carton\)\s*/i';
        $TRAY_PATTERN = '/\s*\((\d+) .+\)\s*/';

        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = ($indexes['sku'] !== false) ? $data[$indexes['sku']] : '';
            $description = $data[$indexes['desc']];
            $upc = str_replace(' ', '', $data[$indexes['upc']]);
            $upc = substr($upc, 0, strlen($upc)-1);
            $upc = BarcodeLib::padUPC($upc);
            $size = ($indexes['size'] !== false) ? $data[$indexes['size']] : '';
            if (is_numeric($size)) {
                $size .= 'CT';
            }
            $type = strtolower($data[$indexes['type']]);
            $qty = 1;
            // zeroes isn't a real item, skip it
            $reg = trim($data[$indexes['cost']]);
            // blank spreadsheet cell
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg)) {
                continue;
            }

            // syntax fixes. kill apostrophes in text fields,
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $reg = str_replace('$',"",$reg);
            $reg = str_replace(",","",$reg);
            $reg = trim($reg);

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg)) {
                continue;
            }

            $description = preg_replace("/[^\x01-\x7F]/","", $description);

            if ($type == 'tray') {
                if (preg_match($TRAY_PATTERN, $description, $matches)) {
                    $size = $matches[1] . 'CT';
                    $description = preg_replace($TRAY_PATTERN, '', $description);
                } elseif (preg_match('/(\d+)/', $description, $matches)) {
                    $size = $matches[1] . 'CT';
                }
            } elseif ($type == 'carton') {
                if (preg_match($CARTON_PATTERN, $description, $matches)) {
                    $size = $matches[1] . 'CT';
                    $description = preg_replace($CARTON_PATTERN, '', $description);
                } elseif (preg_match('/(\d+)/', $description, $matches)) {
                    $size = $matches[1] . 'CT';
                }
            }

            // need unit cost, not case cost
            $reg_unit = $reg / $qty;

            $dbc->execute($extraP, array($reg_unit,$upc));
            $dbc->execute($prodP, array($reg_unit,$upc,$VENDOR_ID));
            $updated_upcs[] = $upc;

            $args = array(
                $sku === false ? '' : $sku, 
                $size === false ? '' : $size,
                $upc,
                $qty,
                $reg_unit,
                $description,
                $VENDOR_ID,
                date('Y-m-d H:i:s'),
            );
            $dbc->execute($itemP,$args);
        }

        $updateModel = new ProdUpdateModel($dbc);
        $updateModel->logManyUpdates($updated_upcs, ProdUpdateModel::UPDATE_EDIT);

        return true;
    }

    function preview_content()
    {
        return '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

