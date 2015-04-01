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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ManufacturerMovementReport extends FannieReportPage 
{
    protected $required_fields = array('date1', 'date2');

    public $description = '[Brand Movement] lists sales for products from a specific brand over a given date range. Brand is given either by name or as a UPC prefix.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    public function preprocess()
    {
        $this->title = _("Fannie") . " : " . _("Manufacturer Movement Report");
        $this->header = _("Manufacturer Movement Report");

        return parent::preprocess();
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $manu = FormLib::get_form_value('manu','');
        $type = FormLib::get_form_value('type','');
        $groupby = FormLib::get_form_value('groupby','upc');

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $type_condition = "p.brand LIKE ?";
        $args = array('%'.$manu.'%');
        if ($type == 'prefix')
            $type_condition = 't.upc LIKE ?';

        $query = "";
        $args[] = $date1.' 00:00:00';
        $args[] = $date2.' 23:59:59';
        switch ($groupby) {
        case 'upc':
            $query = "
                SELECT t.upc,
                    p.description, "
                    . DTrans::sumQuantity('t') . " AS qty,
                    SUM(t.total) AS ttl,
                    d.dept_no,
                    d.dept_name,
                    s.superID
                FROM $dlog AS t " 
                    . DTrans::joinProducts('t', 'p')
                    . DTrans::joinDepartments('t', 'd') . "
                    LEFT JOIN MasterSuperDepts AS s ON d.dept_no = s.dept_ID
                WHERE $type_condition
                    AND t.tdate BETWEEN ? AND ?
                GROUP BY t.upc,
                    p.description,
                    d.dept_no,
                    d.dept_name,
                    s.superID
                ORDER BY SUM(t.total) DESC";
            break;
        case 'date':
            $query = "
                SELECT YEAR(t.tdate) AS year,
                    MONTH(t.tdate) AS month,
                    DAY(t.tdate) AS day, "
                    . DTrans::sumQuantity('t') . " AS qty,
                    SUM(t.total) AS ttl
                FROM $dlog AS t "
                    . DTrans::joinProducts('t', 'p') . "
                WHERE $type_condition
                    AND t.tdate BETWEEN ? AND ?
                GROUP BY YEAR(t.tdate),
                    MONTH(t.tdate),
                    DAY(t.tdate)
                ORDER BY YEAR(t.tdate),
                    MONTH(t.tdate),
                    DAY(t.tdate)";
            break;
        case 'dept':
            $query = "
                SELECT d.dept_no,
                    d.dept_name, "
                    . DTrans::sumQuantity('t') . " AS qty,
                    SUM(t.total) AS ttl,
                    s.superID
                FROM $dlog AS t "
                    . DTrans::joinProducts('t', 'p')
                    . DTrans::joinDepartments('t', 'd') . "
                    LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
                WHERE $type_condition
                    AND t.tdate BETWEEN ? AND ?
                GROUP BY d.dept_no,
                    d.dept_name,
                    s.superID
                ORDER BY SUM(t.total) DESC";
            break;
        }

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep,$args);
        $ret = array();
        while ($row = $dbc->fetch_array($result)) {
            $record = array();
            if ($groupby == "date") {
                $record[] = $row['month'] . '/' . $row['day'] . '/' . $row['year'];
                $record[] = number_format($row['qty'], 2);
                $record[] = number_format($row['ttl'], 2);
            } else {
                for ($i=0;$i<$dbc->num_fields($result);$i++) {
                    if ($dbc->field_name($result, $i) == 'qty' || $dbc->field_name($result, $i) == 'ttl') {
                        $row[$i] = sprintf('%.2f', $row[$i]);
                    }
                    $record[] .= $row[$i];
                }
            }
            $ret[] = $record;
        }

        return $ret;
    }
    
    public function calculate_footers($data)
    {
        if (empty($data)) {
            return array();
        }

        switch (count($data[0])) {
            case 7:
                $this->report_headers = array('UPC','Description','Qty','$',
                    'Dept#','Department','Subdept');
                $sumQty = 0.0;
                $sumSales = 0.0;
                foreach ($data as $row) {
                    $sumQty += $row[2];
                    $sumSales += $row[3];
                }

                return array('Total',null,$sumQty,$sumSales,'',null,null);

            case 5:
                $this->report_headers = array('Dept#','Department','Qty','$','Subdept');
                $sumQty = 0.0;
                $sumSales = 0.0;
                foreach ($data as $row) {
                    $sumQty += $row[2];
                    $sumSales += $row[3];
                }

                return array('Total',null,$sumQty,$sumSales,null);

            case 3:
                $this->report_headers = array('Date','Qty','$');
                $sumQty = 0.0;
                $sumSales = 0.0;
                foreach ($data as $row) {
                    $sumQty += $row[1];
                    $sumSales += $row[2];
                }

                return array('Total',$sumQty,$sumSales);
        }
    }

    public function form_content()
    {
        global $FANNIE_URL;
?>
<form method="get" action="ManufacturerMovementReport.php" class="form-horizontal">
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label"><?php echo _("Manufacturer"); ?></label>
            <div class="col-sm-8">
                <input type=text name=manu id=manu class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">Type</label>
            <div class="col-sm-8">
                <label class="control-label">
                    <input type=radio name=type value=name id="rdoName" checked class="radio-inline" /> Name
                </label>
                <label class="control-label">
                    <input type=radio name=type value=prefix id="rdoPre" class="radio-inline" /> Prefix
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">Sum report by</label>
            <div class="col-sm-8">
                <select name=groupby class="form-control">
                    <option value="upc">UPC</option>
                    <option value="date">Date</option>
                    <option value="dept">Department</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">
                <input type=checkbox name=excel value=xls id="excel" /> Excel
            </label>
        </div>
        <div class="form-group">
        <button type=submit name=submit value="Submit" class="btn btn-default">Submit</button>
        <button type=reset name=reset class="btn btn-default">Start Over</button>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type=text id=date2 name=date2 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>                            
        </div>
    </div>
</form>
<?php
        $this->add_script($FANNIE_URL . 'item/autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#manu', '$ws', 'brand');\n");
        $this->add_onload_command('$(\'#manu\').focus();');
    }

    public function helpContent()
    {
        return '<p>Show sales for items from a given brand over a
            date range. <em>Brand</em> can be specified as either
            a name or a numeric UPC prefix. <em>Sum report by</em>
            gives different report formats.
            <ul>
                <li><em>UPC</em> shows a row for each item. Sales totals
                are for the entire date range.</li>
                <li><em>Date</em> show a row for each days. Sales totals
                are all sales in the brand that day.</li>
                <li><em>Department</em> shows a row for each POS department.
                Sales totals are all sales in that particular department
                for the entire date range.</li>
            </ul>';
    }
}

FannieDispatch::conditionalExec();

?>
