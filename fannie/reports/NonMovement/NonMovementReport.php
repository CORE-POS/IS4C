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

class NonMovementReport extends FannieReportPage {

    protected $title = "Fannie: Non-Movement";
    protected $header = "Non-Movement Report";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Non-Movement] shows items in a department or group of departments that have no sales over a given date range. This is mostly for finding discontinued or mis-entered products.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    function preprocess()
    {
        global $FANNIE_OP_DB;

        // custom: can delete items from report results
        if (isset($_REQUEST['deleteItem'])) {
            $upc = FormLib::get_form_value('deleteItem','');
            if (is_numeric($upc)) {
                $upc = BarcodeLib::padUPC($upc);
            }
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $model = new ProductsModel($dbc);
            $model->upc($upc);
            $model->delete();

            echo 'Deleted';
            exit;
        }

        $ret = parent::preprocess();
        // custom: needs extra JS for delete option
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->add_script("../../src/javascript/jquery.js");
            $this->add_script('delete.js');
        }

        return $ret;
    }

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $dept1 = FormLib::get_form_value('deptStart',0);
        $dept2 = FormLib::get_form_value('deptEnd',0);

        $tempName = "TempNoMove";
        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $tempQ = $dbc->prepare_statement("CREATE TABLE $tempName (upc varchar(13))");
        $dbc->exec_statement($tempQ);

        $insQ = $dbc->prepare_statement("INSERT INTO $tempName
            SELECT d.upc FROM $dlog AS d
            WHERE 
            d.tdate BETWEEN ? AND ?
            GROUP BY d.upc");
        $dbc->exec_statement($insQ,array($date1.' 00:00:00',$date2.' 23:59:59'));

        $query = $dbc->prepare_statement("SELECT p.upc,p.description,d.dept_no,
            d.dept_name FROM products AS p LEFT JOIN
            departments AS d ON p.department=d.dept_no
            WHERE p.upc NOT IN (select upc FROM $tempName)
            AND p.department
            BETWEEN ? AND ?
            ORDER BY p.upc");
        $result = $dbc->exec_statement($query,array($dept1,$dept2));

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetch_array($result)){
            $record = array();
            $record[] = $row[0];
            $record[] = $row[1];
            $record[] = $row[2];
            $record[] = $row[3];
            if ($this->report_format == 'html'){
                $record[] = sprintf('<a href="" id="del%s"
                        onclick="backgroundDelete(\'%s\',\'%s\');return false;">
                        Delete this item</a>',$row[0],$row[0],$row[1]);
            }
            else
                $record[] = '';
            $ret[] = $record;
        }

        $drop = $dbc->prepare_statement("DROP TABLE $tempName");
        $dbc->exec_statement($drop);
        return $ret;
    }
    
    function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->exec_statement($deptsQ);
        $deptsList = "";
        while ($deptsW = $dbc->fetch_array($deptsR))
            $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
?>
<form method="get" action="NonMovementReport.php" class="form-horizontal">
    <div class="col-sm-5">
        <div class="form-group">
            <label class="control-label col-sm-4">Department Start</label>
            <div class="col-sm-6">
            <select id=deptStartSel onchange="$('#deptStart').val(this.value);" class="form-control col-sm-6">
                <?php echo $deptsList ?>
            </select>
            </div>
            <div class="col-sm-2">
            <input type=number name=deptStart id=deptStart size=5 value=1 class="form-control col-sm-2" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Department End</label>
            <div class="col-sm-6">
                <select id=deptEndSel onchange="$('#deptEnd').val(this.value);" class="form-control">
                    <?php echo $deptsList ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type=number name=deptEnd id=deptEnd size=5 value=1 class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">
                Excel
                <input type=checkbox name=excel value=xls id="excel" />
            </label>
            <label class="control-label col-sm-4">
                Netted
                <input type=checkbox name=netted id="netted" />
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
    }

    public function helpContent()
    {
        return '<p>This report finds items that have not sold
            during the date range. It also provides an option
            to delete items.</p>
            <p><em>Netted</em> means total sales is not zero.
            This would exclude items that are rung in then
            voided.</p>';
    }
}

FannieDispatch::conditionalExec();

