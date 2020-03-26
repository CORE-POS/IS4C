<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class SalesByInvoicePage extends FannieRESTfulPage
{
    protected $header = 'Movement By Invoice Order ID';
    protected $title = 'Movement By Invoice';

    public $description = '[Sales By Invoice Order ID Page] 
        Check movement of products listed by purchase order.';
    public $has_unit_tests = true;

    public function formContent()
    {
        $vendorInvoiceID = FormLib::get('vendorInvoiceID');
        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        return <<<HTML
<form method="get">
    <div class="row">
        <div class="col-lg-3">
            <div class="form-group">
                <label for="date1">Start Date</label>
                <input type="text" name="date1" id="date1" class="form-control" value="$date1">
            </div>
        </div>
        <div class="col-lg-3">
            <div class="form-group">
                <label for="date2">End Date</label>
                <input type="text" name="date2" id="date2" class="form-control" value="$date2">
            </div>
        </div>
        <div class="col-lg-3">
            <div class="form-group">
                <label for="vendorInvoiceID">Vendor Invoice ID</label>
                <input type="text" name="vendorInvoiceID" id="vendorInvoiceID" class="form-control" value="$vendorInvoiceID">
            </div>
        </div>
        <div class="col-lg-3">
            <div class="form-group">
                <div>&nbsp;</div>
                <input type="submit" id="submit" class="btn btn-default" value="Submit">
            </div>
        </div>
    </div>
</form>
HTML;
    }

    private function getUpcList($vendorInvoiceID,$dbc)
    {
        $upcs = array();
        $received = array();

        $prep = $dbc->prepare("SELECT orderID FROM PurchaseOrder WHERE vendorInvoiceID = ?"); 
        $res = $dbc->execute($prep, array($vendorInvoiceID));
        while ($row = $dbc->fetchRow($res)) {
            $orderID = $row['orderID'];
        }

        $prep = $dbc->prepare("SELECT internalUPC AS upc, receivedQty FROM PurchaseOrderItems WHERE orderID = ?"); 
        $res = $dbc->execute($prep, array($orderID));
        while ($row = $dbc->fetchRow($res)) {
            $received[$row['upc']] = $row['receivedQty'];
            $upcs[] = $row['upc'];
        }

        return array($upcs, $received);
    }

    public function getView()
    {
        $dbc = $this->connection;
        $vendorInvoiceID = FormLib::get('vendorInvoiceID');
        $date1 = FormLib::get('date1');
        $date1 .= ' 00:00:00';
        $date2 = FormLib::get('date2');
        $date2 .= ' 23:59:59';
        list($upcs, $received) = $this->getUpcList($vendorInvoiceID, $dbc); 
        list($inClause, $args) = $dbc->safeInClause($upcs);
        $args[] = $date1;
        $args[] = $date2;
        $storeID = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $args[] = $storeID;
        $args[] = $storeID;
        $query = "select 
                ". DTrans::sumQuantity('t')." as qty,
                t.department,
                i.sku,
                i.units,
                t.upc,
                p.description,
                p.brand
            FROM " . $dlog = DTransactionsModel::selectDlog(FormLib::get('date1'), FormLib::get('date2')) . " AS t 
                INNER JOIN products AS p ON t.upc=p.upc
                LEFT JOIN vendorItems AS i ON p.default_vendor_id=i.vendorID 
                    AND p.upc=i.upc
            where t.upc IN ({$inClause})
                and tdate > ?
                and tdate < ? 
                and t.store_id = ?
                and p.store_id = ?
            GROUP BY t.upc
            ORDER BY qty DESC
        ";
        $prep = $dbc->prepare($query);
        if ($vendorInvoiceID > 0 && $date1 != ' 00:00:00' && $date2 != ' 23:59:59') {
            $res = $dbc->execute($prep, $args);
            $td = '';
            $sold = array();
            while ($row = $dbc->fetchRow($res)) {
                if ($received[$row['upc']] > 0) {
                    if ($row['qty'] > 0 && $row['qty'] < 1) $row['qty'] = 1;
                    $alert = ($row['qty'] == 0) ? 'danger' : '';
                    $td .= sprintf("<tr class=\"$alert\"><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>",
                        $row['upc'],
                        $row['brand'],
                        $row['description'],
                        $row['units'],
                        $received[$row['upc']],
                        $row['qty']
                    );
                    $sold[] = $row['upc'];
                }
            }
            foreach ($upcs as $upc) {
                if (!in_array($upc, $sold)) {
                    if ($received[$upc] > 0) {
                        $prep = $dbc->prepare("SELECT description, brand, units FROM vendorItems WHERE upc = ?");
                        $res = $dbc->execute($prep, array($upc));
                        $row = $dbc->fetchRow($res);
                        $td .= sprintf("<tr class='danger'><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>",
                            $upc,
                            $row['brand'],
                            $row['description'],
                            $row['units'],
                            $received[$upc],
                            $row['qty']
                        );
                    }
                }
            }
        }
        $th = sprintf("<th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th>",
            'upc', 'brand', 'description', 'case size', 'received', 'sold');

        $this->addOnloadCommand("$('#date1').datepicker({dateFormat: 'yy-mm-dd'});");
        $this->addOnloadCommand("$('#date2').datepicker({dateFormat: 'yy-mm-dd'});");

        return <<<HTML
{$this->formContent()}
<div class="table-responsive">
    <table class="table table-bordered table-striped table-condensed small"><thead>$th</thead><tbody>$td</tbody></table>
</div>
HTML;
    }

    public function unitTest($phpunit)
    {
        $get = $this->getView();
        $phpunit->assertNotEquals(0, strlen($get));
    }

}

FannieDispatch::conditionalExec();
