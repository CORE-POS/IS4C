<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('WfcHtLib')) {
    require(dirname(__FILE__).'/WfcHtLib.php');
}

class WfcHtEditPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('edit_employees');
    protected $header = 'Edit';
    protected $title = 'Edit';

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Edit] the settings for an employee.';

    public function body_content()
    {
        $db = WfcHtLib::hours_dbconnect();

        $empID = FormLib::get('id');
        if (!is_numeric($empID)) {
            return "<b>Error: no employee ID specified</b>";
        }

        $fetchQ = $db->prepare_statement("select adpid,name,department from employees where empID=?");
        $fetchR = $db->exec_statement($fetchQ, array($empID));
        $fetchW = $db->fetch_row($fetchR);
        $ret = "<form action=WfcHtListPage.php method=post>";
        $ret .= "<input type=hidden name=action value=update />";
        $ret .= "<input type=hidden name=id value=$empID />";
        $ret .= "<table cellspacing=4 cellpadding=0>";
        $ret .= "<tr><th>ADP ID#</th><td><input type=text name=adpid value=\"{$fetchW['adpid']}\" /></td></tr>";
        $ret .= "<tr><th>Name</th><td><input type=text name=name value=\"{$fetchW['name']}\" /></td></tr>";
        $ret .= "<tr><th>Department</th><td>";

        $deptsQ = "select name,deptID from Departments order by name";
        $deptsR = $db->query($deptsQ);
        $ret .= "<select name=dept>";
        $ret .= "<option value=\"\"></option>";
        while ($deptsW = $db->fetch_row($deptsR)) {
            if ($deptsW['deptID'] == $fetchW['department']) {
                $ret .= "<option value={$deptsW['deptID']} selected>{$deptsW['name']}</option>";
            } else {
                $ret .= "<option value={$deptsW['deptID']}>{$deptsW['name']}</option>";
            }
        }
        $ret .= "</select>";
        $ret .= "</td></tr>";
        $ret .= "<tr><td><input type=submit value=\"Save Changes\" /></td>";
        $ret .= "<td><input type=submit value=Cancel onclick=\"window.location = 'WfcHtListPage.php'; return false;\" /></td></tr>";
        $ret .= "</table>";
        $ret .= "</form>";

        return $ret;
    }
}

FannieDispatch::conditionalExec();

