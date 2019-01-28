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

class RdwUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {

    public $title = "Fannie - RDW Prices";
    public $header = "Upload RDW price file";
    public $themed = true;

    public $description = '[RDW Catalog Import] specialized vendor import tool. Column choices
    default to RDW price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 3,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 1,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 0,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Unit Cost (Reg) *',
            'default' => 14,
            'required' => true
        ),
    );

    protected $use_splits = false;
    protected $use_js = false;

    protected $skip_first = 18;

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName LIKE ? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('%RUSS DAVIS%'));

        return $vid;
    }

    private function cleanUPC($upc)
    {
        if (strlen($upc) == 5) {
            return BarcodeLib::padUPC(substr($upc, 1));
        }
        if (strstr($upc, '-')) {
            $upc = str_replace('-', '', $upc);
        }
        if (strstr($upc, ' ')) {
            $upc = str_replace(' ', '', $upc);
        }

        return BarcodeLib::padUPC(substr($upc, 0, strlen($upc)-1));
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

        $dbc->startTransaction();
        $resetP = $dbc->prepare('UPDATE vendorItems SET vendorDept=1 WHERE vendorID=?');
        $dbc->execute($resetP, array($VENDOR_ID));
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
                '', ?, ?, ?,
                ?, ?, ?, 999999,
                ?, 0, ?, 0
            )");
        $delP = $dbc->prepare('DELETE FROM vendorItems WHERE sku=? AND vendorID=?');
        $updated_upcs = array();

        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = $data[$indexes['sku']];
            $description = $data[$indexes['desc']];
            $upc = $this->cleanUPC($data[$indexes['upc']]);
            $reg = trim($data[$indexes['cost']]);
            // blank spreadsheet cell
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg) || !is_numeric($reg)) {
                continue;
            }

            $dbc->execute($extraP, array($reg,$upc));
            $dbc->execute($prodP, array($reg,$upc,$VENDOR_ID));
            $updated_upcs[] = $upc;

            $dbc->execute($delP, array($sku, $VENDOR_ID));
            $dbc->execute($itemP, array($sku, '', $upc, 1, $reg,
                $description, $VENDOR_ID, date('Y-m-d H:i:s')));

        }
        $dbc->commitTransaction();

        $updateModel = new ProdUpdateModel($dbc);
        $updateModel->logManyUpdates($updated_upcs, ProdUpdateModel::UPDATE_EDIT);

        return true;
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals(false, $this->getVendorID());
        $phpunit->assertEquals(false, $this->process_file(array(), array()));
        $phpunit->assertEquals('1234', $this->cleanUPC('91234'));
        $phpunit->assertEquals('0001234512345', $this->cleanUPC('12345 12345-0'));
        $phpunit->assertInternalType('string', $this->results_content());
    }
}

FannieDispatch::conditionalExec();

