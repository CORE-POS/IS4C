<?php
include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TsEmployeesEditor extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: TimesheetPlugin';
    protected $title = 'Employees Admin';
    protected $header = 'Employees Admin';

    public function preprocess()
    {
        $this->__routes[] = 'post<newID>';

        return parent::preprocess();
    }

    public function post_newID_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['TimesheetDatabase']);

        $model = new TimesheetEmployeesModel($dbc);
        if ($this->newID) {
            $model->timesheetEmployeeID($this->newID);
            if (!$model->load()) { 
                $model->firstName('NEW');
                $model->lastName('EMPLOYEE');
                $model->save();
            }
        } else {
            $query = 'SELECT MAX(timesheetEmployeeID) FROM TimesheetEmployees';
            $result = $dbc->query($query);
            $id = 1;
            if ($result && $dbc->numRows($result)) {
                $row = $dbc->fetchRow($result);
                $id = $row[0]+1;
            }
            $model->timesheetEmployeeID($id);
            $model->firstName('NEW');
            $model->lastName('EMPLOYEE');
            $model->save();
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        return false;
    }

    public function post_id_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['TimesheetDatabase']);

        $model = new TimesheetEmployeesModel($dbc);
        $model->timesheetEmployeeID($this->id);
        $model->firstName(FormLib::get('firstName'));
        $model->lastName(FormLib::get('lastName'));
        $model->username(FormLib::get('username'));
        $model->posMemberID(FormLib::get('posMemberID'));
        $model->payrollProviderID(FormLib::get('payrollProviderID'));
        $model->timeclockToken(FormLib::get('timeclockToken'));
        $model->timesheetDepartmentID(FormLib::get('timesheetDepartmentID'));
        $model->primaryShiftID(FormLib::get('primaryShiftID'));
        $model->wage(FormLib::get('wage'));
        $model->hireDate(FormLib::get('hireDate'));
        $model->active(FormLib::get('active'));
        $model->save();

        header('Location: ?id=' . $this->id);
        return false;
    }

    public function get_id_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['TimesheetDatabase']);

        $model = new TimesheetEmployeesModel($dbc);
        $model->timesheetEmployeeID($this->id);
        if (!$model->load()) {
            return '<div class="alert alert-danger">Employee does not exist</div>';
        }

        $ret = '<h3>Employee #' . $this->id . '</h3>
            <form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />';

        $ret .= sprintf('
            <div class="form-group">
                <label>First Name</label>
                <input type="text" class="form-control" name="firstName" value="%s" />
            </div>',
            $model->firstName()
        );

        $ret .= sprintf('
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" class="form-control" name="lastName" value="%s" />
            </div>',
            $model->lastName()
        );

        $ret .= '<div class="form-group">
            <label>Active</label>
            <select name="active" class="form-control">
            <option ' . ($model->active() ? 'selected' : '') . ' value="1">Yes</option>
            <option ' . (!$model->active() ? 'selected' : '') . ' value="0">No</option>
            </select>
            </div>';

        $ret .= sprintf('
            <div class="form-group">
                <label>Hire Date</label>
                <input type="text" class="form-control date-field" name="hireDate" value="%s" />
            </div>',
            $model->hireDate()
        );

        $ret .= '<div class="form-group">
            <label>Timesheet Department</label>
            <select name="timesheetDepartmentID" class="form-control">';
        $depts = new TimesheetDepartmentsModel($dbc);
        $ret .= $depts->toOptions($model->timesheetDepartmentID());
        $ret .= '</select></div>';

        $ret .= '<div class="form-group">
            <label>Primary Job Position</label>
            <select name="primaryShiftID" class="form-control">';
        $depts = new ShiftsModel($dbc);
        $ret .= $depts->toOptions($model->primaryShiftID());
        $ret .= '</select></div>';

        $ret .= sprintf('
            <div class="form-group">
                <label>Wage</label>
                <input type="text" class="form-control" name="wage" value="%.2f" />
            </div>',
            $model->wage()
        );

        $ret .= sprintf('
            <div class="form-group">
                <label>Office Username</label>
                <input type="text" class="form-control" name="username" value="%s" />
            </div>',
            $model->username()
        );

        $ret .= sprintf('
            <div class="form-group">
                <label>POS Member ID</label>
                <input type="text" class="form-control" name="posMemberID" value="%s" />
            </div>',
            $model->posMemberID()
        );

        $ret .= sprintf('
            <div class="form-group">
                <label>Payroll Provider ID</label>
                <input type="text" class="form-control" name="payrollProviderID" value="%s" />
            </div>',
            $model->payrollProviderID()
        );

        $ret .= sprintf('
            <div class="form-group">
                <label>Timeclock Token</label>
                <input type="text" class="form-control" name="timeclockToken" value="%s" />
            </div>',
            $model->timeclockToken()
        );

        $ret .= '<p>
            <button type="submit" class="btn btn-default">Save Changes</button>
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">All Employees</a>
            </p>
            </form>';

        return $ret;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['TimesheetDatabase']);

        $model = new TimesheetEmployeesModel($dbc);

        $ret = <<<HTML
<table class="table table-bordered table-striped">
<thead>
<tr>
    <th>#</th>
    <th>First Name</th>
    <th>Last Name</th>
    <th>Department</th>
    <th>Position</th>
    <th>View/Edit</th>
</tr>
</thead>
<tbody>
HTML;
        $depts = array();
        $dModel = new TimesheetDepartmentsModel($dbc);
        foreach ($dModel->find() as $d) {
            $depts[$d->timesheetDepartmentID()] = $d->name();
        }
        $shifts = array();
        $sModel = new ShiftsModel($dbc);
        foreach ($sModel->find() as $s) {
            $shifts[$s->shiftID()] = $s->NiceName();
        }

        foreach ($model->find('timesheetEmployeeID') as $obj) {
            $ret .= sprintf('<tr>
                <td>%d</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td><a href="?id=%d" class="btn btn-default btn-xs">%s</a></td>
                </tr>',
                $obj->timesheetEmployeeID(),
                $obj->firstName(),
                $obj->lastName(),
                isset($depts[$obj->timesheetDepartmentID()]) ? $depts[$obj->timesheetDepartmentID()] : '?',
                isset($shifts[$obj->primaryShiftID()]) ? $shifts[$obj->primaryShiftID()] : '?',
                $obj->timesheetEmployeeID(), \COREPOS\Fannie\API\lib\FannieUI::editIcon()
            );
        }

        $ret .= <<<HTML
</tbody>
</table>
<form class="form-inline" method="post">
<p>
    <label>Timesheet ID</label>
    <input type="text" class="form-control" name="newID"
        placeholder="Optional - omit for automatic ID" 
        title="Optional - omit for automatic ID" 
        />
    <button type="submit" class="btn btn-default">Create New Employee</button>
</p>
HTML;

        return $ret;
    }

    public function helpContent()
    {
        return <<<HTML
<p>
Timesheet employees have the following required attributes:
<ul>
    <li><b>Timesheet Employee Number</b> identifies the employee within the timesheet plugin.</li>
    <li><b>First and Last Name</b></li>
    <li><b>Hire date</b></li>
    <li><b>Active</b></li>
    <li><b>Wage</b> is the employee's current hourly wage</li>
    <li><b>Timesheet Department</b> is solely an organizational feature to group related job positions
    together.</li>
    <li><b>Primary Job Position</b> is the most commonly worked job.</li>
</ul>
And the following optional attributes:
<ul>
    <li><b>Office Username</b> is the employee's login name for the POS backend. This can be used to
    restrict access so an employee must enter a username and password to view or edit their hours.</li>
    <li><b>POS Member ID</b> is the employee's member (or customer) ID. If the employee is using a
    store charge account, this is necessary to track their balance.</li>
    <li><b>Payroll Provider ID</b> if third party software is involved in some part of payroll processing,
    its IDs can be stored here.</li>
    <li><b>Timeclock Token</b> can be a barcode, magnetic stripe, or some other value. It's intended to
    allow employees to quickly clock in or out without typing out a username and password.</li>
</ul> 
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

