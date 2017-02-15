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

class AddCashierPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Add Cashier";
    protected $header = "Add Cashier";
    protected $must_authenticate = true;
    protected $auth_classes = array('editcashiers');

    public $description = '[Add Cashier] is the tool to create new cashiers.';
    public $has_unit_tests = true;

    public function preprocess()
    {
        $this->addRoute('post<fname><lname><fes><birthdate>');
        $this->addRoute('get<flash>');

        return parent::preprocess();
    }

    protected function post_fname_lname_fes_birthdate_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
            
        $passwd = $this->genPassword($dbc);

        $emp_no = $this->nextEmpNo($dbc);

        $employee = new EmployeesModel($dbc);
        $employee->emp_no($emp_no);
        $employee->CashierPassword($passwd);
        $employee->AdminPassword($passwd);
        $employee->FirstName($this->fname);
        $employee->LastName($this->lname);
        $employee->JobTitle('');
        $employee->EmpActive(1);
        $employee->frontendsecurity($this->fes);
        $employee->backendsecurity($this->fes);
        $employee->birthdate($this->birthdate);
        $employee->save();

        try {
            $this->saveStoreMapping($dbc, $emp_no, $this->form->stores);
        } catch (Exception $e) {
            // likely means HQ is disabled or
            // not stores were selected
        }

        $message = sprintf("Cashier Created<br />Name:%s<br />Emp#:%d<br />Password:%d",
            $this->fname.' '.$this->lname,$emp_no,$passwd);

        return '?flash=' . base64_encode($message);
    }

    private function nextEmpNo($dbc)
    {
        $idQ = $dbc->prepare("
            SELECT MAX(emp_no) AS max
            FROM employees 
            WHERE emp_no < 1000
        ");
        $idR = $dbc->execute($idQ);
        $idW = $dbc->fetchRow($idR);
        if ($idW && $idW['max'] !== null) {
            return $idW['max']+1;
        } else {
            return 1;
        }

    }

    private function genPassword($dbc)
    {
        $passwd = '';
        srand();
        $checkP = $dbc->prepare("SELECT * FROM employees WHERE CashierPassword=?");
        while ($passwd === '') {
            $newpass = rand(1000,9999);
            $checkR = $dbc->execute($checkP,array($newpass));
            if ($dbc->num_rows($checkR) == 0) {
                $passwd = $newpass;
            }
        }

        return $passwd;
    }

    private function saveStoreMapping($dbc, $emp_no, $stores)
    {
        $map = new StoreEmployeeMapModel($dbc);
        $map->empNo($emp_no);
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

    protected function get_flash_view()
    {
        $message = base64_decode($this->flash);
        if ($message !== false) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', '$message');\n");
        }

        return $this->get_view();
    }

    protected function get_view()
    {
        ob_start();
        ?>
        <div id="alert-area"></div>
        <form action="AddCashierPage.php" method="post">
        <div class="form-group">
            <label>First Name</label>
            <input type=text name=fname required class="form-control" />
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type=text name=lname class="form-control" />
        </div>
        <div class="form-group">
            <label>Privileges</label>
            <select name="fes" class="form-control">
                <option value=20>Regular</option>
                <option value=30>Manager</option>
            </select>
        </div>
        <div class="form-group">
            <label>Birthdate</label>
            <input type="text" class="form-control date-field" name="birthdate" id="birth-date-field"
                placeholder="Optional; for stores selling age-restricted items" />
        </div>
        <?php
        if ($this->config->get('STORE_MODE') == 'HQ') {
            echo '<div class="form-group">';
            $dbc = $this->connection;
            $stores = new StoresModel($dbc);
            foreach ($stores->find('storeID') as $s) {
                printf('<label>
                    <input type="checkbox" name="store[]" value="%d" />
                    %s
                    </label> | ',
                    $s->storeID(),
                    $s->description());
            }
            echo '</div>';
        }
        ?>
        <p>
            <button type="submit" class="btn btn-default">Create Cashier</button>
        </p>
        </form>
        <?php
        $ret = ob_get_clean();
        $this->add_onload_command("\$('input.form-control:first').focus();\n");

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Create a new cashier. First name is required; last name
            is not. Which operations require <em>Manager</em> privileges
            depends on local lane configuration. The cashier\'s password
            is randomly generated.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        if (!class_exists('CashierTests', false)) {
            include(dirname(__FILE__) . '/CashierTests.php');
        }
        $this->config->set('FANNIE_STORE_MODE', 'HQ');
        $tester = new CashierTests($this->connection, $this->config, $this->logger);
        $tester->testAddCashier($this, $phpunit);

        $map = new StoreEmployeeMapModel($this->connection);
        $map->empNo(35);
        $map->storeID(1);
        // map
        $this->saveStoreMapping($this->connection, 35, array(1));
        $phpunit->assertEquals(true, $map->load());
        // unmap
        $this->saveStoreMapping($this->connection, 35, array());
        $phpunit->assertEquals(false, $map->load());
        $this->config->set('FANNIE_STORE_MODE', 'STORE');
    }
}

FannieDispatch::conditionalExec();

