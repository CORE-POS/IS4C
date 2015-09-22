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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ImportPurchaseOrder extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie - Purchase Order";
    protected $header = "Upload Purchase Order / Invoice";

    public $description = '[Purchase Order Import] loads a vendor purchase order / invoice 
    from a spreadsheet.';
    public $themed = true;

    protected $preview_opts = array(
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU*',
            'default' => 0,
            'required' => true
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Cost (Total)*',
            'default' => 1,
            'required' => false
        ),
        'unitQty' => array(
            'name' => 'unitQty',
            'display_name' => 'Qty (Units)+',
            'default' => 2,
            'required' => false
        ),
        'caseQty' => array(
            'name' => 'caseQty',
            'display_name' => 'Qty (Cases)+',
            'default' => 3,
            'required' => false
        ),
        'caseSize' => array(
            'name' => 'caseSize',
            'display_name' => 'Units / Case',
            'default' => 4,
            'required' => false
        ),
        'unitSize' => array(
            'name' => 'unitSize',
            'display_name' => 'Unit Size',
            'default' => 5,
            'required' => false
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand',
            'default' => 6,
            'required' => false
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description',
            'default' => 7,
            'required' => false
        ),
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC (w/o check)',
            'default' => 8,
            'required' => false
        ),
        'upcc' => array(
            'name' => 'upcc',
            'display_name' => 'UPC (w/ check)',
            'default' => 9,
            'required' => false
        ),
    );

    private $results = '';

    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $skuCol = $this->get_column_index('sku');
        $costCol = $this->get_column_index('cost');
        $uQtyCol = $this->get_column_index('unitQty');
        $cQtyCol = $this->get_column_index('caseQty');
        $uSizeCol = $this->get_column_index('unitSize');
        $cSizeCol = $this->get_column_index('caseSize');
        $brandCol = $this->get_column_index('brand');
        $descCol = $this->get_column_index('desc');
        $upcCol = $this->get_column_index('upc');
        $upccCol = $this->get_column_index('upcc');

        $vendorID = FormLib::get('vendorID');
        $inv = FormLib::get('identifier', '');
        $orderDate = FormLib::get('orderDate', date('Y-m-d H:i:s'));
        $recvDate = FormLib::get('recvDate', '');

        $order = new PurchaseOrderModel($dbc);
        $order->vendorID($vendorID);
        $order->creationDate($orderDate);
        $order->placedDate($orderDate);
        $order->placed(1);
        $order->userID(FannieAuth::getUID());
        $order->vendorOrderID($inv);
        $order->vendorInvoiceID($inv);
        $orderID = $order->save();

        $item = new PurchaseOrderItemsModel($dbc);
        $info = new VendorItemsModel($dbc);

        $ret = '';
        foreach ($linedata as $line) {
            if (!isset($line[$skuCol])) continue;
            if (!isset($line[$costCol])) continue;

            $sku = $line[$skuCol];
            $cost = $line[$costCol];
            $cost = trim($cost,' ');
            $cost = trim($cost,'$');
            if (!is_numeric($cost)) {
                $ret .= "<i>Omitting item {$sku}. Cost {$cost} isn't a number</i><br />";
                continue;
            }

            $unitQty = $uQtyCol !== false && isset($line[$uQtyCol]) ? $line[$uQtyCol] : 0;
            $caseQty = $cQtyCol !== false && isset($line[$cQtyCol]) ? $line[$cQtyCol] : 0;
            if ($unitQty == 0 && $caseQty == 0) {
                // no qty specified. 
                continue;
            }

            $unitSize = $uSizeCol !== false && isset($line[$uSizeCol]) ? $line[$uSizeCol] : 0;
            $caseSize = $cSizeCol !== false && isset($line[$cSizeCol]) ? $line[$cSizeCol] : 0;
            $brand = $brandCol !== '' && isset($line[$brandCol]) ? $line[$brandCol] : '';
            $desc = $descCol !== false && isset($line[$descCol]) ? $line[$descCol] : '';
            $upc = '';
            if ($upcCol !== false && isset($line[$upcCol])) {
                $upc = BarcodeLib::padUPC($line[$upcCol]);
            } elseif ($upccCol !== false && isset($line[$upccCol])) {
                $upc = BarcodeLib::padUPC($line[$upccCol]);
                $upc = '0' . substr($upc, 0, 12);
            }

            $info->reset();
            $info->vendorID($vendorID);
            $info->sku($sku);
            if ($info->load()) {
                if ($brand === '') {
                    $brand = $info->brand();
                }
                 if ($desc === '') {
                    $desc = $info->description();
                }
                if ($unitSize === 0) {
                    $unitSize = $info->size();
                }
                if ($caseSize === 0) {
                    $caseSize = $info->units();
                }
                $upc = $info->upc();
            }

            if ($caseQty == 0 && $unitQty != 0) {
                if ($caseSize == 0) {
                    $caseQty = $unitQty;
                    $caseSize = 1;
                } else {
                    $caseQty = $unitQty / $caseSize;
                }
            } elseif ($caseQty != 0 && $unitQty == 0) {
                if ($caseSize == 0) {
                    $unitQty = $caseQty;
                    $caseSize = 1;
                } else {
                    $unitQty = $caseQty * $caseSize;
                }
            } elseif ($caseQty != 0 && $unitQty != 0) {
                if ($caseSize == 0) {
                    $caseSize = $caseQty / $unitQty;
                }
            }

            $unitCost = $cost / $unitQty;

            $item->orderID($orderID);
            $item->sku($sku);
            if ($item->load()) {
                // multiple records for same item
                $item->quantity($caseQty + $item->quantity());
                if ($recvDate !== '') {
                    $item->receivedTotalCost($cost + $item->receivedTotalCost());
                    $item->receivedQty($caseQty + $item->receivedQty());
                    $item->receivedDate($recvDate);
                }
            } else {
                $item->quantity($caseQty);
                if ($recvDate !== '') {
                    $item->receivedTotalCost($cost);
                    $item->receivedQty($caseQty);
                    $item->receivedDate($recvDate);
                }
            }
            $item->unitCost($unitCost);
            $item->caseSize($caseSize);
            $item->brand($brand);
            $item->description($desc);
            $item->internalUPC($upc);

            $item->save();
        }

        $ret .= "<p>Import Complete";
        $ret .= '<br />';
        $ret .= '<a href="' . $this->config->get('URL') . 'purchasing/ViewPurchaseOrders.php?id=' . $orderID . '">View Order</a></p>';
        $this->results = $ret;

        return true;
    }

    function results_content(){
        return $this->results;
    }

    function preview_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendor = new VendorsModel($dbc);
        $vendor->vendorID(FormLib::get('vendorID'));
        $vendor->load();
        $ret = sprintf("<b>Batch Type</b>: %s <input type=hidden value=%d name=vendorID /><br />",
            $vendor->vendorName(),FormLib::get_form_value('vendorID'));
        $ret .= sprintf("<b>PO/Inv#</b>: %s <input type=hidden value=\"%s\" name=identifier /><br />",
            FormLib::get_form_value('identifier'),FormLib::get_form_value('identifier'));
        $ret .= sprintf("<b>Order Date</b>: %s <input type=hidden value=\"%s\" name=orderDate /><br />",
            FormLib::get_form_value('orderDate'),FormLib::get_form_value('orderDate'));
        $ret .= sprintf("<b>Recv'd Date</b>: %s <input type=hidden value=\"%s\" name=recvDate /><br />",
            FormLib::get_form_value('recvDate'),FormLib::get_form_value('recvDate'));

        return $ret;
    }

    function form_content(){
        ob_start();
        ?>
        <blockquote style="border:solid 1px black;background:#ddd;padding:4px;">
        Use this tool to import a purchase order or vendor invoice.
        Files should have vendor SKUs, costs, and quantity (in units, cases, or both).
        </blockquote>
        <?php
        return ob_get_clean();
    }

    /**
      overriding the basic form since I need several extra fields   
    */
    protected function basicForm()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendors = new VendorsModel($dbc);
        ob_start();
        ?>
        <form enctype="multipart/form-data" class="form-horizontal" action="ImportPurchaseOrder.php" id="FannieUploadForm" method="post">
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Vendor</label>
            <div class="col-sm-9"><select name=vendorID class="form-control">
            <?php foreach($vendors->find('vendorName') as $v) printf("<option value=%d>%s</option>",$v->vendorID(), $v->vendorName()); ?>
                </select></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Order Date</label>
            <div class="col-sm-9"><input type="text" class="form-control date-field" name="orderDate" id="orderDate" /></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">PO#/Invoice#</label>
            <div class="col-sm-9"><input type="text" class="form-control" name="identifier" /></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Recv'd Date</label>
            <div class="col-sm-9"><input type="text" class="form-control date-field" name="recvDate" id="recvDate" /></div>
        </div>
        <div class="form-group col-sm-6">
            <label class="control-label col-sm-3">Filename</label>
            <div class="col-sm-9"><input type="file" class="form-control" name="FannieUploadFile" id="FannieUploadFile" /></div>
        </div>
        <div class="form-group col-sm-6">
            <button type="submit" class="btn btn-default">Upload File</button>
            <button type="button" class="btn btn-default" 
                onclick="location='PurchasingIndexPage.php'; return false;">Home</button>
        </div>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
