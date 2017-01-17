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

use COREPOS\Fannie\API\lib\Store;

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

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Dept#', 'Department', '', '');

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
            $model->store_id(Store::getIdByIp());
            $model->delete();

            echo 'Deleted';
            return false;
        } elseif (FormLib::get('deactivate') !== '') {
            $upc = BarcodeLib::padUPC(FormLib::get('deactivate'));
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $model = new ProductsModel($dbc);
            $model->upc($upc);
            $model->store_id(Store::getIdByIp());
            $model->inUse(0);
            $model->save();

            echo 'Deactivated';
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
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = FormLib::get_form_value('deptStart',0);
        $deptEnd = FormLib::get_form_value('deptEnd',0);
        $deptMulti = FormLib::get('departments', array());
        $subs = FormLib::get('subdepts', array());
        $storeID = FormLib::get('store');

        $tempName = "TempNoMove";
        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $tempQ = $dbc->prepare("CREATE TABLE $tempName (upc varchar(13))");
        $dbc->execute($tempQ);

        $insQ = $dbc->prepare("
            INSERT INTO $tempName
            SELECT d.upc FROM $dlog AS d
            WHERE 
                d.tdate BETWEEN ? AND ?
                AND d.trans_type='I'  
                AND " . DTrans::isStoreID($storeID, 'd') . "
            GROUP BY d.upc");
        $dbc->execute($insQ, array($date1.' 00:00:00',$date2.' 23:59:59',$storeID));

        $where = ' 1=1 ';
        $buyer = FormLib::get('super');
        $args = array();
        if ($buyer !== '') {
            if ($buyer == -2) {
                $where .= ' AND s.superID != 0 ';
            } elseif ($buyer != -1) {
                $where .= ' AND s.superID=? ';
                $args[] = $buyer;
            }
        }
        if ($buyer != -1) {
            list($conditional, $args) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $args, 'p');
            $where .= $conditional;
        }
        if (count($subs) > 0) {
            list($inStr, $args) = $dbc->safeInClause($subs, $args);
            $where .= " AND p.subdept IN ($inStr) ";
        }

        $query = "
            SELECT p.upc,
                p.brand,
                p.description,
                d.dept_no,
                d.dept_name 
            FROM products AS p 
                LEFT JOIN departments AS d ON p.department=d.dept_no ";
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON p.department=s.dept_ID ';
        } elseif ($buyer !== '' && $buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID ';
        }
        $query .= " WHERE p.upc NOT IN (
                SELECT upc FROM $tempName
                )
                AND $where
                AND p.inUse=1
                AND " . DTrans::isStoreID($storeID, 'p') . "
            ORDER BY p.upc";
        $prep = $dbc->prepare($query);
        $args[] = $storeID;
        $result = $dbc->execute($prep,$args);

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $ret[] = $this->rowToRecord($row);
        }

        $drop = $dbc->prepare("DROP TABLE $tempName");
        $dbc->execute($drop);
        return $ret;
    }

    private function rowToRecord($row)
    {
        $record = array();
        $record[] = $row[0];
        $record[] = $row[1];
        $record[] = $row[2];
        $record[] = $row[3];
        $record[] = $row[4];
        if ($this->report_format == 'html') {
            $record[] = sprintf('<a href="" id="del%s"
                    onclick="nonMovement.backgroundDeactivate(\'%s\');return false;">
                    Deactivate this item</a>',$row[0],$row[0]);
        } else {
            $record[] = '';
        }
        if ($this->report_format == 'html'){
            $record[] = sprintf('<a href="" id="del%s"
                    onclick="nonMovement.backgroundDelete(\'%s\',\'%s\');return false;">
                    Delete this item</a>',$row[0],$row[0],$row[1]);
        } else {
            $record[] = '';
        }

        return $record;
    }
    
    function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $deptsQ = $dbc->prepare("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->execute($deptsQ);
        $deptsList = "";
        while ($deptsW = $dbc->fetchRow($deptsR))
            $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
        ob_start();
        $stores = FormLib::storePicker();
?>
<form method="get" action="NonMovementReport.php" class="form-horizontal">
    <div class="col-sm-6">
        <?php echo FormLib::standardDepartmentFields(); ?>
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
            <button type=submit name=submit value="Submit" class="btn btn-default btn-core">Submit</button>
            <button type=reset name=reset class="btn btn-default btn-reset"
                onclick="$('#super-id').val('').trigger('change');">Start Over</button>
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
            <label class="control-label col-sm-4">Store</label>
            <div class="col-sm-8">
                <?php echo $stores['html']; ?>
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>                            
        </div>
    </div>
</form>
<?php
        return ob_get_clean();
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

    public function unitTest($phpunit)
    {
        $data = array('4011', 'test', 'test', 1, 'test');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

