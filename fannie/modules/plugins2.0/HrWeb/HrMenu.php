<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class HrMenu extends FannieRESTfulPage
{
    protected $header = 'HR Web';
    protected $title = 'HR Web';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'hr_viewer');

    protected function get_view()
    {
        return <<<HTML
<table class="table table-bordered table-striped">
    <tr><th colspan="2">People & Other Nouns</th></tr>
    <tr>
        <td><a href="Employee/EmployeesPage.php">Employees</a></td>
        <td>Manage individual employees</td>
    </tr>
    <tr>
        <td><a href="Department/DepartmentsPage.php">Departments</a></td>
        <td>Manage employee departments</td>
    </tr>
    <tr>
        <td><a href="Position/PositionsPage.php">Positions</a></td>
        <td>Manage job positions in the organization</td>
    </tr>
    <tr>
        <td><a href="Status/StatusesPage.php">Statuses</a></td>
        <td>Manage employment statuses (probably won't change often)</td>
    </tr>
    <tr>
        <td><a href="Store/StoresPage.php">Stores</a></td>
        <td>Manage physical work locations (probably won't change often)</td>
    </tr>
    <tr><th colspan="2">Illness Records</th></tr>
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();

