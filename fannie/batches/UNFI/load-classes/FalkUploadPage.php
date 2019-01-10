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
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class FalkUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Falk Prices";
    public $header = "Upload Falk price file";

    public $description = '[Falk Catalog Import] specialized vendor import tool. Column choices
    default to UNFI price file layout.';

    protected $preview_opts = array(
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 0,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 1,
            'required' => true
        ),
        'units' => array(
            'display_name' => 'Case Size *',
            'default' => 2,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 3,
            'required' => true
        ),
    );

    protected $vendor_name = 'Falk';

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName LIKE ? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array($this->vendor_name . '%'));

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

        $itemP = $dbc->prepare("
            INSERT INTO vendorItems 
                (brand, sku, size, upc, units, cost, description,
                 vendorDept, vendorID, saleCost, modified, srp) 
                VALUES 
                (
                 '',    ?,   ?,    ?,   ?,     ?,    ?,
                 999999,          ?,        0,        ?,        0)
        ");
        $updated_upcs = array();
        $prodP = $dbc->prepare('UPDATE products SET modified=?, cost=? WHERE upc=?');
        $existsP = $dbc->prepare("SELECT sku FROM vendorItems WHERE sku=? AND vendorID=?");
        $updateP = $dbc->prepare("
            UPDATE vendorItems
            SET description=?,
                sku=?,
                cost=?,
                units=?,
                size=?,
                modified=?,
                vendorDept=999999
            WHERE upc=?
                AND vendorID=?");

        $dbc->startTransaction();
        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['sku']])) continue;

            // grab data from appropriate columns
            $sku = $data[$indexes['sku']];
            if (!is_numeric($sku)) {
                continue;
            }
            $description = $data[$indexes['desc']];
            // assign fake prefix 4 UPC
            $upc = BarcodeLib::padUPC($sku);
            $upc = '004' . substr($upc, -11);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000" || !preg_match('/^[0-9]+$/', $upc))
                continue;
            elseif (!preg_match('/^[0-9]+$/', $sku))
                continue;
            $reg = trim($data[$indexes['cost']]);
            $caseSize = $data[$indexes['units']];
            $size = '1 CT';

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
            $reg_unit = $reg / $caseSize;

            $dbc->execute($prodP, array(date('Y-m-d H:i:s'), $reg_unit, $upc));

            $exists = $dbc->getValue($existsP, array($sku, $VENDOR_ID));
            if ($exists) {
                $args = array(
                    $description,
                    $sku,
                    $reg_unit,
                    $caseSize,
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
                    $caseSize,
                    $reg_unit,
                    $description,
                    $VENDOR_ID,
                    date('Y-m-d H:i:s'),
                );
                $dbc->execute($itemP,$args);
            }
        }

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
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->results_content());
        $phpunit->assertEquals('123', $this->sanitizePrice('$1,23'));
        $phpunit->assertEquals(false, $this->process_file(array(), array()));
    }
}

FannieDispatch::conditionalExec();

