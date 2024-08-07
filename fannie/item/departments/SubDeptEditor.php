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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
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
            case 'getSettings':
                $ret = array('tax' => 0, 'fs' => 0);
                $prep = $this->connection->prepare("SELECT subdept_tax, subdept_fs FROM subdepts WHERE subdept_no=?");
                $res = $this->connection->execute($prep, array(FormLib::get('sid')));
                while ($row = $this->connection->fetchRow($res)) {
                    $ret['tax'] = $row['subdept_tax'];
                    $ret['fs'] = $row['subdept_fs'];
                }
                echo json_encode($ret);
                break;
            case 'saveSettings':
                $prep = $this->connection->prepare("UPDATE subdepts SET subdept_tax=?, subdept_fs=? WHERE subdept_no=?");
                $res = $this->connection->execute($prep, array(
                    FormLib::get('tax'),
                    FormLib::get('fs'),
                    FormLib::get('sid'),
                ));
                echo 'Done';
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

        $ins = $dbc->prepare('INSERT INTO subdepts (subdept_no, subdept_name, dept_ID) VALUES (?,?,?)');  
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
        
        $ret = array();
        while ($w = $dbc->fetch_row($r)) {
            $ret[] = array(
                'id' => $w['subdept_no'],
                'name' => $w['subdept_name'],
            );
        }
        $ret = json_encode($ret);

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

        $taxR = $dbc->query("SELECT id, description FROM taxrates");
        $taxOpts = '<option value="0">No Tax</option>';
        while ($taxW = $dbc->fetchRow($taxR)) {
            $taxOpts .= sprintf('<option value="%d">%s</option>', $taxW['id'], $taxW['description']);
        }

        ob_start();
        ?>
        <div id="alertarea"></div>
        <label class="control-label">Choose a department</label>
        <select class="form-control" id=deptselect v-on:change="show(this.value);" v-model="deptID">
            <option value="">Select one</option>
        <?php echo $opts ?>
        </select>
        <hr />
        <div class="col-sm-3">
            <p id="subdiv">
                <label class="control-label" id=subname>{{ dept }}</label>
                <select class="form-control" id=subselect size=12 v-model="selected" onchange="subDept.getSettings();">
                    <option v-for="sub in subs" v-bind:key="sub.id" v-bind:value="sub.id">{{ sub.name }}</option>
                </select>
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
        <div id="settingsdiv" class="col-sm-3">
            <label class="control-label">Sub Department Settings</label>
            <div class="input-group">
                <span class="input-group-addon">Tax</span>
                <select class="form-control" id="subtax" name="subtax">
                    <?php echo $taxOpts; ?>
                </select>
            </div>
            <br />
            <div class="input-group">
                <span class="input-group-addon">FS</span>
                <select class="form-control" id="subfs" name="subfs">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <br />
            <div class="form-group">
                <button class="btn btn-default" onclick="subDept.saveSettings();">Save Settings</button>
            </div>
        </div>
        <?php

        $this->addScript('../../src/javascript/vue.js');
        $this->addScript('sub.js?date=20240515');
        $this->addOnloadCommand('subDept.show('.$firstID.');');
        
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

