<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class CashierEditor extends FanniePage {

    protected $title = "Fannie : Edit Cashier";
    protected $header = "Edit Cashier";
    protected $must_authenticate = True;
    protected $auth_classes = array('editcashiers');

    public $description = '[Edit Cashier] is for managing existing cashiers.';

    private $messages = '';

    function preprocess(){
        global $FANNIE_OP_DB;
        $emp_no = FormLib::get_form_value('emp_no',0);

        if (FormLib::get_form_value('fname') !== ''){
            $fn = FormLib::get_form_value('fname');
            $ln = FormLib::get_form_value('lname');
            $passwd = FormLib::get_form_value('passwd');
            $fes = FormLib::get_form_value('fes');
            $active = FormLib::get_form_value('active') !== '' ? 1 : 0;

            $dbc = FannieDB::get($FANNIE_OP_DB);
            $employee = new EmployeesModel($dbc);
            $employee->emp_no($emp_no);
            $employee->FirstName($fn);
            $employee->LastName($ln);
            $employee->CashierPassword($passwd);
            $employee->AdminPassword($passwd);
            $employee->frontendsecurity($fes);
            $employee->backendsecurity($fes);
            $employee->EmpActive($active);
            $employee->save();

            $this->messages = "Cashier Updated. <a href=ViewCashiersPage.php>Back to List of Cashiers</a>";
        }
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
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

        $ret .= "<form action=CashierEditor.php method=post>";
        $ret .= "<table cellspacing=4 cellpadding=4>";
        $ret .= "<tr><th>First Name</th><td><input type=text name=fname value=\"".$employee->FirstName()."\" /></td>";
        $ret .= "<th>Last Name</th><td><input type=text name=lname value=\"".$employee->LastName()."\" /></td></tr>";
        $ret .= "<tr><th>Password</th><td><input type=text name=passwd value=\"".$employee->CashierPassword()."\" /></td>";
        $ret .= "<th>Privileges</th><td><select name=fes>";
        if ($employee->frontendsecurity() <= 20){
            $ret .= "<option value=20 selected>Regular</option>";
            $ret .= "<option value=30>Manager</option>";
        }
        else {
            $ret .= "<option value=20>Regular</option>";
            $ret .= "<option value=30 selected>Manager</option>";
        }
        $ret .= "</select></td></tr>";
        $ret .= "<tr><th>Active</th><td><input type=checkbox name=active ".($employee->EmpActive()==1?'checked':'')." /></td>";
        $ret .= "<td colspan=2><input type=submit value=Save /></td></tr>";
        $ret .= "<input type=hidden name=emp_no value=$emp_no />";
        $ret .= "</table></form>";

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
