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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ItemOrderHistoryReport extends FannieReportPage 
{
    public $description = '[Item Order History] shows purchase orders for a given item. Requires purchase orders or
    invoice information to be entered into POS.';

    protected $title = "Fannie : Item Order History";
    protected $header = "Item Order History";

    protected $report_headers = array('Date', 'Vendor', 'Invoice#', 'SKU', '# Cases', 'Case Size', 'Unit Cost', 'Total');
    protected $required_fields = array('upc');
    protected $sort_direction = 1;

    public function report_description_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prod = new ProductsModel($dbc);
        $prod->upc(BarcodeLib::padUPC(FormLib::get('upc')));
        $prod->load();
        $ret = array('Order History For ' . $prod->upc() . ' ' . $prod->description());
        if (FormLib::get('all')) {
            $ret[] = 'All [known] orders';
            if ($this->report_format = 'html') {
                $ret[] = sprintf('<a href="ItemOrderHistoryReport.php?upc=%s">Show Recent</a>', $prod->upc());
            }
        } else {
            $ret[] = 'Since ' . date('F d, Y', strtotime('92 days ago'));
            if ($this->report_format = 'html') {
                $ret[] = sprintf('<a href="ItemOrderHistoryReport.php?upc=%s&all=1">Show All</a>', $prod->upc());
            }
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);

        $query = 'SELECT i.sku, i.quantity, i.unitCost, i.caseSize,
                        i.quantity * i.unitCost * i.caseSize AS ttl,
                        o.vendorInvoiceID, v.vendorName, o.placedDate
                        FROM PurchaseOrderItems AS i
                            LEFT JOIN PurchaseOrder AS o ON i.orderID=o.orderID
                            LEFT JOIN vendors AS v ON o.vendorID=v.vendorID
                        WHERE i.internalUPC = ?
                            AND o.placedDate >= ?
                        ORDER BY o.placedDate';
        $prep = $dbc->prepare($query);
        $args = array($upc);
        if (FormLib::get('all')) {
            $args[] = '1900-01-01 00:00:00';
        } else {
            $args[] = date('Y-m-d', strtotime('92 days ago'));
        }
        $result = $dbc->execute($prep, $args);
        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                $row['placedDate'],
                $row['vendorName'],
                $row['vendorInvoiceID'],
                $row['sku'],
                $row['quantity'],
                $row['caseSize'],
                $row['unitCost'],
                $row['ttl'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "<form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
                <b>UPC</b> <input type=text name=upc id=upc />
                <input type=submit value=\"Get Report\" />
                </form>";
    }

    public function readinessCheck()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!$dbc->tableExists('PurchaseOrderItems')) {
            $this->error_text = _("You are missing an important table") . " ($FANNIE_OP_DB.PurchaseOrderItems). ";
            $this->error_text .= " Visit the <a href=\"{$FANNIE_URL}install\">Install Page</a> to create it.";
            return false;
        } else {
            $testQ = 'SELECT orderID FROM PurchaseOrderItems';
            $testQ = $dbc->addSelectLimit($testQ, 1);
            $testR = $dbc->query($testQ);
            if ($dbc->num_rows($testR) == 0) {
                $this->error_text = _('No purchase orders have been entered.');
                return false;
            }
        }

        return true;
    }
}

FannieDispatch::conditionalExec();

