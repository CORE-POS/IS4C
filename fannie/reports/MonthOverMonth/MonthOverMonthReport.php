<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class MonthOverMonthReport extends FannieReportPage {

    private $months;
    protected $title = "Fannie : Month Over Month Movement";
    protected $header = "Month Over Month Movement";
    protected $required_fields = array('month1', 'month2');

    public $description = '[Monthly Movement] shows monthly sales totals for items or departments.';
    public $report_set = 'Movement Reports';
    
    function preprocess()
    {
        parent::preprocess();
        if ($this->content_function == 'report_content') {
            $this->report_headers = array('#','Description');
            // build headers and keys off span of months
            $this->months = array();
            $stamp1 = mktime(0,0,0,FormLib::get_form_value('month1',1),1,FormLib::get_form_value('year1',1));
            $stamp2 = mktime(0,0,0,FormLib::get_form_value('month2',1),1,FormLib::get_form_value('year2',1));
            while($stamp1 <= $stamp2){
                $this->report_headers[] = date('F Y',$stamp1);
                $this->months[] = date('Y-n',$stamp1);
                $stamp1 = mktime(0,0,0,date('n',$stamp1)+1,1,date('Y',$stamp1));
            }
        }

        return true;
    }

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $month1 = FormLib::get_form_value('month1',date('n'));
        $month2 = FormLib::get_form_value('month2',date('n'));
        $year1 = FormLib::get_form_value('year1',date('Y'));
        $year2 = FormLib::get_form_value('year2',date('Y'));

        $date1 = date('Y-m-d',mktime(0,0,0,$month1,1,$year1));
        $date2 = date('Y-m-t',mktime(0,0,0,$month2,1,$year2));
        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $date1 .= ' 00:00:00';
        $date2 .= ' 00:00:00';

        $qArgs = array($date1,$date2);
        $query = "";
        $type = FormLib::get_form_value('mtype','upc');
        if ($type == 'upc'){
            $inClause = "(";
            $vals = preg_split("/\D+/",FormLib::get_form_value('upcs',''));
            foreach($vals as $v){
                $qArgs[] = BarcodeLib::padUPC($v);
                $inClause .= "?,";
            }
            $inClause = rtrim($inClause,",").")";

            $query = "SELECT t.upc,
                        p.description, "
                        . DTrans::sumQuantity('t') . " AS qty,
                        SUM(total) AS sales, 
                        MONTH(tdate) AS month, 
                        YEAR(tdate) AS year
                      FROM $dlog AS t "
                        . DTrans::joinProducts('t', 'p') . " 
                      WHERE t.trans_status <> 'M'
                        AND tdate BETWEEN ? AND ?
                        AND t.upc IN $inClause
                      GROUP BY YEAR(tdate),
                        MONTH(tdate),
                        t.upc,
                        p.description
                      ORDER BY YEAR(tdate),
                        MONTH(tdate),
                        t.upc,
                        p.description";
        } else {
            $dept1 = FormLib::get_form_value('dept1',1);
            $dept2 = FormLib::get_form_value('dept2',1);
            $qArgs[] = $dept1;
            $qArgs[] = $dept2;
            $query = "SELECT t.department,d.dept_name,SUM(t.quantity) as qty,
                SUM(total) as sales, MONTH(tdate) as month, YEAR(tdate) as year
                FROM $dlog AS t
                LEFT JOIN departments AS d ON t.department=d.dept_no
                WHERE t.trans_status <> 'M'
                AND tdate BETWEEN ? AND ?
                AND t.department BETWEEN ? AND ?
                GROUP BY YEAR(tdate),MONTH(tdate),t.department,d.dept_name
                ORDER BY YEAR(tdate),MONTH(tdate),t.department,d.dept_name";
        }

        $queryP = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($queryP, $qArgs);

        $ret = array();
        while ($row = $dbc->fetch_array($result)){
            if (!isset($ret[$row[0]])){
                $ret[$row[0]] = array('num'=>$row[0],'desc'=>$row[1]);
                foreach($this->months as $mkey)
                    $ret[$row[0]][$mkey] = 0;
            }
            if (FormLib::get_form_value('results','Sales') == 'Sales')
                $ret[$row[0]][$row['year'].'-'.$row['month']] = $row['sales'];
            else
                $ret[$row[0]][$row['year'].'-'.$row['month']] = $row['qty'];
        }
        return $this->dekey_array($ret);
    }
    
    function form_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = array();
        $q = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
        $r = $dbc->exec_statement($q);
        while($w = $dbc->fetch_row($r))
            $depts[$w[0]] = $w[1];
?>
<div id=main>   
    <form action="MonthOverMonthReport.php" method="get">
    <table>
    <tr>
    <td><select name="month1"><?php
    for($i=1;$i<13;$i++)
        printf("<option value=%d>%s</option>",$i,date("F",mktime(0,0,0,$i,1,2000)));
    ?></select></td>
    <td><input type="text" size=4 name="year1" value="<?php echo date("Y"); ?>" /></td>
    <td>&nbsp;through&nbsp;</td>
    <td><select name="month2"><?php
    for($i=1;$i<13;$i++)
        printf("<option value=%d>%s</option>",$i,date("F",mktime(0,0,0,$i,1,2000)));
    ?></select></td>
    <td><input type="text" size=4 name="year2" value="<?php echo date("Y"); ?>" /></td>
    </tr>
    <tr><td colspan="5">
    <b>Report for</b>:
    <input type="radio" id="upc" name="mtype" value="upc" checked 
        onclick="$('#upctr').show();$('.depttr').hide();" /> UPC
    <input type="radio" id="dept" name="mtype" value="dept" 
        onclick="$('#upctr').hide();$('.depttr').show();" /> Department
    &nbsp;&nbsp; Results in <select name=results><option>Sales</option><option>Quantity</option></select>
    </td></tr>
    <tr id="upctr"><td colspan="5">
    <b>UPC(s)</b>: <input type="text" name="upcs" size="35" />
    </td></tr>
    <tr class="depttr" style="display:none;"><td align=right>
    <b>Department</b>:
    </td><td colspan="4">
    <select name="dept1"><?php
    foreach($depts as $k=>$v)
        printf("<option value=%d>%d %s</option>",$k,$k,$v);
    ?>
    </select>
    </td><tr class="depttr" style="display:none;"><td align=right>
    through</td><td colspan="4">
    <select name="dept2"><?php
    foreach($depts as $k=>$v)
        printf("<option value=%d>%d %s</option>",$k,$k,$v);
    ?>
    </select>
    </table>
    <br />
    <input type="submit" value="Run Report" />
    <input type="checkbox" name="excel" /> Excel
    </form>
</div>
<?php
    }
}

FannieDispatch::conditionalExec(false);

?>
