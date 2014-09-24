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

class DepartmentEditor extends FanniePage {
    protected $title = "Fannie : Manage Departments";
    protected $header = "Manage Departments";

    public $description = '[Department Editor] creates, updates, and deletes POS departments.';

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
        case 'deptDisplay':
            $this->ajax_display_dept(FormLib::get_form_value('did',0));
            break;
        case 'deptSave':
            $this->ajax_save_dept();
            break;
        default:
            echo 'Bad request';
            break;
        }
    }

    private function ajax_display_dept($id){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $name="";
        $tax="";
        $fs=0;
        $disc=1;
        $max=50;
        $min=0.01;
        $margin=0.00;
        $pcode="";

        if ($id != -1){ // -1 means create new department
            $dept = new DepartmentsModel($dbc);
            $dept->dept_no($id);
            $dept->load();
            $name = $dept->dept_name();
            $tax = $dept->dept_tax();
            $fs = $dept->dept_fs();
            $max = $dept->dept_limit();
            $min = $dept->dept_minimum();
            $disc = $dept->dept_discount();

            /**
              Use legacy tables for margin and sales code if needed
            */
            $margin = $dept->margin();
            if (empty($margin) && $dbc->tableExists('deptMargin')) {
                $prep = $dbc->prepare('SELECT margin FROM deptMargin WHERE dept_ID=?');
                $res = $dbc->execute($prep, array($id));
                if ($dbc->num_rows($res) > 0) {
                    $row = $dbc->fetch_row($res);
                    $margin = $row['margin'];
                }
            }
            $pcode = $dept->salesCode();
            if (empty($pcode) && $dbc->tableExists('deptSalesCodes')) {
                $prep = $dbc->prepare('SELECT salesCode FROM deptSalesCodes WHERE dept_ID=?');
                $res = $dbc->execute($prep, array($id));
                if ($dbc->num_rows($res) > 0) {
                    $row = $dbc->fetch_row($res);
                    $pcode = $row['salesCode'];
                }
            }
        }
        $taxes = array();
        $taxes[0] = "NoTax";
        $p = $dbc->prepare_statement("SELECT id,description FROM taxrates ORDER BY id");
        $resp = $dbc->exec_statement($p);
        while($row = $dbc->fetch_row($resp)){
            $taxes[$row[0]] = $row[1];
        }

        $ret = "<table class=\"deptFields\" cellspacing=0 cellpadding=4 border=1><tr>";
        $ret .= "<th>Dept #</th><th colspan=2>Name</th><th>Tax</th><th>FS</th></tr>";
        $ret .= "<tr><td>";
        if ($id == -1){
            $ret .= "<input type=text size=4 name=did id=deptno />";
        }
        else {
            $ret .= $id;
        }
        $ret .= "</td>";
        $ret .= "<td colspan=2><input type=text maxlength=30 name=name id=deptname value=\"$name\" /></td>";
        $ret .= "<td><select id=depttax name=tax>";
        foreach($taxes as $k=>$v){
            if ($k == $tax)
                $ret .= "<option value=$k selected>$v</option>";
            else
                $ret .= "<option value=$k>$v</option>";
        }
        $ret .= "</select></td>";
        $ret .= "<td><input type=checkbox value=1 name=fs id=deptfs ".($fs==1?'checked':'')." /></td>";
        $ret .= "</tr><tr>";
        $ret .= "<th>Discount</th><th>Min</th><th>Max</th><th>Margin</th><th>Sales Code</th></tr>";
        $ret .= "<td><input type=checkbox value=1 name=disc id=deptdisc ".($disc>0?'checked':'')." /></td>";
        $ret .= sprintf("<td>\$<input type=text size=5 name=min id=deptmin value=\"%.2f\" /></td>",$min,0);  
        $ret .= sprintf("<td>\$<input type=text size=5 name=max id=deptmax value=\"%.2f\" /></td>",$max,0);  
        $ret .= sprintf("<td><input type=text size=5 name=margin id=deptmargin value=\"%.2f\" />%%</td>",$margin*100);
        $ret .= "<td><input type=text size=5 id=deptsalescode name=pcode value=\"$pcode\" /></td>";
        if ($id != -1){
            $ret .= "<input type=hidden name=did id=deptno value=\"$id\" />";
            $ret .= "<input type=hidden name=new id=isNew value=0 />";
        }
        else
            $ret .= "<input type=hidden id=isNew name=new value=1 />";
        $ret .= "</tr></table>";
        $ret .= "<p /><input type=submit value=Save onclick=\"deptSave(); return false;\" />";

        echo $ret;
    }

    private function ajax_save_dept(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = FormLib::get_form_value('did',0);
        $name = FormLib::get_form_value('name','');
        $tax = FormLib::get_form_value('tax',0);
        $fs = FormLib::get_form_value('fs',0);
        $disc = FormLib::get_form_value('disc',1);
        $min = FormLib::get_form_value('min',0.01);
        $max = FormLib::get_form_value('max',50.00);
        $margin = FormLib::get_form_value('margin',0);
        $margin = ((float)$margin) / 100.0; 
        $pcode = FormLib::get_form_value('pcode',$id);
        if (!is_numeric($pcode)) $pcode = (int)$id;
        $new = FormLib::get_form_value('new',0);

        $model = new DepartmentsModel($dbc);
        $model->dept_no($id);
        $model->dept_name($name);
        $model->dept_tax($tax);
        $model->dept_fs($fs);
        $model->dept_discount($disc);
        $model->dept_minimum($min);
        $model->dept_limit($max);
        $model->modified(date('Y-m-d H:i:s'));
        $model->margin($margin);
        $model->salesCode($pcode);
        if ($new == 1) {
            $model->modifiedby(1);
            $model->dept_see_id(0);
        }
        $saved = $model->save();

        if ($new == 1){
            if ($saved === False){
                echo 'Error: could not create department';
                return;
            }

            $superP = $dbc->prepare_statement('INSERT INTO superdepts (superID,dept_ID) VALUES (0,?)');
            $superR = $dbc->exec_statement($superP,array($id));
        }
        else {
            if ($saved === False){
                echo 'Error: could not save changes';
                return;
            }
        }
        
        if ($dbc->tableExists('deptMargin')) {
            $chkM = $dbc->prepare_statement('SELECT dept_ID FROM deptMargin WHERE dept_ID=?');
            $mR = $dbc->exec_statement($chkM, array($id));
            if ($dbc->num_rows($mR) > 0){
                $up = $dbc->prepare_statement('UPDATE deptMargin SET margin=? WHERE dept_ID=?');
                $dbc->exec_statement($up, array($margin, $id));
            }
            else {
                $ins = $dbc->prepare_statement('INSERT INTO deptMargin (dept_ID,margin) VALUES (?,?)');
                $dbc->exec_statement($ins, array($id, $margin));
            }
        }

        if ($dbc->tableExists('deptSalesCodes')) {
            $chkS = $dbc->prepare_statement('SELECT dept_ID FROM deptSalesCodes WHERE dept_ID=?');
            $rS = $dbc->exec_statement($chkS, array($id));
            if ($dbc->num_rows($rS) > 0){
                $up = $dbc->prepare_statement('UPDATE deptSalesCodes SET salesCode=? WHERE dept_ID=?');
                $dbc->exec_statement($up, array($pcode, $id));
            }
            else {
                $ins = $dbc->prepare_statement('INSERT INTO deptSalesCodes (dept_ID,salesCode) VALUES (?,?)');
                $dbc->exec_statement($ins, array($id, $pcode));
            }
        }

        $json = array();
        $json['did'] = $id;
        $json['msg'] = 'Department '.$id.' - '.$name.' Saved';

        echo json_encode($json);
    }


    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = "<option value=0>Select a department...</option>";
        $depts .= "<option value=-1>Create a new department</option>";
        $p = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments
                    ORDER BY dept_no");
        $resp = $dbc->exec_statement($p);
        $selectedDID = FormLib::get_form_value('did');
        while($row = $dbc->fetch_row($resp)){
            if ($selectedDID !== '' && $selectedDID == $row[0])
                $depts .= "<option value=$row[0] selected>$row[0] $row[1]</option>";
            else
                $depts .= "<option value=$row[0]>$row[0] $row[1]</option>";
        }
        ob_start();
        ?>
        <div id="deptdiv">
        <b>Department</b> <select id="deptselect" onchange="deptchange();">
        <?php echo $depts ?>
        </select>
        </div>
        <hr />
        <div id="infodiv"></div>
        <?php
    
        $this->add_script('dept.js');
        if ($selectedDID !== '')
            $this->add_onload_command('deptchange();'); 

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
