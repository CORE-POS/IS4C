<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class ManualPurchaseOrderPage extends FannieRESTfulPage 
{

    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';
    public $themed = true;

    public $description = '[Manual Purchase Order] is a tool for entering purchase order info
        in a grid from existing paperwork.';
    public $page_set = 'Purchasing';
    
    public function preprocess()
    {
        $this->__routes[] = 'get<id><adjust>';

        $ret = parent::preprocess();
        return $ret;
    }

    public function post_id_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret = array('error' => false);

        $date = FormLib::get('order-date', date('Y-m-d')); 
        $po_num = FormLib::get('po-number');
        $inv_num = FormLib::get('inv-number');

        $sku = FormLib::get('sku', array());
        $upc = FormLib::get('upc', array());
        $cases = FormLib::get('cases', array());
        $caseSize = FormLib::get('case-size', array());
        $total = FormLib::get('total', array());
        $brand = FormLib::get('brand', array());
        $description = FormLib::get('description', array());

        if (count($sku) == 0) {
            $ret['error'] = true;
            $ret['message'] = 'Order must have at least one item';
            echo json_encode($ret);

            return false;
        }

        /**
          Queries to check for vendorItems entries
        */
        $skuP = $dbc->prepare('
            SELECT size
            FROM vendorItems
            WHERE vendorID=?
                AND sku=?');
        $upcP = $dbc->prepare('
            SELECT size
            FROM vendorItems
            WHERE vendorID=?
                AND upc=?');
        $vitem = new VendorItemsModel($dbc);

        /**
          Create parent record for the order
        */
        $po = new PurchaseOrderModel($dbc);
        $po->vendorID($this->id);
        $po->creationDate($date);
        $po->placed(1);
        $po->placedDate($date);
        $po->userID(FannieAuth::getUID());
        $po->vendorOrderID($po_num);
        $po->vendorInvoiceID($inv_num);
        // if an orderID is supplied, update the existing order
        if (FormLib::get('order-id') !== '' && is_numeric(FormLib::get('order-id'))) {
            $orderID = FormLib::get('order-id');
            $po->orderID($orderID);
            $po->save();
        } else {
            $orderID = $po->save();
        }

        if (!$orderID) {
            $ret['error'] = true;
            $ret['message'] = 'Could not create new order';
            echo json_encode($ret);

            return false;
        }

        /**
          Create item records for the order
        */
        $pitem = new PurchaseOrderItemsModel($dbc);
        for ($i=0; $i<count($sku); $i++) {
            $pitem->reset();
            $pitem->orderID($orderID);
            $pitem->sku($sku[$i]);

            $units = $caseSize[$i];
            $qty = $cases[$i];
            $unitCost = $total[$i] / $qty / $units;
            /**
              Multiple same-SKU records
              Sum the quantities and costs to merge
              into a single record
            */
            if ($pitem->load()) {
                $qty += $pitem->receivedQty();
                $total[$i] += $pitem->receivedTotalCost();
            }

            $pitem->quantity($qty);
            $pitem->caseSize($units);
            $pitem->unitSize('');
            $pitem->unitCost($unitCost);
            $pitem->receivedDate($date);
            $pitem->receivedQty($qty);
            $pitem->receivedTotalCost($total[$i]);
            $pitem->brand($brand[$i]);
            $pitem->description($description[$i]);
            $pitem->internalUPC($upc[$i]);

            /**
              Try to look up unit size using
              vendorID+sku or vendorID+upc.
              This avoids making unit size a required
              field *and* checks for an existing
              vendorItems record
            */
            $size = false;
            $skuR = $dbc->execute($skuP, array($this->id, $sku[$i]));
            if ($skuR && $dbc->numRows($skuR)) {
                $size = true;
                $w = $dbc->fetchRow($skuR);
                $pitem->unitSize($w['size']);
            }
            if ($size === false) {
                $upcR = $dbc->execute($upcP, array($this->id, $upc[$i]));
                if ($upcR && $dbc->numRows($upcR)) {
                    $size = true;
                    $w = $dbc->fetchRow($upcR);
                    $pitem->unitSize($w['size']);
                }
            }
            $pitem->save();

            /**
              If no vendorItems record exists for this
              SKU or UPC then create one
            */
            if ($size === false) {
                $vitem->reset();
                $vitem->vendorID($this->id);
                $vitem->sku($sku[$i]);
                $vitem->upc($upc[$i]);
                $vitem->brand($brand[$i]);
                $vitem->description($description[$i]);
                $vitem->size('');
                $vitem->units($qty);
                $vitem->cost($unitCost);
                $vitem->saleCost(0.00);
                $vitem->vendorDept(0);
                $vitem->save();
            }
        }

        $ret['order_id'] = $orderID;
        echo json_encode($ret);

        return false;
    }

    public function get_id_adjust_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->adjust);
        $order->load(); 
        $orderJSON = $order->toJSON();

        $items = new PurchaseOrderItemsModel($dbc);
        $items->orderID($this->adjust);
        $itemsJSON = '[';
        foreach ($items->find() as $item) {
            $itemsJSON .= $item->toJSON() . ',';
        }
        if (strlen($itemsJSON) > 1) {
            $itemsJSON = substr($itemsJSON, 0, strlen($itemsJSON)-1);
        }
        $itemsJSON .= ']';

        $orderJSON = str_replace('\\', '\\\\', $orderJSON);
        $itemsJSON = str_replace('\\', '\\\\', $itemsJSON);

        $this->addOnloadCommand("existingOrder('$orderJSON', '$itemsJSON');\n");

        return $this->get_id_view();
    }

    public function get_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($this->id);
        $vendor->load();

        $ret = '<p id="vendor-name">New <strong>' . $vendor->vendorName() . '</strong> order</p>';
        $ret .= '<div id="alert-area"></div>';
        $ret .= '<form id="order-form" onsubmit="saveOrder(); return false;">
            <input type="hidden" id="vendor-id" name="id" value="' . $this->id . '" />
            <div class="form-group form-inline">
                <label>Order Date</label>
                <input type="text" name="order-date" class="form-control date-field"
                    value="' . date('Y-m-d') . '" />
                <label>PO #</label>
                <input type="text" name="po-number" class="form-control" />
                <label>Inv. #</label>
                <input type="text" name="inv-number" class="form-control" />
            </div>';
        $ret .= '<div class="collapse" id="delete-html">' . FannieUI::deleteIcon() . '</div>';
        $ret .= '<div class="form-group">
            <button type="button" class="btn btn-default" onclick="addInvoiceLine();">Add Line</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <button type="submit" class="btn btn-default" id="save-btn">Save As Order</button>
            </div>';

        $ret .= '<table class="table table-bordered" id="invoice-table">
            <thead><tr>
                <th>SKU</th>
                <th>UPC</th>
                <th>Cases</th>
                <th>Units/Case</th>
                <th>Total Cost</th>
                <th>Brand</th>
                <th>Description</th>
            </thead>
            <tbody>
            </tbody>
            </table>';

        $ret .= '</form>';

        $this->addScript('js/manual.js');
        $this->addScript('../item/autocomplete.js');
        $this->addOnloadCommand('addInvoiceLine();');

        return $ret;
    }

    public function get_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="form-group">
                <label>Vendor</label>
                <select name="id" class="form-control">';
        $vendors = new VendorsModel($dbc);
        foreach ($vendors->find('vendorName') as $obj) {
            $ret .= sprintf('<option value="%d">%s</option>',
                $obj->vendorID(), $obj->vendorName());
        }
        $ret .= '</select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Continue</button>
            </div>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Build a purchase order or transcribe an invoice
            one line at a time. Auto completion is available
            via both product UPC and vendor item SKU.
            </p>';
    }
}

FannieDispatch::conditionalExec();

