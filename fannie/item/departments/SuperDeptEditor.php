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

class SuperDeptEditor extends FanniePage {
    protected $title = "Fannie : Manage Super Departments";
    protected $header = "Manage Super Departments";

    public $description = '[Superdepartment Editor] manges POS super departments.';

    function preprocess(){
        /* allow ajax calls */
        if(FormLib::get_form_value('action') !== ''){
            $this->ajax_response(FormLib::get_form_value('action'));
            return False;
        }

        return True;
    }

    function ajax_response($action){
        global $FANNIE_OP_DB;
        switch($action){
        case 'deptsInSuper':
            $depts = $this->depts_in_super(FormLib::get_form_value('sid',0));
            foreach($depts as $id=>$v)
                printf('<option value="%d">%d %s</option>',$id,$id,$v);
            break;
        case 'deptsNotInSuper':
            $depts = $this->depts_not_in_super(FormLib::get_form_value('sid',0));
            foreach($depts as $id=>$v)
                printf('<option value="%d">%d %s</option>',$id,$id,$v);
            break;
        case 'superDeptEmail':
            $id = FormLib::get_form_value('sid', 0);
            if ($id == -1) {
                echo '';
            } else {
                $model = new SuperDeptEmailsModel(FannieDB::get($FANNIE_OP_DB));
                $model->superID($id);
                $model->load();
                echo $model->email_address();
            }
            break;
        case 'save':
            $id = FormLib::get_form_value('sid',0); 
            $name = FormLib::get_form_value('name','');
            $email = FormLib::get_form_value('email','');
            $depts = FormLib::get_form_value('depts',array());
            $this->save_super_dept($id,$name,$depts);
            $model = new SuperDeptEmailsModel(FannieDB::get($FANNIE_OP_DB));
            $model->superID($id);
            $model->email_address($email);
            $model->save();
            break;
        default:
            echo 'Bad request';
            break;
        }
    }

    private function depts_in_super($id){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $prep = $dbc->prepare_statement("SELECT 
            superID,dept_ID,dept_name FROM
            superdepts AS s LEFT JOIN
            departments AS d ON s.dept_ID = d.dept_no
            WHERE superID=?
            GROUP BY superID,dept_ID,dept_name
            ORDER BY superID,dept_ID");
        $result = $dbc->exec_statement($prep,array($id));
        $ret = array();
        while($row = $dbc->fetch_row($result)){
            $ret[$row['dept_ID']] = $row['dept_name'];
        }
        return $ret;
    }

    private function depts_not_in_super($id){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
    
        $prep = $dbc->prepare_statement('SELECT dept_no,dept_name
                FROM departments WHERE dept_no NOT IN
                (SELECT dept_ID FROM superdepts WHERE
                superID=?)
                GROUP BY dept_no,dept_name
                ORDER BY dept_no');
        $result = $dbc->exec_statement($prep,array($id));
        $ret = array();
        while($row = $dbc->fetch_row($result)){
            $ret[$row['dept_no']] = $row['dept_name'];
        }
        return $ret;
    }

    private function save_super_dept($id,$name,$depts){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if ($id == -1){
            $p = $dbc->prepare_statement("SELECT max(superID)+1 FROM superdepts");
            $resp = $dbc->exec_statement($p);
            $id = array_pop($dbc->fetch_row($resp));
            if (empty($id)) $id = 1;
        }
        else {
            $prep = $dbc->prepare_statement("DELETE FROM superdepts WHERE superID=?");
            $dbc->exec_statement($prep,array($id));
        }

        $deptP = $dbc->prepare_statement('INSERT INTO superdepts VALUES (?,?)');
        if (!is_array($depts)) $depts = array();
        foreach($depts as $d){
            $dbc->exec_statement($deptP,array($id,$d));
        }

        $delP = $dbc->prepare_statement("DELETE FROM superDeptNames WHERE superID=?");
        $dbc->exec_statement($delP,array($id));
        $insP = $dbc->prepare_statement("INSERT INTO superDeptNames VALUES (?,?)");
        $dbc->exec_statement($insP,array($id,$name));

        echo "Saved Settings for $name";
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $superQ = $dbc->prepare_statement("SELECT s.superID,super_name FROM superdepts as s
            LEFT JOIN superDeptNames AS n ON s.superID=n.superID
            GROUP BY s.superID,super_name
            ORDER BY super_name");
        $superR = $dbc->exec_statement($superQ);
        $opts = "";
        $firstID = False;
        $firstName = "";
        while($superW = $dbc->fetch_row($superR)){
            $opts .= "<option value=$superW[0]>$superW[1]</option>";
            if ($firstID === False){
                $firstID = $superW[0];
                $firstName = $superW[1];
            }
        }
        if (empty($opts)) $opts .= "<option></option>";

        $firstEmail = '';
        if ($firstID !== false) {
            $model = new SuperDeptEmailsModel($dbc);
            $model->superID($firstID);
            $model->load();
            $firstEmail = $model->email_address();
        }

        ob_start();
        ?>
        <div id="superdeptdiv">
        Select super department: <select id="superselect" onchange="superSelected();">
        <?php echo $opts; ?>
        <option value=-1>Create a new super department</option>
        </select><p />
        <span id="namespan" style="display:none;">Name: 
        <input type="text" id="newname" value="<?php echo $firstName; ?>" /></span>
        <span id="emailspan" style="display:<?php echo ($firstEmail === '') ? 'none' : 'block' ?>;">Email address(es): 
        <input type="text" id="sd_email" value="<?php echo $firstEmail; ?>" /></span>
        </div>
        <hr />
        <div id="#deptdiv" style="display:block;">
        <div style="float: left;">
        Members<br />
        <select id="deptselect" multiple size=15>
        <?php 
        foreach($this->depts_in_super($firstID) as $id=>$name) 
            printf('<option value=%d>%d %s</option>',$id,$id,$name);
        ?>
        </select>
        </div>
        <div style="float: left; margin-left: 20px; margin-top: 50px;">
        <input type="submit" value="<<" onclick="addDepts(); return false;" />
        <p />
        <input type="submit" value=">>" onclick="remDepts(); return false;" />
        </div>
        <div style="margin-left: 20px; float: left;">
        Non-members<br />
        <select id="deptselect2" multiple size=15>
        <?php 
        foreach($this->depts_not_in_super($firstID) as $id=>$name) 
            printf('<option value=%d>%d %s</option>',$id,$id,$name);
        ?>
        </select>
        </div>
        <div style="clear:left;"></div>
        <br />
        <input type="submit" value="Save" onclick="saveData(); return false;" />
        </div>
        <?php
        $this->add_script('super.js');

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
