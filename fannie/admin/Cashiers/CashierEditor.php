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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class CashierEditor extends FannieRESTfulPage
{
    protected $title = "Fannie : Edit Cashier";
    protected $header = "Edit Cashier";
    protected $must_authenticate = True;
    protected $auth_classes = array('editcashiers');

    public $description = '[Edit Cashier] is for managing existing cashiers.';
    public $themed = true;

    private $messages = '';

    public function post_handler()
    {
        try {
            $emp_no = $this->form->emp_no;
            $fname = $this->form->fname;

            if ($fname !== '') {
                $dbc = FannieDB::get($this->config->get('OP_DB'));

                // avoid duplicate passwords
                $chkP = $dbc->prepare("
                    SELECT emp_no
                    FROM employees
                    WHERE (CashierPassword=? OR AdminPassword=?)
                        AND emp_no <> ?");
                $passwdInUse = $dbc->getValue($chkP, array($this->form->passwd, $this->form->passwd, $emp_no));
                if ($passwdInUse !== false) {
                    $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Password already in use');\n");
                    return true;
                }

                $employee = new EmployeesModel($dbc);
                $employee->emp_no($emp_no);
                $employee->FirstName($fname);
                $employee->LastName($this->form->lname);
                $employee->CashierPassword($this->form->passwd);
                $employee->AdminPassword($this->form->passwd);
                $employee->frontendsecurity($this->form->fes);
                $employee->backendsecurity($this->form->fes);
                $active = $this->form->tryGet('active', '') !== '' ? 1 : 0;
                $employee->EmpActive($active);
                $employee->birthdate($this->form->birthdate);
                $saved = $employee->save();

                $this->saveStoreMapping($dbc, $emp_no);

                if ($saved) {
                    $message = "Cashier Updated. <a href=ViewCashiersPage.php>Back to List of Cashiers</a>";
                    $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', '$message');\n");
                } else {
                    $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Error saving cashier');\n");
                }
            }
        } catch (Exception $ex) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Error saving cashier (" . $ex->getMessage() . ")');\n");
        }

        return true;
    }

    private function saveStoreMapping($dbc, $emp_no)
    {
        $map = new StoreEmployeeMapModel($dbc);
        $stores = $this->form->tryGet('store', array());
        $map->empNo($emp_no);
        $exists = array();
        foreach ($map->find() as $obj) {
            if (!in_array($obj->storeID(), $stores)) {
                $obj->delete();
            } else {
                $exists[] = $obj->storeID();
            }
        }
        $map->reset();
        $map->empNo($emp_no);
        foreach ($stores as $s) {
            if (!in_array($s, $exists)) {
                $map->storeID($s);
                $map->save();
            }
        }
    }

    private function storeMapHtml($dbc, $emp_no)
    {
        $storeMap = '';
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $storeMap .= '<div class="form-group">';
            $stores = new StoresModel($dbc);
            $mapP = $dbc->prepare('SELECT storeID FROM ' . FannieDB::fqn('StoreEmployeeMap', 'op') . ' WHERE storeID=? AND empNo=?');
            foreach ($stores->find('storeID') as $s) {
                $mapR = $dbc->execute($mapP, array($s->storeID(), $emp_no));
                $checked = ($mapR && $dbc->numRows($mapR)) ? 'checked' : '';
                $storeMap .= sprintf('<label>
                    <input type="checkbox" name="store[]" value="%d" %s />
                    %s
                    </label> | ',
                    $s->storeID(),
                    $checked, $s->description());
            }
            $storeMap .= '</div>';
        }

        return $storeMap;
    }

    protected function post_view()
    {
        return $this->get_view();
    }

    protected function get_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $emp_no = $this->form->tryGet('emp_no',0);

        $storeMap = $this->storeMapHtml($dbc, $emp_no);

        $employee = new EmployeesModel($dbc);
        $employee->emp_no($emp_no);
        $employee->load();
        $emp = $employee->toStdClass();
        $select20 = $emp->frontendsecurity <= 20 ? 'selected' : '';
        $select30 = $emp->frontendsecurity > 20 ? 'selected' : '';
        $active = $emp->EmpActive ? 'checked' : '';

        $action = filter_input(INPUT_SERVER, 'PHP_SELF');
        $this->addOnloadCommand("\$('input.form-control:first').focus();\n");

        return <<<HTML
<div id="alert-area"></div>
<form action="{$action}" method="post">
    <div class="form-group">
        <label>First Name</label>
        <input type="text" name="fname" value="{$emp->FirstName}"
            class="form-control" required />
    </div>
    <div class="form-group">
        <label>Last Name</label>
        <input type="text" name="lname" value="{$emp->LastName}"
            class="form-control" />
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="text" name="passwd" value="{$emp->CashierPassword}"
            class="form-control" required />
    </div>
    <div class="form-group">
        <label>Privileges</label>
        <select name="fes" class="form-control">
        <option value="20" {$select20}>Regular</option>
        <option value="30" {$select30}>>Manager</option>
        </select>
    </div>
    <div class="form-group">
        <label>Active
            <input type="checkbox" name="active" class="checkbox-inline" {$active} />
        </label>
    </div>
    <div class="form-group">
        <label>Birthdate</label>
        <input type="text" class="form-control date-field" name="birthdate" 
            id="birth-date-field" value="{$emp->birthdate}"
            placeholder="Optional; for stores selling age-restricted items" />
    </div>
    {$storeMap}
    <p>
        <button type="submit" class="btn btn-default">Save</button>
        <a class="btn btn-default" href="ViewCashiersPage.php">Back</a>
    </p>
    <input type="hidden" name="emp_no" value="{$emp_no}" />
</form>
HTML;

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
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->post_view()));
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->emp_no = 1;
        $form->fname = 'Test';
        $form->lname = '';
        $form->passwd = '1234';
        $form->fes = 20;
        $form->birthdate = '2000-01-01';
        $form->store = array(1);
        $this->setForm($form);
        $phpunit->assertEquals(true, $this->post_handler());
        $this->config->set('FANNIE_STORE_MODE', 'STORE');
    }
}

FannieDispatch::conditionalExec();

