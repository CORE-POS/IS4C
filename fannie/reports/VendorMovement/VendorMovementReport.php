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

class VendorMovementReport extends FannieReportPage 
{
    public $description = '[Vendor Movement] lists item sales for a particular vendor';
    public $report_set = 'Movement Reports';

    protected $title = "Fannie : Vendor Movement";
    protected $header = "Vendor Movement Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $vendor = FormLib::get_form_value('vendor','');
        $groupby = FormLib::get_form_value('groupby','upc');

        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumUpcSalesByDay";

        $query = "";
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
                        LEFT JOIN vendors AS v ON p.default_vendor_id = v.vendorID
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                        LEFT JOIN MasterSuperDepts AS s ON d.dept_no = s.dept_ID
                    WHERE (v.vendorName LIKE ? OR x.distributor LIKE ?)
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
                        LEFT JOIN vendors AS v ON p.default_vendor_id = v.vendorID
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                    WHERE (v.vendorName LIKE ? OR x.distributor LIKE ?)
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
                        LEFT JOIN vendors AS v ON p.default_vendor_id = v.vendorID
                        LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
                        LEFT JOIN prodExtra AS x ON p.upc=x.upc
                    WHERE (v.vendorName LIKE ? OR x.distributor LIKE ?)
                        AND t.tdate BETWEEN ? AND ?
                    GROUP BY d.dept_no,
                        d.dept_name,
                        s.superID
                    ORDER BY SUM(t.total) DESC";
                break;
        }
        $args = array('%'.$vendor.'%','%'.$vendor.'%',$date1.' 00:00:00',$date2.' 23:59:59');
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
                        $row[$i] = number_format($row[$i], 2);
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

                return array('Total',null,$sumQty,$sumSales,null,null,null);

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
?>
<div id=main>   
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <th>Vendor</th>
            <td>
            <input type=text name=vendor id=vendor  />
            </td>
            <th>Date Start</th>
            <td>
                <input type=text size=14 id=date1 name=date1 />
            </td>
        </tr>
        <tr>
            <th>Sum report by</th>
            <td><select name=groupby>
            <option value="upc">UPC</option>
            <option value="date">Date</option>
            <option value="dept">Department</option>
            </select></td>
            <th>Date End</th>   
            <td>
            <input type=text size=14 id=date2 name=date2 />
            </td>
        </tr>
        <tr>
            <td colspan="2">
            <input type=checkbox name=excel value=xls /> Excel 
            </td>
            <td colspan="2" rowspan="2">
            <?php echo FormLib::date_range_picker(); ?>
            </td>
        </tr>
        <tr>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
        </tr>
    </table>
</form>
</div>
<?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
    }
}

FannieDispatch::conditionalExec(false);

?>
