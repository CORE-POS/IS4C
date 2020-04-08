<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

use COREPOS\Fannie\Plugin\HrWeb\sql\HrEmployeesModel as EmployeesModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\IllnessTypesModel as IllnessTypesModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\IllnessLogsModel as IllnessLogsModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\IllnessLogsIllnessTypesModel as LogTypeMapModel;
use COREPOS\Fannie\API\lib\FannieUI;

class IllnessLogsPage extends FannieRESTfulPage
{
    protected $header = 'Illness Logs';
    protected $title = 'Illness Logs';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'illness_editor', 'illness_entry');

    protected function post_id_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new IllnessLogsModel($dbc);
        $model->illnessLogID($this->id);
        $model->illnessDate(FormLib::get('iDate'));
        $model->exclusionary(FormLib::get('ex') ? 1 : 0);
        $model->MDHContacted(FormLib::get('mdh') ? 1 : 0);
        $model->comments(FormLib::get('comment'));
        $uid = FannieAuth::getUID($this->current_user);
        $model->lastModified(date('Y-m-d H:i:s'));
        $model->modifiedBy($uid);
        $model->returnToWorkDate(FormLib::get('rtw'));
        $model->finalFormSubmitted(FormLib::get('ffs') ? 1 : 0);
        $model->inactive(FormLib::get('close') ? 1 : 0);
        $model->save();

        return 'IllnessLogsPage.php?id=' . $this->id;
    }

    protected function post_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new IllnessLogsModel($dbc);
        $model->employeeID(FormLib::get('emp'));
        $model->illnessDate(FormLib::get('idate'));
        $model->exclusionary(FormLib::get('ex') ? 1 : 0);
        $model->MDHContacted(FormLib::get('mdh') ? 1 : 0);
        $model->dateCreated(date('Y-m-d H:i:s'));
        $uid = FannieAuth::getUID($this->current_user);
        $model->createdBy($uid);
        $model->lastModified(date('Y-m-d H:i:s'));
        $model->modifiedBy($uid);
        $model->comments(FormLib::get('comment'));
        $iID = $model->save();
        $dbc->startTransaction();
        $model = new LogTypeMapModel($dbc);
        foreach (FormLib::get('type') as $t) {
            $model->illnessLogID($iID);
            $model->illnessTypeID($t);
            $model->save();
        }
        $dbc->commitTransaction();

        return 'IllnessLogsPage.php';
    }

    protected function get_id_view()
    {
        if (!FannieAuth::validateUserQuiet('hr_editor') && !FannieAuth::validateUserQuiet('illness_editor')) {
            return '<div class="alert alert-danger">You do not have access to this</div>';
        }
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new IllnessLogsModel($dbc);
        $model->illnessLogID($this->id);
        if (!$model->load()) {
            return '<div class="alert alert-danger">No entry found</div>';
        }
        $emp = new EmployeesModel($dbc);
        $emp->employeeID($model->employeeID());
        $emp->load();

        $ret = '<p>
            <a href="IllnessLogsPage.php" class="btn btn-default">Back</a>
            </p> 
            <form method="post" action="IllnessLogsPage.php">
            <input type="hidden" name="id" value="' . $this->id . '" />';
        $ret .= '<p>
            <strong>Employee</strong>: ' . $emp->lastName() . ', ' . $emp->firstName() . '<br />
            <strong>Illness Date</strong>: ' . $model->illnessDate() . '<br />
            </p>';
        $ret .= sprintf('<div class="form-group">
            <label>Illness Date</label>
            <input type="text" name="iDate" class="form-control date-field" value="%s" />
            </div>', $model->illnessDate());
        $chkP = $dbc->prepare('SELECT illnessLogID FROM IllnessLogsIllnessTypes WHERE illnessLogID=? AND illnessTypeID=?');
        $type = new IllnessTypesModel($dbc);
        $ret .= '<div class="form-group">
            <label>Type(s)</label>
            <select name="type[]" class="form-control" multiple required size="5">';
        foreach ($type->find() as $t) {
            $selected = $dbc->getValue($chkP, array($this->id, $t->illnessTypeID())) ? 'selected' : '';
            $ret .= sprintf('<option %s value="%d">%s</option>',
                $selected, $t->illnessTypeID(), $t->illnessType());
        }
        $ret .= '</select></div>';
        $ret .= sprintf('<div class="form-group">
            <label><input type="checkbox" name="ex" value="1" %s />
            Exclusionary</label></div>',
            $model->exclusionary() ? 'checked' : '');
        $ret .= sprintf('<div class="form-group">
            <label><input type="checkbox" name="mdh" value="1" %s />
            MDH</label></div>',
            $model->MDHContacted() ? 'checked' : '');
        $ret .= '<div class="form-group"><label>Comments</label>
            <textarea name="comment" class="form-control" rows="5">'
            . $model->comments() . '</textarea></div>';
        $ret .= '<hr />';
        $ret .= sprintf('<div class="form-group"><label>Return to Work Date</label>
            <input type="text" name="rtw" value="%s" class="form-control date-field" /></div>',
            $model->returnToWorkDate());
        $ret .= sprintf('<div class="form-group">
            <label><input type="checkbox" name="ffs" value="1" %s />
            Final Form from Manager</label></div>',
            $model->finalFormSubmitted() ? 'checked' : '');
        $ret .= sprintf('<div class="form-group">
            <label><input type="checkbox" name="close" value="1" %s />
            Close out Entry</label></div>',
            $model->inactive() ? 'checked' : '');
        $ret .= '<p>
            <button type="submit" class="btn btn-default">Save Changes</button>
            <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
            </p></form>';

        return $ret;
    }

    private function getAccess()
    {
        $uid = FannieAuth::getUID($this->current_user);
        $prep = $this->connection->prepare("SELECT * FROM AccessIllness WHERE userId=?");
        $res = $this->connection->execute($prep, array($uid));
        $perms = array();
        while ($row = $this->connection->fetchRow($res)) {
            $key = $row['storeID'] . ':' . $row['deptID'];
            $perms[$key] = array('edit' => $row['canEdit'], 'view' => $row['canView']);
        }

        return $perms;
    }

    private function getEmployeeAreas($empID)
    {
        $prep = $this->connection->prepare("SELECT storeID FROM EmployeeStores WHERE employeeID=?");
        $stores = $this->connection->getAllValues($prep, array($empID));

        $prep = $this->connection->prepare("SELECT departmentID FROM EmployeeDepartments WHERE employeeID=?");
        $depts = $this->connection->getAllValues($prep, array($empID));

        $ret = array();
        foreach ($stores as $sID) {
            foreach ($depts as $dID) {
                $ret[$sID . ':' . $dID] = true;
            }
        }

        return $ret;
    }

    protected function get_view()
    {
        $editCSS = 'collapse';
        $includeList = false;
        if (FannieAuth::validateUserQuiet('hr_editor') || FannieAuth::validateUserQuiet('illness_editor')) {
            $editCSS = '';
            $includeList = true;
        }
        $access = $this->getAccess();
        $allAccess = FannieAuth::validateUserQuiet('hr_editor');
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);

        $openR = $dbc->query('
            SELECT i.illnessLogID,
                i.illnessDate,
                e.firstName,
                e.lastName,
                e.employeeID,
                i.exclusionary,
                i.MDHContacted,
                MAX(i.comments) AS comments,
                GROUP_CONCAT(t.illnessType, \', \') AS types
            FROM IllnessLogs AS i
                LEFT JOIN IllnessLogsIllnessTypes AS m ON i.illnessLogID=m.illnessLogID
                LEFT JOIN IllnessTypes AS t ON m.illnessTypeID=t.illnessTypeID
                LEFT JOIN Employees AS e ON i.employeeID = e.employeeID
            WHERE i.inactive=0
                AND i.finalFormSubmitted=0
            GROUP BY i.illnessLogID,
                i.illnessDate,
                e.firstName,
                e.lastName,
                i.exclusionary,
                i.MDHContacted
            ORDER BY i.illnessDate DESC');
        $open = '';
        $empAreas = array();
        while ($row = $dbc->fetchRow($openR)) {
            $eID = $row['employeeID'];
            if (!isset($empAreas[$eID])) {
                $empAreas[$eID] = $this->getEmployeeAreas($eID);
            }
            $hasAccess = false;
            $userEditCSS = 'collapse';
            if ($allAccess) {
                $hasAccess = true;
                $userEditCSS = '';
            } else {
                foreach ($access as $area => $type) {
                    if (isset($empAreas[$eID][$area])) {
                        $hasAccess = true;
                        if ($type['edit']) {
                            $userEditCSS = '';
                        }
                    }
                }
            }
            if (!$hasAccess) continue;
            $open .= sprintf('<tr><td>%s</td>
                <td>%s, %s</td><td>%s</td>
                <td>%s</td><td>%s</td>
                <td>%s</td>
                <td class="%s"><a href="?id=%d">%s</a></td>
                </tr>',
                $row['illnessDate'],
                $row['lastName'], $row['firstName'], $row['types'],
                ($row['exclusionary'] ? 'Yes' : 'No'),
                ($row['MDHContacted'] ? 'Yes' : 'n/a'),
                htmlentities($row['comments']),
                $userEditCSS, $row['illnessLogID'], FannieUI::editIcon()
            );
        }
        if (!$includeList) {
            $open = '<tr><td colspan="7">Greater access required</td></tr>';
        }

        $types = new IllnessTypesModel($dbc);
        $tOpts = $types->toOptions();
        $eOpts = '<option value="">Select one...</option>';
        $res = $dbc->query('SELECT employeeID, firstName, lastName FROM Employees WHERE deleted=0 ORDER BY lastName, firstName');
        while ($row = $dbc->fetchRow($res)) {
            $eOpts .= sprintf('<option value="%d">%s, %s</option>', $row['employeeID'], $row['lastName'], $row['firstName']);
        }
        return <<<HTML
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
<table class="table table-bordered table-striped">
<tr><th colspan="6">Currently Open Entries</th><th class="{$editCSS}"></th></tr>
<tr>
    <th>Illness Date</th>
    <th>Employee</th>
    <th>Type(s)</th>
    <th>Exclusionary</th>
    <th>MDH</th>
    <th>Comments</th>
    <th class="{$editCSS}">Edit</th>
</tr>
    {$open}
</table>
<div class="panel panel-default {$editCSS}">
    <div class="panel-heading">New Log Entry</div>
    <div class="panel-body">
    <form method="post" action="IllnessLogsPage.php">
        <div class="form-group">
            <label>Employee *</label>
            <select name="emp" class="form-control" required>
                {$eOpts}
            </select>
        </div>
        <div class="form-group">
            <label>Illness Date *</label>
            <input type="text" name="idate" required class="form-control date-field" />
        </div>
        <div class="form-group">
            <label>Illness Type(s) *</label>
            <select name="type[]" class="form-control" multiple required size="5">
                {$tOpts}
            </select>
        </div>
        <div class="form-group">
            <label>Exclusionary
                <input type="checkbox" name="ex" value="1" />
            </label>
        </div>
        <div class="form-group">
            <label>MDH Contacted
                <input type="checkbox" name="mdh" value="1" />
            </label>
        </div>
        <div class="form-group">
            <label>Comments</label>
            <textarea name="comment" rows="5" class="form-control"></textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Create Log Entry</button>
        </div>
    </form>
    </div>
</div>
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
HTML;
    }
}

FannieDispatch::conditionalExec();

