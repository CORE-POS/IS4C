<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

use COREPOS\Fannie\Plugin\HrWeb\sql\HrStoresModel as HrStoresModel;
use COREPOS\Fannie\Plugin\HrWeb\sql\HrDepartmentsModel as HrDepartmentsModel;

class PermissionsPage extends FannieRESTfulPage
{
    protected $header = 'Permissions';
    protected $title = 'Permissions';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor');

    public function preprocess()
    {
        $this->addRoute('get<illness>', 'post<illness>');

        return parent::preprocess();
    }

    protected function post_illness_handler()
    {
        $delP = $this->connection->prepare("DELETE FROM AccessIllness WHERE userID=?");
        $this->connection->execute($delP, array($this->illness));

        $insP = $this->connection->prepare("INSERT INTO AccessIllness (userID, storeID, deptID, canEdit, canView) VALUES (?, ?, ?, ?, ?)");
        $this->connection->startTransaction();
        $edit = FormLib::get('edit', array());
        foreach ($edit as $e) {
            list($store, $dept) = explode(':', $e);
            $this->connection->execute($insP, array($this->illness, $store, $dept, 1, 1));
        }
        $this->connection->commitTransaction();
        $this->connection->startTransaction();
        foreach (FormLib::get('view', array()) as $v) {
            if (in_array($v, $edit)) continue;
            list($store, $dept) = explode(':', $v);
            $this->connection->execute($insP, array($this->illness, $store, $dept, 0, 1));
        }
        $this->connection->commitTransaction();

        return true;
    }

    protected function post_illness_view()
    {
        return '<div class="alert alert-success">Saved Changes</div>'
            . $this->get_illness_view();
    }

    protected function get_illness_view()
    {
        $this->illness = str_pad($this->illness, 4, '0', STR_PAD_LEFT);
        $userP = $this->connection->prepare("SELECT name, real_name FROM " . FannieDB::fqn('Users', 'op') . " WHERE uid=?");
        $user = $this->connection->getRow($userP, array($this->illness));
        if (!$user) {
            return '<div class="alert alert-danger">User not found</div>';
        }
        $currentP = $this->connection->prepare("SELECT * FROM AccessIllness WHERE userID=?");
        $currentR = $this->connection->execute($currentP, array($this->illness));
        $perms = array();
        while ($row = $this->connection->fetchRow($currentR)) {
            $key = $row['storeID'] . ':' . $row['deptID'];
            $perms[$key] = array('edit' => $row['canEdit'], 'view'=>$row['canView']);
        }
        $stores = new HrStoresModel($this->connection);
        $stores = $stores->find();
        $depts = new HrDepartmentsModel($this->connection);
        $table = '';
        foreach ($depts->find('departmentName') as $d) {
            foreach ($stores as $s) {
                $key = $s->storeID() . ':' . $d->departmentID();
                $edit = '';
                $view = '';
                if (isset($perms[$key]) && $perms[$key]['edit']) {
                    $edit = 'checked';
                    $view = 'checked';
                } elseif (isset($perms[$key]) && $perms[$key]['view']) {
                    $view = 'checked';
                }
                $table .= sprintf('<tr><td>%s %s</td>
                        <td><input type="checkbox" %s name="edit[]" value="%s" /></td>
                        <td><input type="checkbox" %s name="view[]" value="%s" /></td>
                        </tr>',
                    $s->storeName(),
                    $d->departmentName(),
                    $edit, $key, $view, $key
                );
            }
        }

        return <<<HTML
<h3>{$user['name']} ({$user['real_name']})</h3>
<form method="post" action="PermissionsPage.php">
<input type="hidden" name="illness" value="{$this->illness}" />
<table class="table table-bordered table-striped">
<tr><th>Store & Department</th><th>Edit</th><th>View</th></tr>
{$table}
</table>
<p>
    <button type="submit" class="btn btn-default">Save Changes</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="PermissionsPage.php" class="btn btn-default">Home</a>
</p>
</form>
HTML;
    }

    protected function get_view()
    {
        $hrEditors = $this->getUsers('hr_editor');
        $hrEdit = '';
        foreach ($hrEditors as $row) {
            $hrEdit .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $row['uid'], $row['name']);
        }
        $hrViewers = $this->getUsers('hr_viewer');
        $hrView = '';
        foreach ($hrViewers as $row) {
            $hrView .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $row['uid'], $row['name']);
        }

        $illness = $this->getUsers('hr_editor', 'illness_editor');
        $illnessT = '';
        foreach ($illness as $row) {
            $illnessT .= sprintf('<tr><td>%s</td><td>%s</td>
                <td><a href="PermissionsPage.php?illness=%d">Details</a></td></tr>',
                $row['uid'], $row['name'], $row['uid']);
        }

        return <<<HTML
<h3>System Editors</h3>
<p>These people have access to everything</p>
<table class="table table-bordered small">
    <tr><th>ID</th><th>Name</th></tr>
    {$hrEdit}
</table>
<h3>System Viewers</h3>
<p>These people see most everything but not necessarily edit</p>
<table class="table table-bordered small">
    <tr><th>ID</th><th>Name</th></tr>
    {$hrEdit}
</table>
<h3>Illness Logging</h3>
<p>These people can view and/or edit illness logging entries</p>
<table class="table table-bordered small">
    <tr><th>ID</th><th>Name</th><th>Specific Access</th></tr>
    {$illnessT}
</table>
<p>
    <a href="../HrMenu.php" class="btn btn-default">Main Menu</a>
</p>
HTML;
    }

    private function getUsers($perm)
    {
        if (!is_array($perm)) {
            $perm = array($perm);
        }
        list($inStr, $args) = $this->connection->safeInClause($perm);
        $prep = $this->connection->prepare('SELECT
            u.uid, u.name
            FROM ' . FannieDB::fqn('Users', 'op') . ' AS u
                INNER JOIN ' . FannieDB::fqn('userPrivs', 'op') . ' AS p ON u.uid=p.uid
            WHERE p.auth_class IN (' . $inStr . ')
            ORDER BY u.name');  
        return $this->connection->getAllRows($prep, $args);
    }
}

FannieDispatch::conditionalExec();

