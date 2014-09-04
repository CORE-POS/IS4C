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

class EditManyPurchaseOrders extends FannieRESTfulPage {

    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[Multi-Vendor Purchase Order] creates and edits multiple purchase orders
    as items from different vendors are scanned.';

    protected $must_authenticate = True;

    function preprocess(){
        $this->__routes[] = 'get<search>';
        $this->__routes[] = 'get<id><sku><qty>';
        return parent::preprocess();
    }

    function get_search_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = array(); 

        // search by vendor SKU
        $skuQ = 'SELECT brand, description, size, units, cost, sku,
            i.vendorID, vendorName
            FROM vendorItems AS i LEFT JOIN vendors AS v ON
            i.vendorID=v.vendorID WHERE sku LIKE ?';
        $skuP = $dbc->prepare_statement($skuQ);
        $skuR = $dbc->exec_statement($skuP, array('%'.$this->search.'%'));
        while($w = $dbc->fetch_row($skuR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => '['.$w['vendorName'].'] '.$w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'vendorID' => $w['vendorID']
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            echo json_encode($ret);
            return False;
        }

        // search by UPC
        $upcQ = 'SELECT brand, description, size, units, cost, sku,
            i.vendorID, vendorName
            FROM vendorItems AS i LEFT JOIN vendors AS v ON
            i.vendorID = v.vendorID WHERE upc=?';
        $upcP = $dbc->prepare_statement($upcQ);
        $upcR = $dbc->exec_statement($upcP, array(BarcodeLib::padUPC($this->search)));
        while($w = $dbc->fetch_row($upcR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => '['.$w['vendorName'].'] '.$w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'vendorID' => $w['vendorID']
            );
            $ret[] = $result;
        }
        if (count($ret) > 0){
            echo json_encode($ret);
            return False;
        }

        // search by internalSKU / order code
        $iskuQ = 'SELECT brand, description, size, units, cost, sku,
            v.vendorID, vendorName
            FROM internalSKUs as i
            INNER JOIN vendorItems as v
            ON i.vendor_sku = v.sku AND i.vendorID=v.vendorID
            LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
            WHERE our_sku = ? ';
        $iskuP = $dbc->prepare_statement($iskuQ);
        $iskuR = $dbc->exec_statement($iskuP, array($this->search));
        while($w = $dbc->fetch_row($iskuR)){
            $result = array(
            'sku' => $w['sku'],
            'title' => '['.$w['vendorName'].'] '.$w['brand'].' - '.$w['description'],
            'unitSize' => $w['size'],   
            'caseSize' => $w['units'],
            'unitCost' => sprintf('%.2f',$w['cost']),
            'caseCost' => sprintf('%.2f',$w['cost']*$w['units']),
            'vendorID' => $w['vendorID']
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
        $pitem->quantity($this->qty);
        $pitem->unitCost($vitem->cost());
        $pitem->caseSize($vitem->units());
        $pitem->unitSize($vitem->size());
        $pitem->brand($vitem->brand());
        $pitem->description($vitem->description());
        $pitem->internalUPC($vitem->upc());
    
        $pitem->save();

        $ret = array();
        $pitem->reset();
        $pitem->orderID($orderID);
        $pitem->sku($this->sku);
        if (count($pitem->find()) == 0){
            $ret['error'] = 'Error saving entry';
        }
        else {
            $ret['sidebar'] = $this->calculate_sidebar();
        }
        echo json_encode($ret);
        return False;
    }

    function calculate_sidebar(){
        global $FANNIE_OP_DB;
        $userID = FannieAuth::getUID($this->current_user);

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = 'SELECT p.orderID, vendorName, 
            sum(case when i.orderID is null then 0 else 1 END) as rows, 
            MAX(creationDate) as date,
            sum(unitCost*caseSize*quantity) as estimatedCost
            FROM PurchaseOrder as p 
            INNER JOIN vendors as v ON p.vendorID=v.vendorID
            LEFT JOIN PurchaseOrderItems as i
            ON p.orderID=i.orderID
            WHERE p.userID=?
            GROUP BY p.orderID, vendorName
            ORDER BY vendorName';
        $p = $dbc->prepare_statement($q);
        $r = $dbc->exec_statement($p, array($userID));  

        $ret = '<ul id="vendorList">';
        while($w = $dbc->fetch_row($r)){
            $ret .= '<li><span id="orderInfoVendor">'.$w['vendorName'].'</span>';
            $ret .= '<ul class="vendorSubList"><li>'.$w['date'];
            $ret .= '<li># of Items: <span class="orderInfoCount">'.$w['rows'].'</span>';
            $ret .= '<li>Est. cost: $<span class="orderInfoCost">'.sprintf('%.2f',$w['estimatedCost']).'</span>';
            $ret .= '</ul></li>';
        }
        $ret .= '</ul>';

        return $ret;
    }

    function get_view(){
        $ret = '<div id="col2">';
        $ret .= '<div id="ItemSearch">';
        $ret .= '<form action="" onsubmit="itemSearch();return false;">';
        $ret .= '<b>UPC/SKU</b>: <input type="text" id="searchField" />';
        $ret .= '<input type="submit" value="Search" />';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<div id="SearchResults"></div>';
        $ret .= '</div>';

        $ret .= '<div id="orderInfo">';
        $ret .= $this->calculate_sidebar();
        $ret .= '</div>';

        $ret .= '<div style="clear: left;"></div>';

        $this->add_onload_command("\$('#searchField').focus();\n");
        $this->add_script('js/editmany.js');
    
        return $ret;
    }

    function css_content(){
        ob_start();
        ?>
div#orderInfo {
    border-left: solid 1px black;
    float: left;
    margin-left: 10px;
}
div#col2 {
    float: left;
    text-align: left;
}
ul.vendorSubList {
    line-height: 1em;
    padding-left: 5px;
    font-weight: normal;
    font-size: 90%;
}
ul.vendorSubList li {
    margin-left: 5px;
}
ul#vendorList {
    line-height: 1.1em;
    font-weight: bold;
}
        <?php
        return ob_get_clean();
    }

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
}

FannieDispatch::conditionalExec();

?>
