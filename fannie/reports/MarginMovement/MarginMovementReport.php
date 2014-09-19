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

class MarginMovementReport extends FannieReportPage 
{
    public $description = '[Margin Movement] lists item movement with margin information.';
    public $report_set = 'Movement Reports';

    protected $title = "Fannie : Margin Movement Report";
    protected $header = "Margin Movement Report";

    protected $sort_column = 5;
    protected $sort_direction = 1;

    protected $report_headers = array('UPC', 'Desc', 'Dept#', 'Dept', 'Cost', 'Sales', 'Margin', 'Markup', 'Contrib');
    protected $required_fields = array('date1', 'date2');

    public function report_description_content()
    {
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $include_sales = FormLib::get('includeSales', 0);
        $buyer = FormLib::get('buyer', '');
    
        $ret = array();
        if ($buyer === '') {
            $ret[] = 'Department '.$deptStart.' to '.$deptEnd;
        } else if ($buyer == -1) {
            $ret[] = 'All Super Departments';
        } else {
            $ret[] = 'Super Department '.$buyer;
        }

        if ($include_sales == 1) {
            $ret[] = 'Includes sale items';
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $include_sales = FormLib::get('includeSales', 0);
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer != -1) {
                $where = ' s.superID=? ';
                $args[] = $buyer;
            }
        } else {
            $where = ' d.department BETWEEN ? AND ? ';
            $args[] = $deptStart;
            $args[] = $deptEnd;
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = "SELECT d.upc,
                    p.description,
                    d.department,
                    t.dept_name,
                    SUM(total) AS total,
                    SUM(d.cost) AS cost,"
                    . DTrans::sumQuantity('d') . " AS qty
                  FROM $dlog AS d "
                    . DTrans::joinProducts('d', 'p', 'inner')
                    . DTrans::joinDepartments('d', 't');
        // join only needed with specific buyer
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON d.department=s.dept_ID ';
        }
        $query .= "WHERE tdate BETWEEN ? AND ?
            AND $where
            AND d.cost <> 0 ";
        if ($include_sales != 1) {
            $query .= "AND d.discounttype=0 ";
        }
        $query .= "GROUP BY d.upc,p.description,d.department,t.dept_name
            ORDER BY sum(total) DESC";

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($query, $args);

        $data = array();
        $sum_total = 0.0;
        $sum_cost = 0.0;
        while($row = $dbc->fetch_row($result)) {
            $margin = ($row['total'] - $row['cost']) / $row['total'] * 100;
            $record = array(
                $row['upc'],
                $row['description'],
                $row['department'],
                $row['dept_name'],
                sprintf('%.2f', $row['cost']),
                sprintf('%.2f', $row['total']),
                sprintf('%.2f', $margin),
                sprintf('%.2f', ($row['total'] - $row['cost']) / $row['qty']),
            );

            $sum_total += $row['total'];
            $sum_cost += $row['cost'];

            $data[] = $record;
        }

        // go through and add a contribution to margin value
        for ($i=0; $i<count($data); $i++) {
            // (item_total - item_cost) / total sales
            $contrib = ($data[$i][5] - $data[$i][4]) / $sum_total * 100;
            $data[$i][] = sprintf('%.2f', $contrib);
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }

        $sum_cost = 0.0;
        $sum_ttl = 0.0;
        foreach($data as $row) {
            $sum_cost += $row[4];
            $sum_ttl += $row[5];
        }

        return array('Totals', null, null, null, sprintf('%.2f',$sum_cost), sprintf('%.2f',$sum_ttl), '', null, null);
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->exec_statement($deptsQ);
        $deptsList = "";

        $deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames
                WHERE superID <> 0 
                ORDER BY superID");
        $deptSubR = $dbc->exec_statement($deptSubQ);

        $deptSubList = "";
        while($deptSubW = $dbc->fetch_array($deptSubR)) {
            $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
        }
        while ($deptsW = $dbc->fetch_array($deptsR)) {
            $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
        }

        ob_start();
        ?>
<script type="text/javascript">
function swap(src,dst){
    var val = document.getElementById(src).value;
    document.getElementById(dst).value = val;
}
</script>
<div id=main>   
<form method = "get" action="MarginMovementReport.php">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr>
            <td><b>Select Buyer/Dept</b></td>
            <td><select id=buyer name=buyer>
               <option value=""></option>
               <?php echo $deptSubList; ?>
               <option value=-1 >All</option>
               </select>
            </td>
            <td><b>Send to Excel</b></td>
            <td><input type=checkbox name=excel id=excel value=1></td>
        </tr>
        <tr>
            <td colspan=5><i>Selecting a Buyer/Dept overrides Department Start/Department End, but not Date Start/End.
            To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'</i></td>
        </tr>
        <tr> 
            <td> <p><b>Department Start</b></p>
            <p><b>End</b></p></td>
            <td> <p>
            <select id=deptStartSel onchange="swap('deptStartSel','deptStart');">
            <?php echo $deptsList ?>
            </select>
            <input type=text name=deptStart id=deptStart size=5 value=1 />
            </p>
            <p>
            <select id=deptEndSel onchange="swap('deptEndSel','deptEnd');">
            <?php echo $deptsList ?>
            </select>
            <input type=text name=deptEnd id=deptEnd size=5 value=1 />
            </p></td>

             <td>
            <p><b>Date Start</b> </p>
                 <p><b>End</b></p>
               </td>
                    <td>
                     <p>
                       <input type=text id=date1 name=date1 />
                       </p>
                       <p>
                        <input type=text id=date2 name=date2 />
                 </p>
               </td>

        </tr>
        <tr> 
             <td colspan="2"><input type=checkbox name=includeSales value=1>Include Sale Items</td>
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
        <?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
