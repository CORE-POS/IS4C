<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    protected $must_authenticate = True;
    
    function preprocess(){
        $this->__routes[] = 'get<id><search>';
        $this->__routes[] = 'get<id><sku><qty>';
        $this->__routes[] = 'get<id><sku><index>';
        return parent::preprocess();
    }

    /**
      AJAX call: ?id=<vendor ID>&search=<search string>
      Find vendor items based on search string
    */
    function get_id_search_handler(){
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
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units'])
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            echo json_encode($ret);
            return False;
        }

        // search by UPC
        $upcQ = 'SELECT brand, description, size, units, cost, sku
            FROM vendorItems WHERE upc = ? AND vendorID=?';
        $upcP = $dbc->prepare_statement($upcQ);
        $upcR = $dbc->exec_statement($upcP, array(BarcodeLib::padUPC($this->search)));
        while($w = $dbc->fetch_row($upcR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => $w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units'])
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            echo json_encode($ret);
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
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units'])
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            echo json_encode($ret);
            return False;
        }

        echo '[]';
        return False;
    }

    /**
      AJAX call: ?id=<vendor ID>&sku=<vendor SKU>&qty=<# of cases>
      Add the given SKU & qty to the order
    */
    function get_id_sku_qty_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $orderID = $this->getOrderID($this->id, FannieAuth::getUID($this->current_user));

        $vitem = new VendorItemsModel($dbc);
        $vitem->vendorID($this->id);
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
        $orderID = $this->getOrderID($this->id, FannieAuth::getUID($this->current_user));

        $ret = array(
            'qty' => 0,
            'index' => $this->index,
        );
        $item = new PurchaseOrderItemsModel($dbc);
        $item->orderID($orderID);
        $item->sku($this->sku);
        if ($item->load()) {
            $ret['qty'] = $item->quantity();
        }

        echo json_encode($ret);

        return false;
    }

    /**
      Main page. Vendor is selected. Find/create order
      based on vendorID & userID
    */
    function get_id_view(){
        global $FANNIE_OP_DB;
        $vendorID = $this->id;
        $userID = FannieAuth::getUID($this->current_user);
        $orderID = $this->getOrderID($vendorID, $userID);

        $dbc = FannieDB::get($FANNIE_OP_DB);
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

        $ret .= '<div id="ItemSearch">';
        $ret .= '<form action="" onsubmit="itemSearch();return false;">';
        $ret .= '<b>UPC/SKU</b>: <input type="text" id="searchField" />';
        $ret .= '<input type="submit" value="Search" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button onclick="location=\'PurchasingIndexPage.php\'; return false;">Home</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button onclick="location=\'ViewPurchaseOrders.php?id=' . $orderID . '\'; return false;">View Order</button>';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<div id="SearchResults"></div>';

        $ret .= sprintf('<input type="hidden" id="id" value="%d" />',$this->id);

        $this->add_onload_command("\$('#searchField').focus();\n");
        $this->add_script('js/editone.js');

        return $ret;
    }

    /**
      Utility: find orderID from vendorID and userID
    */
    private function getOrderID($vendorID, $userID){
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
        }
        else {
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
    function get_view(){
        global $FANNIE_OP_DB;
        $model = new VendorsModel(FannieDB::get($FANNIE_OP_DB));
        $ret = 'Select a vendor';
        $ret .= '<form action="EditOnePurchaseOrder.php" method="get">';
        $ret .= '<select name="id">';
        foreach($model->find('vendorName') as $vendor){
            $ret .= sprintf('<option value="%d">%s</option>',
                $vendor->vendorID(), $vendor->vendorName());
        }
        $ret .= '</select>';
        $ret .= ' <input type="submit" value="Go" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= ' <button onclick="location=\'PurchasingIndexPage.php\'; return false;">Home</button>';
        $ret .= '</form>';
        return $ret;
    }
}

FannieDispatch::conditionalExec();

?>
