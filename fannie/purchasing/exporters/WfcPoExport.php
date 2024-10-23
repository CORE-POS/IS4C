<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

if (!class_exists('DefaultCsvPoExport')) {
    include(dirname(__FILE__) . '/DefaultCsvPoExport.php');
}

class WfcPoExport extends DefaultCsvPoExport 
{
    public $nice_name = 'WFC';
    public $extension = 'csv';
    public $mime_type = 'text/csv';

    public function exportString($id)
    {
        ob_start();
        $this->export_order($id);
        return ob_get_clean();
    }

    public function export_order($id)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($id);
        $order->load();

        $auto = new AutoOrderMapModel($dbc);
        $auto->vendorID($order->vendorID());
        $auto->storeID($order->storeID());
        $auto->load();

        $notes = new PurchaseOrderNotesModel($dbc);
        $notes->orderID($id);
        $notes->load();
        $noteContent = trim($notes->notes());

        if ($noteContent != '') {
            echo "<div style=\"background: pink;\">Notes:</div>";
            echo "<div style=\"border: 1px solid pink;\">{$noteContent}</div>";
        }

        //if ($noteContent != '') {
        //    echo "Notes:\r\n";
        //    echo "\"{$noteContent}\"\r\n";
        //}

        echo "\r\n";
        if ($auto->accountID() != '') {
            echo "Account# " . $auto->accountID() . "\r\n";
        }
        echo "PO# " . $id . "\r\n";
        if ($order->storeID() == 1) {
            echo "Whole Foods Co-op\r\n";
            echo "610 E 4th St\r\n";
            echo "\"Duluth, MN 55805\"\r\n";
            echo "(218) 728-0884\r\n";
        } elseif ($order->storeID() == 2) {
            echo "Whole Foods Co-op\r\n";
            echo "4426 Grand Ave\r\n";
            echo "\"Duluth, MN 55807\"\r\n";
            echo "(218) 336-0279\r\n";
        }

        parent::export_order($id);
    }
}

