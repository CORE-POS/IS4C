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

class EditOnePurchaseOrder extends FannieRESTfulPage {
    
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[Single-Vendor Purchase Order] creates and edits a purchase order
    for a specific vendor. When scanning, only items available from that vendor are shown.';
    public $themed = true;

    protected $must_authenticate = True;
    
    function preprocess(){
        $this->__routes[] = 'get<id><search>';
        $this->__routes[] = 'get<id><sku><qty>';
        $this->__routes[] = 'get<id><sku><index>';
        $this->__routes[] = 'get<vendorID>';
        $this->__routes[] = 'post<id><sku><case><qty>';
        return parent::preprocess();
    }

    /**
      AJAX call: ?id=<vendor ID>&search=<search string>
      Find vendor items based on search string
    */
    function get_id_search_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = array(); 

        // search by vendor SKU
        $skuQ = 'SELECT v.brand, v.description, v.size, v.units, v.cost, v.sku
                 FROM vendorItems AS v
                 WHERE v.sku LIKE ? AND v.vendorID=?';
        $skuP = $dbc->prepare_statement($skuQ);
        $skuR = $dbc->exec_statement($skuP, array('%'.$this->search.'%', $this->id));   
        while($w = $dbc->fetch_row($skuR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => $w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'cases' => 1,
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            $this->mergeSearchResult($ret);
            return false;
        }

        // search by UPC
        $upcQ = 'SELECT brand, description, size, units, cost, sku
            FROM vendorItems WHERE upc = ? AND vendorID=?';
        $upcP = $dbc->prepare_statement($upcQ);
        $upcR = $dbc->exec_statement($upcP, array(BarcodeLib::padUPC($this->search), $this->id));
        while($w = $dbc->fetch_row($upcR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => $w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'cases' => 1,
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            $this->mergeSearchResult($ret);
            return False;
        }

        // search by internalSKU / order code
        $iskuQ = 'SELECT brand, description, size, units, cost, sku
            FROM internalSKUs as i
            INNER JOIN vendorItems as v
            ON i.vendor_sku = v.sku AND i.vendorID=v.vendorID
            WHERE our_sku = ? AND i.vendorID=?';
        $iskuP = $dbc->prepare_statement($iskuQ);
        $iskuR = $dbc->exec_statement($iskuP, array($this->search, $this->id));
        while($w = $dbc->fetch_row($iskuR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => $w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'cases' => 1,
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            $this->mergeSearchResult($ret);
            return False;
        }

        echo '[]';
        return False;
    }

    private function mergeSearchResult($ret)
    {
        echo json_encode($ret);
    }

    /**
      AJAX call: ?id=<order ID>&sku=<vendor SKU>&qty=<# of cases>
      Add the given SKU & qty to the order
    */
    function get_id_sku_qty_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $orderID = $this->id;
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($orderID);
        $order->load();

        $vitem = new VendorItemsModel($dbc);
        $vitem->vendorID($order->vendorID());
        $vitem->sku($this->sku);
        $vitem->load();

        $pitem = new PurchaseOrderItemsModel($dbc);
        $pitem->orderID($orderID);
        $pitem->sku($this->sku);
        if ($this->qty == 0) {
            $pitem->delete();
        } else {
            $pitem->quantity($this->qty);
            $pitem->unitCost($vitem->cost());
            $pitem->caseSize($vitem->units());
            $pitem->unitSize($vitem->size());
            $pitem->brand($vitem->brand());
            $pitem->description($vitem->description());
            $pitem->internalUPC($vitem->upc());
    
            $pitem->save();
        }

        $ret = array();
        $pitem->reset();
        $pitem->orderID($orderID);
        $pitem->sku($this->sku);
        if (count($pitem->find()) == 0 && $this->qty != 0) {
            $ret['error'] = 'Error saving entry';
        } else {
            $q = 'SELECT count(*) as rows,
                SUM(unitCost*caseSize*quantity) as estimatedCost
                FROM PurchaseOrderItems WHERE orderID=?';
            $p = $dbc->prepare_statement($q);
            $r = $dbc->exec_statement($p, array($orderID));
            $w = $dbc->fetch_row($r);
            $ret['count'] = $w['rows'];
            $ret['cost'] = sprintf('%.2f',$w['estimatedCost']);
        }
        echo json_encode($ret);
        return False;
    }

    function get_id_sku_index_handler()
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

    protected function post_id_sku_case_qty_handler()
    {
        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($this->id);
        for ($i=0; $i<count($this->sku); $i++) {
            $poi->sku($this->sku[$i]);
            if (isset($this->case[$i])) {
                $poi->caseSize($this->case[$i]);
            }
            if (isset($this->qty[$i])) {
                $poi->quantity($this->qty[$i]);
            }
            $poi->save();
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
    function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendorID = $this->id;
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
        $p = $dbc->prepare_statement($q);
        $r = $dbc->exec_statement($p, array($orderID)); 
        $w = $dbc->fetch_row($r);

        $ret = '<div id="orderInfo">
            <span id="orderInfoVendor">'.$w['vendorName'].'</span>';
        $ret .= ' '.$w['date'];
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= ' # of Items: <span id="orderInfoCount">'.$w['rows'].'</span>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= ' Est. cost: $<span id="orderInfoCost">'.sprintf('%.2f',$w['estimatedCost']).'</span>';
        $ret .= '</div><hr />';

        $ret .= '<ul class="nav nav-tabs" role="tablist">
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
                <a href="ViewPurchaseOrders.php?id=' . $orderID . '">View Order</a>
            </li>
        </ul>
        <p>
        <div class="tab-content">';

        $ret .= '<div id="item-wrapper" role="tabpanel" class="tab-pane active">';
        $ret .= $this->itemSearchTab($orderID);
        $ret .= '</div>';
        $ret .= '<div id="list-wrapper" role="tabpanel" class="tab-pane">';
        $ret .= $this->itemListTab($orderID);
        $ret .= '</div>';
        $ret .= '</div></p>';

        $ret .= sprintf('<input type="hidden" id="vendor-id" value="%d" />',$vendorID);
        $ret .= sprintf('<input type="hidden" id="order-id" value="%d" />',$orderID);

        $this->add_onload_command("\$('#searchField').focus();\n");
        $this->add_script('js/editone.js');

        return $ret;
    }

    private function itemSearchTab($orderID)
    {
        return '
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
            </p>';
    }

    private function itemListTab($orderID)
    {
        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($orderID);
        $poi->load();

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
        foreach ($poi->find() as $item) {
            $ret .= sprintf('<tr>
                <td>%s<input type="hidden" name="sku[]" value="%s" /></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td><input type="text" class="form-control" name="case[]" value="%s" /></td>
                <td><input type="text" class="form-control" name="qty[]" value="%s" /></td>
                <td>%.2f</td>
                </tr>',
                $item->sku(), $item->sku(),
                \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($item->internalUPC()),
                $item->brand(),
                $item->description(),
                $item->unitSize(),
                $item->caseSize(),
                $item->quantity(),
                $item->unitCost() * $item->caseSize() * $item->quantity()
            );
        }
        $ret .= '</table>
            <p>
                <a href="" onclick="updateList(); return false;"
                    class="btn btn-default">Save</a>
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
        $orderP = $dbc->prepare_statement($orderQ);
        $orderR = $dbc->exec_statement($orderP, array($vendorID, $userID));
        if ($dbc->num_rows($orderR) > 0){
            $row = $dbc->fetch_row($orderR);
            return $row['orderID'];
        } else {
            $insQ = 'INSERT INTO PurchaseOrder (vendorID, creationDate,
                placed, userID) VALUES (?, '.$dbc->now().', 0, ?)';
            $insP = $dbc->prepare_statement($insQ);
            $insR = $dbc->exec_statement($insP, array($vendorID, $userID));
            return $dbc->insert_id();
        }
    }

    /**
      First page. Show vendor list.
    */
    function get_view()
    {
        global $FANNIE_OP_DB;
        $model = new VendorsModel(FannieDB::get($FANNIE_OP_DB));
        $ret = '';
        $ret .= '<form class="form" action="EditOnePurchaseOrder.php" method="get">';
        $ret .= '<div class="form-group">';
        $ret .= '<label>Select a vendor</label>';
        $ret .= '<select name="id" class="form-control">';
        foreach($model->find('vendorName') as $vendor){
            $ret .= sprintf('<option value="%d">%s</option>',
                $vendor->vendorID(), $vendor->vendorName());
        }
        $ret .= '</select>';
        $ret .= '</div>';
        $ret .= '<p><button type="submit" class="btn btn-default">Go</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="btn btn-default"
            onclick="location=\'PurchasingIndexPage.php\'; return false;">Home</button></p>';
        $ret .= '</form>';
        return $ret;
    }

    public function helpContent()
    {
        return '<p>First choose a vendor. This order will only contain
            items from the chosen vendor.</p>
            <p>Next enter UPCs or SKUs. If there are multiple matching items,
            use the dropdown to specify which. Finally enter the number
            of cases to order.</p>';
    }
}

FannieDispatch::conditionalExec();

