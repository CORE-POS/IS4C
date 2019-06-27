<?php

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class OrderGuidePage extends FannieRESTfulPage 
{
    protected $header = 'Order Guides';
    protected $title = 'Order Guides';

    public function preprocess()
    {
        $this->addRoute('get<new>', 'post<new>', 'get<order>', 'post<order>', 'get<edit>', 'post<edit>');

        return parent::preprocess();
    }

    protected function post_order_handler()
    {
        $vendorID = FormLib::get('vendor');
        $storeID = FormLib::get('store');
        $onhand = FormLib::get('onhand');
        $cases = FormLib::get('order');
        $upcs = FormLib::get('upc');
        $haveItems = false;
        for ($i=0; $i<count($cases); $i++) {
            if ($cases[$i]) {
                $haveItems = true;
                break;
            }
        }
        if (!$haveItems) {
            return 'OrderGuidePage.php?order=' . $vendorID . ',' . $storeID;
        }

        $order = new PurchaseOrderModel($this->connection);
        $uid = FannieAuth::getUID($this->current_user);
        $order->vendorID($vendorID);
        $order->storeID($storeID);
        $order->creationDate(date('Y-m-d H:i:s'));
        $order->userID($uid);
        $orderID = $order->save();

        $vendP = $this->connection->prepare("SELECT * FROM vendorItems WHERE vendorID=? AND upc=?");
        $model = new PurchaseOrderItemsModel($this->connection);
        $hands = new OrderGuideOnHandsModel($this->connection);
        $model->orderID($orderID);
        $hands->orderID($orderID);
        $this->connection->startTransaction();
        for ($i=0; $i<count($upcs); $i++) {
            if (!$cases[$i]) {
                continue;
            }
            $vendW = $this->connection->getRow($vendP, array($vendorID, $upcs[$i]));
            $model->sku($vendW['sku']);
            $model->quantity($cases[$i]);
            $model->unitCost($vendW['cost']);
            $model->caseSize($vendW['units']);
            $model->unitSize($vendW['size']);
            $model->brand($vendW['brand']);
            $model->description($vendW['description']);
            $model->internalUPC($upcs[$i]);
            $model->save();

            $hands->upc($upcs[$i]);
            $hands->onHand($onhand[$i]);
            $hands->save();
        }
        $this->connection->commitTransaction();

        return 'ViewPurchaseOrders.php?id=' . $orderID;
    }

    protected function post_edit_handler()
    {
        $vendor = FormLib::get('vendor');
        $store = FormLib::get('store');
        $ids = FormLib::get('guide');
        $upcs = FormLib::get('upc');
        $items = FormLib::get('item');
        $pars = FormLib::get('par');

        $upP = $this->connection->prepare("
            UPDATE OrderGuides
            SET upc=?,
                description=?,
                par=?,
                seq=?
            WHERE orderGuideID=?");
        $delP = $this->connection->prepare("DELETE FROM OrderGuides WHERE orderGuideID=?");
        $insP = $this->connection->prepare("
            INSERT INTO OrderGuides (vendorID, storeID, upc, description, par, seq)
                VALUES (?, ?, ?, ?, ?, ?)");
        $seq = 0;
        for ($i=0; $i<count($ids); $i++) {
            $orderID = $ids[$i];
            $upc = trim($upcs[$i]);
            $item = trim($items[$i]);
            $par = trim($pars[$i]);
            if ($upc == '' && $item == '') {
                if ($orderID) {
                    $this->connection->execute($delP, array($orderID));
                }
            } else {
                if ($orderID) {
                    $this->connection->execute($upP, array(
                        $upc, $item, $par, $seq, $orderID));
                } else {
                    $this->connection->execute($insP, array(
                        $vendor, $store, $upc, $item, $par, $seq));
                }
                $seq++;
            }
        }

        return 'OrderGuidePage.php';
    }

    protected function post_new_handler()
    {
        $model = new OrderGuidesModel($this->connection);
        $model->vendorID(FormLib::get('vendor'));
        $model->storeID(FormLib::get('store'));
        $exists = $model->find();
        if (count($exists) > 0) {
            return 'OrderGuidePage.php';
        }
        $upcs = FormLib::get('upc');
        $items = FormLib::get('item');
        $pars = FormLib::get('par');
        for ($i=0; $i<count($upcs); $i++) {
            if (trim($upcs[$i]) == '' || trim($items[$i]) == '') {
                continue;
            }
            $model->upc(BarcodeLib::padUPC($upcs[$i]));
            $model->description(trim($items[$i]));
            $model->par(trim($pars[$i]));
            $model->seq($i);
            $model->save();
        }

        return 'OrderGuidePage.php';
    }

    protected function get_edit_view()
    {
        list($vendorID, $storeID) = explode(',', $this->edit);
        $prep = $this->connection->prepare("SELECT * FROM OrderGuides WHERE vendorID=? AND storeID=? ORDER BY seq");
        $guide = $this->connection->getAllRows($prep, array($vendorID, $storeID));
        $rows = '';
        for ($i=0; $i<20; $i++) {
            $guideID = isset($guide[$i]) ? $guide[$i]['orderGuideID'] : '';
            $upc = isset($guide[$i]) ? $guide[$i]['upc'] : '';
            $item = isset($guide[$i]) ? $guide[$i]['description'] : '';
            $par = isset($guide[$i]) ? $guide[$i]['par'] : '';
            $rows .= <<<HTML
<tr>
    <td>
        <input type="hidden" name="guide[]" value="{$guideID}" />
        <input type="text" class="form-control upc" name="upc[]" value="{$upc}" />
    </td>
    <td><input type="text" class="form-control" name="item[]" value="{$item}" /></td>
    <td><input type="text" class="form-control" name="par[]" value="{$par}" /></td>
</tr>
HTML;
        }
        $this->addScript('../item/autocomplete.js');
        $this->addOnloadCommand("bindAutoComplete('.upc', '../ws/', 'item');");

        return <<<HTML
<form method="post" action="OrderGuidePage.php">
<table class="table table-bordered table-striped">
    <tr><th>UPC</th><th>Item</th><th>Par</th></tr>
    {$rows}
</table>
<p>
    <button type="submit" class="btn btn-default">Update Guide</button>
    <input type="hidden" name="edit" value="1" />
    <input type="hidden" name="vendor" value="{$vendorID}" />
    <input type="hidden" name="store" value="{$storeID}" />
</p>
</form>
HTML;
    }

    protected function get_new_view()
    {
        $stores = FormLib::storePicker();
        $vendor = new VendorsModel($this->connection);
        $vOpts = $vendor->toOptions();
        $rows = '';
        for ($i=0; $i<20; $i++) {
            $rows .= <<<HTML
<tr>
    <td><input type="text" class="form-control upc" name="upc[]" /></td>
    <td><input type="text" class="form-control" name="item[]" /></td>
    <td><input type="text" class="form-control" name="par[]" /></td>
</tr>
HTML;
        }
        $this->addScript('../item/autocomplete.js');
        $this->addOnloadCommand("bindAutoComplete('.upc', '../ws/', 'item');");

        return <<<HTML
<form method="post" action="OrderGuidePage.php">
<p>
<div class="row form-inline">
    <div class="col-sm-2">
        Store
        {$stores['html']}
    </div>
    <div class="col-sm-4">
        Vendor
        <select name="vendor" class="form-control">
            {$vOpts}
        </select>
    </div>
</div>
</p>
<table class="table table-bordered table-striped">
    <tr><th>UPC</th><th>Item</th><th>Par</th></tr>
    {$rows}
</table>
<p>
    <button type="submit" class="btn btn-default">Create Guide</button>
    <input type="hidden" name="new" value="1" />
</p>
</form>
HTML;
    }

    protected function get_order_view()
    {
        list($vendorID, $storeID) = explode(',', $this->order);
        $prep = $this->connection->prepare("
            SELECT o.description, o.upc,
                o.par,
                v.units
            FROM OrderGuides AS o
                LEFT JOIN vendorItems AS v ON o.upc=v.upc AND o.vendorID=v.vendorID
            WHERE o.vendorID=?
                AND o.storeID=?
            ORDER BY o.seq");
        $res = $this->connection->execute($prep, array($vendorID, $storeID));
        $historyP = $this->connection->prepare("
            SELECT o.placedDate, i.quantity
            FROM PurchaseOrderItems AS i
                INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
            WHERE i.internalUPC=?
                AND o.vendorID=?
                AND o.storeID=?
                AND o.placed=1
            ORDER BY o.placedDate DESC LIMIT 5");
            
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%s</td><td>%.2f</td><td>%d</td>
                <td><input type="number" min="0" max="999" name="onhand[]" 
                        class="form-control" pattern="[0-9]*" placeholder="0" /></td>
                <td><input type="number" min="0" max="999" name="order[]"
                        class="form-control" pattern="[0-9]*" placeholder="0" /></td>
                <input type="hidden" name="upc[]" value="%s" />
                </tr>',
                $row['description'], $row['par'], $row['units'], $row['upc']
            );
            $historyR = $this->connection->execute($historyP, array($row['upc'], $vendorID, $storeID));
            $table .= '<tr class="small"><td colspan="5" class="text-right">' . $row['description'] . ' history: ';
            while ($historyW = $this->connection->fetchRow($historyR)) {
                $table .= sprintf('%s - <strong>%s</strong>, ',
                    date('D M j', strtotime($historyW['placedDate'])), $historyW['quantity']);
            }
            $table .= '</td>';
        }

        return <<<HTML
<form method="post" action="OrderGuidePage.php">
<table class="table table-bordered table-striped">
    <tr><th>Item</th><th>Par</th><th>Case</th><th>On Hand</th><th>Order</th></tr>
    {$table}
</table>
<p>
    <button type="submit" class="btn btn-default">Create Purchase Order</button>
    <input type="hidden" name="store" value="{$storeID}" />
    <input type="hidden" name="vendor" value="{$vendorID}" />
</p>
</form>
HTML;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        $storeID = FormLib::get('store');
        if (!$storeID) {
            $storeID = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $stores['html'] = str_replace('<select', '<select onchange="location=\'OrderGuidePage.php?store=\'+this.value;"', $stores['html']);

        $prep = $this->connection->prepare('
            SELECT o.vendorID, vendorName
            FROM OrderGuides AS o
                INNER JOIN vendors AS v ON o.vendorID=v.vendorID
            WHERE o.storeID=?
            GROUP BY o.vendorID, vendorName
            ORDER BY vendorName');
        $res = $this->connection->execute($prep, array($storeID));
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%s</td>
                <td><a href="OrderGuidePage.php?edit=%d,%d" class="btn btn-default">Edit</a></td>
                <td><a href="OrderGuidePage.php?order=%d,%d" class="btn btn-default">Order</a></td></tr>',
                $row['vendorName'],
                $row['vendorID'], $storeID,
                $row['vendorID'], $storeID
            );
        }

        return <<<HTML
<p>
    <a href="OrderGuidePage.php?new=1" class="btn btn-default">Create New Guide</a>
    {$stores['html']}
</p>
<table class="table table-bordered table-striped">
    {$table}
</table>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->new = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_new_view()));
        $this->order = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_order_view()));
        $this->edit = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_edit_view()));
    }
}

FannieDispatch::conditionalExec();

