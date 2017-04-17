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

class TransferPurchaseOrder extends FannieRESTfulPage 
{
    protected $header = 'Transfer Order';
    protected $title = 'Transfer Order';

    public $description = '[Transfer Purchase Orders] moves some or all items from one order to another store.';
    protected $must_authenticate = true;

    protected function storeToStore()
    {
        $stores = FormLib::storePicker('fromStore', false);
        $stores2 = FormLib::storePicker('toStore', false);

        return '<div class="form-inline form-group">
            <label>Leaving</label> ' . $stores['html'] . '
             <label>Entering</label> ' . $stores2['html'] . '
            </div>';
    }

    protected function post_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get(('OP_DB')));
        $upcs = FormLib::get('upc', array()); 
        $from = FormLib::get('fromStore');
        $dest = FormLib::get('toStore');
        $valid = array_filter($upcs, function($u) { return $u != ''; }); 
        if (count($skus) === 0 || empty($from) || empty($dest) || $from === $dest) { 
            // nothing selected, bad IDs, same IDs
            return 'TransferPurchaseOrder.php';
        }
        $descs = FormLib::get('desc', array());
        $qtys = FormLib::get('qty', array());
        $costs = FormLib::get('cost', array());

        $order1 = new PurchaseOrderModel($dbc);
        $order1->vendorID(FormLib::get('vendor'));
        $order1->storeID($from);
        $order1->placed(1);
        $order1->placedDate(date('Y-m-d H:i:s'));
        $order1->creationDate(date('Y-m-d H:i:s'));
        $fromID = $order1->save();
        $order1->vendorInvoiceID('XFER-' . $fromID);
        $order1->orderID($fromID);
        $order1->save();

        $order2 = new PurchaseOrderModel($dbc);
        $order1->vendorID(FormLib::get('vendor'));
        $order2->storeID($dest);
        $order2->placed(1);
        $order2->placedDate(date('Y-m-d H:i:s'));
        $order1->creationDate(date('Y-m-d H:i:s'));
        $order2->vendorInvoiceID('XFER-' . $fromID);
        $destID = $order2->save();

        $poi = new PurchaseOrderItemsModel($dbc);
        for ($i=0; $i<count($upcs); $i++) {
            if ($upcs[$i] == '') {
                continue;
            }
            $poi->internalUPC($upcs[$i]);
            $poi->sku($upcs[$i]);
            $poi->quantity(isset($qtys[$i]) ? $qtys[$i] : 0);
            $poi->caseSize(1);
            $poi->description(isset($descs[$i]) ? $descs[$i] : 'unknown');
            $poi->unitCost(isset($costs[$i]) ? $costs[$i]/$poi->quantity() : 0);
            $poi->receivedQty(isset($qtys[$i]) ? $qtys[$i] : 0);
            $poi->receivedTotalCost(isset($costs[$i]) ? $costs[$i] : 0);
            $poi->orderID($destID);
            $poi->save();

            $poi->orderID($fromID);
            $poi->quantity(-1*$item->quantity());
            $poi->receivedQty(-1*$item->receivedQty());
            $poi->receivedTotalCost(-1*$item->receivedTotalCost());
            $poi->save();
        }

        return 'ViewPurchaseOrders.php?id=' . $destID;
    }

    private function getStoreVendor($dbc, $storeID)
    {
        $prep = $dbc->prepare("
            SELECT v.vendorID
            FROM vendors AS v
                INNER JOIN Stores AS s ON v.vendorName=s.description
            WHERE s.storeID=?");
        return $dbc->getValue($prep, array($storeID));
    }

    protected function post_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get(('OP_DB')));
        $skus = FormLib::get('selected', array()); 
        $from = FormLib::get('fromStore');
        $dest = FormLib::get('toStore');
        if (count($skus) === 0 || empty($from) || empty($dest) || $from === $dest) { 
            // nothing selected, bad IDs, same IDs
            return 'TransferPurchaseOrder.php?id=' . $this->id;
        }

        $original = new PurchaseOrderModel($dbc);
        $original->orderID($this->id);
        $original->load();

        $order1 = new PurchaseOrderModel($dbc);
        $order1->vendorID($this->getStoreVendor($dbc, $dest));
        $order1->storeID($from);
        $order1->placed($original->placed());
        $order1->placedDate(date('Y-m-d H:i:s'));
        $order1->creationDate(date('Y-m-d H:i:s'));
        $fromID = $order1->save();
        $order1->vendorInvoiceID('XFER-OUT-' . $fromID);
        $order1->orderID($fromID);
        $order1->save();

        $order2 = new PurchaseOrderModel($dbc);
        $order1->vendorID($this->getStoreVendor($dbc, $from));
        $order2->storeID($dest);
        $order2->placed($original->placed());
        $order2->placedDate(date('Y-m-d H:i:s'));
        $order1->creationDate(date('Y-m-d H:i:s'));
        $order2->vendorInvoiceID('XFER-IN-' . $fromID);
        $destID = $order2->save();

        $poi = new PurchaseOrderItemsModel($dbc);
        $poi->orderID($this->id);
        foreach ($poi->find() as $item) {
            if (in_array($item->sku(), $skus)) {
                // copy item to destination order
                $item->orderID($destID);
                $item->save();

                // copy reverse of item to from order
                $item->orderID($fromID);
                $item->quantity(-1*$item->quantity());
                $item->receivedQty(-1*$item->receivedQty());
                $item->receivedTotalCost(-1*$item->receivedTotalCost());
                $item->save();
            }
        }

        return 'ViewPurchaseOrders.php?id=' . $destID;
    }

    protected function get_id_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get(('OP_DB')));
        $this->addScript('js/transfer.js');
        $this->addOnloadCommand("\$('#xfer-form').submit(xferPO.sameStore);\n");

        $poi = new PurchaseOrderItemsModel($dbc);
        $poi->orderID($this->id);
        $ret = '<form method="post" id="xfer-form">'
            . $this->storeToStore();
        $ret .= '<table class="table table-bordered">';
        $ret .= '<tr><th><input type="checkbox" onchange="xferPO.checkAll(this);" /></th>
            <th>SKU</th><th>Brand</th><th>Description</th><th>Qty</th><th>Cost</th></tr>';
        foreach ($poi->find() as $item) {
            $ret .= sprintf('<tr>
                <td><input type="checkbox" name="selected[]" class="checkAll" value="%s" /></td>
                <td>%s</td><td>%s</td><td>%s</td>
                <td>%.2f</td>
                <td>%.2f</td>
                </tr>',
                $item->sku(),
                $item->internalUPC(),
                $item->brand(),
                $item->description(),
                $item->receivedQty(),
                $item->receivedTotalCost()
            );
        }
        $ret .= '</table>
            <input type="hidden" name="id" value="' . $this->id . '" />
            <p><button type="submit" class="btn btn-default btn-core">Transfer</button></p>';

        return $ret;
    }

    protected function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get(('OP_DB')));
        $this->addScript('js/transfer.js');
        $this->addOnloadCommand("\$('#xfer-form').submit(xferPO.sameStore);\n");
        
        $ret = '<form method="post" id="xfer-form">'
            . $this->storeToStore();
        $ret .= '<div class="form-group form-inline">
                <label>Vendor</label>
                <select name="vendor" class="form-control" required><option value="">Select vendor...</option>';
        $vendors = new VendorsModel($dbc);
        $ret .= $vendors->toOptions() . '</select></div>';
        $ret .= '<table class="table table-bordered">';
        $ret .= '<tr><th>&nbsp;</th><th>UPC</th><th>Description</th><th>Qty</th><th>Cost</th></tr>';
        for ($i=0; $i<25; $i++) {
            $ret .= '<tr>
                <td>' . ($i+1) . '</td>
                <td><input type="text" class="form-control input-sm" name="upc[]" placeholder="UPC or SKU" /></td>
                <td><input type="text" class="form-control input-sm" name="desc[]" placeholder="Description" /></td>
                <td><input type="text" class="form-control input-sm" name="qty[]" placeholder="Quantity" /></td>
                <td><input type="text" class="form-control input-sm" name="cost[]" placeholder="Total Cost" /></td>
                </tr>';
        }
        $ret .= '</table>
            <p><button type="submit" class="btn btn-default btn-core">Transfer</button></p>';
        
        return $ret;
    }

    public function helpContent()
    {
        return '<p>Create a pair of purchase orders representing a transfer. Specify which store is sending the items,
which is receiving them, and a vendor then fill in as many rows as necessary to represent all the items.</p>';
    }
}

FannieDispatch::conditionalExec();

