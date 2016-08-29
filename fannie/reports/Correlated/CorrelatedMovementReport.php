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
    public $themed = true;

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        // creates a temporary table so requesting a writable connection
        // does make sense here
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $depts = FormLib::get('depts', array());
        $upc = FormLib::get('upc');
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $filters = FormLib::get('filters', array());

        list($dClause, $dArgs) = $dbc->safeInClause($depts);
        $where = "d.department IN ($dClause)";
        $inv = "d.department NOT IN ($dClause)";
        if ($upc != "") {
            $upc = BarcodeLib::padUPC($upc);
            $where = "d.upc = ?";
            $inv = "d.upc <> ?";
            $dArgs = array($upc);
        }

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $filter = "";
        $fArgs = array();
        if (is_array($filters) && count($filters) > 0) {
            $fClause = "";
            foreach($filters as $f){
                $fClause .= "?,";
                $fArgs[] = $f;
            }
            $fClause = "(".rtrim($fClause,",").")";
            $filter = "AND d.department IN $fClause";
        }

        $query = $dbc->prepare("CREATE TABLE groupingTemp (tdate varchar(11), emp_no int, register_no int, trans_no int)");
        $dbc->execute($query);

        $dateConvertStr = ($FANNIE_SERVER_DBMS=='MSSQL')?'convert(char(11),d.tdate,110)':'convert(date(d.tdate),char)';

        $loadQ = $dbc->prepare("INSERT INTO groupingTemp
            SELECT $dateConvertStr as tdate,
            emp_no,register_no,trans_no FROM $dlog AS d
            WHERE $where AND tdate BETWEEN ? AND ?
            GROUP BY $dateConvertStr, emp_no,register_no,trans_no");
        $dArgs[] = $date1.' 00:00:00';
        $dArgs[] = $date2.' 23:59:59';
        $dbc->execute($loadQ,$dArgs);

        $dataQ = $dbc->prepare("
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
        $dataR = $dbc->execute($dataQ,$dArgs);

        $data = array();
        while($dataW = $dbc->fetch_row($dataR)){
            $record = array($dataW['upc'],
                            $dataW['description'],
                            $dataW['dept_no'].' '.$dataW['dept_name'],
                            sprintf('%.2f',$dataW['quantity']));
            $data[] = $record;
        }

        $drop = $dbc->prepare("DROP TABLE groupingTemp");
        $dbc->execute($drop);

        return $data;
    }

    public function report_description_content()
    {
        $ret = array();
        $line = 'Corresponding sales for: ';
        if (FormLib::get('upc') === '') {
            $line .= 'departments ';
            foreach (FormLib::get('depts', array()) as $d) {
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
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $deptQ = $dbc->prepare("select dept_no,dept_name from departments order by dept_no");
        $deptR = $dbc->execute($deptQ);
        $depts = array();
        while ($deptW = $dbc->fetchRow($deptR)){
            $depts[$deptW[0]] = $deptW[1];
        }

        ob_start();
        ?>
<form action="CorrelatedMovementReport.php" method="get">
<div class="row">
    <div class="col-sm-6">
        <ul class="nav nav-tabs" role="tablist">
            <li class="active"><a href="#department-tab" role="tab"
                onclick="$(this).tab('show'); $('.tab-pane :input').prop('disabled', true); 
                $('.tab-pane.active :input').prop('disabled', false); return false;">Department</a></li>
            <li><a href="#upc-tab" role="tab" 
                onclick="$(this).tab('show'); $('.tab-pane :input').prop('disabled', true); 
                $('.tab-pane.active :input').prop('disabled', false); return false;">UPC</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="department-tab">
                <label class="control-label">Department(s)</label>
                <select size=7 multiple name=depts[] class="form-control">
                <?php 
                foreach ($depts as $no=>$name) {
                    echo "<option value=$no>$no $name</option>";    
                }
                ?>
                </select>
            </div>
            <div class="tab-pane" id="upc-tab">
                <label class="control-label">UPC</label>
                <input type=text name=upc class="form-control" disabled />
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <label class="control-label">Start date</label>
        <input type="text" id="date1" name="date1" class="form-control date-field" />
        <label class="control-label">End date</label>
        <input type="text" id="date2" name="date2" class="form-control date-field" />
    </div>
</div>
<hr />
<div class="row">
    <div class="col-sm-6">
        <label class="control-label">Result Filter (optional)</label>
        <select size=7 multiple name=filters[] class="form-control">
        <?php 
        foreach ($depts as $no=>$name) {
            echo "<option value=$no>$no $name</option>";    
        }
        ?>
        </select>
    </div>
    <div class="col-sm-6">
        <?php echo FormLib::date_range_picker(); ?>
    </div>
</div>
<hr />
<p>
    <button type=submit name=submit value="Run Report" class="btn btn-default">Run Report</button>
    <label><input type=checkbox name=excel value="xls" /> Excel</label>
</p>
</form>
        <?php

        return ob_get_clean(); 
    }
    
    public function helpContent()
    {
        return '<p>Correlated Movement shows item sales from a set
            of transactions. The top department(s) or UPC plus
            date range find the set of transactions.</p>
            <p>The report lists all items in those transations.
            For example, you could find every transaction where
            a customer bought a cup of coffee. This report will then
            list every <em>other</em> item that those particular
            customers purchased with their coffee.</p>
            <p>The optional result filter trims down that list of
            other items. Continuing the example, you might apply a 
            filter to see which bakery items a customer purchased
            with their coffee.</p>'; 
    }
}

FannieDispatch::conditionalExec();

