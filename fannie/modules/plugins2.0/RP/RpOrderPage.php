<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpOrderCategoriesModel')) {
    include(__DIR__ . '/RpOrderCategoriesModel.php');
}
if (!class_exists('RpOrderItemsModel')) {
    include(__DIR__ . '/RpOrderItemsModel.php');
}

class RpOrderPage extends FannieRESTfulPage
{
    protected $header = 'RP Order Guide';
    protected $title = 'RP Order Guide';

    public function preprocess()
    {
        $this->addRoute('get<searchVendor>', 'get<searchLC>');

        return parent::preprocess();
    }
    
    protected function get_searchLC_handler()
    {
        $prep = $this->connection->prepare("SELECT * FROM likeCodes WHERE likeCodeDesc LIKE ?");
        $res = $this->connection->getAllRows($prep, array('%' . $this->searchLC . '%'));
        $ret = array();
        foreach ($res as $row) {
            $ret[] = array(
                'label' => $row['likeCodeDesc'],
                'value' => $row['likeCode'],
            );
        }

        echo json_encode($ret);

        return false;
    }

    protected function get_searchVendor_handler()
    {
        $query = "SELECT * FROM vendorItems WHERE (description LIKE ? OR brand LIKE ?)";
        $args = array('%' . $this->searchVendor . '%', '%' . $this->searchVendor . '%');
        if (FormLib::get('vendorID')) {
            $args[] = FormLib::get('vendorID');
            $query .= ' AND vendorID=?';
        } else {
            $query .= ' AND vendorID IN (292, 293, 136)';
        }

        $ret = array();
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $label = $row['description'] . ' ' . $row['units'] . '/' . $row['size'] . ' ($' . $row['cost'] . ')';
            $value = array(
                'vendorID' => $row['vendorID'],
                'caseSize' => $row['units'],
                'sku' => $row['sku'],
                'upc' => $row['upc'],
                'item' => $row['description'],
            );
            $value = json_encode($value);
            $ret[] = array(
                'label' => $label,
                'value' => $value,
            );
        }

        echo json_encode($ret);

        return false;
    }

    protected function delete_id_handler()
    {
        // forcing sequential requests here
        $_SESSION['appendingOrder'] = true;

        list($upc, $store, $vendor) = explode(',', $this->id);
        $findP = $this->connection->prepare("SELECT orderID
            FROM PurchaseOrder WHERE placed=0 AND storeID=? AND vendorID=? AND userID=-99");
        $orderID = $this->connection->getValue($findP, array($store, $vendor));

        $delP = $this->connection->prepare("DELETE FROM PurchaseOrderItems
            WHERE orderID=? AND internalUPC=?");
        $this->connection->execute($delP, array($orderID, $upc));

        echo json_encode(array('unlink' => false));

        return false;
    }

    protected function post_id_handler()
    {
        // forcing sequential requests here
        $_SESSION['appendingOrder'] = true;

        list($upc, $store, $vendor) = explode(',', $this->id);
        $findP = $this->connection->prepare("SELECT orderID
            FROM PurchaseOrder WHERE placed=0 AND storeID=? AND vendorID=? AND userID=-99");
        $orderID = $this->connection->getValue($findP, array($store, $vendor));
        if (!$orderID) {
            $model = new PurchaseOrderModel($this->connection);
            $model->storeID($store);
            $model->vendorID($vendor);
            $model->creationDate(date('Y-m-d H:i:s'));
            $model->userID(-99);
            $orderID = $model->save();
        }
    
        $itemP = $this->connection->prepare("SELECT * FROM RpOrderItems WHERE upc=? AND storeID=?");
        $item = $this->connection->getRow($itemP, array($upc, $store));
        if (substr($upc, 0, 2) == "LC") {
            $prodP = $this->connection->prepare("SELECT p.brand, p.size, p.cost FROM upcLike AS u
                    INNER JOIN products AS p ON u.upc=p.upc WHERE u.likeCode=?");
            $prod = $this->connection->getRow($prodP, array(substr($upc, 2)));
        } else {
            $prodP = $this->connection->prepare("SELECT brand, size, cost FROM products WHERE upc=?");
            $prod = $this->connection->getRow($prodP, array($upc));
        }

        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($orderID);
        $poi->sku($vendor == $item['backupID'] ? $item['backupSKU'] : $item['vendorSKU']);
        $poi->quantity(FormLib::get('qty'));
        $poi->unitCost($prod['cost']);
        $poi->caseSize($item['caseSize']);
        $poi->unitSize($prod['size']);
        $poi->brand($prod['brand']);
        $poi->description($vendor == $item['backupID'] ? $item['backupItem'] : $item['vendorItem']);
        $poi->internalUPC($upc);
        $poi->save();

        $vendP = $this->connection->prepare("SELECT vendorName FROM vendors WHERE vendorID=?");
        $vend = $this->connection->getValue($vendP, array($vendor));

        echo json_encode(array('orderID' => $orderID, 'name'=>$vend));

        $_SESSION['appendingOrder'] = false;

        return false;
    }

    protected function post_view()
    {
        $model = new RpOrderItemsModel($this->connection);
        $model->storeID(FormLib::get('store'));
        $model->categoryID(FormLib::get('catID'));
        $model->addedBy(1);
        $model->caseSize(FormLib::get('newCase'));
        $model->vendorID(FormLib::get('newVendor'));
        $model->vendorSKU(FormLib::get('newSKU'));
        $model->vendorItem(FormLib::get('newItem'));

        $lc = FormLib::get('newLC');
        $upc = BarcodeLib::padUPC(FormLib::get('newUPC'));
        if ($lc) {
            $model->upc('LC' . $lc);
        } elseif ($upc != '0000000000000') {
            $model->upc($upc);
        } elseif (FormLib::get('newSKU')) {
            $model->upc(FormLib::get('newSKU'));
        } else {
            $model->upc(uniqid());
        }

        $saved = $model->save();
        $ret = '';
        if ($saved) {
            $ret .= '<div class="alert alert-success">Added item</div>';
        } else {
            $ret .= '<div class="alert alert-danger">Error adding item</div>';
        }

        return $ret . $this->get_view();
    }

    protected function get_view()
    {
        $this->addScript('rpOrder.js?date=20190319');
        $this->addOnloadCommand('rpOrder.initAutoCompletes();');
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $sSelect = FormLib::storePicker();
        $sSelect['html'] = str_replace('<select', '<select onchange="location=\'RpOrderPage.php?store=\' + this.value;"', $sSelect['html']);

        $ordersP = $this->connection->prepare("
            SELECT o.orderID, v.vendorName
            FROM PurchaseOrder AS o
                LEFT JOIN vendors AS v ON o.vendorID=v.vendorID
            WHERE o.storeID=?
                AND o.placed=0
                AND o.userID=-99");
        $orderLinks = '';
        $orderIDs = array();
        $orders = $this->connection->getAllRows($ordersP, array($store));
        foreach ($orders as $o) {
            $orderIDs[] = $o['orderID'];
            $orderLinks .= sprintf('<li id="link%d"><a href="../../../purchasing/ViewPurchaseOrders.php?id=%d">%s</a></li>',
                $o['orderID'], $o['orderID'], $o['vendorName']);
        }
        list($ioStr, $ioArgs) = $this->connection->safeInClause($orderIDs);
        $inOrderP = $this->connection->prepare("SELECT vendorID, quantity FROM PurchaseOrder AS o
            INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.orderID IN ({$ioStr}) AND i.internalUPC=?");

        $parP = $this->connection->prepare("
            SELECT movement
            FROM " . FannieDB::fqn('Smoothed', 'plugin:WarehouseDatabase') . "
            WHERE storeID=?
                AND upc=?"); 

        $priceP = $this->connection->prepare("
            SELECT CASE WHEN p.special_price > 0 THEN p.special_price ELSE p.normal_price END AS price
            FROM upcLike AS u
                INNER JOIN products AS p ON p.upc=u.upc
            WHERE u.likeCode=?");

        $prep = $this->connection->prepare("
            SELECT r.upc,
                r.categoryID,
                c.name,
                v.vendorName AS vendorName,
                b.vendorName AS backupVendor,
                r.vendorSKU,
                r.vendorItem,
                r.backupSKU,
                r.backupItem,
                r.caseSize,
                r.vendorID,
                r.backupID
            FROM RpOrderItems AS r
                LEFT JOIN RpOrderCategories AS c ON r.categoryID=c.rpOrderCategoryID
                LEFT JOIN vendors AS v ON r.vendorID=v.vendorID
                LEFT JOIN vendors AS b ON r.backupID=b.vendorID
            WHERE r.storeID=?
            ORDER BY c.name, r.vendorItem");
        $res = $this->connection->execute($prep, array($store));
        $tables = '';
        $category = false;
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['categoryID'] !== $category) {
                if ($category !== false) {
                    $tables .= '</table>';
                }
                $catName = $row['categoryID'] ? $row['name'] : 'Uncategorized';
                $tables .= '<h3>' . $catName . '</h3>';
                $category = $row['categoryID'];
                $tables .= '<table class="table table-bordered table-striped small">
                    <tr><th>LC</th><th>Primary</th><th>Secondary</th><th>Item</th><th>Case Size</th>
                    <th>On Hand</th><th>Par</th><th>Order</th></tr>';
            }
            $par = $this->connection->getValue($parP, array($store, $row['upc']));
            $price = $this->connection->getValue($priceP, array(substr($row['upc'], 2)));
            $upc = $row['upc'];
            if (substr($row['upc'], 0, 2) == 'LC') {
                $row['upc'] = sprintf('<a href="../../../item/likecodes/LikeCodeEditor.php?start=%d">%s</a>',
                    substr($row['upc'], 2), $row['upc']);
            }
            $orderAmt = 0;
            $start = $par;
            while ($start > (0.25 * $row['caseSize'])) {
                $orderAmt++;
                $start -= $row['caseSize'];
            }
            $inOrder = $this->connection->getRow($inOrderP, array_merge($ioArgs, array($upc)));
            if ($inOrder) {
                $orderAmt = $inOrder['quantity'];
            }
            $row['vendorName'] = str_replace(' (Produce)', '', $row['vendorName']);
            $row['backupVendor'] = str_replace(' (Produce)', '', $row['backupVendor']);
            $tables .= sprintf('<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s %s</td>
                <td class="caseSize">%s</td>
                <td><input type="number" class="form-control input-sm onHand" value="0" 
                    style="width: 5em;"
                    onchange="rpOrder.reCalcRow($(this).closest(\'tr\'));"
                    onfocus="this.select();" onkeyup="rpOrder.keybind(event);" /></td>
                <input type="hidden" class="price" value="%.2f" />
                <input type="hidden" class="basePar" value="%.2f" />
                <td class="parCell">%.2f</td>
                <td class="form-inline %s"><input type="number" style="width: 5em;" class="form-control input-sm orderAmt" value="%d" />
                    <button class="btn btn-success btn-sm" onclick="rpOrder.inc(this, 1);">+</button>
                    <button class="btn btn-danger btn-sm" onclick="rpOrder.inc(this, -1);">-</button>
                    <label><input type="checkbox" onchange="rpOrder.placeOrder(this);" value="%s,%d,%d" %s /> Pri</label>
                    <label><input type="checkbox" onchange="rpOrder.placeOrder(this);" value="%s,%d,%d" %s %s /> Sec</label>
                </td>
                </tr>',
                $row['upc'],
                $row['vendorName'],
                $row['backupVendor'],
                ($row['vendorSKU'] ? '(' . $row['vendorSKU'] . ')' : ''),
                $row['vendorItem'],
                $row['caseSize'],
                $price,
                $par,
                $par,
                ($inOrder ? 'info' : ''),
                $orderAmt,
                $upc, $store, $row['vendorID'],
                ($inOrder['vendorID'] == $row['vendorID'] ? 'checked' : ''),
                $upc, $store, $row['backupID'],
                ($inOrder['vendorID'] == $row['backupID'] ? 'checked' : ''),
                ($row['backupID'] ? '' : 'disabled')
            );
        }
        $tables .= '</table>';

        $ts = time();
        while (date('N', $ts) != 1) {
            $ts = mktime(0, 0, 0, date('n', $ts), date('j',$ts) - 1, date('Y', $ts));
        }
        $weekStart = date('Y-m-d', $ts);
        $weekP = $this->connection->prepare("SELECT * FROM RpSegments WHERE startDate=? AND storeID=?");
        $projected = 'n/a';
        $days = array(
            'Mon' => 'n/a',
            'Tue' => 'n/a',
            'Wed' => 'n/a',
            'Thu' => 'n/a',
            'Fri' => 'n/a',
            'Sat' => 'n/a',
            'Sun' => 'n/a',
        );
        $week = $this->connection->getRow($weekP, array($weekStart, $store));
        if ($week) {
            $projected = number_format($week['sales']);
            $days = json_decode($week['segmentation'], true);
            $days = array_map(function ($i) { return sprintf('%.2f%%', $i*100); }, $days);
        }

        $cats = new RpOrderCategoriesModel($this->connection);
        $catOpts = $cats->toOptions();

        return <<<HTML
<div class="row">
<div class="col-sm-6">
<p class="form-inline">
    <label>Store</label>: {$sSelect['html']}
    &nbsp;&nbsp;&nbsp;&nbsp;
    <label>Projected Sales this Week</label>:
    <a href="RpSegmentation.php" id="projSales">{$projected}</a>
    <fieldset>
        <label title="{$days['Mon']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Mon']}" /> Monday</label>
        <label title="{$days['Tue']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Tue']}" /> Tuesday</label>
        <label title="{$days['Wed']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Wed']}" /> Wednesday</label>
        <label title="{$days['Thu']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Thu']}" /> Thursday</label>
        <label title="{$days['Fri']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Fri']}" /> Friday</label>
        <label title="{$days['Sat']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Sat']}" /> Saturday</label>
        <label title="{$days['Sun']}"><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" value="{$days['Sun']}" /> Sunday</label>
    </fieldset>
    <label>Projected Sales these Days</label>:
    <span id="selectedSales">0</span>
    <br />
    <label>Retail x Expected Movement</label>:
    <span id="guessRetail">0</span>
    <br />
    <label>Adjustment</label>:
    <span id="adjDiff">0</span>
    <ul id="openOrders">{$orderLinks}</ul>
</p>
</div>
<div class="col-sm-6">
<div class="panel panel-default">
<div class="panel-heading">Add Item</div>
<div class="panel-body">
<form method="post">
    <input type="hidden" name="store" value="{$store}" />
    <div class="form-group input-group">
        <span class="input-group-addon">Vendor</span>
        <select name="vendor" class="form-control input-sm" required
            id="newVendor" onchange="rpOrder.setSearchVendor(this.value);">
            <option value=""></option>
            <option value="292">Alberts</option>
            <option value="293">CPW</option>
            <option value="136">RDW</option>
            <option value="1">UNFI</option>
            <option value="-2">Direct</option>
        </select>
    </div>
    <div class="form-group input-group">
        <span class="input-group-addon">Item</span>
        <input type="text" class="form-control input-sm" name="item" required id="newItem" />
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">Likecode</span>
            <input type="text" class="form-control input-sm" name="lc" id="newLC" />
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">or UPC</span>
            <input type="text" class="form-control input-sm" name="upc" id="newUPC" />
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">SKU</span>
            <input type="text" class="form-control input-sm" name="sku" id="newSKU" />
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">Case Size</span>
            <input type="text" class="form-control input-sm" name="caseSize" required id="newCase" />
        </div>
    </div>
    <div class="form-group input-group">
        <span class="input-group-addon">Category</span>
        <select name="catID" class="form-control input-sm" required>{$catOpts}</select>
    </div>
    <button type="submit" class="btn btn-default">Add Item</button>
    <button type="reset" class="btn btn-default btn-reset">Clear</button>
</form>
</div>
</div>
</div>
</div>
{$tables}
HTML;
    }
}

FannieDispatch::conditionalExec();

