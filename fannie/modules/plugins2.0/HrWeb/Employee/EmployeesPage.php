<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

use COREPOS\Fannie\Plugin\HrWeb\sql\HrEmployeesModel as EmployeesModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\HrDepartmentsModel as DepartmentsModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\HrStoresModel as StoresModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\PositionsModel as PositionsModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\StatusesModel as StatusesModel;
use COREPOS\Fannie\API\lib\FannieUI;

class EmployeesPage extends FannieRESTfulPage
{
    protected $header = 'Employees';
    protected $title = 'Employees';

    public $default_db = 'wfc_hr';

    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'hr_viewer');

    public function preprocess()
    {
        $this->addRoute('get<all>');

        return parent::preprocess();
    }

    protected function delete_handler()
    {
        $allowed = FannieAuth::validateUserQuiet('hr_editor');
        if ($allowed) {
            try {
                $model = new EmployeesModel($this->connection);
                $model->employeeID($this->form->employeeID);
                $model->deleted(1);
                $model->save();
            } catch (Exception $ex) {
            }
        }

        return 'EmployeesPage.php';
    }

    protected function post_handler()
    {
        $allowed = FannieAuth::validateUserQuiet('hr_editor');
        if ($allowed) {
            try {
                $model = new EmployeesModel($this->connection);
                $model->firstName($this->form->fname);
                $model->lastName($this->form->lname);
                $model->employeeStatusID($this->form->status);
                // Form fields go here.
                $model->save();
            } catch (Exception $ex) {
            }
        }


        return 'EmployeesPage.php'; 
    }

    protected function post_id_handler()
    {
        $new = !$this->id ? true : false;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['HrWebDB']);
        $model = new EmployeesModel($this->connection);
        if (!$new) {
            $model->employeeID($this->id);
        }
        $model->firstName(FormLib::get('fn'));
        $model->lastName(FormLib::get('ln'));
        $model->phoneNumber(FormLib::get('ph'));
        $model->emailAddress(FormLib::get('email'));
        $model->dateHired(FormLib::get('hdate'));
        $model->employeeStatusID(FormLib::get('stat'));
        $saved = $model->save();
        if ($new) {
            $this->id = $saved;
        }

        $this->connection->startTransaction();
        $delP = $this->connection->prepare('DELETE FROM EmployeeStores WHERE employeeID=?');
        $insP = $this->connection->prepare('INSERT INTO EmployeeStores (employeeID, storeID) VALUES (?, ?)');
        $this->connection->execute($delP, array($this->id));
        foreach (FormLib::get('store') as $s) {
            $this->connection->execute($insP, array($this->id, $s));
        }
        $delP = $this->connection->prepare('DELETE FROM EmployeeDepartments WHERE employeeID=?');
        $insP = $this->connection->prepare('INSERT INTO EmployeeDepartments (employeeID, departmentID) VALUES (?, ?)');
        $this->connection->execute($delP, array($this->id));
        foreach (FormLib::get('dept') as $d) {
            $this->connection->execute($insP, array($this->id, $d));
        }
        $delP = $this->connection->prepare('DELETE FROM EmployeePositions WHERE employeeID=?');
        $insP = $this->connection->prepare('INSERT INTO EmployeePositions (employeeID, positionID) VALUES (?, ?)');
        $this->connection->execute($delP, array($this->id));
        foreach (FormLib::get('pos') as $p) {
            $this->connection->execute($insP, array($this->id, $p));
        }
        $this->connection->commitTransaction();

        return 'EmployeesPage.php?id=' . $this->id;
    }

    protected function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['HrWebDB']);
        $model = new EmployeesModel($this->connection);
        $model->employeeID($this->id);
        if (!$model->load()) {
            return '<div class="alert alert-danger">No such employee</div>';
        }

        return $this->viewOrEdit($model);
    }

    private function viewOrEdit($emp)
    {
        $canEdit = FannieAuth::validateUserQuiet('hr_editor');
        $store = new StoresModel($this->connection);
        $dept = new DepartmentsModel($this->connection);
        $pos = new PositionsModel($this->connection);
        $stat = new StatusesModel($this->connection);

        $ret = '<p>
            <a href="EmployeesPage.php" class="btn btn-default">Back</a>
            </p><div class="panel panel-default"><div class="panel-heading">' 
            . $emp->lastName() . ', ' . $emp->firstName()
            . '</div><div class="panel-body">';
        if ($canEdit) {
            $ret .= '<form method="post" action="EmployeesPage.php">
                <input type="hidden" name="id" value="' . $emp->employeeID() . '" />';
        }
        $ret .= '<table class="table table-bordered table-striped">';
        $ret .= '<tr><th colspan="3">First Name</th><td colspan="3">';
        if ($canEdit) {
            $ret .= sprintf('<input type="text" name="fn" value="%s" class="form-control" />',
                $emp->firstName());
        } else {
            $ret .= $emp->firstName();
        }
        $ret .= '</td>';
        $ret .= '<th colspan="3">Last Name</th><td colspan="3">';
        if ($canEdit) {
            $ret .= sprintf('<input type="text" name="ln" value="%s" class="form-control" />',
                $emp->lastName());
        } else {
            $ret .= $emp->lastName();
        }
        $ret .= '</td></tr>';
        $ret .= '<tr><th colspan="3">Phone</th><td colspan="3">';
        if ($canEdit) {
            $ret .= sprintf('<input type="text" name="ph" value="%s" class="form-control" />',
                $emp->phoneNumber());
        } else {
            $ret .= $emp->phoneNumber();
        }
        $ret .= '</td>';
        $ret .= '<th colspan="3">Email</th><td colspan="3">';
        if ($canEdit) {
            $ret .= sprintf('<input type="text" name="email" value="%s" class="form-control" />',
                $emp->emailAddress());
        } else {
            $ret .= $emp->emailAddress();
        }
        $ret .= '</td></tr>';
        $ret .= '<tr><th colspan="3">Hire Date</th><td colspan="3">';
        if ($canEdit) {
            $ret .= sprintf('<input type="text" name="hdate" value="%s" class="form-control date-field" />',
                $emp->dateHired());
        } else {
            $ret .= $emp->dateHired();
        }
        $ret .= '</td>';
        $ret .= '<th colspan="3">Status</th><td colspan="3">';
        if ($canEdit) {
            $ret .= '<select name="stat" class="form-control"><option value="">';
            $ret .= $stat->toOptions($emp->employeeStatusID());
            $ret .= '</select>';
        } else {
            foreach ($stat->find() as $s) {
                if ($s->statusID() == $emp->employeeStatusID()) {
                    $ret .= $s->statusName();
                }
            }
        }
        $ret .= '</td></tr>';

        $ret .= '<tr><th colspan="4">Store(s)</th><th colspan="4">Department(s)</th>
            <th colspan="4">Position(s)</th></tr>';
        $ret .= '<tr><td colspan="4">';
        $prep = $this->connection->prepare('SELECT employeeID FROM EmployeeStores WHERE employeeID=? AND storeID=?');
        if ($canEdit) {
            $ret .= '<select name="store[]" multiple class="form-control" size="5">';
            foreach ($store->find() as $s) {
                $selected = $this->connection->getValue($prep, array($emp->employeeID(), $s->storeID())) ? 'selected' : '';
                $ret .= sprintf('<option %s value="%d">%s</option>', $selected, $s->storeID(), $s->storeName());
            }
            $ret .= '</select>';
        } else {
            foreach ($store->find() as $s) {
                if ($this->connection->getValue($prep, array($emp->employeeID(), $s->storeID()))) {
                    $ret .= $s->storeName() . '<br />';
                }
            }
        }
        $ret .= '</td><td colspan="4">';
        $prep = $this->connection->prepare('SELECT employeeID FROM EmployeeDepartments WHERE employeeID=? AND departmentID=?');
        if ($canEdit) {
            $ret .= '<select name="dept[]" multiple class="form-control" size="5">';
            foreach ($dept->find('departmentName') as $d) {
                $selected = $this->connection->getValue($prep, array($emp->employeeID(), $d->departmentID())) ? 'selected' : '';
                $ret .= sprintf('<option %s value="%d">%s</option>', $selected, $d->departmentID(), $d->departmentName());
            }
            $ret .= '</select>';
        } else {
            foreach ($dept->find() as $d) {
                if ($this->connection->getValue($prep, array($emp->employeeID(), $d->departmentID()))) {
                    $ret .= $d->departmentName() . '<br />';
                }
            }
        }
        $ret .= '</td><td colspan="4">';
        $prep = $this->connection->prepare('SELECT employeeID FROM EmployeePositions WHERE employeeID=? AND positionID=?');
        if ($canEdit) {
            $ret .= '<select name="pos[]" multiple class="form-control" size="5">';
            foreach ($pos->find('positionName') as $p) {
                $selected = $this->connection->getValue($prep, array($emp->employeeID(), $p->positionID())) ? 'selected' : '';
                $ret .= sprintf('<option %s value="%d">%s</option>', $selected, $p->positionID(), $p->positionName());
            }
            $ret .= '</select>';
        } else {
            foreach ($pos->find() as $p) {
                if ($this->connection->getValue($prep, array($emp->employeeID(), $p->positionID()))) {
                    $ret .= $p->positionName() . '<br />';
                }
            }
        }
        $ret .= '</td></tr>';
        $ret .= '</table></div></div>';

        if ($canEdit) {
            $ret .= '<p><button type="submit" class="btn btn-default">Save Changes</button>
                <button type="reset" class="btn btn-default btn-reset">Reset to Current</button>
                </p>
                </form>';
        }

        return $ret;
    }

    public function get_all_view()
    {
        return $this->get_view();
    }

    public function get_view()
    {
        $editCSS = FannieAuth::validateUserQuiet('hr_editor') ? '' : 'collapse';
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $where = isset($this->all) ? 'WHERE 1=1' : 'WHERE e.deleted=0';
        // this query does not work... 
        $res = $dbc->query("
            SELECT e.firstName,
                e.lastName,
                e.employeeID,
                s.statusID,
                s.statusName,
                e.deleted
            FROM Employees as e
                LEFT JOIN Statuses as s ON e.employeeStatusID=s.statusID
            {$where}
            ORDER BY e.lastName, e.firstName");
        $tableBody = '';
        while ($row = $dbc->fetchRow($res)){
            $tableBody .= sprintf('<tr %s><td><a href="?id=%d">%s</a></td>
            <td>%s</td>
            <td class="%s"><a href="?_method=delete&employeeID=%d">%s</a></td>
            </tr>',
            ($row['deleted'] ? 'class="danger"' : ''),
            $row['employeeID'], ($row['lastName'] . ', ' . $row['firstName']),
            $row['statusName'],
            $editCSS,$row['employeeID'], FannieUI::deleteIcon()
            );
        }
        $stat = new COREPOS\Fannie\Plugin\HrWeb\sql\StatusesModel($dbc);
        $stat = $stat->toOptions();
        $allBtn = !isset($this->all) ? '<a href="EmployeesPage.php?all=1" class="btn btn-default">Show Archived</a>' 
             : '<a href="EmployeesPage.php" class="btn btn-default">Show Only Current</a>';

         return <<<HTML
<p>
    <a href="../HrMenu.php" class="btn btn-default">Main Menu</a>
    &nbsp;&nbsp;&nbsp;&nbsp;{$allBtn}
</p>
<table class="table table-bordered table-striped">
<tr><th>Employee Name </th><th>Employement Status</th><th>Delete</th></tr>
{$tableBody}
</table>
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
<div class="panel panel-default {$editCSS}">
    <div class="panel panel-heading">Add Entry</div>
    <div class="panel panel-body">
        <form method="post" action="EmployeesPage.php">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fname" class="form-control" />
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lname" class="form-control" />
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">{$stat}</select>
            </div>
            <div class="form-group">
                <button class="btn btn-default">Add Entry</button>
            </div>
        </form>
    </div>
</div>
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
HTML;
    }
}

FannieDispatch::conditionalExec();

