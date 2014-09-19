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

class PurchasingSearchPage extends FannieRESTfulPage {
    
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[Search Purchase Orders] finds orders/invoices containing a given item.';

    protected $must_authenticate = true;

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $start = FormLib::get('date1');
        $end = FormLib::get('date2');

        $query = 'SELECT o.placedDate, o.orderID, o.vendorInvoiceID,
                v.vendorName, i.sku, i.internalUPC, i.description,
                i.brand, i.quantity
                FROM PurchaseOrderItems AS i
                    LEFT JOIN PurchaseOrder AS o ON i.orderID=o.orderID
                    LEFT JOIN vendors AS v ON o.vendorID=v.vendorID
                WHERE (i.internalUPC=? OR i.sku LIKE ?) ';
        if ($start !== '' && $end !== '') {
            $query .= ' AND o.placedDate BETWEEN ? AND ? ';
        }
        $query .= 'ORDER BY o.placedDate DESC';

        $args = array(BarcodeLib::padUPC($this->id), '%'.$this->id);
        if ($start !== '' && $end !== '') {
            $args[] = $start . ' 00:00:00';
            $args[] = $end . ' 23:59:59';
        }

        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);

        $ret = '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Date</th><th>Invoice</th><th>Vendor</th>
                <th>UPC</th><th>SKU</th><th>Brand</th><th>Desc</th>
                <th>Qty</th></tr>';
        while($row = $dbc->fetch_row($res)) {
            $ret .= sprintf('<tr>
                            <td><a href="ViewPurchaseOrders.php?id=%d">%s</a></td>
                            <td><a href="ViewPurchaseOrders.php?id=%d">%s</a></td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%d</td>
                            </tr>',
                            $row['orderID'], date('Y-m-d', strtotime($row['placedDate'])),
                            $row['orderID'], $row['vendorInvoiceID'],
                            $row['vendorName'],
                            $row['internalUPC'],
                            $row['sku'],
                            $row['brand'],
                            $row['description'],
                            $row['quantity']
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    public function get_view()
    {
        $ret = '<form action="PurchasingSearchPage.php" method="get">';
        $ret .= '<table>';
        $ret .= '<tr><th>UPC or SKU</th><td><input type="text" name="id" /></td>';
        $ret .= '<td rowspan="3">' . FormLib::dateRangePicker() . '</td></tr>';
        $ret .= '<tr><th>Start Date</th><td><input type="text" size="10" 
            id="date1" name="date1" /></td></tr>';
        $ret .= '<tr><th>End Date</th><td><input type="text" size="10" 
            id="date2" name="date2" /></td></tr>';
        $ret .= '<tr><td><input type="submit" value="Search" /></td>';
        $ret .= '<td colspan="2">Omit dates to search all orders
                (<a href="" onclick="$(\'#date1\').val(\'\');$(\'#date2\').val(\'\');return false;">Clear
                Dates</a>)</td></tr>';
        $ret .= '</table>';
        $ret .= '</form>';

        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

        return $ret;
    }
}

FannieDispatch::conditionalExec();

