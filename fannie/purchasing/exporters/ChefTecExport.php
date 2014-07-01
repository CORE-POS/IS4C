<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class ChefTecExport 
{

    public $nice_name = 'ChefTec (CSV)';

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

        $columns = array(
            'Product Code',
            'Inventory Item',
            'Invoice Number',
            'Date',
            'Unit',
            'Quantity',
            'Cost',
            'Description',
            'Alt. Unit Indicator',
            'Alternate Unit',
        );

        foreach ($items->find() as $obj) {
            $units = 1.0;
            $unit_of_measure = $obj->unitSize();
            if (strstr($obj->unitSize(), ' ')) {
                list($units, $unit_of_measure) = explode(' ', $obj->unitSize(), 2);
            }
            if ($unit_of_measure == '#') {
                $unit_of_measure = 'lb';
            } else if ($unit_of_measure == 'FZ') {
                $unit_of_measure = 'fl oz';
            }
            if (strstr($units, '/')) { // 6/12 oz on six pack of soda
                list($a, $b) = explode('/', $units, 2);
                $units = $a * $b;
            }
            if (strstr($unit_of_measure, '/')) { // space probably omitted
                preg_match('/([0-9.]+)\/([0-9.]+)(.+)/', $unit_of_measure, $matches);
                $units = $matches[1] * $matches[2];
                $unit_of_measure = $matches[3];
            }
            echo $obj->sku().',';
            echo '"'.$obj->description().'",';
            echo $order->vendorInvoiceID() . ',';
            echo date('Ymd', strtotime($obj->receivedDate())) . ',';
            printf('%f,', $units * $obj->caseSize() * $obj->quantity());
            echo $unit_of_measure . ',';
            printf('%.2f,', $obj->unitCost() * $obj->caseSize() * $obj->quantity());
            echo '"'.$obj->description().'",';
            echo '"",'; // alt. indicator
            echo '"",'; // alt. unit
            echo "\r\n";
        }
    }
}

