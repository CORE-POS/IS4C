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

class AlaffiaUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {

    public $title = "Fannie - Alaffia Prices";
    public $header = "Upload Alaffia price file";
    public $themed = true;

    public $description = '[Alaffia Catalog Import] specialized vendor import tool. Column choices
    default to Alaffia order form layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 6,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 5,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 1,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 7,
            'required' => true
        ),
    );

    protected $use_splits = false;
    protected $use_js = false;

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('ALAFFIA'));

        return $vid;
    }

    private function prepStatements($dbc)
    {
        $extraP = $dbc->prepare("update prodExtra set cost=? where upc=?");
        $prodP = $dbc->prepare('
            UPDATE products
            SET cost=?,
                modified=' . $dbc->now() . '
            WHERE upc=?
                AND default_vendor_id=?');
        $itemP = $dbc->prepare("
            INSERT INTO vendorItems (
                brand, sku, size, upc,
                units, cost, description, vendorDept,
                vendorID, saleCost, modified, srp
            ) VALUES (
                'ALAFFIA', ?, ?, ?,
                ?, ?, ?, 0,
                ?, 0, ?, 0
            )");

        return array($extraP, $prodP, $itemP);
    }

    private function getQtyAndSize($description)
    {
        $CASE_PATTERN = '/\s*\(case of (\d+)\)/i';
        $SIZE_PATTERN = '/,? +([\d\.]+ oz)\.?\s*/i';
        $qty = 1;
        $size = '';
        if (preg_match($SIZE_PATTERN, $description, $matches)) {
            $size = $matches[1];
            $description = preg_replace($SIZE_PATTERN, '', $description);
        }
        if (preg_match($CASE_PATTERN, $description, $matches)) {
            $qty = $matches[1];
            $description = preg_replace($CASE_PATTERN, '', $description);
        }

        return array($description, $qty, $size);
    }

    private function cleanDescription($description)
    {
        if (substr($description, 0, 5) == "*NEW ") {
            $description = substr($description, 5);
        }
        if (strstr($description, ' Available ')) {
            list($description, $junk) = explode(' Available ', $description);
        }

        return $description;
    }

    function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        $clean = $dbc->prepare('
            DELETE 
            FROM vendorItems
            WHERE 
            vendorID=?');
        $dbc->execute($clean, array($VENDOR_ID));

        list($extraP, $prodP, $itemP) = $this->prepStatements($dbc);
        $updated_upcs = array();

        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = ($indexes['sku'] !== false) ? $data[$indexes['sku']] : '';
            $description = $data[$indexes['desc']];
            $upc = str_replace(' ', '', $data[$indexes['upc']]);
            $upc = substr($upc, 0, strlen($upc)-1);
            $upc = BarcodeLib::padUPC($upc);
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

            $description = $this->cleanDescription();
            list($description, $qty, $size) = $this->getQtyAndSize($description);

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
        return '';  
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

