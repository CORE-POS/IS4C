<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

class SubDeptEditor extends FanniePage {
    protected $title = "Fannie : Manage Subdepartments";
    protected $header = "Manage Subdepartments";

    public $description = '[Subdepartment Editor] manges POS sub departments.';

    function preprocess(){
        /* allow ajax calls */
        if(FormLib::get_form_value('action') !== ''){
            $this->ajax_response(FormLib::get_form_value('action'));
            return False;
        }
    
        return True;
    }

    function ajax_response($action){
        switch($action){
        case 'addSub':
            $this->add_sub_dept(FormLib::get_form_value('name'),FormLib::get_form_value('did'));
            echo $this->get_subs_as_options(FormLib::get_form_value('did'));
            break;
        case 'deleteSub':
            $this->delete_sub_depts(FormLib::get_form_value('sid',array()));
            echo $this->get_subs_as_options(FormLib::get_form_value('did'));
            break;
        case 'showSubsForDept':
            echo $this->get_subs_as_options(FormLib::get_form_value('did'));
            break;
        default:
            echo 'Bad request';
            break;
        }
    }

    private function add_sub_dept($name, $deptID){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $p = $dbc->prepare_statement("SELECT max(subdept_no) FROM subdepts");
        $res = $dbc->exec_statement($p);
        $sid = 1;
        if ($dbc->num_rows($res) > 0){
            $tmp = array_pop($dbc->fetch_row($res));
            if (is_numeric($tmp)) $sid = $tmp+1;
        }

        $ins = $dbc->prepare_statement('INSERT INTO subdepts VALUES (?,?,?)');  
        $dbc->exec_statement($ins,array($sid, $name, $deptID));
    }

    private function delete_sub_depts($ids){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        if (!is_array($ids)) $ids = array();
        $delP = $dbc->prepare_statement('DELETE FROM subdepts WHERE subdept_no=?');
        foreach($ids as $id)
            $dbc->exec_statement($delP, array($id));
    }

    private function get_subs_as_options($deptID){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $p = $dbc->prepare_statement("SELECT subdept_no,subdept_name FROM subdepts
                WHERE dept_ID=? ORDER BY subdept_name");
        $r = $dbc->exec_statement($p,array($deptID));
        
        $ret = '';
        while($w = $dbc->fetch_row($r)){
            $ret .= sprintf('<option value="%d">%s</option>',
                    $w['subdept_no'],$w['subdept_name']);
        }
        return $ret;
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $superQ = $dbc->prepare_statement("SELECT d.dept_no,dept_name FROM departments as d
            ORDER BY d.dept_no");
        $superR = $dbc->exec_statement($superQ);
        $opts = "";
        $firstID = False;
        $firstName = "";
        while($superW = $dbc->fetch_row($superR)){
            $opts .= "<option value=$superW[0]>$superW[0] $superW[1]</option>";
            if ($firstID === False){
                $firstID = $superW[0];
                $firstName = $superW[1];
            }
        }

        ob_start();
        ?>
        Choose a department: <select id=deptselect onchange="showSubsForDept(this.value);">
        <?php echo $opts ?>
        </select>
        <hr />
        <div>
        <div style="float:left; display:none; padding-right:10px; border-right:solid 1px #999999;" id="subdiv">
        <span id=subname></span><br />
        <select id=subselect size=12 style="min-width:100px;" multiple></select>
        </div>
        <div style="float:left; margin-left:10px; display:none;" id="formdiv">
        <span>Add/Remove</span><br />
        <input type=text size=7 id=newname /> 
        <input type=submit value=Add onclick="addSub(); return false;" />
        <p />
        <input type=submit value="Delete Selected" onclick="deleteSub(); return false;" />
        </div>
        </div>
        <?php

        $this->add_script('sub.js');
        $this->add_onload_command('showSubsForDept('.$firstID.');');
        
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
