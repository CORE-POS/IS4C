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

class DepartmentEditor extends FannieRESTfulPage 
{
    protected $title = "Fannie : Manage Departments";
    protected $header = "Manage Departments";
    
    protected $must_authenticate = true;
    protected $auth_classes = array('departments', 'admin');

    public $description = '[Department Editor] creates, updates, and deletes POS departments.';

    private function getDept($dbc, $deptID)
    {
        $dept = new DepartmentsModel($dbc);
        if ($deptID !== -1) { // not new department
            $dept->dept_no($deptID);
            $dept->load();
            /**
              Use legacy tables for margin and sales code if needed
            */
            $margin = $dept->margin();
            if (empty($margin) && $dbc->tableExists('deptMargin')) {
                $prep = $dbc->prepare('SELECT margin FROM deptMargin WHERE dept_ID=?');
                $dept->margin($dbc->getValue($prep, array($id)));
            }
            $pcode = $dept->salesCode();
            if (empty($pcode) && $dbc->tableExists('deptSalesCodes')) {
                $prep = $dbc->prepare('SELECT salesCode FROM deptSalesCodes WHERE dept_ID=?');
                $dept->salesCode($dbc->getValue($prep, array($id)));
            }
        }

        return $dept;
    }

    private function getTaxes($dbc)
    {
        $taxes = array();
        $taxes[0] = "NoTax";
        $p = $dbc->prepare("SELECT id,description FROM taxrates ORDER BY id");
        $resp = $dbc->execute($p);
        while($row = $dbc->fetch_row($resp)){
            $taxes[$row[0]] = $row[1];
        }

        return $taxes;
    }

    protected function get_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $dept = $this->getDept($dbc, $this->id);
        $taxes = $this->getTaxes($dbc);

        $ret = '<div class="row">'
            . '<label class="control-label col-sm-2">Dept #</label>'
            . '<label class="control-label col-sm-4">Name</label>'
            . '<label class="control-label col-sm-2">Tax</label>'
            . '<label class="control-label col-sm-1">FS</label>'
            . '<label class="control-label col-sm-1">WIC</label>'
            . '</div>';
        $ret .= '<div class="row">';
        $ret .= '<div class="col-sm-2">';
        if ($this->id == -1){
            $ret .= "<input class=\"form-control\" type=text name=did id=deptno />";
        } else {
            $ret .= $this->id;
        }
        $ret .= "</div>";
        $ret .= "<div class=\"col-sm-4\"><input type=text maxlength=30 name=name 
            id=deptname value=\"" . $dept->dept_name() . "\" class=\"form-control\" /></div>";
        $ret .= "<div class=\"col-sm-2\"><select class=\"form-control\" id=depttax name=tax>";
        foreach ($taxes as $k=>$v) {
            if ($k == $dept->dept_tax()) {
                $ret .= "<option value=$k selected>$v</option>";
            } else {
                $ret .= "<option value=$k>$v</option>";
            }
        }
        $ret .= "</select></div>";
        $ret .= "<div class=\"col-sm-1\"><input type=checkbox value=1 name=fs id=deptfs "
            . ($dept->dept_fs()==1?'checked':'') . " class=\"checkbox\" /></div>";
        $ret .= "<div class=\"col-sm-1\"><input type=checkbox value=1 name=wic id=deptwic "
            . ($dept->dept_wicable()==1?'checked':'') . " class=\"checkbox\" /></div>";
        $ret .= "</div>";
        $ret .= '<div class="row">'
            . '<label class="control-label col-sm-2">Discount</label>'
            . '<label class="control-label col-sm-2">Min</label>'
            . '<label class="control-label col-sm-2">Max</label>'
            . '<label class="control-label col-sm-2">Margin</label>'
            . '<label class="control-label col-sm-2">Sales Code</label>'
            . '</div>';
        $ret .= '<div class="row form-inline">';
        $ret .= "<div class=\"col-sm-2\">
            <select name=\"disc\" id=\"deptdisc\" class=\"form-control\">
            " . $this->discountOpts($dept->dept_discount(), $dept->line_item_discount()) . "
            </select>
            </div>";
        $ret .= sprintf("<div class=\"col-sm-2\"><div class=\"input-group\">
            <span class=\"input-group-addon\">\$</span>
            <input type=number name=min class=\"form-control\" 
            id=deptmin value=\"%.2f\" min=\"0\" max=\"9999\" step=\"0.01\" />
            </div></div>",$dept->dept_minimum(),0);  
        $ret .= sprintf("<div class=\"col-sm-2\"><div class=\"input-group\">
            <span class=\"input-group-addon\">\$</span>
            <input type=number name=max class=\"form-control\" id=deptmax 
            value=\"%.2f\" min=\"0\" max=\"99999\" step=\"0.01\" /></div></div>",$dept->dept_limit(),0);  
        $ret .= sprintf("<div class=\"col-sm-2\"><div class=\"input-group\"><input type=number name=margin 
            class=\"form-control\" id=deptmargin value=\"%.2f\" min=\"0\" max=\"999\" step=\"0.01\" />
            <span class=\"input-group-addon\">%%</span></div></div>",$dept->margin()*100);
        $ret .= "<div class=\"col-sm-2\"><input type=text id=deptsalescode 
           class=\"form-control\" name=pcode value=\"" . $dept->salesCode() . "\" /></div>";
        $ret .= '</div>';
        if ($this->id != -1) {
            $ret .= "<input type=hidden name=did id=deptno value=\"" . $this->id . "\" />";
            $ret .= "<input type=hidden name=new id=isNew value=0 />";
        } else {
            $ret .= "<input type=hidden id=isNew name=new value=1 />";
        }
        $ret .= "<p><button type=submit value=Save onclick=\"deptEdit.deptSave(); return false;\"
            class=\"btn btn-default\">Save</button></p>";

        echo $ret;

        return false;
    }

    private function discountOpts($reg, $line)
    {
        $select = 0;
        if ($reg && $line) {
            $select = 1;
        } elseif ($reg && !$line) {
            $select = 2;
        } elseif (!$reg && $line) {
            $select = 3;
        }
        $opts = array(0=>'No', 1=>'Yes', 2=>'Trans only', 3=>'Line Only');
        $ret = '';
        foreach ($opts as $k => $v) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($k == $select ? 'selected' : ''), $k, $v);
        }

        return $ret;
    }

    protected function post_handler()
    {
        $dbc = $this->connection;

        $deptID = FormLib::get('did',0);
        $margin = FormLib::get('margin',0);
        $margin = ((float)$margin) / 100.0; 
        $pcode = FormLib::get('pcode',$deptID);
        if (!is_numeric($pcode)) $pcode = (int)$deptID;

        $model = new DepartmentsModel($dbc);
        $model->dept_no($deptID);
        $model->dept_name(FormLib::get('name', ''));
        $model->dept_tax(FormLib::get('tax', 0));
        $model->dept_fs(FormLib::get('fs', 0));
        $model->dept_wicable(FormLib::get('wic', 0));
        $disc = FormLib::get('disc');
        if ($disc == 1 || $disc == 2) {
            $model->dept_discount(1);
        } else {
            $model->dept_discount(0);
        }
        if ($disc == 1 || $disc == 3) {
            $model->line_item_discount(1);
        } else {
            $model->line_item_discount(0);
        }
        $model->dept_minimum(FormLib::get('min', 0.01));
        $model->dept_limit(FormLib::get('max', 50.00));
        $model->modified(date('Y-m-d H:i:s'));
        $model->margin($margin);
        $model->salesCode($pcode);
        if (FormLib::get('new', 0) == 1) {
            $model->modifiedby(1);
            $model->dept_see_id(0);
        }

        if ($model->save() === false) {
            return false;
        }

        if (FormLib::get('new', 0) == 1) {
            $superP = $dbc->prepare('INSERT INTO superdepts (superID,dept_ID) VALUES (0,?)');
            $superR = $dbc->execute($superP,array($deptID));
        }

        $json = array();
        $json['did'] = $deptID;
        $json['msg'] = 'Department '.$deptID.' - '.$name.' Saved';

        echo json_encode($json);

        return false;
    }

    private function legacySave($dbc, $deptID, $margin, $pcode)
    {
        if ($dbc->tableExists('deptMargin')) {
            $chkM = $dbc->prepare('SELECT dept_ID FROM deptMargin WHERE dept_ID=?');
            $marginR = $dbc->execute($chkM, array($deptID));
            if ($dbc->num_rows($marginR) > 0){
                $upP = $dbc->prepare('UPDATE deptMargin SET margin=? WHERE dept_ID=?');
                $dbc->execute($upP, array($margin, $deptID));
            } else {
                $ins = $dbc->prepare('INSERT INTO deptMargin (dept_ID,margin) VALUES (?,?)');
                $dbc->execute($ins, array($deptID, $margin));
            }
        }

        if ($dbc->tableExists('deptSalesCodes')) {
            $chkS = $dbc->prepare('SELECT dept_ID FROM deptSalesCodes WHERE dept_ID=?');
            $codeR = $dbc->execute($chkS, array($deptID));
            if ($dbc->num_rows($codeR) > 0) {
                $upP = $dbc->prepare('UPDATE deptSalesCodes SET salesCode=? WHERE dept_ID=?');
                $dbc->execute($upP, array($pcode, $deptID));
            } else {
                $ins = $dbc->prepare('INSERT INTO deptSalesCodes (dept_ID,salesCode) VALUES (?,?)');
                $dbc->execute($ins, array($deptID, $pcode));
            }
        }
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = "<option value=0>Select a department...</option>";
        $depts .= "<option value=-1>Create a new department</option>";
        $prep = $dbc->prepare("SELECT dept_no,dept_name FROM departments
                    ORDER BY dept_no");
        $resp = $dbc->execute($prep);
        $selectedDID = FormLib::get_form_value('did');
        while ($row = $dbc->fetch_row($resp)) {
            if ($selectedDID !== '' && $selectedDID == $row[0]) {
                $depts .= "<option value=$row[0] selected>$row[0] $row[1]</option>";
            } else {
                $depts .= "<option value=$row[0]>$row[0] $row[1]</option>";
            }
        }
        ob_start();
        ?>
        <div id="deptdiv" class="form-group">
            <label class="control-label">Department</label>
            <select class="form-control" id="deptselect" onchange="deptEdit.deptchange();">
            <?php echo $depts ?>
            </select>
        </div>
        <hr />
        <div id="infodiv" class="deptFields"></div>
        <?php
    
        $this->add_script('dept.js');
        if ($selectedDID !== '') {
            $this->add_onload_command('deptEdit.deptchange();'); 
        }

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Departments are the base level of categorization for
            items. All items must belong to a department.</p>
            <p>To create a department, choose <i>Create a new department</i>
            from the <i>Department</i> drop down. To edit an existing department,
            choose that department from the drop down.</p>
            <p>Tax, foodstamp, and discount are the defaults for new items added
            to the department. These values are also used for open rings to the
            department.</p>
            <p>Min and max are soft limits. If the cashier open rings a price 
            outside this range they get a warning but can confirm the price and
            continue.</p>
            <p>Margin may be used to calculate suggested retail price for items
            whose cost is known.</p>
            <p>Sales codes are yet another form of categorization. Typically this
            field is used for account numbers or similar identifiers that appear
            in the accounting software used. It is particularly helpful if the 
            accounting team and the operational team want to categorize items and
            sales differently.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        ob_start();
        $this->id = 1;
        $this->get_id_handler();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
    }
}

FannieDispatch::conditionalExec();

