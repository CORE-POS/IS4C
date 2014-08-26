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

class CorrelatedMovementReport extends FannieReportPage 
{
    protected $report_cache = 'day';
    protected $title = "Fannie : Correlated Movement Report";
    protected $header = "Correlated Movement Report";
    protected $report_headers = array('UPC', 'Desc', 'Dept', 'Qty');
    protected $sort_column = 3;
    protected $sort_direction = 1;
    protected $required_fields = array('date1', 'date2');

    public $description = '[Correlated Movement] shows what items purchasers from a certain department or group of departments also buy. Optionally, results can be filtered by department too. This may be clearer with an example: among transactions that include a sandwich, what do sales from the beverages department look like?';
    public $report_set = 'Movement Reports';

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $depts = FormLib::get('depts', array());
        $upc = FormLib::get('upc');
        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $filters = FormLib::get('filters', array());

        $dClause = "";
        $dArgs = array();
        foreach($depts as $d){
            $dClause .= "?,";
            $dArgs[] = $d;
        }
        $dClause = "(".rtrim($dClause,",").")";

        $where = "d.department IN $dClause";
        $inv = "d.department NOT IN $dClause";
        if ($upc != "") {
            $upc = BarcodeLib::padUPC($upc);
            $where = "d.upc = ?";
            $inv = "d.upc <> ?";
            $dArgs = array($upc);
        }

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $filter = "";
        $fArgs = array();
        if (is_array($filters)){
            $fClause = "";
            foreach($filters as $f){
                $fClause .= "?,";
                $fArgs[] = $f;
            }
            $fClause = "(".rtrim($fClause,",").")";
            $filter = "AND d.department IN $fClause";
        }

        $query = $dbc->prepare_statement("CREATE TABLE groupingTemp (tdate varchar(11), emp_no int, register_no int, trans_no int)");
        $dbc->exec_statement($query);

        $dateConvertStr = ($FANNIE_SERVER_DBMS=='MSSQL')?'convert(char(11),d.tdate,110)':'convert(date(d.tdate),char)';

        $loadQ = $dbc->prepare_statement("INSERT INTO groupingTemp
            SELECT $dateConvertStr as tdate,
            emp_no,register_no,trans_no FROM $dlog AS d
            WHERE $where AND tdate BETWEEN ? AND ?
            GROUP BY $dateConvertStr, emp_no,register_no,trans_no");
        $dArgs[] = $date1.' 00:00:00';
        $dArgs[] = $date2.' 23:59:59';
        $dbc->exec_statement($loadQ,$dArgs);

        $dataQ = $dbc->prepare_statement("
            SELECT d.upc,
                p.description,
                t.dept_no,
                t.dept_name,
                SUM(d.quantity) AS quantity
            FROM $dlog AS d 
                INNER JOIN groupingTemp AS g ON 
                    $dateConvertStr = g.tdate
                    AND g.emp_no = d.emp_no
                    AND g.register_no = d.register_no
                    AND g.trans_no = d.trans_no "
                . DTrans::joinProducts('d', 'p')
                . DTrans::joinDepartments('d', 't') . "
            WHERE $inv 
                AND trans_type IN ('I','D')
                AND d.tdate BETWEEN ? AND ?
                AND d.trans_status=''
                $filter
            GROUP BY d.upc,
                p.description,
                t.dept_no,
                t.dept_name
            ORDER BY SUM(d.quantity) DESC");
        foreach($fArgs as $f) $dArgs[] = $f;
        $dataR = $dbc->exec_statement($dataQ,$dArgs);

        $data = array();
        while($dataW = $dbc->fetch_row($dataR)){
            $record = array($dataW['upc'],
                            $dataW['description'],
                            $dataW['dept_no'].' '.$dataW['dept_name'],
                            sprintf('%.2f',$dataW['quantity']));
            $data[] = $record;
        }

        $drop = $dbc->prepare_statement("DROP TABLE groupingTemp");
        $dbc->exec_statement($drop);

        return $data;
    }

    public function report_description_content()
    {
        $ret = array();
        $line = 'Corresponding sales for: ';
        if (FormLib::get('upc') === '') {
            $line .= 'departments ';
            foreach(FormLib::get('depts') as $d) {
                $line .= $d.', ';
            }
            $line = substr($line, 0, strlen($line)-1);
        } else {
            $line .= 'UPC '.FormLib::get('upc');
        }
        $ret[] = $line;

        if (count(FormLib::get('filters', array())) > 0) {
            $line = 'Filtered to departments ';
            foreach(FormLib::get('filters') as $d) {
                $line .= $d.', ';
            }
            $line = substr($line, 0, strlen($line)-1);
            $ret[] = $line;
        }

        return $ret;
    }

    public function css_content()
    {
        if ($this->content_function != 'form_content') {
            return '';
        }

        return '
            #inputset2 {
                display: none;
            }
        ';
    }

    public function javascript_content()
    {
        if ($this->content_function != 'form_content') {
            return '';
        }

        ob_start();
        ?>
function flipover(opt){
    if (opt == 'UPC'){
        document.getElementById('inputset1').style.display='none';
        document.getElementById('inputset2').style.display='block';
        document.forms[0].dept1.value='';
        document.forms[0].dept2.value='';
    }
    else {
        document.getElementById('inputset2').style.display='none';
        document.getElementById('inputset1').style.display='block';
        document.forms[0].upc.value='';
    }
}
        <?php
        return ob_get_clean();
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptR = $dbc->exec_statement($deptQ);
        $depts = array();
        while ($deptW = $dbc->fetch_array($deptR)){
            $depts[$deptW[0]] = $deptW[1];
        }

        ob_start();
        ?>
<form action="CorrelatedMovementReport.php" method=post>

<select onchange="flipover(this.value);">
<option>Department</option>
<option>UPC</option>
</select>

<table border=0 cellspacing=5 cellpadding=3>
<tr>
    <td rowspan="2" valign=middle>
    <div id="inputset1">
    <b>Department(s)</b><br />
    <select size=7 multiple name=depts[]>
    <?php 
    foreach($depts as $no=>$name)
        echo "<option value=$no>$no $name</option>";    
    ?>
    </select>
    </div>
    <div id="inputset2">
    <b>UPC</b>: <input type=text size=13 name=upc />
    </div>
    </td>
    <th>Start date</th>
    <td><input type="text" id="date1" name="date1" /></td>
</tr>
<tr>
    <th>End date</th>
    <td><input type="text" id="date2" name="date2" /></td>
</tr>
</table>
<hr />
<table border=0 cellspacing=5 cellpadding=3>
<tr>
    <td colspan="2"><b>Result Filter</b> (optional)</td>
</tr>
<tr>
    <td rowspan="2" valign=middle>
    <select size=7 multiple name=filters[]>
    <?php 
    foreach($depts as $no=>$name)
        echo "<option value=$no>$no $name</option>";    
    ?>
    </select>
    </td>
    <td colspan="2">
        <?php echo FormLib::date_range_picker(); ?>
    </td>
</tr>
</table>
<hr />
<input type=submit name=submit value="Run Report" />
<input type=checkbox name=excel value="xls" /> Excel
</form>
        <?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

        return ob_get_clean(); 
    }

}

FannieDispatch::conditionalExec();

?>
