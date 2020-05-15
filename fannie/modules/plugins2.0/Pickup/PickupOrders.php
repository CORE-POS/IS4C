<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('PickupOrdersModel')) {
    include(__DIR__ . '/models/PickupOrdersModel.php');
}
if (!class_exists('PickupOrderItemsModel')) {
    include(__DIR__ . '/models/PickupOrderItemsModel.php');
}

class PickupOrders extends FannieRESTfulPage
{
    protected $header = 'Pickup Orders';
    protected $title = 'Pickup Orders';

    protected function post_id_handler()
    {
        $id = trim($this->id);

        $order = new PickupOrdersModel($this->connection);
        $order->orderNumber($id);
        $order->name(FormLib::get('name'));
        $order->phone(FormLib::get('phone'));
        $order->vehicle(FormLib::get('vehicle'));
        $order->pDate(FormLib::get('pdate'));
        $order->pTime(FormLib::get('ptime'));
        $order->notes(FormLib::get('notes'));
        $order->storeID(FormLib::get('store'));
        $order->status('NEW');
        $order->cardNo(FormLib::get('cardno'));
        $orderID = $order->save();

        $upcs = FormLib::get('upc');
        $brands = FormLib::get('brand');
        $items = FormLib::get('description');
        $qtys = FormLib::get('qty');
        $totals = FormLib::get('total');
        $this->connection->startTransaction();
        $insP = $this->connection->prepare("INSERT INTO PickupOrderItems
            (pickupOrderID, upc, brand, description, quantity, total)
            VALUES (?, ?, ?, ?, ?, ?)");
        for ($i=0; $i<count($upcs); $i++) {
            $this->connection->execute($insP, array(
                $orderID,
                $upcs[$i],
                $brands[$i],
                $items[$i],
                $qtys[$i],
                $totals[$i],
            ));
        }
        $this->connection->commitTransaction();

        include(__DIR__ . '/../../../src/Credentials/OutsideDB.tunneled.php');
        $web = $dbc;
        list($empno, $transno) = explode('-', $id);
        $delP = $web->prepare("DELETE FROM localtemptrans WHERE emp_no=? AND trans_no=?");
        $web->execute($delP, array($empno, $transno));
        $noteP = $web->prepare("DELETE FROM CurrentOrderNotes WHERE userID=?");
        $web->execute($noteP, array($empno));

        return 'ViewPickups.php?id=' . $orderID;
    }

    protected function get_id_view()
    {
        $id = trim($this->id);
        if (!preg_match('/^\d+-\d+$/', $id)) {
            return '<div class="alert alert-danger">Order Number should be two numbers separated by a dash</div>
                <p><a href="PickupOrders.php" class="btn btn-default">Back</a></p>';
        }

        include(__DIR__ . '/../../../src/Credentials/OutsideDB.tunneled.php');
        $web = $dbc;
        list($empno, $transno) = explode('-', $id);

        $userP = $web->prepare("SELECT real_name, name, owner FROM Users WHERE uid=?");
        $user = $web->getRow($userP, array($empno));

        $itemP = $web->prepare("SELECT l.upc,
            CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END AS brand,
            CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END AS description,
            l.quantity,
            l.unitPrice,
            l.total
            FROM localtemptrans AS l
                INNER JOIN products AS p ON l.upc=p.upc
                LEFT JOIN productUser AS u ON l.upc=u.upc
            WHERE l.emp_no=?
                AND l.trans_no=?");
        $items = $web->getAllRows($itemP, array($empno, $transno));

        $noteP = $web->prepare("SELECT notes FROM CurrentOrderNotes WHERE userID=?");
        $notes = $web->getValue($noteP, array($empno));

        if ($items === false || count($items) == 0) {
            $oursP = $this->connection->prepare("SELECT pickupOrderID FROM PickupOrders WHERE orderNumber=?");
            $ours = $this->connection->getValue($oursP, array($this->id));
            if ($ours) {
                $this->addOnloadCommand("location = 'ViewPickups.php?id={$ours}';");
                return 'Redirecting...';
            }

            return '<div class="alert alert-danger">Order Number not found</div>
                <p><a href="PickupOrders.php" class="btn btn-default">Back</a></p>';
        }

        $table = '<table id="itemtable" class="table table-bordered table-striped collapse">';
        $table .= '<tr><th>Brand</th><th>Item</th><th>Quantity</th><th>Total ($)</th></tr>';
        foreach ($items as $item) {
            $table .= sprintf('<tr>
                <td>
                    <input type="hidden" name="upc[]" value="%s" />
                    <input type="hidden" name="brand[]" value="%s" />
                    %s
                </td>
                <td>
                    <input type="hidden" name="description[]" value="%s" />
                    %s
                </td>
                <td>
                    <input type="hidden" name="qty[]" value="%.2f" />
                    %.2f
                </td>
                <td>
                    <input type="hidden" name="total[]" value="%.2f" />
                    %.2f
                </td>
                </tr>',
                $item['upc'],
                $item['brand'],
                $item['brand'],
                $item['description'],
                $item['description'],
                $item['quantity'],
                $item['quantity'],
                $item['total'],
                $item['total']
            );                    
        }
        $table .= '</table>';
        $this->addOnloadCommand("\$('#firstIn').focus();");
        $stores = FormLib::storePicker('store', false);

        return <<<HTML
<h3>Order Number: {$id}</h3>
<p>
Username: {$user['name']}<br />
Name: {$user['real_name']}
</p>
<form method="post" action="PickupOrders.php">
<input type="hidden" name="id" value="{$id}" />
<p>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Name</span>
            <input type="text" id="firstIn" class="form-control" name="name" value="{$user['real_name']}" required />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Owner #</span>
            <input type="text" class="form-control" name="cardno" value="{$user['owner']}" />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Phone</span>
            <input type="phone" class="form-control" name="phone" required />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Vehicle Info</span>
            <input type="text" class="form-control" name="vehicle" required />
        </div>
    </div>
</p>
<p>
<span style="font-size:150%;">
<a href="" onclick="$('#itemtable').toggle(); return false;">Items</a> (Show/Hide)
</span>
{$table}
</p>
<p>
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Pick-up Date</span>
            <input type="text" class="form-control date-field" name="pdate" required />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group form-inline">
            <span class="input-group-addon">Pick-up Time</span>
            <input type="text" class="form-control" name="ptime" required 
                placeholder="ex: 4:30pm" />
        </div>
    </div>
    <div class="form-group">
        <div class="input-group form-inline">
            <span class="input-group-addon">Pick-up Location</span>
            {$stores['html']}
        </div>
    </div>
    <div class="form-group">
        <label>Notes</label>
        <textarea rows="4" class="form-control" name="notes">{$notes}</textarea>
    </div>
    <div class="form-group">
        <button class="btn btn-default">Accept Order</button>
    </div>
    <div class="form-group">
        <a href="PickupOrders.php" class="btn btn-danger">Cancel</a>
    </div>
</p>
</form>
HTML;
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#orderID').focus();");
        return <<<HTML
<p>
    <a href="ViewPickups.php" class="btn btn-default">View Orders</a>
</p>
<p>
    <a href="PickupEnabledPage.php" class="btn btn-default">Manage Item Listings</a>
<p>
<form method="get" action="PickupOrders.php">
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">Order #</span>
            <input type="text" class="form-control" name="id" id="orderID" />
        </div>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Start New Order</button>
    </div> 
</form>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

