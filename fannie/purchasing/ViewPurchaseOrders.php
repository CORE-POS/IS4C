<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class ViewPurchaseOrders extends FannieRESTfulPage 
{
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[View Purchase Orders] lists pending orders and completed invoices.';

    protected $must_authenticate = true;

    private $show_all = true;

    public function preprocess()
    {
        $this->addRoute(
            'get<pending>',
            'get<placed>',
            'post<id><setPlaced>',
            'get<id><export>',
            'get<id><receive>',
            'get<id><receiveAll>',
            'get<id><sku>',
            'get<id><recode>',
            'post<id><sku><recode>',
            'post<id><sku><qty><cost>',
            'post<id><sku><upc><brand><description><orderQty><orderCost><receiveQty><receiveCost>',
            'post<id><sku><qty><receiveAll>',
            'post<id><note>',
            'post<id><sku><isSO>'
        );
        if (FormLib::get('all') === '0')
            $this->show_all = false;
        return parent::preprocess();
    }

    /**
      Callback: save item's isSpecialOrder setting
    */
    protected function post_id_sku_isSO_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $item = new PurchaseOrderItemsModel($this->connection);
        $item->orderID($this->id);
        $item->sku($this->sku);
        $item->isSpecialOrder($this->isSO);
        $item->save();

        return false;
    }

    /**
      Callback: save notes associated with order
    */
    protected function post_id_note_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $note = new PurchaseOrderNotesModel($this->connection);
        $note->orderID($this->id);
        $note->notes(trim($this->note));
        if ($note->notes() === '') {
            $note->delete();
        } else {
            $note->save();
        }

        return false;
    }

    protected function get_id_export_handler()
    {
        if (!file_exists('exporters/'.$this->export.'.php'))
            return $this->unknown_request_handler();
        include_once('exporters/'.$this->export.'.php');    
        if (!class_exists($this->export))
            return $this->unknown_request_handler();

        $exportObj = new $this->export();
        $exportObj->send_headers();
        $exportObj->export_order($this->id);
        return false;
    }

    protected function post_id_setPlaced_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderModel(FannieDB::get($this->connection));
        $model->orderID($this->id);
        $model->load();
        $model->placed($this->setPlaced);
        if ($this->setPlaced == 1) {
            $model->placedDate(date('Y-m-d H:m:s'));
        } else {
            $model->placedDate(0);
        }
        $model->save();

        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($this->id);
        $cache = new InventoryCacheModel($this->connection);
        if (!class_exists('SoPoBridge')) {
            include(__DIR__ . '/../ordering/SoPoBridge.php');
        }
        $bridge = new SoPoBridge($this->connection, $this->config);
        foreach ($poi->find() as $item) {
            $cache->recalculateOrdered($item->internalUPC(), $model->storeID());
            if ($this->setPlaced ==1 && $poi->isSpecialOrder()) {
                $soID = substr($poi->internalUPC(), 0, 9);
                $transID = substr($poi->internalUPC(), 9);
                $bridge->markAsPlaced($soID, $transID);
            }
        }
        echo ($this->setPlaced == 1) ? $model->placedDate() : 'n/a';

        return false;
    }

    protected function get_pending_handler()
    {
        echo $this->get_orders(0);
        return false;
    }

    protected function get_placed_handler()
    {
        echo $this->get_orders(1);
        return false;
    }

    protected function get_orders($placed)
    {
        $dbc = $this->connection;
        $store = FormLib::get('store', 0);

        $month = FormLib::get('month');
        $year = FormLib::get('year');
        $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, $month, 1, $year));
        $end = date('Y-m-t 23:59:59', mktime(0, 0, 0, $month, 1, $year));
        $args = array($placed, $start, $end);
        
        $query = 'SELECT p.orderID, p.vendorID, MIN(creationDate) as creationDate,
                MIN(placedDate) as placedDate, COUNT(i.orderID) as records,
                SUM(i.unitCost*i.caseSize*i.quantity) as estimatedCost,
                SUM(i.receivedTotalCost) as receivedCost, v.vendorName,
                MAX(i.receivedDate) as receivedDate,
                MAX(p.vendorInvoiceID) AS vendorInvoiceID,
                MAX(s.description) AS storeName
            FROM PurchaseOrder as p
                LEFT JOIN PurchaseOrderItems AS i ON p.orderID = i.orderID
                LEFT JOIN vendors AS v ON p.vendorID=v.vendorID
                LEFT JOIN Stores AS s ON p.storeID=s.storeID
            WHERE placed=? 
                AND creationDate BETWEEN ? AND ? ';
        if (!$this->show_all) {
            $query .= 'AND userID=? ';
        }
        if ($store != 0) {
            $query .= ' AND p.storeID=? ';
            $args[] = $store;
        }
        $query .= 'GROUP BY p.orderID, p.vendorID, v.vendorName 
                   ORDER BY MIN(creationDate) DESC';
        if (!$this->show_all) $args[] = FannieAuth::getUID($this->current_user);

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $ret = '<div class="table-responsive">
            <table class="table table-striped table-bordered tablesorter">';
        $ret .= '<thead><tr><th>Created</th><th>Invoice#</th><th>Store</th><th>Vendor</th><th># Items</th><th>Est. Cost</th>
            <th>Placed</th><th>Received</th><th>Rec. Cost</th></tr></thead><tbody>';
        $count = 1;
        while ($row = $dbc->fetchRow($result)) {
            $ret .= $this->orderRowToTable($row, $placed);
        }
        $ret .= '</tbody></table></div>';

        return $ret;
    }

    private function orderRowToTable($row, $placed)
    {
        return sprintf('<tr><td><a href="ViewPurchaseOrders.php?id=%d">%s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td><td>%d</td><td>%.2f</td>
                <td>%s</td><td>%s</td><td>%.2f</td></tr>',
                $row['orderID'],
                $row['creationDate'], $row['vendorInvoiceID'], $row['storeName'], $row['vendorName'], $row['records'],
                $row['estimatedCost'],
                ($placed == 1 ? $row['placedDate'] : '&nbsp;'),
                (!empty($row['receivedDate']) ? $row['receivedDate'] : '&nbsp;'),
                (!empty($row['receivedCost']) ? $row['receivedCost'] : 0.00)
        );
    }

    protected function delete_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->delete();

        $items = new PurchaseOrderItemsModel($dbc);
        $items->orderID($this->id);
        foreach ($items->find() as $item) {
            $item->delete();
        }

        echo 'deleted';

        return false;
    }

    protected function post_id_sku_qty_receiveAll_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $re_date = FormLib::get('re-date', false);
        $uid = FannieAuth::getUID($this->current_user);
        for ($i=0; $i<count($this->sku); $i++) {
            $model->sku($this->sku[$i]);
            $model->load();
            $model->receivedQty($this->qty[$i]);
            $model->receivedBy($uid);
            $model->receivedTotalCost($model->receivedQty()*$model->unitCost());
            if ($model->receivedDate() === null || $re_date) {
                $model->receivedDate(date('Y-m-d H:i:s'));
            }
            $model->save();
        }

        $prep = $dbc->prepare('
            SELECT o.storeID, i.internalUPC
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.orderID=?');
        $res = $dbc->execute($prep, array($this->id));
        $cache = new InventoryCacheModel($dbc);
        while ($row = $dbc->fetchRow($res)) {
            $cache->recalculateOrdered($row['internalUPC'], $row['storeID']);
        }

        return 'ViewPurchaseOrders.php?id=' . $this->id;
    }

    protected function post_id_sku_recode_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);

        for ($i=0; $i<count($this->sku); $i++) {
            if (!isset($this->recode[$i])) {
                continue;
            }
            $model->sku($this->sku[$i]);
            $model->salesCode($this->recode[$i]);
            $model->save();
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $this->id;
    }

    protected function get_id_recode_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);

        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <table class="table table-striped">
            <tr>
                <td><input type="text" placeholder="Change All" class="form-control" 
                    onchange="if (this.value != \'\') { $(\'.recode-sku\').val(this.value); }" /></td>
                <th>SKU</th>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
            </tr>';
        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }
        foreach ($model->find() as $item) {
            $ret .= sprintf('<tr>
                <td><input class="form-control recode-sku" type="text" 
                    name="recode[]" value="%s" required /></td>
                <td>%s<input type="hidden" name="sku[]" value="%s" /></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                </tr>',
                $accounting::toPurchaseCode($item->salesCode()),
                $item->sku(), $item->sku(),
                $item->internalUPC(),
                $item->brand(),
                $item->description()
            );
        }
        $ret .= '</table>
            <p><button type="submit" class="btn btn-default">Save Codings</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="ViewPurchaseOrders.php?id=' . $this->id . '" class="btn btn-default">Back to Order</a>
            </p>
        </form>';

        return $ret;
    }

    private $empty_vendor = array(
        'vendorName'=>'',
        'phone'=>'',
        'fax'=>'',
        'email'=>'',
        'address'=>'',
        'city'=>'',
        'state'=>'',
        'zip'=>'',
        'notes'=>'',
    );

    protected function get_id_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->load();
        $orderObj = $order->toStdClass();
        $orderObj->placedDate = $orderObj->placed ? $orderObj->placedDate : 'n/a';
        $placedCheck = $orderObj->placed ? 'checked' : '';
        $init = $orderObj->placed ? 'init=placed' : 'init=pending';
    
        $notes = $dbc->prepare('SELECT notes FROM PurchaseOrderNotes WHERE orderID=?');
        $notes = $dbc->getValue($notes, $this->id);
        $vname = $dbc->prepare('SELECT * FROM vendors WHERE vendorID=?');
        $vendor = $dbc->getRow($vname, array($orderObj->vendorID));
        if ($vendor) {
            $vendor['notes'] = nl2br($vendor['notes']);
        } else {
            $vendor = $this->empty_vendor;
        }
        $sname = $dbc->prepare('SELECT description FROM Stores WHERE storeID=?');
        $sname = $dbc->getValue($sname, array($orderObj->storeID));

        $exportOpts = '';
        foreach (COREPOS\Fannie\API\item\InventoryLib::orderExporters() as $class => $name) {
            $selected = $class === $this->config->get('DEFAULT_PO_EXPORT') ? 'selected' : '';
            $exportOpts .= '<option ' . $selected . ' value="'.$class.'">'.$name.'</option>';
        }
        $uname = FannieAuth::getName($order->userID());
        if (!$uname) {
            $uname = 'n/a';
        }

        $ret = <<<HTML
<p>
    <div class="form-inline">
        <b>Store</b>: {$sname}
        &nbsp;&nbsp;&nbsp;&nbsp;
        <b>Vendor</b>: {$vendor['vendorName']}
        &nbsp;&nbsp;&nbsp;&nbsp;
        <b>Created</b>: {$orderObj->creationDate}
        &nbsp;&nbsp;&nbsp;&nbsp;
        <b>Placed</b>: <span id="orderPlacedSpan">{$orderObj->placedDate}</span>
        <input type="checkbox" {$placedCheck} id="placedCheckbox"
                onclick="togglePlaced({$this->id});" />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Export as: <select id="exporterSelect" class="form-control input-sm">
            {$exportOpts}
        </select> 
        <button type="submit" class="btn btn-default btn-sm" onclick="doExport({$this->id});return false;">Export</button>
        &nbsp;&nbsp;&nbsp;
        <a type="button" class="btn btn-default btn-sm" 
            href="ViewPurchaseOrders.php?{$init}">All Orders</a>
    </div>
</p>
<div class="row">
    <div class="col-sm-6">
        <table class="table table-bordered small">
            <tr>
                <td><b>PO#</b>: {$orderObj->vendorOrderID}</td>
                <td><b>Invoice#</b>: {$orderObj->vendorInvoiceID}</td>
                <th colspan="2">Coding(s)</th>
            </tr>
            <tr> 
                <td rowspan="10" colspan="2">
                    <label>Notes</label>
                    <textarea class="form-control" 
                        onkeypress="autoSaveNotes({$this->id}, this);">{$notes}</textarea>
                </td>
            {{CODING}}
            <tr>
                <td><b>Created by</b>: {$uname}</td>
                <td>&nbsp;</td>
            </tr>
        </table>
    </div>
    <div class="col-sm-6">
    <p>
HTML;
        if (!$order->placed()) {
            $ret .= <<<HTML
<a class="btn btn-default btn-sm"
    href="EditOnePurchaseOrder.php?id={$this->id}">Add Items</a>
&nbsp;&nbsp;&nbsp;&nbsp;
<button class="btn btn-default btn-sm" 
    onclick="deleteOrder({$this->id}); return false;">Delete Order</button>
HTML;
        } else {
            $sentDate = new DateTime($order->creationDate());
            $today = new DateTime();
            if ($today->diff($sentDate)->format('%a') <= 90) {
                $ret .= <<<HTML
<a class="btn btn-default btn-sm"
    href="ManualPurchaseOrderPage.php?id={$orderObj->vendorID}&adjust={$this->id}">Edit Order</a>
&nbsp;&nbsp;&nbsp;&nbsp;
<a class="btn btn-default btn-sm" id="receiveBtn"
    href="ViewPurchaseOrders.php?id={$this->id}&receive=1">Receive Order</a>
&nbsp;&nbsp;&nbsp;&nbsp;
<a class="btn btn-default btn-sm" id="receiveBtn"
    href="TransferPurchaseOrder.php?id={$this->id}">Transfer Order</a>
&nbsp;&nbsp;&nbsp;&nbsp;
<a class="btn btn-default btn-sm"
    href="ViewPurchaseOrders.php?id={$this->id}&recode=1">Alter Codings</a>
HTML;
            }
        }
        $ret .= <<<HTML
        </p>
<div class="panel panel-default"><div class="panel-body">
Ph: {$vendor['phone']}<br />
Fax: {$vendor['fax']}<br />
Email: {$vendor['email']}<br />
{$vendor['address']}, {$vendor['city']}, {$vendor['state']} {$vendor['zip']}<br />
{$vendor['notes']}
</div></div>
HTML;
        $ret .= '</div></div>';

        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $codings = array();
        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }

        $ret .= '<table class="table tablesorter table-bordered small"><thead>';
        $ret .= '<tr><th>Coding</th><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Unit Size</th><th>Units/Case</th><th>Cases</th>
            <th>Est. Cost</th><th>Received</th>
            <th>Rec. Qty</th><th>Rec. Cost</th><th>SO</th></tr></thead><tbody>';
        foreach ($model->find() as $obj) {
            $css = $this->qtyToCss($order->placed(), $obj->quantity(),$obj->receivedQty());
            if ($obj->salesCode() == '') {
                $code = $obj->guessCode();
                $obj->salesCode($code);
                $obj->save();
            }
            $coding = (int)$obj->salesCode();
            $coding = $accounting::toPurchaseCode($coding);
            if (!isset($codings[$coding])) {
                $codings[$coding] = 0.0;
            }
            $codings[$coding] += $obj->receivedTotalCost();
            $ret .= sprintf('<tr %s><td>%d</td><td>%s</td>
                    <td><a href="../item/ItemEditorPage.php?searchupc=%s">%s</a></td><td>%s</td><td>%s</td>
                    <td>%s</td><td>%s</td><td>%s</td><td>%.2f</td>
                    <td>%s</td><td>%s</td><td>%.2f</td>
                    <td>
                        <select class="form-control input-sm" onchange="isSO(%d, \'%s\', this.value);">
                        %s
                        </select>
                    </tr>',
                    $css,
                    $accounting::toPurchaseCode($obj->salesCode()),
                    $obj->sku(),
                    $obj->internalUPC(), $obj->internalUPC(),
                    $obj->brand(),
                    $obj->description(),
                    $obj->unitSize(), $obj->caseSize(),
                    $obj->quantity(),
                    ($obj->quantity() * $obj->caseSize() * $obj->unitCost()),
                    strtotime($obj->receivedDate()) ? date('Y-m-d', strtotime($obj->receivedDate())) : 'n/a',
                    $obj->receivedQty(),
                    $obj->receivedTotalCost(),
                    $this->id, $obj->sku(), $this->specialOrderSelect($obj->isSpecialOrder())
            );
        }
        $ret .= '</tbody></table>';

        $coding_rows = '';
        foreach ($codings as $coding => $ttl) {
            $coding_rows .= sprintf('<tr><td>%d</td><td>%.2f</td></tr>',
                $coding, $ttl);
        }
        $ret = str_replace('{{CODING}}', $coding_rows, $ret);

        $this->add_script('js/view.js');
        $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();\n");

        return $ret;
    }

    private function qtyToCss($placed, $ordered, $received)
    {
        if (!$placed) {
            return '';
        } elseif ($received == 0 && $ordered != 0) {
            return 'class="danger"';
        } elseif ($received < $quantity) {
            return 'class="warning"';
        } else {
            return '';
        }
    }

    private function specialOrderSelect($isSO)
    {
        if ($isSO) {
            return '<option value="1" selected>Yes</option><option value="0">No</option>';
        } else {
            return '<option value="1">Yes</option><option value="0" selected>No</option>';
        }
    }

    /**
      Receiving interface for processing enter recieved costs and quantities
      on an order
    */
    protected function get_id_receive_view()
    {
        $this->add_script('js/view.js');
        $ret = '
            <p>Receiving order #<a href="ViewPurchaseOrders.php?id=' . $this->id . '">' . $this->id . '</a></p>
            <p><div class="form-inline">
                <form onsubmit="receiveSKU(); return false;" id="receive-form">
                <label>SKU</label>
                <input type="text" name="sku" id="sku-in" class="form-control" />
                <input type="hidden" name="id" value="' . $this->id . '" />
                <button type="submit" class="btn btn-default">Continue</button>
                <a href="?id=' . $this->id . '&receiveAll=1" class="btn btn-default btn-reset">All</a>
                </form>
            </div></p>
            <div id="item-area">
            </div>';
        $this->addOnloadCommand("\$('#sku-in').focus();\n");

        return $ret;
    }

    protected function get_id_receiveAll_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $poi = new PurchaseOrderItemsModel($dbc);
        $poi->orderID($this->id);
        $ret = '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <input type="hidden" name="receiveAll" value="1" />
            <table class="table table-bordered table-striped">
            <tr>
                <th>SKU</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Unit Size</th>
                <th>Qty Ordered</th>
                <th>Qty Receveived</th>
            </tr>';
        foreach ($poi->find() as $item) {
            $qty = $item->caseSize() * $item->quantity();
            $ret .= sprintf('<tr>
                <td><input type="hidden" name="sku[]" value="%s" />%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td><input type="text" class="form-control input-sm" name="qty[]" value="%.2f" /></td>
                </tr>',
                $item->sku(), $item->sku(),
                $item->brand(),
                $item->description(),
                $item->unitSize(),
                $qty,
                ($item->receivedQty() === null ? $qty : $item->receivedQty())
            );
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default btn-core">Receive Order</button>
                <button type="reset" class="btn btn-default btn-reset">Reset</button>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <label>Update Received Date <input type="checkbox" name="re-date" value="1" /></label>
            </p>
            </form>';

        return $ret;
    }

    /**
      Receiving AJAX callback. For items that were in
      the purchase order, just save the received quantity and cost
    */
    protected function post_id_sku_qty_cost_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        $model->receivedQty($this->qty);
        $model->receivedTotalCost($this->cost);
        $model->receivedBy(FannieAuth::getUID($this->current_user));
        if ($model->receivedDate() === null) {
            $model->receivedDate(date('Y-m-d H:i:s'));
        }
        $model->save();

        return false;
    }

    /**
      Receiving AJAX callback. For items that were NOT in
      the purchase order, create a whole record for the
      item that showed up. 
    */
    protected function post_id_sku_upc_brand_description_orderQty_orderCost_receiveQty_receiveCost_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        $model->internalUPC(BarcodeLib::padUPC($this->upc));
        $model->brand($this->brand);
        $model->description($this->description);
        $model->quantity($this->orderQty);
        $model->unitCost($this->orderCost);
        $model->caseSize(1);
        $model->receivedQty($this->receiveQty);
        $model->receivedTotalCost($this->receiveCost);
        $model->receivedDate(date('Y-m-d H:i:s'));
        $model->receivedBy(FannieAuth::getUID($this->current_user));
        $model->save();

        return false;
    }

    /**
      Receiving AJAX callback.
      Lookup item in the order and display form fields
      to enter required info 
    */
    protected function get_id_sku_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        // lookup by SKU but if nothing is found
        // try using the value as a UPC instead
        $found = false;
        if ($model->load()) {
            $found = true;
        } else {
            $model->reset();
            $model->orderID($this->id);
            $model->internalUPC(BarcodeLib::padUPC($this->sku));
            $matches = $model->find();
            if (count($matches) == 1) {
                $model = $matches[0];
                $found = true;
            }
        }
        
        // item not in order. need all fields to add it.
        echo '<form onsubmit="saveReceive(); return false;">';
        if (!$found) {
            $this->receiveUnOrderedItem($dbc);
        } else {
            // item in order. just need received qty and cost
            $this->receiveOrderedItem($dbc, $model);
        }
        echo '</form>';

        return false;
    }

    private function receiveUnOrderedItem($dbc)
    {
        echo '<div class="alert alert-danger">SKU not found in order</div>';
        echo '<table class="table table-bordered">';
        echo '<tr><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Qty Ordered</th><th>Cost (est)</th><th>Qty Received</th><th>Cost Received</th></tr>';
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->load();
        $item = new VendorItemsModel($dbc);
        $item->vendorID($order->vendorID());
        $item->sku($this->sku);
        $item->load();
        printf('<tr>
            <td>%s<input type="hidden" name="sku" value="%s" /></td>
            <td><input type="text" class="form-control" name="upc" value="%s" /></td>
            <td><input type="text" class="form-control" name="brand" value="%s" /></td>
            <td><input type="text" class="form-control" name="description" value="%s" /></td>
            <td><input type="text" class="form-control" name="orderQty" value="%s" /></td>
            <td><input type="text" class="form-control" name="orderCost" value="%.2f" /></td>
            <td><input type="text" class="form-control" name="receiveQty" value="%s" /></td>
            <td><input type="text" class="form-control" name="receiveCost" value="%.2f" /></td>
            <td><button type="submit" class="btn btn-default">Add New Item</button><input type="hidden" name="id" value="%d" /></td>
            </tr>',
            $item->sku(), $item->sku(),
            $item->upc(),
            $item->brand(),
            $item->description(),
            1,
            $item->cost() * $item->units(),
            0,
            0,
            $this->id
        );
        echo '</table>';
    }

    private function receiveOrderedItem($dbc, $model)
    {
        echo '<table class="table table-bordered">';
        echo '<tr><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Qty Ordered</th><th>Cost (est)</th><th>Qty Received</th><th>Cost Received</th></tr>';
        $uid = FannieAuth::getUID($this->current_user);
        if ($model->receivedQty() === null) {
            $model->receivedQty($model->quantity());
            $model->receivedBy($uid);
        }
        if ($model->receivedTotalCost() === null) {
            $model->receivedTotalCost($model->quantity()*$model->unitCost()*$model->caseSize());
            $model->receivedBy($uid);
        }
        printf('<tr>
            <td>%s<input type="hidden" name="sku" value="%s" /></td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%.2f</td>
            <td><input type="text" class="form-control" name="qty" value="%s" /></td>
            <td><input type="text" class="form-control" name="cost" value="%.2f" /></td>
            <td><button type="submit" class="btn btn-default">Save</button><input type="hidden" name="id" value="%d" /></td>
            </tr>',
            $model->sku(), $model->sku(),
            $model->internalUPC(),
            $model->brand(),
            $model->description(),
            $model->quantity(),
            $model->quantity() * $model->unitCost() * $model->caseSize(),
            $model->receivedQty(),
            $model->receivedTotalCost(),
            $this->id
        );
        echo '</table>';
    }

    protected function get_view()
    {
        $init = FormLib::get('init', 'placed');

        $month = date('n');
        $monthOpts = '';
        for($i=1; $i<= 12; $i++) {
            $label = date('F', mktime(0, 0, 0, $i)); 
            $monthOpts .= sprintf('<option %s value="%d">%s</option>',
                        ($i == $month ? 'selected' : ''),
                        $i, $label);
        }

        $statusOpts = '';
        foreach (array('pending', 'placed') as $s) {
            $statusOpts .= sprintf('<option %s value="%s">%s</option>',
                        ($init == $s ? 'selected' : ''),
                        $s, ucwords($s));
        }

        $stores = FormLib::storePicker();
        $storeSelect = str_replace('<select ', '<select id="storeID" onchange="fetchOrders();" ', $stores['html']);

        $yearOpts = '';
        for ($i = date('Y'); $i >= 2013; $i--) {
            $yearOpts .= '<option>' . $i . '</option>';
        }

        $allSelected = $this->show_all ? 'selected' : '';
        $mySelected = !$this->show_all ? 'selected' : '';

        $this->addScript('../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->addScript('js/view.js');
        $this->addOnloadCommand("fetchOrders();\n");

        return <<<HTML
<div class="form-group form-inline">
    <label>Status</label> 
    <select id="orderStatus" onchange="fetchOrders();" class="form-control">
        {$statusOpts}
    </select>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <label>Showing</label> 
    <select id="orderShow" onchange="fetchOrders();" class="form-control">
        <option {$mySelected} value="0">My Orders</option><option {$allSelected} value="1">All Orders</option>
    </select>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    {$storeSelect}
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <label>During</label> 
    <select id="viewMonth" onchange="fetchOrders();" class="form-control">
        {$monthOpts}
    </select>
    &nbsp;
    <select id="viewYear" onchange="fetchOrders();" class="form-control">
        {$yearOpts}
    </select>
    &nbsp;
    <button class="btn btn-default" onclick="location='PurchasingIndexPage.php'; return false;">Home</button>
</div>
<hr />
<div id="ordersDiv"></div>
HTML;
    }

    public function css_content()
    {
        return '
            .tablesorter thead th {
                cursor: hand;
                cursor: pointer;
            }';
    }

    public function helpContent()
    {
        if (isset($this->receive)) {
            return '<p>Receive an order. First enter a SKU (or UPC) to see
            the quantities that were ordered. Then enter the actual quantities
            received as well as costs. If a received item was <b>not</b> on the
            original order, you will be prompted to provide additional information
            so the item can be added to the order.</p>';
        } elseif (isset($this->id)) {
            return '<p>Details of a Purchase Order. Coding(s) are driven by POS department
            <em>Sales Codes</em>. Export outputs the order data in various formats.
            Edit Order loads the order line-items into an editing interface where adjustments
            to all fields can be made. Receive Order is used to resolve a purchase order
            with actual quantities received.
            </p>';
        } else {
            return '<p>Click the date link to view a particular purchase order. Use
                the dropdowns to filter the list. The distinction between <em>All Orders</em>
                and <em>My Orders</em> only works if user authentication is enabled.</p>';
        }
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = '4011';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $this->recode = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_recode_view()));
        $this->receive = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_receive_view()));
    }
}

FannieDispatch::conditionalExec();

