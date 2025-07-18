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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class UnfiExportForMas extends FannieReportPage 
{

    protected $report_headers = array('Method', 'Vendor', 'Inv#', 'Date', 'Date', 'Inv Ttl', 'Code Ttl', 'Code', 'PO#');
    protected $sortable = false;
    protected $no_sort_but_style = true;

    public $report_set = 'Finance';
    public $description = '[MAS Invoice Export] exports vendor invoices for MAS90.';
    protected $required_fields = array('date1', 'date2');
    public $discoverable = true;

    protected $header = 'Invoice Export for MAS';
    protected $title = 'Invoice Export for MAS';

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

        $vendorID = FormLib::get('vendorID');
        $args = array($vendorID, $date1.' 00:00:00', $date2.' 23:59:59');
        $codingQ = 'SELECT o.orderID, 
                        o.salesCode, 
                        i.vendorOrderID,
                        i.vendorInvoiceID, 
                        SUM(o.receivedTotalCost) as rtc,
                        MAX(o.receivedDate) AS rdate,
                        MAX(i.storeID) AS storeID,
                        MAX(n.vendorName) AS vendor
                    FROM PurchaseOrderItems AS o
                        LEFT JOIN PurchaseOrder as i ON o.orderID=i.orderID 
                        LEFT JOIN vendors AS n ON n.vendorID=i.vendorID
                    WHERE i.vendorID=? 
                        AND o.receivedDate BETWEEN ? AND ?
                        AND (i.userID <> -99 OR i.userID IS NULL) ';
        if (FormLib::get('store')) {
            $codingQ .= ' AND i.storeID=? ';
            $args[] = FormLib::get('store');
        }
        $codingQ .= ' GROUP BY o.orderID, o.salesCode, i.vendorInvoiceID, i.vendorOrderID
                    ORDER BY rdate, i.vendorInvoiceID, o.salesCode';
        $codingP = $dbc->prepare($codingQ);

        $report = array();
        $invoice_sums = array();
        $codingR = $dbc->execute($codingP, $args);
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
            $method = 2;
            if ($codingW['vendor'] == 'RDW') {
                $codingW['vendor'] = 'RUSS';
            } elseif ($codingW['vendor'] == 'US FOODS') {
                $method = 1;
            }
            list($rdate,) = explode(' ', $codingW['rdate']);
            $record = array(
                $method,
                $codingW['vendor'],
                '<a href="../ViewPurchaseOrders.php?id=' . $codingW['orderID'] . '">' . $codingW['vendorInvoiceID'] . '</a>',
                date('n/j/Y', strtotime($rdate)),
                date('n/j/Y', strtotime($rdate)),
                0.00,
                sprintf('%.2f', $codingW['rtc']),
                $code,
                $codingW['vendorOrderID'] ? $codingW['vendorOrderID'] : $codingW['orderID'],
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
                $invTTL += $row[6];
            }

            foreach ($orders[$id] as $row) {
                $row[5] = $invTTL;
                $report[] = $row;
            }
        }

        return $report;
    }

    private function wfcCoding($code,$storeID)
    {
        if (substr($code, 0, 3) === '512') {
            return $code . '0' . $storeID . '20';
        } elseif ($code === '51300' || $code === '51310' || $code === '51315' || $code == '50510') {
            return $code . '0' . $storeID . '30';
        } elseif (substr($code, 0, 1) == '6') {
            return $code . '0' . $storeID . '00';
        } else {
            return $code . '0' . $storeID . '60';
        }
    }
    
    function form_content()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $stores = FormLib::storePicker();
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
    <div class="form-group">
        <label>Store</label>
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
            Transforms vendor invoice data into a format that
            can be imported into Sage MAS90. This probably cannot
            be used outside WFC other than as an example to
            base similar import/export reports on.
            </p>';
    }
}

FannieDispatch::conditionalExec();

