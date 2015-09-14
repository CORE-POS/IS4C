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

class ViewCashiersPage extends FanniePage 
{

    protected $title = "Fannie : View Cashiers";
    protected $header = "View Cashiers";
    protected $must_authenticate = True;
    protected $auth_classes = array('editcashiers');

    public $description = '[View Cashiers] shows information about cashiers.';
    public $themed = true;


    function javascript_content(){
        ob_start();
        ?>
function deleteEmp(emp_no,filter){
    if (confirm('Deleting a cashier completely removes them. If you just want to disable the login, use "Edit" instead.')){
        window.location='ViewCashiersPage.php?filter='+filter+'&emp_no='+emp_no+'&delete=yes';  
    }
}
        <?php
        return ob_get_clean();
    }

    function preprocess()
    {
        global $FANNIE_OP_DB;
        $emp = FormLib::get_form_value('emp_no');
        if (FormLib::get_form_value('delete') !== '' && $emp !== '') {
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $employee = new EmployeesModel($dbc);
            $employee->emp_no($emp);
            $deleted = $employee->delete();
            if ($deleted) {
                $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Deleted #$emp');\n");
            } else {
                $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Error deleting #$emp');\n");
            }
        }

        return true;
    }

    function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $filter = FormLib::get_form_value('filter',1);
        $order = FormLib::get_form_value('order','num');
        $orderby = '';
        switch($order){
        case 'num':
        default:
            $orderby = 'emp_no';
            break;
        case 'name':
            $orderby = 'FirstName';
            break;
        case 'pass':
            $orderby = 'CashierPassword';
            break;
        case 'fes':
            $orderby = 'frontendsecurity';
            break;
        }
        
        $ret = '<div id="alert-area"></div><div class="form-inline">';
        $ret .= "<label>Showing</label> <select class=\"form-control\"
            onchange=\"location='ViewCashiersPage.php?filter='+this.value;\">";
        if ($filter == 1){
            $ret .= "<option value=1 selected>Active Cashiers</option>";
            $ret .= "<option value=0>Disabled Cashiers</option>";
        }
        else{
            $ret .= "<option value=1>Active Cashiers</option>";
            $ret .= "<option value=0 selected>Disabled Cashiers</option>";
        }
        $ret .= "</select></div><hr />";

        $ret .= "<table class=\"table\"><tr>";
        $ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=num>#</th>";
        $ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=name>Name</th>";
        $ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=pass>Password</th>";
        $ret .= "<th><a href=ViewCashiersPage.php?filter=$filter&order=fes>Privileges</th>";
        $ret .= "<th>&nbsp;</th><th>&nbsp;</th></tr>";

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $employees = new EmployeesModel($dbc);
        $employees->EmpActive($filter);
        foreach($employees->find($orderby) as $emp){
            $ret .= sprintf("<tr><td>%d</td><td>%s</td><td>%d</td><td>%s</td>",
                    $emp->emp_no(),
                    $emp->FirstName().' '.$emp->LastName(),
                    $emp->CashierPassword(),
                    ($emp->frontendsecurity()<=20?'Regular':'Manager'));
            $ret .= sprintf("<td><a href=\"CashierEditor.php?emp_no=%d\">%s</a></td>
                <td><a href=\"\" onclick=\"deleteEmp(%d,%d); return false;\">%s</a></td></tr>",
                $emp->emp_no(),\COREPOS\Fannie\API\lib\FannieUI::editIcon(),
                $emp->emp_no(),$filter, \COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
        }
        $ret .= "</table>";

        return $ret;
    }

    public function helpContent()
    {
        return '<p>View, edit, or delete cashiers. Only <em>Active</em> cashiers can
            log into the lanes. Click column headers to sort the list.</p>';
    }
}

FannieDispatch::conditionalExec(false);

?>
