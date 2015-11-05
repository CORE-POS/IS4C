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

class ViewPurchaseOrders extends FannieRESTfulPage {

    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[View Purchase Orders] lists pending orders and completed invoices.';
    public $themed = true;

    protected $must_authenticate = false;

    private $show_all = true;

    function preprocess()
    {
        $this->__routes[] = 'get<pending>';
        $this->__routes[] = 'get<placed>';
        $this->__routes[] = 'post<id><setPlaced>';
        $this->__routes[] = 'get<id><export>';
        $this->__routes[] = 'get<id><receive>';
        $this->__routes[] = 'get<id><sku>';
        $this->__routes[] = 'get<id><recode>';
        $this->__routes[] = 'post<id><sku><recode>';
        $this->__routes[] = 'post<id><sku><qty><cost>';
        $this->__routes[] = 'post<id><sku><upc><brand><description><orderQty><orderCost><receiveQty><receiveCost>';
        if (FormLib::get_form_value('all') === '0')
            $this->show_all = false;
        return parent::preprocess();
    }

    function get_id_export_handler(){
        if (!file_exists('exporters/'.$this->export.'.php'))
            return $this->unknown_request_handler();
        include_once('exporters/'.$this->export.'.php');    
        if (!class_exists($this->export))
            return $this->unknown_request_handler();

        $exportObj = new $this->export();
        $exportObj->send_headers();
        $exportObj->export_order($this->id);
        return False;
    }

    function post_id_setPlaced_handler(){
        global $FANNIE_OP_DB;
        $model = new PurchaseOrderModel(FannieDB::get($FANNIE_OP_DB));
        $model->orderID($this->id);
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
        foreach ($poi->find() as $item) {
            $cache->recalculateOrdered($item->internalUPC(), 1);
        }
        echo ($this->setPlaced == 1) ? $model->placedDate() : 'n/a';

        return false;
    }

    function get_pending_handler(){
        echo $this->get_orders(0);
        return False;
    }

    function get_placed_handler(){
        echo $this->get_orders(1);
        return False;
    }

    function get_orders($placed)
    {
        global $FANNIE_OP_DB;   
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $month = FormLib::get('month');
        $year = FormLib::get('year');
        $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, $month, 1, $year));
        $end = date('Y-m-t 23:59:59', mktime(0, 0, 0, $month, 1, $year));
        
        $query = 'SELECT p.orderID, p.vendorID, MIN(creationDate) as creationDate,
                MIN(placedDate) as placedDate, COUNT(i.orderID) as records,
                SUM(i.unitCost*i.caseSize*i.quantity) as estimatedCost,
                SUM(i.receivedTotalCost) as receivedCost, v.vendorName,
                MAX(i.receivedDate) as receivedDate,
                p.vendorInvoiceID
            FROM PurchaseOrder as p
                LEFT JOIN PurchaseOrderItems AS i ON p.orderID = i.orderID
                LEFT JOIN vendors AS v ON p.vendorID=v.vendorID
            WHERE placed=? 
                AND creationDate BETWEEN ? AND ? ';
        if (!$this->show_all) {
            $query .= 'AND userID=? ';
        }
        $query .= 'GROUP BY p.orderID, p.vendorID, v.vendorName 
                   ORDER BY MIN(creationDate) DESC';
        $args = array($placed, $start, $end);
        if (!$this->show_all) $args[] = FannieAuth::getUID($this->current_user);

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep, $args);

        $ret = '<div class="table-responsive">
            <table class="table table-striped table-bordered tablesorter">';
        $ret .= '<thead><tr><th>Created</th><th>Invoice#</th><th>Vendor</th><th># Items</th><th>Est. Cost</th>
            <th>Placed</th><th>Received</th><th>Rec. Cost</th></tr></thead><tbody>';
        $count = 1;
        while($w = $dbc->fetch_row($result)){
            $ret .= sprintf('<tr><td><a href="ViewPurchaseOrders.php?id=%d">%s</a></td>
                    <td>%s</td>
                    <td>%s</td><td>%d</td><td>%.2f</td>
                    <td>%s</td><td>%s</td><td>%.2f</td></tr>',
                    $w['orderID'],
                    $w['creationDate'], $w['vendorInvoiceID'], $w['vendorName'], $w['records'],
                    $w['estimatedCost'],
                    ($placed == 1 ? $w['placedDate'] : '&nbsp;'),
                    (!empty($w['receivedDate']) ? $w['receivedDate'] : '&nbsp;'),
                    (!empty($w['receivedCost']) ? $w['receivedCost'] : 0.00)
            );
        }
        $ret .= '</tbody></table></div>';

        return $ret;
    }

    function delete_id_handler()
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

    public function post_id_sku_recode_handler()
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

        return $_SERVER['PHP_SELF'] . '?id=' . $this->id;
    }

    public function get_id_recode_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);

        $ret = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
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

    function get_id_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->load();

        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($order->vendorID());
        $vendor->load();

        $ret = '<p><div class="form-inline">';
        $ret .= '<b>Vendor</b>: '.$vendor->vendorName();
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Created</b>: '.$order->creationDate();
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Placed</b>: <span id="orderPlacedSpan">'.($order->placed() ? $order->placedDate() : 'n/a').'</span>';
        $ret .= '<input type="checkbox" '.($order->placed() ? 'checked' : '').' id="placedCheckbox"
                onclick="togglePlaced('.$this->id.');" />';

        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

        $ret .= 'Export as: <select id="exporterSelect" class="form-control">';
        $dh = opendir('exporters');
        while( ($file=readdir($dh)) !== False){
            if (substr($file,-4) != '.php')
                continue;
            include('exporters/'.$file);
            $class = substr($file,0,strlen($file)-4);
            if (!class_exists($class)) continue;
            $obj = new $class();
            if (!isset($obj->nice_name)) continue;
            $ret .= '<option value="'.$class.'">'.$obj->nice_name.'</option>';
        }
        $ret .= '</select> ';
        $ret .= '<button type="submit" class="btn btn-default" onclick="doExport('.$this->id.');return false;">Export</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $init = ($order->placed() ? 'init=placed' : 'init=pending');
        $ret .= '<button type="button" class="btn btn-default" 
            onclick="location=\'ViewPurchaseOrders.php?' . $init . '\'; return false;">All Orders</button>';
        $ret .= '</div></p>';

        $ret .= '<div class="row"><div class="col-sm-6">';
        $ret .= '<table class="table table-bordered"><tr><th colspan="2">Coding(s)</th>';
        $ret .= '<td><b>PO#</b>: '.$order->vendorOrderID().'</td>';
        $ret .= '<td><b>Invoice#</b>: '.$order->vendorInvoiceID().'</td>';
        $ret .= '</tr>';
        $ret .= '{{CODING}}';
        $ret .= '</table>';
        $ret .= '</div><div class="col-sm-6">';
        if (!$order->placed()) {
            $ret .= '<button class="btn btn-default"
                onclick="location=\'EditOnePurchaseOrder.php?id=' . $this->id . '\'; return false;">Add Items</button>';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a class="btn btn-default collapse" id="receiveBtn"
                href="ViewPurchaseOrders.php?id=' . $this->id . '&receive=1">Receive Order</a>';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<button class="btn btn-default" onclick="deleteOrder(' . $this->id . '); return false;">Delete Order</button>';
        } else {
            $ret .= '<a class="btn btn-default"
                href="ManualPurchaseOrderPage.php?id=' . $order->vendorID() . '&adjust=' . $this->id . '">Edit Order</a>';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a class="btn btn-default id="receiveBtn"
                href="ViewPurchaseOrders.php?id=' . $this->id . '&receive=1">Receive Order</a>';
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a class="btn btn-default"
                href="ViewPurchaseOrders.php?id=' . $this->id . '&recode=1">Alter Codings</a>';
        }
        $ret .= '</div></div>';

        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $codings = array();
        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }

        $ret .= '<table class="table tablesorter"><thead>';
        $ret .= '<tr><th>Coding</th><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Unit Size</th><th>Units/Case</th><th>Cases</th>
            <th>Est. Cost</th><th>&nbsp;</th><th>Received</th>
            <th>Rec. Qty</th><th>Rec. Cost</th></tr></thead><tbody>';
        foreach($model->find() as $obj){
            $css = '';
            if ($order->placed() == 0) {
            } elseif ($obj->receivedQty() == 0 && $obj->quantity() != 0) {
                $css = 'class="danger"';
            } elseif ($obj->receivedQty() < $obj->quantity()) {
                $css = 'class="warning"';
            }
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
                    <td>%s</td><td>%s</td><td>%d</td><td>%.2f</td>
                    <td>&nbsp;</td><td>%s</td><td>%d</td><td>%.2f</td>
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
                    $obj->receivedTotalCost()
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

    /**
      Receiving interface for processing enter recieved costs and quantities
      on an order
    */
    public function get_id_receive_view()
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
                </form>
            </div></p>
            <div id="item-area">
            </div>';
        $this->addOnloadCommand("\$('#sku-in').focus();\n");

        return $ret;
    }

    /**
      Receiving AJAX callback. For items that were in
      the purchase order, just save the received quantity and cost
    */
    public function post_id_sku_qty_cost_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        $model->receivedQty($this->qty);
        $model->receivedTotalCost($this->cost);
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
    public function post_id_sku_upc_brand_description_orderQty_orderCost_receiveQty_receiveCost_handler()
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
        $model->save();

        return false;
    }

    /**
      Receiving AJAX callback.
      Lookup item in the order and display form fields
      to enter required info 
    */
    public function get_id_sku_handler()
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
        if (!$found) {
            echo '<div class="alert alert-danger">SKU not found in order</div>';
            echo '<form onsubmit="saveReceive(); return false;">';
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
                $this->sku, $this->sku,
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
            echo '</form>';
        } else {
            // item in order. just need received qty and cost
            echo '<form onsubmit="saveReceive(); return false;">';
            echo '<table class="table table-bordered">';
            echo '<tr><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
                <th>Qty Ordered</th><th>Cost (est)</th><th>Qty Received</th><th>Cost Received</th></tr>';
            if ($model->receivedQty() === null) {
                $model->receivedQty($model->quantity());
            }
            if ($model->receivedTotalCost() === null) {
                $model->receivedTotalCost($model->quantity()*$model->unitCost()*$model->caseSize());
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
                $this->sku, $this->sku,
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
            echo '</form>';
        }

        return false;
    }

    public function get_view()
    {
        $init = FormLib::get('init', 'placed');

        $ret = '<div class="form-group form-inline">
            <label>Status</label> <select id="orderStatus" onchange="fetchOrders();" class="form-control">';
        $status = array('pending', 'placed');
        foreach ($status as $s) {
            $ret .= sprintf('<option %s value="%s">%s</option>',
                        ($init == $s ? 'selected' : ''),
                        $s, ucwords($s));
        }
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

        $ret .= '<label>Showing</label> <select id="orderShow" onchange="fetchOrders();" class="form-control">';
        if ($this->show_all)
            $ret .= '<option value="0">My Orders</option><option selected value="1">All Orders</option>';
        else
            $ret .= '<option selected value="0">My Orders</option><option value="1">All Orders</option>';
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        
        $ret .= '<label>During</label> ';
        $ret .= '<select id="viewMonth" onchange="fetchOrders();" class="form-control">';
        $month = date('n');
        for($i=1; $i<= 12; $i++) {
            $label = date('F', mktime(0, 0, 0, $i)); 
            $ret .= sprintf('<option %s value="%d">%s</option>',
                        ($i == $month ? 'selected' : ''),
                        $i, $label);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;';
        $ret .= '<select id="viewYear" onchange="fetchOrders();" class="form-control">';
        $year = date('Y');
        for($i = $year; $i >= 2013; $i--) {
            $ret .= '<option>' . $i . '</option>';
        }
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<button class="btn btn-default" onclick="location=\'PurchasingIndexPage.php\'; return false;">Home</button>';

        $ret .= '</div>';

        $ret .= '<hr />';
        
        $ret .= '<div id="ordersDiv"></div>';   

        $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->add_script('js/view.js');
        $this->add_onload_command("fetchOrders();\n");

        return $ret;
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
}

FannieDispatch::conditionalExec();

?>
