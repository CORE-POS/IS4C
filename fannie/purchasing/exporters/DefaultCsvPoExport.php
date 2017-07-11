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

class DefaultCsvPoExport 
{
    public $nice_name = 'CSV (Default)';
    public $extension = 'csv';
    public $mime_type = 'test/csv';

    public function send_headers()
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=order_export.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    public function export_order($id)
    {
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

        echo '"'.$vendor->vendorName().'",Order Date,'.date('Y-m-d')."\r\n";
        echo "\r\n";
        echo "SKU,\"Order Qty (Cases)\",\"Case Size\",\"Total Units\",\"Unit Size\",Description\r\n";
        foreach ($items->find() as $obj) {
            echo $obj->sku().',';
            echo $obj->quantity().',';
            echo '"'.$obj->caseSize().'",';
            echo '"'.(is_numeric($obj->caseSize()) ? $obj->caseSize()*$obj->quantity() : $obj->quantity()).'",';
            echo '"'.$obj->unitSize().'",';
            echo '"'.$obj->description().'",';
            echo "\r\n";
        }
    }
}

