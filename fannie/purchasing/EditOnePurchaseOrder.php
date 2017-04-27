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

class EditOnePurchaseOrder extends FannieRESTfulPage 
{
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[Single-Vendor Purchase Order] creates and edits a purchase order
    for a specific vendor. When scanning, only items available from that vendor are shown.';

    protected $must_authenticate = true;
    protected $enable_linea = true;
    
    public function preprocess()
    {
        $this->__routes[] = 'get<id><search>';
        $this->__routes[] = 'get<id><sku><qty>';
        $this->__routes[] = 'get<id><sku><index>';
        $this->__routes[] = 'get<vendorID>';
        $this->__routes[] = 'post<id><sku><case><qty>';
        return parent::preprocess();
    }

    private function asciiFilter($str)
    {
        return preg_replace('/[^\x20-\x7E]/','', $str);
    }

    /**
      AJAX call: ?id=<vendor ID>&search=<search string>
      Find vendor items based on search string
      Called by: editone.js: itemSearch()
    */
    protected function get_id_search_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = array(); 
        $orderID = FormLib::get('orderID');

        // search by vendor SKU
        $skuQ = 'SELECT v.brand, v.description, v.size, v.units, v.cost, v.sku
                 FROM vendorItems AS v
                 WHERE v.sku LIKE ? AND v.vendorID=?';
        $skuP = $dbc->prepare($skuQ);
        $skuR = $dbc->execute($skuP, array('%'.$this->search.'%', $this->id));   
        while($w = $dbc->fetch_row($skuR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => $w['brand'].' - '. $this->asciiFilter($w['description']),
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'cases' => 1,
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            $this->mergeSearchResult($ret, $orderID, $dbc);
            return false;
        }

        // search by UPC
        $upcQ = 'SELECT brand, description, size, units, cost, sku
            FROM vendorItems WHERE upc = ? AND vendorID=?';
        $upcP = $dbc->prepare($upcQ);
        $upcR = $dbc->execute($upcP, array(BarcodeLib::padUPC($this->search), $this->id));
        while($w = $dbc->fetch_row($upcR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => $w['brand'].' - '. $this->asciiFilter($w['description']),
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'cases' => 1,
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            $this->mergeSearchResult($ret, $orderID, $dbc);
            return False;
        }

        echo '[]';
        return False;
    }

    /**
      Finalize a search result

      This adds an item's order history and quantity present in the
      current order. If the item is not in the current order it's
      automatically added with quantity one. Otherwise it's incremented
      by one. The logic here is oriented around using a handheld scanner
      where scanning something three times results in quantity three.
    */
    private function mergeSearchResult($ret, $orderID, $dbc)
    {
        $storeP = $dbc->prepare('SELECT storeID FROM PurchaseOrder WHERE orderID=?');
        $storeID = $dbc->getValue($storeP, array($orderID));

        $historyQ = 'SELECT placedDate, quantity
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.storeID=?
                AND i.sku=?
                AND placed=1
                AND (receivedQty > 0 OR receivedQty IS NULL)
            ORDER BY placedDate DESC';
        $historyQ = $dbc->addSelectLimit($historyQ, 3);
        $historyP = $dbc->prepare($historyQ);

        $currentP = $dbc->prepare('SELECT quantity FROM PurchaseOrderItems WHERE orderID=? AND sku=?');
        for ($i=0; $i<count($ret); $i++) {
            $sku = $ret[$i]['sku'];
            $cases = $dbc->getValue($currentP, array($orderID, $sku));
            $ret[$i]['cases'] = ($cases) ? $cases+1 : 1;
            $this->sku = $ret[$i]['sku'];
            $this->qty = $ret[$i]['cases']; 
            $this->id = $orderID;
            ob_start();
            $this->get_id_sku_qty_handler();
            $result = ob_get_clean();
            $ret[$i]['history'] = array();
            $historyR = $dbc->execute($historyP, array($storeID, $sku));
            while ($historyW = $dbc->fetchRow($historyR)) {
                $ret[$i]['history'][] = array(
                    'date' => date('m/d/y', strtotime($historyW['placedDate'])),
                    'cases' => $historyW['quantity'],
                );
            }
        }
        $json = array('items' => $ret, 'table' => $this->itemListTab($orderID));
        echo json_encode($json);
    }

    /**
      AJAX call: ?id=<order ID>&sku=<vendor SKU>&qty=<# of cases>
      Add the given SKU & qty to the order

      Called by: editone.js: saveItem()
    */
    protected function get_id_sku_qty_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $orderID = $this->id;
        $vendorP = $dbc->prepare('SELECT vendorID FROM PurchaseOrder WHERE orderID=?');
        $vendorID = $dbc->getValue($vendorP, array($orderID));

        $vitem = new VendorItemsModel($dbc);
        $vitem->vendorID($vendorID);
        $vitem->sku($this->sku);
        $vitem->load();

        $pitem = new PurchaseOrderItemsModel($dbc);
        $pitem->orderID($orderID);
        $pitem->sku($this->sku);
        $saved = false;
        if ($this->qty == 0) {
            $saved = $pitem->delete();
        } else {
            $pitem->quantity($this->qty);
            $pitem->unitCost($vitem->cost());
            $pitem->caseSize($vitem->units());
            $pitem->unitSize($vitem->size());
            $pitem->brand($vitem->brand());
            $pitem->description($vitem->description());
            $pitem->internalUPC($vitem->upc());
    
            $saved = $pitem->save();
        }

        $ret = array();
        if ($saved === false) {
            $ret['error'] = 'Error saving entry';
        } else {
            $ret['table'] = $this->itemListTab($orderID);
        }
        echo json_encode($ret);

        return false;
    }

    /**
      Called by: editone.js: markInCurrentOrder()
    */
    protected function get_id_sku_index_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = array(
            'qty' => 0,
            'index' => $this->index,
        );
        $item = new PurchaseOrderItemsModel($dbc);
        $item->orderID($this->id);
        $item->sku($this->sku);
        if ($item->load()) {
            $ret['qty'] = $item->quantity();
        }

        echo json_encode($ret);

        return false;
    }

    /**
      Called by: editone.js: updateList()
    */
    protected function post_id_sku_case_qty_handler()
    {
        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($this->id);
        $offset = FormLib::get('listOffset', 0);
        $upcs = FormLib::get('upc', array());
        $brands = FormLib::get('brand', array());
        $descriptions = FormLib::get('description', array());
        $sizes = FormLib::get('unitSize', array());
        $costs = FormLib::get('totalCost', array());
        for ($i=0; $i<count($this->sku); $i++) {
            $poi->sku($this->sku[$i]);
            if (isset($this->case[$i])) {
                $poi->caseSize($this->case[$i]);
            }
            if (isset($this->qty[$i])) {
                $poi->quantity($this->qty[$i]);
            }
            if ($i >= $offset) {
                // this is a manual entry
                $index = $i - $offset;
                if (trim($this->sku[$i]) === '') {
                    // cannot save w/o a SKU
                    continue;
                }
                $poi->internalUPC(BarcodeLib::padUPC($upcs[$index]));
                $poi->brand(trim($brands[$index]));
                $poi->description(trim($descriptions[$index]));
                $poi->unitSize(trim($sizes[$index]));
                $poi->unitSize(trim($sizes[$index]));
                $poi->unitCost($costs[$index]);
            }
            $saved = $poi->quantity() == 0 ? $poi->delete() : $poi->save();
        }

        $ret = array();
        $ret['table'] = $this->itemListTab($this->id);
        echo json_encode($ret);

        return false;
    }

    /**
      Main page. Vendor is selected. Find/create order
      based on vendorID & userID
    */
    protected function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $userID = FannieAuth::getUID($this->current_user);
        $orderID = $this->id;
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($orderID);
        $order->load();
        $vendorID = $order->vendorID();

        $q = 'SELECT vendorName, 
            sum(case when i.orderID is null then 0 else 1 END) as rows, 
            MAX(creationDate) as date,
            sum(unitCost*caseSize*quantity) as estimatedCost
            FROM PurchaseOrder as p 
            INNER JOIN vendors as v ON p.vendorID=v.vendorID
            LEFT JOIN PurchaseOrderItems as i
            ON p.orderID=i.orderID
            WHERE p.orderID=?';
        $p = $dbc->prepare($q);
        $row = $dbc->getRow($p, array($orderID)); 
        $cost = sprintf('%.2f', $row['estimatedCost']);

        $search = $this->itemSearchTab($orderID);
        $list = $this->itemListTab($orderID);

        $ret = <<<HTML
<div id="orderInfo">
    <span id="orderInfoVendor">{$row['vendorName']}</span>
    {$row['date']}
    &nbsp;&nbsp;&nbsp;&nbsp;
    # of Items: <span id="orderInfoCount">{$row['rows']}</span>
    &nbsp;&nbsp;&nbsp;&nbsp;
    Est. cost: $<span id="orderInfoCost">{$cost}</span>
</div>
<hr />
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active">
        <a href="#item-wrapper" aria-controls="item-wrapper" role="tab" data-toggle="tab">
            Item Search
        </a>
    </li> 
    <li role="presentation">
        <a href="#list-wrapper" aria-controls="list-wrapper" role="tab" data-toggle="tab">
            Item List
        </a>
    </li> 
    <li>
        <a href="PurchasingIndexPage.php">Home</a>
    </li>
    <li>
        <a href="ViewPurchaseOrders.php?id={$orderID}">View Order</a>
    </li>
</ul>
<p>
    <div class="tab-content">
        <div id="item-wrapper" role="tabpanel" class="tab-pane active">
            {$search}
        </div>
        <div id="list-wrapper" role="tabpanel" class="tab-pane">
            {$list}
        </div>
    </div>
</p>
<input type="hidden" id="vendor-id" value="{$vendorID}" />
<input type="hidden" id="order-id" value="{$orderID}" />
HTML;

        $this->addOnloadCommand("\$('#searchField').focus();\n");
        $this->addOnloadCommand("enableLinea('#searchField', function(){ itemSearch(); });\n");
        $this->addScript('js/editone.js?id=1');

        return $ret;
    }

    /**
      Search for & display single item
    */
    private function itemSearchTab($orderID)
    {
        return <<<HTML
            <div id="ItemSearch">
                <form class="form-inline" action="" onsubmit="itemSearch();return false;">
                    <div class="form-group">
                        <label class="control-label">UPC/SKU</label>
                        <input class="form-control" type="text" id="searchField" />
                    </div>
                    <div class="form-group">
                        &nbsp;&nbsp;&nbsp;
                        <button type="submit" class="btn btn-default">Search</button>
                    </div>
                </form>
            </div>
            <p>
                <div id="SearchResults"></div>
            </p>
HTML;
    }

    /**
      Display all items in the order
      as an editable table
    */
    private function itemListTab($orderID)
    {
        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($orderID);
        $poi->load();

        $order = new PurchaseOrderModel($this->connection);
        $order->orderID($orderID);
        $order->load();

        $batchP = $this->connection->prepare("
            SELECT b.batchName
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE l.upc=?
                AND m.storeID=?
                AND b.startDate <= " . $this->connection->curdate() . "
                AND b.endDate >= " . $this->connection->curdate() . "
                AND b.discounttype > 0
        ");

        $ret = '
            <table class="table table-bordered table-striped">
            <tr>
                <th>SKU</th>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Size</th>
                <th>Units/Case</th>
                <th>Cases</th>
                <th>Est. Cost</th>
            </tr>';
        $offset = 0;
        foreach ($poi->find() as $item) {
            $batch = $this->connection->getValue($batchP, array($item->internalUPC(), $order->storeID()));
            $ret .= sprintf('<tr %s>
                <td>%s<input type="hidden" name="sku[]" value="%s" /></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td><input type="text" class="form-control" name="case[]" value="%s" /></td>
                <td><input type="text" class="form-control" name="qty[]" value="%s" /></td>
                <td>%.2f</td>
                </tr>',
                $batch ? 'class="info" title="' . $batch . '"' : '',
                $item->sku(), $item->sku(),
                \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($item->internalUPC()),
                $item->brand(),
                $item->description(),
                $item->unitSize(),
                $item->caseSize(),
                $item->quantity(),
                $item->unitCost() * $item->caseSize() * $item->quantity()
            );
            $offset++;
        }
        $ret .= '</table>
            <p>
                <input type="hidden" name="listOffset" value="' . $offset . '" />
                <a href="" onclick="updateList(); return false;"
                    class="btn btn-default">Save</a>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <a href="" onclick="addManualRow(); return false;"
                    class="btn btn-default">Add Manual Entry</a>
            </p>';

        return $ret;
    }

    protected function get_vendorID_handler()
    {
        $userID = FannieAuth::getUID($this->current_user);
        $orderID = $this->getOrderID($this->vendorID, $userID);

        return filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $orderID;
    }

    /**
      Utility: find orderID from vendorID and userID
    */
    private function getOrderID($vendorID, $userID)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $orderQ = 'SELECT orderID FROM PurchaseOrder WHERE
            vendorID=? AND userID=? and placed=0
            ORDER BY creationDate DESC';
        $orderP = $dbc->prepare($orderQ);
        $orderR = $dbc->execute($orderP, array($vendorID, $userID));
        if ($dbc->num_rows($orderR) > 0){
            $row = $dbc->fetch_row($orderR);
            return $row['orderID'];
        } else {
            $insQ = 'INSERT INTO PurchaseOrder (vendorID, creationDate,
                placed, userID, storeID) VALUES (?, '.$dbc->now().', 0, ?, ?)';
            $insP = $dbc->prepare($insQ);
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
            $insR = $dbc->execute($insP, array($vendorID, $userID, $store));
            return $dbc->insertID();
        }
    }

    /**
      First page. Show vendor list.
    */
    function get_view()
    {
        global $FANNIE_OP_DB;
        $model = new VendorsModel(FannieDB::get($FANNIE_OP_DB));
        $vOpts = $model->toOptions();

        return <<<HTML
<form class="form" action="EditOnePurchaseOrder.php" method="get">
    <div class="form-group">
        <label>Select a vendor</label>
        <select name="vendorID" class="form-control">
            {$vOpts}
        </select>
    </div>
    <p>
        <button type="submit" class="btn btn-default">Go</button>
        &nbsp;&nbsp;&nbsp;
        <a class="btn btn-default" href="PurchasingIndexPage.php">Home</a>
    </p>
</form>
HTML;

    }

    public function helpContent()
    {
        return '<p>First choose a vendor. This order will only contain
            items from the chosen vendor.</p>
            <p>Next enter UPCs or SKUs. If there are multiple matching items,
            use the dropdown to specify which. Finally enter the number
            of cases to order.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $this->search = '4011';
        ob_start();
        $this->get_id_search_handler();
        $phpunit->assertInternalType('array', json_decode(ob_get_clean(), true));
        $this->sku = '4011';
        $this->qty = 1;
        ob_start();
        $this->get_id_sku_qty_handler();
        $phpunit->assertInternalType('array', json_decode(ob_get_clean(), true));
        $this->index = 1;
        ob_start();
        $this->get_id_sku_index_handler();
        $phpunit->assertInternalType('array', json_decode(ob_get_clean(), true));
    }
}

FannieDispatch::conditionalExec();

