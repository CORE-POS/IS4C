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
        $prep = $dbc->prepare("SELECT id,description FROM taxrates ORDER BY id");
        $resp = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($resp)) {
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
        $vals = array(
            'name' => $dept->dept_name(),
            'fs' => ($dept->dept_fs()==1 ? 'checked' : ''),
            'wic' => ($dept->dept_wicable()==1 ? 'checked' : ''),
            'dOpts' => $this->discountOpts($dept->dept_discount(), $dept->line_item_discount()),
            'min' => sprintf('%.2f', $dept->dept_minimum()),
            'max' => sprintf('%.2f', $dept->dept_limit()),
            'margin' => sprintf('%.2f', 100*$dept->margin()),
            'pcode' => $dept->salesCode(),
            'tax' => '',
        );
        foreach ($taxes as $k=>$v) {
            if ($k == $dept->dept_tax()) {
                $vals['tax'] .= "<option value=$k selected>$v</option>";
            } else {
                $vals['tax'] .= "<option value=$k>$v</option>";
            }
        }
        if ($this->id != -1) {
            $vals['hiddenID'] = "<input type=hidden name=did id=deptno value=\"" . $this->id . "\" />";
            $vals['isNew'] = 0;
            $vals['textID'] = $this->id;
        } else {
            $vals['hiddenID'] = '';
            $vals['isNew'] = 1;
            $vals['textID'] = "<input class=\"form-control\" type=text name=did id=deptno />";
        }

        echo include(__DIR__ . '/dept.form.html');

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
        $sets = array(
            array('deptMargin', 'margin', $margin),
            array('deptSalesCodes', 'salesCode', $pcode),
        );
        foreach ($sets as $set) {
            if ($dbc->tableExists($set[0])) {
                $chkM = $dbc->prepare('SELECT dept_ID FROM ' . $set[0] . ' WHERE dept_ID=?');
                $marginR = $dbc->execute($chkM, array($deptID));
                if ($dbc->numRows($marginR) > 0){
                    $upP = $dbc->prepare('UPDATE ' . $set[0] . ' SET ' . $set[1] . '=? WHERE dept_ID=?');
                    $dbc->execute($upP, array($set[2], $deptID));
                } else {
                    $ins = $dbc->prepare('INSERT INTO ' . $set[0] . '(dept_ID,' . $set[1] . ') VALUES (?,?)');
                    $dbc->execute($ins, array($deptID, $set[2]));
                }
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

