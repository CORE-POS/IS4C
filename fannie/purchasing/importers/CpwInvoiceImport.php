<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\Fannie\API\data\FileData;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class CpwInvoiceImport extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $COOL_MAP = array(
        'LOCAL' => 'USA',
        'WA' => 'USA',
        'OR' => 'USA',
        'CA' => 'USA',
        'AZ' => 'USA',
        'NM' => 'USA',
        'NV' => 'USA',
        'ID' => 'USA',
        'MT' => 'USA',
        'CO' => 'USA',
        'UT' => 'USA',
        'WY' => 'USA',
        'TX' => 'USA',
        'OK' => 'USA',
        'KS' => 'USA',
        'NE' => 'USA',
        'SD' => 'USA',
        'ND' => 'USA',
        'MN' => 'USA',
        'WI' => 'USA',
        'IA' => 'USA',
        'MO' => 'USA',
        'AR' => 'USA',
        'LA' => 'USA',
        'MS' => 'USA',
        'AL' => 'USA',
        'GA' => 'USA',
        'FL' => 'USA',
        'SC' => 'USA',
        'NC' => 'USA',
        'TN' => 'USA',
        'KY' => 'USA',
        'IN' => 'USA',
        'IL' => 'USA',
        'MI' => 'USA',
        'OH' => 'USA',
        'WV' => 'USA',
        'VA' => 'USA',
        'MD' => 'USA',
        'PA' => 'USA',
        'NY' => 'USA',
        'DE' => 'USA',
        'NJ' => 'USA',
        'MA' => 'USA',
        'NH' => 'USA',
        'VT' => 'USA',
        'RI' => 'USA',
        'ME' => 'USA',
        'AK' => 'USA',
        'HI' => 'USA',
        'MX' => 'MEXICO',
        'ARG' => 'ARGENTINA',
        'NZ' => 'NEW ZEALAND',
        'THAI' => 'THAILAND',
        'PERU' => 'PERU',
        'CH' => 'CHILE',
        'CHILE' => 'CHILE',
        'ECUADOR' => 'ECUADOR',
    );
    protected $header = 'Import CPW Invoice';
    protected $title = 'Import CPW Invoice';

    public $description = '[CPW Invoice Import] is a specialized tool for importing CPW invoices';
    public $page_set = 'Purchasing';

    protected $preview_opts = array(
        'qty' => array(
            'display_name' => 'Qty Ordered *',
            'default' => 0,
            'required' => true
        ),
        'ship' => array(
            'display_name' => 'Qty Shipped *',
            'default' => 1,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Desc *',
            'default' => 2,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 5,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Case Cost *',
            'default' => 6,
            'required' => true
        ),
    );

    protected $use_splits = false;
    protected $use_js = false;

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('CPW'));

        return $vid;
    }

    protected function expandCOOL($str)
    {
        return $this->COOL_MAP[$str] ? $this->COOL_MAP[$str] : $str;
    }

    protected function findCOOL($str)
    {
        if (preg_match('/[A-Z]+\/[A-Z\/]+/', $str, $matches)) {
            $origins = array();
            $all = explode('/', $matches[0]);
            foreach ($all as $a) {
                $exp = $this->expandCOOL($a);
                if (!isset($origins[$exp])) {
                    $origins[$exp] = $exp;
                }
            }
            $vals = array_values($origins);
            sort($vals);

            return implode(' and ', $vals);
        }

        foreach ($this->COOL_MAP as $abbrev => $full) {
            if (strpos($str, ' ' . $abbrev)) {
                return $full;
            }
        }

        return '';
    }

    private function findHorizontal($fields, $key)
    {
        for ($i=0; $i<count($fields); $i++) {
            if (strpos($fields[$i], $key) === 0 && isset($fields[$i+2])) {
                return $fields[$i+2];
            }
        }

        return false;
    }

    function process_file($linedata, $indexes)
    {
        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        $upcP = $this->connection->prepare('SELECT upc FROM vendorItems WHERE sku=? AND vendorID=?');
        $items = array();
        $invNum = false;
        $orderDate = false;
        $shipDate = false;
        foreach ($linedata as $data) {
            $sku = trim($data[$indexes['sku']]);
            if (!preg_match('/^[0-9]+$/', $sku)) {
                if (!$invNum) {
                    $invNum = $this->findHorizontal($data, 'Invoice No');
                }
                if (!$orderDate) {
                    $orderDate = $this->findHorizontal($data, 'Order Date');
                }
                if (!$shipDate) {
                    $shipDate = $this->findHorizontal($data, 'Ship Date');
                }
                continue;
            }
            $item = array(
                'sku' => $sku,
                'ordered' => trim($data[$indexes['qty']]),
                'shipped' => trim($data[$indexes['ship']]),
                'description' => trim($data[$indexes['desc']]),
                'caseCost' => trim($data[$indexes['cost']]),
                'upc' => $this->connection->getValue($upcP, array($sku, $VENDOR_ID)),
            );
            $item['cool'] = $this->findCOOL($item['description']);
            $items[] = $item;
        }

        //echo '<pre>' . print_r($items, true) . '</pre>';

        $orderDate = $orderDate ? FileData::excelFloatToDate($orderDate) : date('Y-m-d');
        $shipDate = $shipDate ? FileData::excelFloatToDate($shipDate) : date('Y-m-d');
        $order = new PurchaseOrderModel($this->connection);
        $order->vendorID($VENDOR_ID);
        $order->storeID(FormLib::get('store'));
        $order->creationDate($orderDate);
        $order->placedDate($orderDate);
        $order->placed(1);
        $order->userID(0);
        $order->vendorInvoiceID($invNum);
        $orderID = $order->save();
        if ($orderID === false) {
            $this->error_details = 'Could not create purchase order';
            return false;
        }

        $this->connection->startTransaction();
        $itemP = $this->connection->prepare("INSERT INTO PurchaseOrderItems
            (orderID, sku, quantity, unitCost, caseSize, receivedDate, receivedQty, receivedTotalCost,
            unitSize, brand, description, internalUPC) VALUES (?, ?, ?, ?, 1, ?, ?, ?, '', '', ?, ?)");
        $coolP = $this->connection->prepare("SELECT coolText FROM SkuCOOLHistory WHERE vendorID=? AND sku=? AND ordinal=1");
        $coolModel = new SkuCOOLHistoryModel($this->connection);
        $logCOOL = FormLib::get('logCOOL', false);
        $this->lineCount = 0;
        $this->coolCount = 0;
        foreach ($items as $item) {
            $this->connection->execute($itemP, array($orderID, $item['sku'], $item['ordered'], $item['caseCost'],
                $shipDate, $item['shipped'], $item['shipped']*$item['caseCost'], $item['description'], $item['upc']));
            $this->lineCount++;
            if ($logCOOL) {
                $current = $this->connection->getValue($coolP, array($VENDOR_ID, $item['sku']));
                if ($current === false || strtolower($current) != strtolower($item['cool'])) {
                    $coolModel->rotateIn($VENDOR_ID, $item['sku'], $item['cool']);
                    $this->coolCount++;
                }
            }
        }
        $this->connection->commitTransaction();
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

