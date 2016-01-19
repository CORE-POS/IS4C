<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class DeptSettingsReport extends FannieReportPage 
{
    public $description = '[Department Settings] provides a quick overview of current department settings for margin, tax, and foodstamp status.';
    public $themed = true;
    public $report_set = 'Operational Data';

    protected $report_headers = array('Dept #', 'Dept Name', 'Super', 'Sales Code', 'Margin', 'Tax', 'FS');
    protected $title = "Fannie : Department Settings";
    protected $header = "Department Settings";
    protected $required_fields = array('submit');

    public function readinessCheck()
    {
        global $FANNIE_OP_DB;
        // Check added 22Jan14
        if (!$this->tableHasColumnReadinessCheck($FANNIE_OP_DB, 'departments', 'margin')) {
            return false;
        } else if (!$this->tableHasColumnReadinessCheck($FANNIE_OP_DB, 'departments', 'salesCode')) {
            return false;
        } 

        return true;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $FANNIE_URL = $this->config->get('URL');

        $super = $this->form->submit =="by_sd" ? 1 : 0;

        $join = "";
        $where = "";
        $args = array();
        if ($super == 1){
            $superID = FormLib::get('superdept');
            $join = "LEFT JOIN superdepts AS s ON d.dept_no=s.dept_ID
                LEFT JOIN superDeptNames AS m ON s.superID=m.superID ";
            $where = "s.superID = ?";
            $args[] = $superID;
        } else {
            $d1 = FormLib::get('dept1');
            $d2 = FormLib::get('dept2');
            $join = " LEFT JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID";
            $where = "d.dept_no BETWEEN ? AND ?";
            $args = array($d1,$d2);
        }

        $query = $dbc->prepare("SELECT d.dept_no,d.dept_name,d.salesCode,d.margin,
            CASE WHEN d.dept_tax=0 THEN 'NoTax' ELSE t.description END as tax,
            CASE WHEN d.dept_fs=1 THEN 'Yes' ELSE 'No' END as fs,
            m.super_name
            FROM departments AS d 
                LEFT JOIN taxrates AS t ON d.dept_tax = t.id 
            $join
            WHERE $where
            ORDER BY d.dept_no");
        $result = $dbc->execute($query,$args);
        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $record = array(
            $row[0],
            (isset($_REQUEST['excel']))?$row[1]:"<a href=\"" . $this->config->get('URL') . "item/departments/DepartmentEditor.php?did=$row[0]\">$row[1]</a>",
            $row['super_name'],
            $row[2],
            sprintf('%.2f%%',$row[3]*100),
            $row[4],
            $row[5],
        );
        if (empty($row['super_name'])) {
            $record['meta'] = FannieReportPage::META_COLOR;
            $record['meta_background'] = '#ff9999';
        }

        return $record;
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $opts = "";
        $prep = $dbc->prepare("SELECT superID,super_name fROM superDeptNames ORDER BY super_name");
        $resp = $dbc->execute($prep);
        while($row = $dbc->fetch_row($resp)) {
            $opts .= "<option value=$row[0]>$row[1]</option>";
        }

        $depts = "";
        $prep = $dbc->prepare("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
        $resp = $dbc->execute($prep);
        $d1 = false;
        while($row = $dbc->fetch_row($resp)) {
            $depts .= "<option value=$row[0]>$row[0] $row[1]</option>";
            if ($d1 === false) $d1 = $row[0];
        }

        ob_start();
        ?>
<form action=DeptSettingsReport.php method=get>
<div class="panel panel-default">
    <div class="panel-heading">
        Report by Super Department
    </div>
    <div class="panel-body">
        <p>
            <select name="superdept" class="form-control"><?php echo $opts; ?></select>
        </p>
        <p>
            <button type=submit name=submit value="by_sd" class="btn btn-default">Get Report</button>
        </p>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">
        Report by Department Range
    </div>
    <div class="panel-body">
        <p>
            <div class="col-sm-2">
                <input type=text name=dept1 id=dept1 value="<?php echo $d1; ?>" class="form-control" />
            </div>
            <div class="col-sm-10">
                <select onchange="$('#dept1').val(this.value)" class="form-control">
                <?php echo $depts; ?>
                </select>
            </div>
        </p>
        <p>
            <div class="col-sm-2">
                <input type=text name=dept2 id=dept2 value="<?php echo $d1; ?>" class="form-control" />
            </div>
            <div class="col-sm-10">
                <select onchange="$('#dept2').val(this.value)" class="form-control">
                <?php echo $depts; ?>
                </select>
            </div>
        </p>
        <br />
        <p>
            <button type=submit name=submit value="by_dr" class="btn btn-default">Get Report</button>
        </p>
    </div>
</div>
</form>
<?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This is just a quick list of current margin, tax,
            and foodstamp settings for a set of POS departments.</p>';
    }

    public function unitTest($phpunit)
    {
        $data = array(0=>1, 1=>'TEST', 2=>100, 3=>0.5, 4=>0, 5=>1, 'super_name'=>'test');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

