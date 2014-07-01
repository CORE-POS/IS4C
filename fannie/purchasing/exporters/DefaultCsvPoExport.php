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

class DefaultCsvPoExport {

    public $nice_name = 'CSV (Default)';

    function send_headers(){
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=order_export.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    function export_order($id){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($id);
        $order->load();

        $items = new PurchaseOrderItemsModel($dbc);
        $items->orderID($id);

        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($order->vendorID());
        $vendor->load();

        echo 'Vendor,"'.$vendor->vendorName().'",Order Date,'.date('Y-m-d')."\r\n";
        echo "\r\n";
        echo "SKU,\"Order Qty\",Brand,Description,\"Case Size\",\"Est. Cost\"\r\n";
        foreach($items->find() as $obj){
            echo $obj->sku().',';
            echo $obj->quantity().',';
            echo '"'.$obj->brand().'",';
            echo '"'.$obj->description().'",';
            echo '"'.$obj->caseSize().'",';
            printf('%.2f', $obj->unitCost()*$obj->caseSize()*$obj->quantity());
            echo "\r\n";
        }
    }
}

?>
