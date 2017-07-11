<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class UnfiExportForMas extends FannieReportPage 
{

    protected $report_headers = array('Vendor', 'Inv#', 'PO#', 'Date', 'Inv Ttl', 'Code Ttl', 'Code');
    protected $sortable = false;
    protected $no_sort_but_style = true;

    public $report_set = 'Finance';
    public $description = '[MAS Invoice Export] exports vendor invoices for MAS90.';
    protected $required_fields = array('date1', 'date2');
    public $discoverable = false;

    /**
      Lots of options on this report.
    */
    function fetch_report_data()
    {
        $date1 = FormLib::get('date1',date('Y-m-d'));
        $date2 = FormLib::get('date2',date('Y-m-d'));

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $mustCodeP = $dbc->prepare('
            SELECT i.orderID,
                i.sku
            FROM PurchaseOrderItems AS i
            WHERE i.receivedDate BETWEEN ? AND ?
                AND (i.salesCode IS NULL OR i.salesCode=0)
        ');
        $codeR = $dbc->execute($mustCodeP, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        $model = new PurchaseOrderItemsModel($dbc);
        while ($w = $dbc->fetchRow($codeR)) {
            $model->orderID($w['orderID']);
            $model->sku($w['sku']);
            if ($model->load()) {
                $code = $model->guessCode();
                $model->salesCode($code);
                $model->save();
            }
        }

        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }

        $codingQ = 'SELECT o.orderID, 
                        o.salesCode, 
                        i.vendorOrderID,
                        i.vendorInvoiceID, 
                        SUM(o.receivedTotalCost) as rtc,
                        MAX(o.receivedDate) AS rdate,
                        MAX(i.storeID) AS storeID
                    FROM PurchaseOrderItems AS o
                        LEFT JOIN PurchaseOrder as i ON o.orderID=i.orderID 
                    WHERE i.vendorID=? 
                        AND i.userID=0
                        AND o.receivedDate BETWEEN ? AND ?
                    GROUP BY o.orderID, o.salesCode, i.vendorInvoiceID, i.vendorOrderID
                    ORDER BY rdate, i.vendorInvoiceID, o.salesCode';
        $codingP = $dbc->prepare($codingQ);

        $report = array();
        $invoice_sums = array();
        $vendorID = FormLib::get('vendorID');
        $codingR = $dbc->execute($codingP, array($vendorID, $date1.' 00:00:00', $date2.' 23:59:59'));
        $orders = array();
        while ($codingW = $dbc->fetch_row($codingR)) {
            if ($codingW['rtc'] == 0) {
                // skip zero lines (tote charges)
                continue;
            }
            $code = $accounting::toPurchaseCode($codingW['salesCode']);
            $code = $this->wfcCoding($code, $codingW['storeID']);
            if (empty($code) && $this->report_format == 'html') {
                $code = 'n/a';
            }
            $record = array(
                'UNFI',
                '<a href="../ViewPurchaseOrders.php?id=' . $codingW['orderID'] . '">' . $codingW['vendorInvoiceID'] . '</a>',
                $codingW['vendorOrderID'],
                $codingW['rdate'],
                0.00,
                sprintf('%.2f', $codingW['rtc']),
                $code,
            );
            if (!isset($invoice_sums[$codingW['vendorInvoiceID']])) {
                $invoice_sums[$codingW['vendorInvoiceID']] = 0;
            }
            $invoice_sums[$codingW['vendorInvoiceID']] += $codingW['rtc'];
            if (!isset($orders[$codingW['orderID']])) {
                $orders[$codingW['orderID']] = array();
            }
            $orders[$codingW['orderID']][] = $record;
        }

        $po = new PurchaseOrderModel($dbc);
        foreach ($orders as $id => $data) {
            $invTTL = 0;
            for ($i=0; $i<count($data); $i++) {
                $row = $data[$i];
                $invTTL += $row[5];
            }

            foreach ($orders[$id] as $row) {
                $row[4] = $invTTL;
                $report[] = $row;
            }
        }

        return $report;
    }

    private function wfcCoding($code,$storeID)
    {
        if (substr($code, 0, 3) === '512' || $code === '51600') {
            return $code . '0' . $storeID . '20';
        } elseif ($code === '51300' || $code === '51310' || $code === '51315') {
            return $code . '0' . $storeID . '30';
        } else {
            return $code . '0' . $storeID . '60';
        }
    }
    
    function form_content()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        ob_start();
        ?>
<form method = "get" action="UnfiExportForMas.php">
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
        <?php
        $vendors = new VendorsModel($dbc);
        foreach ($vendors->find('vendorName') as $obj) {
            printf('<option %s value="%d">%s</option>',
                ($obj->vendorName() == 'UNFI' ? 'selected' : ''),
                $obj->vendorID(), $obj->vendorName());
        }
        ?>
        </select>
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
            Transforms vendor invoice data into a format that
            can be imported into Sage MAS90. This probably cannot
            be used outside WFC other than as an example to
            base similar import/export reports on.
            </p>';
    }
}

FannieDispatch::conditionalExec();

