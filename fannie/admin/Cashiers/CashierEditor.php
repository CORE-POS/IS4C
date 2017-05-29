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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CashierEditor extends FanniePage {

    protected $title = "Fannie : Edit Cashier";
    protected $header = "Edit Cashier";
    protected $must_authenticate = True;
    protected $auth_classes = array('editcashiers');

    public $description = '[Edit Cashier] is for managing existing cashiers.';
    public $themed = true;

    private $messages = '';

    public function preprocess()
    {
        $emp_no = FormLib::get('emp_no',0);

        if (FormLib::get('fname') !== '') {
            $dbc = FannieDB::get($this->config->get('OP_DB'));

            // avoid duplicate passwords
            $chkP = $dbc->prepare("
                SELECT emp_no
                FROM employees
                WHERE (CashierPassword=? OR AdminPassword=?)
                    AND emp_no <> ?");
            $passwdInUse = $dbc->getValue($chkP, array(FormLib::get('passwd'), FormLib::get('passwd'), $emp_no));
            if ($passwdInUse !== false) {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Password already in use');\n");
                return true;
            }

            $employee = new EmployeesModel($dbc);
            $employee->emp_no($emp_no);
            $employee->FirstName(FormLib::get('fname'));
            $employee->LastName(FormLib::get('lname'));
            $employee->CashierPassword(FormLib::get('passwd'));
            $employee->AdminPassword(FormLib::get('passwd'));
            $employee->frontendsecurity(FormLib::get('fes'));
            $employee->backendsecurity(FormLib::get('fes'));
            $active = FormLib::get('active') !== '' ? 1 : 0;
            $employee->EmpActive($active);
            $employee->birthdate(FormLib::get('birthdate'));
            $saved = $employee->save();

            $this->saveStoreMapping($dbc, $emp_no);

            if ($saved) {
                $message = "Cashier Updated. <a href=ViewCashiersPage.php>Back to List of Cashiers</a>";
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', '$message');\n");
            } else {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Error saving cashier');\n");
            }
        }

        return true;
    }

    private function saveStoreMapping($dbc, $emp_no)
    {
        $map = new StoreEmployeeMapModel($dbc);
        $map->empNo($emp_no);
        $stores = FormLib::get('store', array());
        foreach ($stores as $s) {
            $map->storeID($s);
            $map->save();
        }
        $map->reset();
        $map->empNo($emp_no);
        foreach ($map->find() as $obj) {
            if (!in_array($obj->storeID(), $stores)) {
                $obj->delete();
            }
        }
    }

    function body_content()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $ret = '';
        if (!empty($this->messages)){
            $ret .= '<blockquote style="background: solid 1x black; 
                padding: 5px; margin: 5px;">';
            $ret .= $this->messages;
            $ret .= '</blockquote>';
        }   

        $emp_no = FormLib::get_form_value('emp_no',0);
        $employee = new EmployeesModel($dbc);
        $employee->emp_no($emp_no);
        $employee->load();

        ob_start();
        ?>
        <div id="alert-area"></div>
        <form action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>" method="post">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="fname" value="<?php echo $employee->FirstName(); ?>"
                class="form-control" required />
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="lname" value="<?php echo $employee->LastName(); ?>"
                class="form-control" />
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="text" name="passwd" value="<?php echo $employee->CashierPassword(); ?>"
                class="form-control" required />
        </div>
        <div class="form-group">
            <label>Privileges</label>
            <select name="fes" class="form-control">
            <option value="20" <?php echo $employee->frontendsecurity() <= 20 ? 'selected' : '' ?>>Regular</option>
            <option value="30" <?php echo $employee->frontendsecurity() > 20 ? 'selected' : '' ?>>Manager</option>
            </select>
        </div>
        <div class="form-group">
            <label>Active
                <input type="checkbox" name="active" class="checkbox-inline"
                    <?php echo $employee->EmpActive()==1 ? 'checked' : ''; ?> />
            </label>
        </div>
        <div class="form-group">
            <label>Birthdate</label>
            <input type="text" class="form-control date-field" name="birthdate" 
                id="birth-date-field" value="<?php echo $employee->birthdate(); ?>"
                placeholder="Optional; for stores selling age-restricted items" />
        </div>
        <?php
        if ($this->config->get('STORE_MODE') == 'HQ') {
            echo '<div class="form-group">';
            $stores = new StoresModel($dbc);
            $mapP = $dbc->prepare('SELECT storeID FROM StoreEmployeeMap WHERE storeID=? AND empNo=?');
            foreach ($stores->find('storeID') as $s) {
                $mapR = $dbc->execute($mapP, array($s->storeID(), $emp_no));
                $checked = ($mapR && $dbc->numRows($mapR)) ? 'checked' : '';
                printf('<label>
                    <input type="checkbox" name="store[]" value="%d" %s />
                    %s
                    </label> | ',
                    $s->storeID(),
                    $checked, $s->description());
            }
            echo '</div>';
        }
        ?>
        <p>
            <button type="submit" class="btn btn-default">Save</button>
            <button type="button" class="btn btn-default"
                onclick="location='ViewCashiersPage.php';return false;">Back</button>
        </p>
        <input type="hidden" name="emp_no" value="<?php echo $emp_no; ?>" />
        </form>
        <?php
        $this->add_onload_command("\$('input.form-control:first').focus();\n");

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Edit an existing cashier. <em>First Name</em> and <em>Password</em>
            are required fields. Which operations require <em>Manager</em> privileges
            depends on local lane configuration. Only <em>Active</em> cashiers are allowed
            to log into lanes.</p>'
            ;
    }

    public function unitTest($phpunit)
    {
        $this->config->set('FANNIE_STORE_MODE', 'HQ');
        $this->messages = 'Test Message';
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $this->config->set('FANNIE_STORE_MODE', 'STORE');
    }
}

FannieDispatch::conditionalExec();

