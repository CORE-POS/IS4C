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

class MyUnfi7DigitCsvExport 
{
    public $nice_name = 'MyUNFI (7 Digit Code CSV)';
    public $extension = 'csv';
    public $mime_type = 'text/csv';

    public function send_headers()
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=order_export.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    public function exportString($id)
    {
        ob_start();
        $this->export_order($id);
        return ob_get_clean();
    }

    public function export_order($id)
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('OP_DB'));
        $items = new PurchaseOrderItemsModel($dbc);
        $items->orderID($id);
        $NL = "\r\n";

        echo 'Item Code,Quantity' . $NL;
        foreach ($items->find() as $item) {
            echo str_pad(str_replace('.', '', $item->sku()), 7, '0', STR_PAD_LEFT);
            echo ',';
            echo $item->quantity();
            echo $NL;
        }
    }
}

