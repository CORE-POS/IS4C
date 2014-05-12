<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class DeptSettingsReport extends FannieReportPage 
{
    public $description = '[Department Settings] provides a quick overview of current department settings for margin, tax, and foodstamp status.';

    protected $report_headers = array('Dept #', 'Dept Name', 'Sales Code', 'Margin', 'Tax', 'FS');
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
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $super = FormLib::get('submit')=="Report by Super Department" ? 1 : 0;

        $join = "";
        $where = "";
        $args = array();
        if ($super == 1){
            $superID = FormLib::get('superdept');
            $join = "LEFT JOIN superdepts AS s ON d.dept_no=s.dept_ID";
            $where = "s.superID = ?";
            $args[] = $superID;
        }
        else {
            $d1 = FormLib::get('dept1');
            $d2 = FormLib::get('dept2');
            $join = "";
            $where = "d.dept_no BETWEEN ? AND ?";
            $args = array($d1,$d2);
        }

        $query = $dbc->prepare_statement("SELECT d.dept_no,d.dept_name,d.salesCode,d.margin,
            CASE WHEN d.dept_tax=0 THEN 'NoTax' ELSE t.description END as tax,
            CASE WHEN d.dept_fs=1 THEN 'Yes' ELSE 'No' END as fs
            FROM departments AS d LEFT JOIN taxrates AS t
            ON d.dept_tax = t.id 
            $join
            WHERE $where
            ORDER BY d.dept_no");
        $result = $dbc->exec_statement($query,$args);
        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                    $row[0],
                    (isset($_REQUEST['excel']))?$row[1]:"<a href=\"{$FANNIE_URL}item/departments/DepartmentEditor.php?did=$row[0]\">$row[1]</a>",
                    $row[2],
                    sprintf('%.2f%%',$row[3]*100),
                    $row[4],
                    $row[5]
            );
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $opts = "";
        $prep = $dbc->prepare_statement("SELECT superID,super_name fROM superDeptNames ORDER BY super_name");
        $resp = $dbc->exec_statement($prep);
        while($row = $dbc->fetch_row($resp)) {
            $opts .= "<option value=$row[0]>$row[1]</option>";
        }

        $depts = "";
        $prep = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
        $resp = $dbc->exec_statement($prep);
        $d1 = false;
        while($row = $dbc->fetch_row($resp)) {
            $depts .= "<option value=$row[0]>$row[0] $row[1]</option>";
            if ($d1 === false) $d1 = $row[0];
        }

        ob_start();
        ?>
<form action=DeptSettingsReport.php method=get>
<fieldset title="Choose a super department">
<select name="superdept"><?php echo $opts; ?></select><p />
<input type=submit name=submit value="Report by Super Department" />
</fieldset>
<p />
<fieldset title="Choose a department range">
<input type=text size=4 name=dept1 id=dept1 value="<?php echo $d1; ?>" />
<select onchange="$('#dept1').val(this.value)">
<?php echo $depts; ?></select>
<p />
<input type=text size=4 name=dept2 id=dept2 value="<?php echo $d1; ?>" />
<select onchange="$('#dept2').val(this.value)">
<?php echo $depts; ?></select>
<p />
<input type=submit name=submit value="Report by Department Range" />
</fieldset>
</form>
<?php
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

?>
