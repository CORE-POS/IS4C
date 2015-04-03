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

class SuperDeptEditor extends FanniePage {
    protected $title = "Fannie : Manage Super Departments";
    protected $header = "Manage Super Departments";

    public $description = '[Superdepartment Editor] manges POS super departments.';
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
        global $FANNIE_OP_DB;
        switch ($action) {
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
            $ret = $this->save_super_dept($id,$name,$depts);
            $model = new SuperDeptEmailsModel(FannieDB::get($FANNIE_OP_DB));
            $model->superID($id);
            $model->email_address($email);
            $model->save();
            echo json_encode($ret);
            break;
        default:
            echo 'Bad request';
            break;
        }
    }

    private function depts_in_super($id)
    {
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
        while ($row = $dbc->fetch_row($result)) {
            $ret[$row['dept_ID']] = $row['dept_name'];
        }

        return $ret;
    }

    private function depts_not_in_super($id)
    {
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
        while ($row = $dbc->fetch_row($result)) {
            $ret[$row['dept_no']] = $row['dept_name'];
        }

        return $ret;
    }

    private function save_super_dept($id,$name,$depts)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if ($id == -1) {
            $p = $dbc->prepare_statement("SELECT max(superID)+1 FROM superdepts");
            $resp = $dbc->exec_statement($p);
            $row = $dbc->fetch_row($resp);
            $id = $row[0]; 
            if (empty($id)) {
                $id = 1;
            }
        } else {
            $prep = $dbc->prepare_statement("DELETE FROM superdepts WHERE superID=?");
            $dbc->exec_statement($prep,array($id));
        }

        $deptP = $dbc->prepare_statement('INSERT INTO superdepts VALUES (?,?)');
        if (!is_array($depts)) {
            $depts = array();
        }
        foreach ($depts as $d) {
            $dbc->exec_statement($deptP,array($id,$d));
        }

        $delP = $dbc->prepare_statement("DELETE FROM superDeptNames WHERE superID=?");
        $dbc->exec_statement($delP,array($id));
        $insP = $dbc->prepare_statement("INSERT INTO superDeptNames VALUES (?,?)");
        $dbc->exec_statement($insP,array($id,$name));

        return array('id' => $id, 'name' => $name);
    }

    function body_content()
    {
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
        <div id="alertarea"></div>
        <div id="superdeptdiv">
            <div class="form-group">
            <label class="control-label">Select super department</label>
            <select class="form-control" id="superselect" onchange="superSelected();">
            <?php echo $opts; ?>
            <option value=-1>Create a new super department</option>
            </select>
            </div>
            <div id="namefield" class="form-group collapse">
            <label class="control-label">Name</label>
            <input type="text" id="newname" class="form-control" value="<?php echo $firstName; ?>" />
            </div>
            <div class="form-group <?php echo ($firstEmail === '') ? 'hidden' : 'shown' ?>">
            <label class="control-label">Email Address(es)</label>
            <input class="form-control" type="text" id="sd_email" value="<?php echo $firstEmail; ?>" />
            </div>
        </div>
        <hr />
        <div class="row">
        <div id="deptdiv" class="form-group col-sm-4">
            <label class="control-label">Members</label>
            <select class="form-control" id="deptselect" multiple size=15>
            <?php 
            foreach ($this->depts_in_super($firstID) as $id=>$name) {
                printf('<option value=%d>%d %s</option>',$id,$id,$name);
            }
            ?>
            </select>
        </div>
        <div class="col-sm-1">
            <!-- lazy alignment -->
            <br />
            <br />
            <p>
            <button class="btn btn-default" type="submit" value="<<" 
                onclick="addDepts(); return false;">&lt;&lt;</button>
            </p>
            <p>
            <button class="btn btn-default" type="submit" value=">>" 
                onclick="remDepts(); return false;">&gt;&gt;</button>
            </p>
        </div>
        <div class="form-group col-sm-4">
            <label class="control-label">Non-members</label>
            <select class="form-control" id="deptselect2" multiple size=15>
            <?php 
            foreach ($this->depts_not_in_super($firstID) as $id=>$name) {
                printf('<option value=%d>%d %s</option>',$id,$id,$name);
            }
            ?>
            </select>
        </div>
        </div>
        <p>
            <button type="submit" value="Save" onclick="saveData(); return false;"
                class="btn btn-default">Save</button>
        </p>
        <?php
        $this->add_script('super.js');

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Super Departments are the highest level of catgorization.
            Each super department contains one or more departments.</p>
            <p>To create a new super department, choose <i>Create new super
            department</i> from the <i>Select super department</i> drop down.</p>
            <p>To add a department to a super department, first select the super
            department then select the department in the <i>non-members</i>
            list. Click the left arrow (&lt;&lt;) to move the department over
            to the members list.</p>
            <p>To remove a department from a super department, first select the super
            department then select the department in the <i>members</i>
            list. Click the right arrow (&gt;&gt;) to move the department over
            to the non-members list.</p>
            <p>A department may belong to more than one super department
            <strong>however</strong> in that case the lowest-number super department
            is considered the department\'s <i>home</i> super department. This
            convention is necessary when viewing store-wide sales by super
            department. These reports use only the <i>home</i> super department
            to avoid counting a department\'s sales multiple times.</p>
            <p>By convention super department #0 (zero) is used for departments
            that are not considered sales. Examples of things that may fit
            well in super department #0 are gift cards and member equity.</p>';
    }
}

FannieDispatch::conditionalExec(false);

?>
