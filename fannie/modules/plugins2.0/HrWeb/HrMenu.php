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
    protected $auth_classes = array('hr_editor', 'illness_editor', 'illness_viewer');

    protected function get_view()
    {
        $hrEdit = FannieAuth::validateUserQuiet('hr_editor');
        $illnessEdit = FannieAuth::validateUserQuiet('illness_editor');
        $ret  = '<table class="table table-bordered table-striped">';
        if ($hrEdit) {
            $ret .= '
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
                <td><a href="Training/TrainingsPage.php">Trainings</a></td>
                <td>Manager training classes</td>
            </tr>
            <tr>
                <td><a href="Status/StatusesPage.php">Statuses</a></td>
                <td>Manage employment statuses (probably won\'t change often)</td>
            </tr>
            <tr>
                <td><a href="Store/StoresPage.php">Stores</a></td>
                <td>Manage physical work locations (probably won\'t change often)</td>
            </tr>';
        }
        $ret .= '<tr><th colspan="2">Illness Records</th></tr>';
        if ($hrEdit || $illnessEdit) {
            $ret .= '
                <tr>
                    <td><a href="IllnessType/IllnessTypesPage.php">Illness Types</a></td>
                    <td>Manage illness categorizations</td>
                </tr>
                <tr>
                    <td><a href="IllnessLog/IllnessLogsPage.php">Illness Logs</a></td>
                    <td>Manage open illness log entries and create new entries</td>
                </tr>';
        }
        $ret .= '
            <tr>
                <td><a href="IllnessReport/IllnessLogReport.php">Illness Log Reporting</a></td>
                <td>View and filter illness log entries</td>
            </tr>
        </table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

