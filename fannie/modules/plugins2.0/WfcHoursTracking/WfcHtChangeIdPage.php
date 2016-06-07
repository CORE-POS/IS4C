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

class WfcHtChangeIdPage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('edit_employees');
    protected $header = 'Alter Employee ID';
    protected $title = 'Alter Employee ID';

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Alter] the ID# for an employee.';
    public $themed = true;

    public function post_handler()
    {
        $db = WfcHtLib::hours_dbconnect();
        $old = FormLib::get('oldID'); 
        $new = FormLib::get('newID'); 
        echo "$old to $new<br />";

        $model = new WfcHtEmployeesModel($db);
        $model->empID($new);
        if ($model->load()) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'ID $new already in use');\n");
        } else {
            $tables = array(
                'fullTimeStatus',
                'salaryHours',
                'weeklyHours',
                'evalComments',
                'evalScores',
                'evalInfo',
                'employees',
                'EmpWeeklyNotes',
                'OldPTO',
                'ImportedHoursData',
            );
            $args = array($new, $old);
            foreach ($tables as $t) {
                $q = '
                    UPDATE ' . $t . '
                    SET empID=?
                    WHERE empID=?'; 
                $p = $db->prepare($q);
                $r = $db->execute($p, $args);
            }
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'ID Updated');\n");
        }

        return true;
    }
    
    public function post_view()
    {
        return '
            <div id="alert-area"></div>
            <p>
                <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">Change ID</a>
                |
                <a href="WfcHtMenuPage.php" class="btn btn-default">Home</a>
            </p>';
    }

    public function get_view()
    {
        $db = WfcHtLib::hours_dbconnect();
        $model = new WfcHtEmployeesModel($db);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
            <div class="form-group">
                <label>Employee</label>
                <select name="oldID" class="form-control">';
        foreach ($model->find('name') as $e) {
            $ret .= sprintf('<option value="%d">%s (%d)</option>',
                $e->empID(), $e->name(), $e->empID());
        }
        $ret .= '</select>
            </div>
            <div class="form-group">
                <label>New ID#</label>
                <input type="number" name="newID" class="form-control" />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Change ID</button>
            </p>
            </form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();
