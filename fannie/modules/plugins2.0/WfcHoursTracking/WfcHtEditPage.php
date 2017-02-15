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
    public $themed = true;

    public function body_content()
    {
        $db = WfcHtLib::hours_dbconnect();

        $empID = FormLib::get('id');
        if (!is_numeric($empID)) {
            return '<div class="alert alert-error">Error: no employee ID specified</div>';
        }

        $fetchQ = $db->prepare("select adpid,name,department from employees where empID=?");
        $fetchR = $db->execute($fetchQ, array($empID));
        $fetchW = $db->fetch_row($fetchR);
        $ret = "<form action=WfcHtListPage.php method=post>";
        $ret .= "<input type=hidden name=action value=update />";
        $ret .= "<input type=hidden name=id value=$empID />";

        $ret .= '<div class="form-group">';
        $ret .= "<label>ADP ID#</label>
            <input class=\"form-control\" type=text name=adpid value=\"{$fetchW['adpid']}\" />";
        $ret .= '</div>';

        $ret .= '<div class="form-group">';
        $ret .= "<label>Name</label>
            <input class=\"form-control\" type=text name=name value=\"{$fetchW['name']}\" />";
        $ret .= '</div>';

        $ret .= '<div class="form-group">';
        $ret .= "<label>Department</label>";

        $deptsQ = "select name,deptID from Departments order by name";
        $deptsR = $db->query($deptsQ);
        $ret .= "<select name=dept class=\"form-control\">";
        $ret .= "<option value=\"\"></option>";
        while ($deptsW = $db->fetch_row($deptsR)) {
            if ($deptsW['deptID'] == $fetchW['department']) {
                $ret .= "<option value={$deptsW['deptID']} selected>{$deptsW['name']}</option>";
            } else {
                $ret .= "<option value={$deptsW['deptID']}>{$deptsW['name']}</option>";
            }
        }
        $ret .= "</select>";
        $ret .= "</div>";
        $ret .= "<p><button type=submit class=\"btn btn-default\">Save Changes</button>";
        $ret .= ' <a href="WfcHtListPage.php" class="btn btn-default">Cancel</a>';
        $ret .= "</p>";
        $ret .= "</form>";

        return $ret;
    }
}

FannieDispatch::conditionalExec();

