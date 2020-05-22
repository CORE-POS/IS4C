<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('COREPOS\\Fannie\\Plugin\\HrWeb\\sql\\ScreeningEmployeesModel')) {
    include(__DIR__ . '/../sql/ScreeningEmployeesModel');
}

class ScreeningEmployeePage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('illness_editor', 'hr_editor', 'illness_viewer');
    protected $header = 'Screening Logins';
    protected $title = 'Screening Logins';
    public $discoverable = false;

    protected function delete_id_handler()
    {
        $prep = $this->connection->prepare("
            UPDATE " . FannieDB::fqn('ScreeningEmployees', 'plugin:HrWebDB') . "
            SET deleted=1
            WHERE screeningEmployeeID=?");
        $this->connection->execute($prep, array($this->id));

        return 'ScreeningEmployeePage.php';
    }

    protected function post_id_handler()
    {
        $prep = $this->connection->prepare("
            UPDATE " . FannieDB::fqn('ScreeningEmployees', 'plugin:HrWebDB') . "
            SET name=?, code=?
            WHERE screeningEmployeeID=?");
        $this->connection->execute($prep, array(
            FormLib::get('name'),
            FormLib::get('login'),
            $this->id,
        ));

        return 'ScreeningEmployeePage.php';
    }

    protected function post_handler()
    {
        $prep = $this->connection->prepare("INSERT INTO "
            . FannieDB::fqn('ScreeningEmployees', 'plugin:HrWebDB') . "
            (name, code) VALUES (?, ?)");
        $this->connection->execute($prep, array(
            FormLib::get('name'),
            FormLib::get('login'),
        ));

        return 'ScreeningEmployeePage.php';
    }

    protected function get_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new COREPOS\Fannie\Plugin\HrWeb\sql\ScreeningEmployeesModel($dbc);
        $model->screeningEmployeeID($this->id);
        $model->load();
        $obj = $model->toStdClass();

        return <<<HTML
<form method="post" action="ScreeningEmployeePage.php">
    <input type="hidden" name="id" value="{$obj->screeningEmployeeID}" />
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{$obj->name}" />
    </div>
    <div class="form-group">
        <label>Login</label>
        <input type="text" name="login" class="form-control" value="{$obj->code}" />
    </div>
    <div class="form-group">
        <button class="btn btn-default" type="submit">Save</button>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="ScreeningEmployeePage.php" class="btn btn-default">Back</a>
    </div>
</form>
HTML;
    }

    protected function put_view()
    {
        return <<<HTML
<form method="post" action="ScreeningEmployeePage.php">
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required />
    </div>
    <div class="form-group">
        <label>Login</label>
        <input type="text" name="login" class="form-control" required />
    </div>
    <div class="form-group">
        <button class="btn btn-default" type="submit">Save</button>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="ScreeningEmployeePage.php" class="btn btn-default">Back</a>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new COREPOS\Fannie\Plugin\HrWeb\sql\ScreeningEmployeesModel($dbc);
        $model->deleted(0);

        $noEdit = 'collapse';
        if (FannieAuth::validateUserQuiet('illness_editor') || FannieAuth::validateUserQuiet('hr_editor')) {
            $noEdit = '';
        }

        $body = '';
        foreach ($model->find('name') as $obj) {
            $obj = $obj->toStdClass();
            $body .= sprintf('<tr><td>%s</td><td>%s</td>
                <td class="%s"><a href="ScreeningEmployeePage.php?id=%d">Edit</a></td>
                <td class="%s"><a href="ScreeningEmployeePage.php?id=%d&_method=delete">Delete</a></td>
                </tr>',
                $obj->name, $obj->code,
                $noEdit, $obj->screeningEmployeeID,
                $noEdit, $obj->screeningEmployeeID);
        }

        return <<<HTML
<p>
    <a href="ScreeningEmployeePage.php?_method=put" class="btn btn-default {$noEdit}">Add Entry</a>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="../HrMenu.php" class="btn btn-default">Main Menu</a>
</p>
<table class="table table-bordered table-striped">
    <tr><th>Name</th><th>Login</th><th colspan="2" class="{$noEdit}"></th></tr>
    {$body}
</table>
<p>
    <a href="ScreeningEmployeePage.php?_method=put" class="btn btn-default {$noEdit}">Add Entry</a>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="../HrMenu.php" class="btn btn-default">Main Menu</a>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

