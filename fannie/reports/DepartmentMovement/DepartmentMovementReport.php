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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DepartmentMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Department Movement";
    protected $header = "Department Movement";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Department Movement] lists sales for a department or group of departments over a given date range.';
    public $report_set = 'Movement Reports';

    /**
      Add a javascript function for the form
      This could probably be re-done in jQuery and
      just inlined directly into the form
    */
    function javascript_content()
    {
        if ($this->content_function == "form_content") {
            ob_start();
            ?>
            function swap(src,dst){
                var val = document.getElementById(src).value;
                document.getElementById(dst).value = val;
            }
            <?php
            $js = ob_get_contents();
            ob_end_clean();

            return $js;
        }
    }

    /**
      Lots of options on this report.
    */
    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::getDate('date1',date('Y-m-d'));
        $date2 = FormLib::getDate('date2',date('Y-m-d'));
        $deptStart = FormLib::get_form_value('deptStart','');
        $deptEnd = FormLib::get_form_value('deptEnd','');
        $buyer = FormLib::get_form_value('buyer','');
        $groupby = FormLib::get_form_value('sort','PLU');
        $store = FormLib::get('store', 0);
        $superP = $dbc->prepare('SELECT dept_ID FROM superdepts WHERE superID=?');

        /**
          Build a WHERE condition for later.
          Superdepartment (buyer) takes precedence over
          department and negative values have special
          meaning

          Extra lookup to write condition in terms of
          transaction.department seems to result in
          better index utilization and faster queries
        */
        $filter_condition = 't.department BETWEEN ? AND ?';
        $args = array($deptStart,$deptEnd);
        if ($buyer !== "" && $buyer > 0) {
            $superR = $dbc->execute($superP, array($buyer));
            $filter_condition = 't.department IN (';
            $args = array();
            while ($superW = $dbc->fetch_row($superR)) {
                $filter_condition .= '?,';
                $args[] = $superW['dept_ID'];
            }
            $filter_condition = substr($filter_condition, 0, strlen($filter_condition)-1) . ')';
            $filter_condition .= ' AND s.superID=?';
            $args[] = $buyer;
        } else if ($buyer !== "" && $buyer == -1) {
            $filter_condition = "1=1";
            $args = array();
        } else if ($buyer !== "" && $buyer == -2){
            $superR = $dbc->execute($superP, array(0));
            $filter_condition = 't.department NOT IN (0,';
            $args = array();
            while ($superW = $dbc->fetch_row($superR)) {
                $filter_condition .= '?,';
                $args[] = $superW['dept_ID'];
            }
            $filter_condition = substr($filter_condition, 0, strlen($filter_condition)-1) . ')';
            $filter_condition .= ' AND s.superID <> 0';
        }

        /**
         * Provide more WHERE conditions to filter irrelevant
         * transaction records, as a stop-gap until this is
         * handled more uniformly across the application.
         */
        $filter_transactions = "t.trans_status NOT IN ('D','X','Z')
            AND t.emp_no <> 9999
            AND t.register_no <> 99";
        $filter_transactions = DTrans::isValid() . ' AND ' . DTrans::isNotTesting();
        
        /**
          Select a summary table. For UPC results, per-unique-ring
          summary is needed. For date/dept/weekday results the
          per-department summary is fine (and a smaller table)
        */
        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        /**
          Build an appropriate query depending on the grouping option
        */
        $query = "";
        $superTable = ($buyer !== "" && $buyer > 0) ? 'superdepts' : 'MasterSuperDepts';
        $args[] = $date1.' 00:00:00';
        $args[] = $date2.' 23:59:59';
        $args[] = $store;
        switch($groupby) {
            case 'PLU':
                $query = "SELECT t.upc,
                      CASE WHEN p.description IS NULL THEN t.description ELSE p.description END as description, 
                      SUM(CASE WHEN trans_status IN('','0') THEN 1 WHEN trans_status='V' THEN -1 ELSE 0 END) as rings,"
                      . DTrans::sumQuantity('t')." as qty,
                      SUM(t.total) AS total,
                      d.dept_no,d.dept_name,s.superID,x.distributor
                      FROM $dlog as t "
                      . DTrans::joinProducts()
                      . DTrans::joinDepartments()
                      . "LEFT JOIN $superTable AS s ON t.department = s.dept_ID
                      LEFT JOIN prodExtra as x on t.upc = x.upc
                      WHERE $filter_condition
                      AND t.trans_type IN ('I', 'D')
                      AND tdate BETWEEN ? AND ?
                      AND $filter_transactions
                      AND " . DTrans::isStoreID($store, 't') . "
                      GROUP BY t.upc,
                          CASE WHEN p.description IS NULL THEN t.description ELSE p.description END,
                      d.dept_no,d.dept_name,s.superID,x.distributor ORDER BY SUM(t.total) DESC";
                break;
            case 'Department':
                $query =  "SELECT t.department,d.dept_name,"
                    . DTrans::sumQuantity('t')." as qty,
                    SUM(total) as Sales 
                    FROM $dlog as t "
                    . DTrans::joinDepartments()
                    . "LEFT JOIN $superTable AS s ON s.dept_ID = t.department 
                    WHERE $filter_condition
                    AND tdate BETWEEN ? AND ?
                    AND $filter_transactions
                    AND " . DTrans::isStoreID($store, 't') . "
                    GROUP BY t.department,d.dept_name ORDER BY SUM(total) DESC";
                break;
            case 'Date':
                $query =  "SELECT year(tdate),month(tdate),day(tdate),"
                    . DTrans::sumQuantity('t')." as qty,
                    SUM(total) as Sales ,
                    MAX(" . $dbc->dayofweek('tdate') . ") AS dow
                    FROM $dlog as t "
                    . DTrans::joinDepartments()
                    . "LEFT JOIN $superTable AS s ON s.dept_ID = t.department
                    WHERE $filter_condition
                    AND tdate BETWEEN ? AND ?
                    AND $filter_transactions
                    AND " . DTrans::isStoreID($store, 't') . "
                    GROUP BY year(tdate),month(tdate),day(tdate) 
                    ORDER BY year(tdate),month(tdate),day(tdate)";
                break;
            case 'Weekday':
                $cols = $dbc->dayofweek("tdate").",CASE 
                    WHEN ".$dbc->dayofweek("tdate")."=1 THEN 'Sun'
                    WHEN ".$dbc->dayofweek("tdate")."=2 THEN 'Mon'
                    WHEN ".$dbc->dayofweek("tdate")."=3 THEN 'Tue'
                    WHEN ".$dbc->dayofweek("tdate")."=4 THEN 'Wed'
                    WHEN ".$dbc->dayofweek("tdate")."=5 THEN 'Thu'
                    WHEN ".$dbc->dayofweek("tdate")."=6 THEN 'Fri'
                    WHEN ".$dbc->dayofweek("tdate")."=7 THEN 'Sat'
                    ELSE 'Err' END";
                $query =  "SELECT $cols,"
                    . DTrans::sumQuantity('t') . " as qty,
                    SUM(total) as Sales 
                    FROM $dlog as t "
                    . DTrans::joinDepartments()
                    . "LEFT JOIN $superTable AS s ON s.dept_ID = t.department 
                    WHERE $filter_condition
                    AND tdate BETWEEN ? AND ?
                    AND $filter_transactions
                    AND " . DTrans::isStoreID($store, 't') . "
                    GROUP BY $cols
                    ORDER BY ".$dbc->dayofweek('tdate');
                break;
        }

        /**
          Copy the results into an array. Date requires a
          special case to combine year, month, and day into
          a single field
        */
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep,$args);
        $ret = array();
        while ($row = $dbc->fetch_array($result)) {
            $record = array();
            if ($groupby == "Date") {
                $record[] = $row[1]."/".$row[2]."/".$row[0];
                $record[] = date('l', strtotime($record[0]));
                $record[] = $row[3];
                $record[] = $row[4];
            } else {
                for($i=0;$i<$dbc->num_fields($result);$i++) {
                    $record[] .= $row[$i];
                }
            }
            $ret[] = $record;
        }

        return $ret;
    }
    
    /**
      Sum the quantity and total columns for a footer,
      but also set up headers and sorting.

      The number of columns varies depending on which
      data grouping the user selected. 
    */
    function calculate_footers($data)
    {
        // no data; don't bother
        if (empty($data)) {
            return array();
        }

        /**
          Use the width of the first record to determine
          how the data is grouped
        */
        switch(count($data[0])) {
            case 9:
                $this->report_headers = array('UPC','Description','Rings','Qty','$',
                    'Dept#','Department','Subdept','Vendor');
                $this->sort_column = 4;
                $this->sort_direction = 1;
                $sumQty = 0.0;
                $sumSales = 0.0;
                $sumRings = 0.0;
                foreach($data as $row) {
                    $sumRings += $row[2];
                    $sumQty += $row[3];
                    $sumSales += $row[4];
                }

                return array('Total',null,$sumRings,$sumQty,$sumSales,'',null,null,null);
                break;
            case 4:
                /**
                  The Department and Weekday datasets are both four
                  columns wide so I have to resort to form parameters
                */
                if (FormLib::get_form_value('sort')=='Weekday') {
                    $this->report_headers = array('Day','Day','Qty','$');
                    $this->sort_column = 0;
                    $this->sort_direction = 0;
                } elseif (FormLib::get_form_value('sort')=='Date') {
                    $this->report_headers = array('Date','Day','Qty','$');
                    $this->sort_column = 0;
                    $this->sort_direction = 0;
                } else {
                    $this->report_headers = array('Dept#','Department','Qty','$');
                    $this->sort_column = 3;
                    $this->sort_direction = 1;
                }
                $sumQty = 0.0;
                $sumSales = 0.0;
                foreach($data as $row) {
                    $sumQty += $row[2];
                    $sumSales += $row[3];
                }

                return array('Total',null,$sumQty,$sumSales);
                break;
        }
    }

    function report_description_content()
    {
        $ret = array();
        $ret[] = "Summed by ".FormLib::get_form_value('sort','');
        $buyer = FormLib::get_form_value('buyer','');
        if ($buyer === '0') {
            $ret[] = "Department ".FormLib::get_form_value('deptStart','').' to '.FormLib::get_form_value('deptEnd','');
        }

        return $ret;
    }

    function form_content()
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
?>
<div id=main>    
<form method = "get" action="DepartmentMovementReport.php">
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
            <td><input type=checkbox name=excel id=excel value=1>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php $ret=FormLib::storePicker();echo $ret['html']; ?>
            </td>
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
            <td><b>Sum movement by?</b></td>
            <td> <select name="sort" size="1">
            <option>PLU</option>
            <option>Date</option>
            <option>Department</option>
            <option>Weekday</option>
            </select> 
            </td>
            <td colspan=2 rowspan=2>
            <?php echo FormLib::date_range_picker(); ?>                            
            </td>
        </tr>
        <tr> 
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    </table>
</form>
<?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
    }
}

FannieDispatch::conditionalExec(false);

?>
