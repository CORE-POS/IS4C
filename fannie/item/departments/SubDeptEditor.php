<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SubDeptEditor extends FanniePage 
{
    protected $title = "Fannie : Manage Subdepartments";
    protected $header = "Manage Subdepartments";

    protected $must_authenticate = true;
    protected $auth_classes = array('departments', 'admin');

    public $description = '[Subdepartment Editor] manges POS sub departments.';
    public $themed = true;

    public function preprocess()
    {
        /* allow ajax calls */
        if (FormLib::get_form_value('action') !== '') {
            $this->ajax_response(FormLib::get_form_value('action'));
            return false;
        }
    
        return true;
    }

    private function ajax_response($action)
    {
        switch ($action) {
            case 'addSub':
                $this->add_sub_dept(FormLib::get('name'),FormLib::get('did'));
                echo $this->get_subs_as_options(FormLib::get('did'));
                break;
            case 'deleteSub':
                $this->delete_sub_depts(FormLib::get('sid',array()));
                echo $this->get_subs_as_options(FormLib::get('did'));
                break;
            case 'showSubsForDept':
                echo $this->get_subs_as_options(FormLib::get('did'));
                break;
            default:
                echo 'Bad request';
                break;
        }
    }

    private function add_sub_dept($name, $deptID)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $p = $dbc->prepare("SELECT max(subdept_no) FROM subdepts");
        $res = $dbc->execute($p);
        $sid = 1;
        if ($dbc->num_rows($res) > 0) {
            $row = $dbc->fetch_row($res);
            $tmp = $row[0];
            if (is_numeric($tmp)) {
                $sid = $tmp+1;
            }
        }

        $ins = $dbc->prepare('INSERT INTO subdepts VALUES (?,?,?)');  
        $dbc->execute($ins,array($sid, $name, $deptID));
    }

    private function delete_sub_depts($ids)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!is_array($ids)) {
            $ids = array();
        }
        $delP = $dbc->prepare('DELETE FROM subdepts WHERE subdept_no=?');
        foreach ($ids as $id) {
            $dbc->execute($delP, array($id));
        }
    }

    private function get_subs_as_options($deptID)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $p = $dbc->prepare("SELECT subdept_no,subdept_name FROM subdepts
                WHERE dept_ID=? ORDER BY subdept_name");
        $r = $dbc->execute($p,array($deptID));
        
        $ret = '';
        while ($w = $dbc->fetch_row($r)) {
            $ret .= sprintf('<option value="%d">%s</option>',
                    $w['subdept_no'],$w['subdept_name']);
        }

        return $ret;
    }

    public function body_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $superQ = $dbc->prepare("SELECT d.dept_no,dept_name FROM departments as d
            ORDER BY d.dept_no");
        $superR = $dbc->execute($superQ);
        $opts = "";
        $firstID = False;
        $firstName = "";
        while ($superW = $dbc->fetch_row($superR)) {
            $opts .= "<option value=$superW[0]>$superW[0] $superW[1]</option>";
            if ($firstID === false) {
                $firstID = $superW[0];
                $firstName = $superW[1];
            }
        }

        ob_start();
        ?>
        <div id="alertarea"></div>
        <label class="control-label">Choose a department</label>
        <select class="form-control" id=deptselect onchange="subDept.show(this.value);">
        <?php echo $opts ?>
        </select>
        <hr />
        <div class="col-sm-3">
            <p id="subdiv">
                <label class="control-label" id=subname></label>
                <select class="form-control" id=subselect size=12 multiple></select>
            </p>
        </div>
        <div id="formdiv" class="col-sm-3">
            <label class="control-label">Add Sub Department</label>
            <input type=text class="form-control" id=newname placeholder="New Sub Department Name" /> 
            <p>
                <button type=submit value=Add onclick="subDept.add(); return false;"
                    class="btn btn-default">Add</button>
            </p>
            <p>
                <button type=submit value="Delete Selected" onclick="subDept.del(); return false;"
                    class="btn btn-default">Delete Selected</button>
            </p>
        </div>
        <?php

        $this->add_script('sub.js');
        $this->add_onload_command('showSubsForDept('.$firstID.');');
        
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>A department may contain multiple sub departments.
            This layer of categorization is strictly for reporting and
            organization.</p>
            <p>This field is not supported much in the current release
            although local customizations or plugins may differ.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}

FannieDispatch::conditionalExec();

