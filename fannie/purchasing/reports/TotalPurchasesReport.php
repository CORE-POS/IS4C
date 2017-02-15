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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TotalPurchasesReport extends FannieReportPage 
{
    protected $title = 'Total Purchases Report';
    protected $header = 'Total Purchases Report';
    public $description = '[Total Purchases Report] lists all invoice totals from a vendor for a given period';
    public $report_set = 'Purchasing';

    protected $report_headers = array('Order Date', 'Received Date', 'Vendor', 'Invoice#', '$ Cost');
    protected $required_fields = array('date1', 'date2');
    public $themed = true;
    protected $sort_column = 8;
    protected $sort_direction = 1;

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        $vendor = FormLib::get('vendorID');
        $store = FormLib::get('store');

        $query = '
            SELECT o.vendorID,
                v.vendorName,
                o.orderID,
                o.vendorInvoiceID,
                SUM(i.receivedTotalCost) AS ttl,
                o.placedDate,
                MAX(i.receivedDate) AS received
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
                LEFT JOIN vendors AS v ON o.vendorID=v.vendorID
            WHERE i.quantity > 0
                AND o.placedDate BETWEEN ? AND ?';
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        if ($vendor !== '') {
            $query .= ' AND o.vendorID=? ';
            $args[] = $vendor;
        }
        if ($store > 0) {
            $query .= ' AND o.storeID=? ';
            $args[] = $store;
        }
        $query .= 'GROUP BY o.orderID, o.vendorID, v.vendorName, o.placedDate, o.vendorInvoiceID';

        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $report = array();
        while ($w = $dbc->fetchRow($res)) {
            $report[] = array(
                $w['placedDate'],
                $w['received'],
                $w['vendorName'],
                '<a href="../ViewPurchaseOrders.php?id=' . $w['orderID'] . '">' . $w['vendorInvoiceID'] . '</a>',
                sprintf('%s', $w['ttl']),
            );
        }

        return $report;
    }

    function calculate_footers($data)
    {
        $sum = 0;
        foreach ($data as $d) {
            $sum += $d[4];
        }

        return array('Total', null, null, null, sprintf('%.2f', $sum));
    }

    function form_content()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $stores = FormLib::storePicker();
        ob_start();
        ?>
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<div class="col-sm-5">
    <div class="form-group">
        <label>Date Start</label>
            <input type=text id=date1 name=date1 
                class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>Date End</label>
            <input type=text id=date2 name=date2 
                class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>Vendor</label>
        <select name="vendorID" class="form-control">
        <option value="">All</option>
        <?php
        $vendors = new VendorsModel($dbc);
        foreach ($vendors->find('vendorName') as $obj) {
            printf('<option value="%d">%s</option>',
                $obj->vendorID(), $obj->vendorName());
        }
        ?>
        </select>
    </div>
    <div class="form-group">
        <label>Vendor</label>
        <?php echo $stores['html']; ?>
    </div>
    <p>
        <button type="submit" class="btn btn-default btn-core">Submit</button>
        <button type="reset" class="btn btn-default btn-reset">Start Over</button>
    </p>
</div>
<div class="col-sm-5">
    <?php echo FormLib::date_range_picker(); ?>                         
</div>
</form>
<?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            The Out of Stock report lines items from purchase orders
            where the quantity ordered was greater than zero but the
            quantity received was zero - normally indicating the vendor
            does not have the item ordered in stock.
            </p>
            <p>A date range is required but vendor is optional. Either
            choose a single vendor to show just its out of stocks or leave
            the selection at All for all vendors.
            </p>
            ';
    }

}

FannieDispatch::conditionalExec();

