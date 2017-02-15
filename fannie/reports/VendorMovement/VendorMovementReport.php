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

class VendorMovementReport extends FannieReportPage 
{
    public $description = '[Vendor Movement] lists item sales for a particular vendor';
    public $report_set = 'Movement Reports';
    public $themed = true;

    protected $title = "Fannie : Vendor Movement";
    protected $header = "Vendor Movement Report";
    protected $required_fields = array('date1', 'date2');

    private function getQuery($groupby, $store, $dlog)
    {
        switch ($groupby) {
            case 'upc':
            default:
                return "
                    SELECT t.upc,
                        COALESCE(p.brand, x.manufacturer) AS brand,
                        p.description, "
                        . DTrans::sumQuantity('t') . " AS qty,
                        SUM(t.total) AS ttl,
                        d.dept_no,
                        d.dept_name,
                        s.super_name
                    FROM $dlog AS t "
                        . DTrans::joinProducts('t', 'p', 'INNER')
                        . DTrans::joinDepartments('t', 'd') . "
                        LEFT JOIN vendors AS v ON p.default_vendor_id = v.vendorID
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                        LEFT JOIN MasterSuperDepts AS s ON d.dept_no = s.dept_ID
                    WHERE (v.vendorName LIKE ? OR x.distributor LIKE ?)
                        AND t.tdate BETWEEN ? AND ?
                        AND " . DTrans::isStoreID($store, 't') . "
                    GROUP BY t.upc,
                        COALESCE(p.brand, x.manufacturer),
                        p.description,
                        d.dept_no,
                        d.dept_name,
                        s.super_name
                    ORDER BY SUM(t.total) DESC";
            case 'date':
                return "
                    SELECT YEAR(t.tdate) AS year,
                        MONTH(t.tdate) AS month,
                        DAY(t.tdate) AS day, "
                        . DTrans::sumQuantity('t') . " AS qty,
                        SUM(t.total) AS ttl
                    FROM $dlog AS t "
                        . DTrans::joinProducts('t', 'p') . "
                        LEFT JOIN vendors AS v ON p.default_vendor_id = v.vendorID
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                    WHERE (v.vendorName LIKE ? OR x.distributor LIKE ?)
                        AND t.tdate BETWEEN ? AND ?
                        AND " . DTrans::isStoreID($store, 't') . "
                    GROUP BY YEAR(t.tdate),
                        MONTH(t.tdate),
                        DAY(t.tdate)
                    ORDER BY YEAR(t.tdate),
                        MONTH(t.tdate),
                        DAY(t.tdate)";
                break;
            case 'dept':
                return "
                    SELECT d.dept_no,
                        d.dept_name, "
                        . DTrans::sumQuantity('t') . " AS qty,
                        SUM(t.total) AS ttl,
                        s.super_name
                    FROM $dlog AS t "
                        . DTrans::joinProducts('t', 'p', 'INNER')
                        . DTrans::joinDepartments('t', 'd') . "
                        LEFT JOIN vendors AS v ON p.default_vendor_id = v.vendorID
                        LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                    WHERE (v.vendorName LIKE ? OR x.distributor LIKE ?)
                        AND t.tdate BETWEEN ? AND ?
                        AND " . DTrans::isStoreID($store, 't') . "
                    GROUP BY d.dept_no,
                        d.dept_name,
                        s.super_name
                    ORDER BY SUM(t.total) DESC";
                break;
        }
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;

            // backwards compat; convert submitted vendorID to name
            $vendor = $this->form->vendor;
            $model = new VendorsModel($dbc);
            $model->vendorID($vendor);
            $model->load();
            $vendor = $model->vendorName();

            $groupby = $this->form->groupby;
            $store = $this->form->store;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $query = $this->getQuery($groupby, $store, $dlog);
        $args = array('%'.$vendor.'%','%'.$vendor.'%',$date1.' 00:00:00',$date2.' 23:59:59', $store);
        $prep = $dbc->prepare($query);

        $result = $dbc->execute($prep,$args);
        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $record = array();
            if ($groupby == "date") {
                $record[] = $row['month'] . '/' . $row['day'] . '/' . $row['year'];
                $record[] = number_format($row['qty'], 2);
                $record[] = number_format($row['ttl'], 2);
            } else {
                for ($i=0;$i<$dbc->numFields($result);$i++) {
                    if ($dbc->fieldName($result, $i) == 'qty' || $dbc->fieldName($result, $i) == 'ttl') {
                        $row[$i] = number_format($row[$i], 2);
                    }
                    $record[] .= $row[$i];
                }
            }
            $ret[] = $record;
        }

        return $ret;
    }

    private function getSums($data, $q, $t)
    {
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach ($data as $row) {
            $sumQty += $row[$q];
            $sumSales += $row[$t];
        }

        return array($sumQty, $sumSales);
    }
    
    public function calculate_footers($data)
    {
        if (empty($data)) {
            return array();
        }

        switch (count($data[0])) {
            case 8:
                $this->report_headers = array('UPC','Brand','Description','Qty','$',
                    'Dept#','Department','Super');
                list($sumQty, $sumSales) = $this->getSums($data, 3, 4);

                return array('Total',null,null,$sumQty,$sumSales,'',null,null);

            case 5:
                $this->report_headers = array('Dept#','Department','Qty','$','Super');
                list($sumQty, $sumSales) = $this->getSums($data, 2, 3);

                return array('Total',null,$sumQty,$sumSales,'');

            case 3:
                $this->report_headers = array('Date','Qty','$');
                list($sumQty, $sumSales) = $this->getSums($data, 1, 2);

                return array('Total',$sumQty,$sumSales);
        }
    }

    public function form_content()
    {
        $this->addScript('../../item/autocomplete.js');
        $vendor = new VendorsModel($this->connection);
        ob_start();
?>
<form method = "get" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
<div class="col-sm-5">
    <div class="form-group">
        <label>Vendor</label>
        <select name="vendor" class="form-control chosen-select">
            <?php echo $vendor->toOptions(); ?>
        </select>
    </div>
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
    <div class="form-group">
        <label>Sum report by</label>
        <select name=groupby class="form-control">
            <option value="upc">UPC</option>
            <option value="date">Date</option>
            <option value="dept">Department</option>
        </select>
    </div>
    <div class="form-group">
        <label>Excel
            <input type="checkbox" name="excel" value="xls" />
        </label>
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
        $this->addScript($this->config->get('URL') . 'src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile($this->config->get('URL') . 'src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('.chosen-select').chosen();\n");

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            Lists product movement for products from a given
            vendor during the specified date range.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->date1 = date('Y-m-d');
        $form->date2 = date('Y-m-d');
        $form->vendor = 'foo';
        foreach (array('upc', 'date', 'dept') as $col) {
            $form->groupby = $col;
            $this->setForm($form);
            $phpunit->assertInternalType('array', $this->fetch_report_data());
        }
    }
}

FannieDispatch::conditionalExec();

