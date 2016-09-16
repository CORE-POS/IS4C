<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class AllVendorMovementReport extends FannieReportPage 
{
    public $description = '[All Vendor Movement] lists total sales for each vendor';
    public $report_set = 'Movement Reports';

    protected $title = "Fannie : All Vendor Movement";
    protected $header = "All Vendor Movement Report";
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Vendor', 'SKU Count', 'Qty Sold', '$ Sold', '% of Sales');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = $this->form->store;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $query = $dbc->prepare("
            SELECT v.vendorID,
                v.vendorName,
                COUNT(DISTINCT d.upc) AS skus,
                SUM(total) AS ttl,
                " . DTrans::sumQuantity('d') . " AS qty
            FROM {$dlog} AS d
                " . DTrans::joinProducts('d', 'p', 'INNER') . "
                INNER JOIN vendors AS v ON p.default_vendor_id=v.vendorID
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
            GROUP BY v.vendorID,
                v.vendorName");
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $store);
        $res = $dbc->execute($query, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                sprintf('<a href="VendorMovementReport.php?date1=%s&date2=%s&vendor=%d&groupby=upc&store=%d">%s</a>',
                    $date1, $date2, $row['vendorID'], $store, $row['vendorName']),
                $row['skus'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        $total = array_reduce($data, function($c, $i) { return $c+$i[3]; });
        $data = array_map(function($i) use ($total) {
            $i[] = sprintf('%.4f', ($i[3]/$total)*100);
            return $i;
        }, $data);

        return $data;
    }

    public function calculate_footers($data)
    {
        if (empty($data)) {
            return array();
        }

   }

    public function form_content()
    {
        ob_start();
?>
<form method="get" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
<div class="col-sm-5">
    <div class="form-group">
        <label>Start Date</label>
        <input type=text name=date1 id=date1 
            class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>End Date</label>
        <input type=text name=date2 id=date2 
            class="form-control date-field" required />
    </div>
   <p>
        <button type="submit" class="btn btn-default">Submit</button>
    </p>
</div>
<div class="col-sm-5">
    <?php echo FormLib::date_range_picker(); ?>
    <div class="form-group">
        <label>Store</label>
        <?php
        $store = FormLib::storePicker();
        echo $store['html'];
        ?>
    </div>
</div>
</form>
<?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            Lists product movement for each
            vendor during the specified date range.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->date1 = date('Y-m-d');
        $form->date2 = date('Y-m-d');
        foreach (array('upc', 'date', 'dept') as $col) {
            $form->groupby = $col;
            $this->setForm($form);
            $phpunit->assertInternalType('array', $this->fetch_report_data());
        }
    }
}

FannieDispatch::conditionalExec();

