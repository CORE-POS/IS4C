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

class AlbertsUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {

    public $title = "Fannie - Alberts Prices";
    public $header = "Upload Alberts price file";
    public $themed = true;

    public $description = '[Alberts Catalog Import] specialized vendor import tool. Column choices
    default to Alberts price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 7,
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
            'display_name' => 'Case Cost (Reg) *',
            'default' => 5,
            'required' => true
        ),
        'size' => array(
            'display_name' => 'Size Info',
            'default' => 2,
            'required' => true
        ),
    );

    protected $skip_first = 26;

    protected $use_splits = false;
    protected $use_js = false;

    private $presetID = false;
    private $remaps = '';

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('ALBERTS'));

        return $vid !== false ? $vid : $this->presetID;
    }

    private function cleanUPC($upc)
    {
        if (strlen($upc) == 5 && substr($upc, 0, 1) == '9' && $upc !== '99999') {
            return BarcodeLib::padUPC(substr($upc, 1));
        }
        if (strstr($upc, '-')) {
            $upc = str_replace('-', '', $upc);
            return BarcodeLib::padUPC(substr($upc, 0, strlen($upc)-1));
        }
        if ($upc == '9999' || $upc == '99999') {
            return '0000000000000';
        }

        return BarcodeLib::padUPC($upc);
    }

    private function parseSize($str)
    {
        if (preg_match('/\d+x\d+/', $str)) {
            list($case, $size) = explode('x', $str, 2);
            return array(trim($case), trim($size));
        }
        if (strpos(strtolower($str), 'lb')) {
            list($case,) = explode('lb', strtolower($str), 2);
            return array(trim($case), 'lb');
        }
        if (strpos(strtolower($str), 'ct')) {
            list($case,) = explode('ct', strtolower($str), 2);
            return array(trim($case), 'ea');
        }

        return array(1, $str);
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
        $mapP = $dbc->prepare('UPDATE VendorLikeCodeMap SET sku=? WHERE vendorID=? AND sku=?');
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
            $size = trim($data[$indexes['size']]);
            list($case, $unit) = $this->parseSize($size);

            // need unit cost, not case cost
            $reg_unit = $reg / $case;

            $dbc->execute($extraP, array($reg_unit,$upc));
            $dbc->execute($prodP, array($reg_unit,$upc,$VENDOR_ID));
            $updated_upcs[] = $upc;

            $dbc->execute($delP, array($sku, $VENDOR_ID));
            $dbc->execute($itemP, array($sku, $unit, $upc, $case, $reg_unit,
                $description, $VENDOR_ID, date('Y-m-d H:i:s')));

            $oldSKU = $this->sameItem($VENDOR_ID, $description, $case, $unit);
            if ($oldSKU) {
                $dbc->execute($mapP, array($sku, $VENDOR_ID, $oldSKU));
                $this->remaps .= "$oldSKU => $sku<br />";
            }
        }
        $dbc->commitTransaction();

        $updateModel = new ProdUpdateModel($dbc);
        $updateModel->logManyUpdates($updated_upcs, ProdUpdateModel::UPDATE_EDIT);

        return true;
    }

    private function sameItem($vendorID, $description, $units, $size)
    {
        $parts = explode(',', $description);
        $parts = array_map('trim', $parts);
        $last = array_pop($parts);
        $chkP = $this->connection->prepare('SELECT sku FROM vendorItems WHERE vendorID=? AND units=? AND size=? AND description LIKE ?');
        while (count($parts) > 1) {
            $partial = implode(', ', $parts);
            $chk = $this->connection->getValue($chkP, array($vendorID, $units, $size, $partial . '%'));
            if ($chk) {
                return $chk;
            }
            $last = array_pop($parts);
        }

        return false;
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';
        $ret .= $this->remaps;

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->results_content());
        $phpunit->assertEquals(false, $this->process_file(array(), array()));
        $this->presetID = 100;
        $indexes = array('upc'=>0, 'sku'=>1, 'desc'=>2, 'cost'=>3, 'size'=>4);
        $data = array(
            array('1234567890123', '1', 'test import', 1.99, '3x10'),
            array('1234567890123', '2', 'test import', 1.99, '3lb'),
            array('1234567890123', '3', 'test import', 1.99, '3ct'),
        );
        $phpunit->assertEquals(true, $this->process_file($data, $indexes));
    }
}

FannieDispatch::conditionalExec();

