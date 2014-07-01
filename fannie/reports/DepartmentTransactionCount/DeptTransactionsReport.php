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

class DeptTransactionsReport extends FannieReportPage 
{
    public $description = '[Department Transactions] lists the number of transactions in a department
        or departments over a given date range.';

    protected $report_headers = array('Date', '# Matching Trans', '# Total Trans', '%');

    protected $title = "Fannie : Department Transactions Report";
    protected $header = "Department Transactions";

    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
    
        $buyer = FormLib::get('buyer', -999);

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $queryAll = "SELECT YEAR(tdate) AS year, MONTH(tdate) AS month, DAY(tdate) AS day,
            COUNT(DISTINCT trans_num) as trans_count
            FROM $dlog AS d 
            WHERE tdate BETWEEN ? AND ?
            GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
            ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate)";
        $argsAll = array($date1.' 00:00:00',$date2.' 23:59:59');

        $querySelected = "SELECT YEAR(tdate) AS year, MONTH(tdate) AS month, DAY(tdate) AS day,
            COUNT(DISTINCT trans_num) as trans_count
            FROM $dlog AS d ";
        if ($buyer != -999) {
            $querySelected .= " LEFT JOIN superdepts AS s ON d.department=s.dept_ID ";
        }
        $querySelected .= " WHERE tdate BETWEEN ? AND ? ";
        $argsSel = $argsAll;
        if ($buyer != -999) {
            $querySelected .= " AND s.superID=? ";
            $argsSel[] = $buyer;
        } else {
            $querySelected .= " AND department BETWEEN ? AND ?";
            $argsSel[] = $deptStart;
            $argsSel[] = $deptEnd;
        }
        $querySelected .= " GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)";

        $dataset = array();

        $prep = $dbc->prepare_statement($queryAll);
        $result = $dbc->exec_statement($prep,$argsAll);
        while($row = $dbc->fetch_row($result)) {
            $datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
            $dataset[$datestr] = array('ttl'=>$row['trans_count'],'sub'=>0);
        }

        $prep = $dbc->prepare_statement($querySelected);
        $result = $dbc->exec_statement($prep,$argsSel);
        while($row = $dbc->fetch_row($result)) {
            $datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
            if (isset($dataset[$datestr])) {
                $dataset[$datestr]['sub'] = $row['trans_count'];
            }
        }

        $data = array();
        foreach($dataset as $date => $count){
            $record = array($date, $count['sub'], $count['ttl']);
            $record[] = sprintf('%.2f%%', ($count['sub']/$count['ttl'])*100);
            $data[] = $record;
        }

        return $data;
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
<form method = "get" action="DeptTransactionsReport.php">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr>
            <td><b>Select Buyer/Dept</b></td>
            <td><select id=buyer name=buyer>
               <option value=0 >
               <?php echo $deptSubList; ?>
               <option value=-2 >All Retail</option>
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
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <td colspan="2" rowspan="2">
                <?php echo FormLib::date_range_picker(); ?>
            </td>
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
