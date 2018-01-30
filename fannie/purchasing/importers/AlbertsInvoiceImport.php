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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class AlbertsInvoiceImport extends \COREPOS\Fannie\API\FannieUploadPage
{
    public $title = "Fannie - Alberts Invoice";
    public $header = "Upload Alberts Invoice";

    public $description = '[Alberts Invoice Import] specialized invoice import tool. Column choices
    default to Alberts layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 19,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 3,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 2,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Unit Cost *',
            'default' => 22,
            'required' => true
        ),
        'size' => array(
            'display_name' => 'Size Info *',
            'default' => 11,
            'required' => true
        ),
        'cases' => array(
            'display_name' => 'Cases *',
            'default' => 20,
            'required' => true
        ),
        'date' => array(
            'display_name' => 'Date *',
            'default' => 23,
            'required' => true
        ),
        'cool' => array(
            'display_name' => 'COOL *',
            'default' => 24,
            'required' => true
        ),
        'inv' => array(
            'display_name' => 'Invoice # *',
            'default' => 0,
            'required' => true
        ),
        'po' => array(
            'display_name' => 'PO # *',
            'default' => 1,
            'required' => true
        ),
    );

    protected $use_splits = false;
    protected $use_js = false;

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('ALBERTS'));

        return $vid;
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

        $realDate = date('Y-m-d');
        $order = new PurchaseOrderModel($dbc);
        $order->vendorID($VENDOR_ID);
        $order->storeID(FormLib::get('store'));
        $order->creationDate($realDate);
        $order->placedDate($realDate);
        $order->placed(1);
        $order->userID(0);
        $orderID = $order->save();
        if ($orderID === false) {
            $this->error_details = 'Could not create purchase order';
            return false;
        }

        $dbc->startTransaction();
        $itemP = $dbc->prepare("INSERT INTO PurchaseOrderItems
            (orderID, sku, quantity, unitCost, caseSize, receivedDate, receivedQty, receivedTotalCost,
            unitSize, brand, description, internalUPC) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $coolP = $dbc->prepare("SELECT coolText FROM SkuCOOLHistory WHERE vendorID=? AND sku=? AND ordinal=1");
        $coolModel = new SkuCOOLHistoryModel($dbc);
        $logCOOL = FormLib::get('logCOOL', false);
        $this->lineCount = 0;
        $this->coolCount = 0;

        $first = true;
        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['sku']])) continue;

            // grab data from appropriate columns
            $sku = trim($data[$indexes['sku']]);
            if (!is_numeric($sku)) continue;
            $description = $data[$indexes['desc']];
            $upc = $this->cleanUPC($data[$indexes['upc']]);
            $unitCost = trim($data[$indexes['cost']]);
            $size = trim($data[$indexes['size']]);
            list($case, $unit) = $this->parseSize($size);
            $qty = trim($data[$indexes['cases']]);
            $cool = trim($data[$indexes['cool']]);
            if (strtolower($cool) == 'united states') {
                $cool = 'USA';
            }
            if (strpos($cool, ' and ')) {
                $tmp = explode(' and ', $cool);
                sort($tmp);
                $cool = implode(' and ', $tmp);
            }

            if ($first) {
                $inv = trim($data[$indexes['inv']]);
                $poNum = trim($data[$indexes['po']]);
                $date = trim($data[$indexes['date']]);
                $stamp = strtotime($date);
                $realDate = $stamp ? date('Y-m-d', $stamp) : date('Y-m-d');
                $upP = $dbc->prepare("UPDATE PurchaseOrder SET creationDate=?, placedDate=?, vendorOrderID=?, vendorInvoiceID=? WHERE orderID=?");
                $dbc->execute($upP, array($realDate, $realDate, $poNum, $inv, $orderID));
                $first = false;
            }

            $dbc->execute($itemP, array($orderID, $sku, $qty, $unitCost, $case,
                $realDate, $case*$qty, $case*$qty*$unitCost, $unit, '', $description, $upc));
            $this->lineCount++;
            if ($logCOOL) {
                $current = $dbc->getValue($coolP, array($VENDOR_ID, $sku));
                if ($current === false || strtolower($current) != strtolower($cool)) {
                    $coolModel->rotateIn($VENDOR_ID, $sku, $cool);
                    $this->coolCount++;
                }
            }
        }
        $dbc->commitTransaction();
        $this->orderID = $orderID;

        return true;
    }

    public function preview_content()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<div class="form-inline">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <label><input type="checkbox" value="1" name="logCOOL" checked />
        Update COOL data</label>
    </div>
</div>
HTML;
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= "<p>Imported {$this->lineCount} records. Saw {$this->coolCount} COOL changes</p>";
        $ret .= "<p><a href=\"../ViewPurchaseOrders.php?id={$this->orderID}\">View order</a></p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

