<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OrderingReport extends FannieReportPage 
{
    public $description = '[Ordering Report] shows info to assist in placing orders';
    public $report_set = 'Purchasing';

    protected $title = "Fannie : Ordering Report";
    protected $header = "Ordering Report";
    protected $report_headers = array('UPC', 'SKU', 'Brand', 'Description', 'Case Size', 'Qty Sold',
        'Cases Sold', 'On Sale', 'Current Batch', 'Next Sale', 'Start Date');
    protected $required_fields = array('date1', 'date2');
    protected $sort_direction = 1;
    protected $sort_column = 5;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $supers = $this->form->super;
            $vendorID = $this->form->vendor;
            $storeID = $this->form->store;
            $start = $this->form->date1;
            $end = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }

        $args = array(
            $start . ' 00:00:00',
            $end . ' 23:59:59',
            $storeID,
            $vendorID,        
        );
        list($inStr, $args) = $dbc->safeInClause($supers, $args);

        $dlog = DTransactionsModel::selectDlog($start, $end);
        $query = $dbc->prepare("
            SELECT d.upc,
                COALESCE(p.brand, v.brand, '') AS brand,
                p.description,
                COALESCE(v.sku, '') AS sku,
                COALESCE(v.units, 'n/a') AS units,
                " . DTrans::sumQuantity('d') . " AS qty
            FROM {$dlog} AS d
                INNER JOIN products AS p ON d.upc=p.upc
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($storeID, 'd') . "
                AND p.default_vendor_id=?
                AND m.superID IN ({$inStr})
                AND d.charflag <> 'SO'
            GROUP BY d.upc,
                brand,
                p.description,
                sku,
                units
            HAVING qty <> 0
        "); 
        $res = $dbc->execute($query, $args);
        $items = array();
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $items[$upc] = array(
                $row['upc'],
                $row['sku'],
                $row['brand'],
                $row['description'],
                $row['units'],
                sprintf('%.2f', $row['qty']),
                (is_numeric($row['units']) ? sprintf('%.2f', $row['qty']/$row['units']) : 'n/a'),
            );
            $data[] = $items[$upc];
        }

        $batchP = $dbc->prepare("
            SELECT b.batchName
            FROM batchList AS l
                INNER JOIN batches AS b ON b.batchID=l.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE " . $dbc->curdate() . " BETWEEN b.startDate AND b.endDate
                AND l.upc=?
                AND m.storeID=?
                AND b.discounttype <> 0
        ");

        $futureP = $dbc->prepare("
            SELECT b.batchName, b.startDate
            FROM batchList AS l
                INNER JOIN batches AS b ON b.batchID=l.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE " . $dbc->curdate() . " < b.startDate
                AND l.upc=?
                AND m.storeID=?
                AND b.discounttype <> 0
                ORDER BY b.startDate
        ");
        foreach (array_keys($items) as $upc) {
            $batch = $dbc->getValue($batchP, array($upc, $storeID));
            if ($batch) {
                $items[$upc][] = 'Yes';
                $items[$upc][] = $batch;
            } else  {
                $items[$upc][] = 'No';
                $items[$upc][] = 'n/a';
            }
            $future = $dbc->getRow($futureP, array($upc, $storeID));
            if ($future) {
                $items[$upc][] = $future['batchName'];
                $items[$upc][] = $future['startDate'];
            } else {
                $items[$upc][] = 'n/a';
                $items[$upc][] = 'n/a';
            }
        }

        return $this->dekey_array($items);
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $model = new MasterSuperDeptsModel($dbc);
        $sopts = $model->toOptions(-999);
        $model = new VendorsModel($dbc);
        $vopts = $model->toOptions();
        $stores = FormLib::storePicker();
        $dates = FormLib::standardDateFields();

        return <<<HTML
<form method="get">
    <div class="row">
        <div class="col-sm-5">
            <div class="form-group">
                <label>Super Department(s)</label>
                <select name="super[]" class="form-control" multiple 
                    size="10" required>{$sopts}</select>
            </div>
            <div class="form-group">
                <label>Vendor</label>
                <select name="vendor" class="form-control">{$vopts}</select>
            </div>
            <div class="form-group">
                <label>Vendor</label>
                {$stores['html']}
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Submit</button>
            </div>
        </div>
        {$dates}
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

