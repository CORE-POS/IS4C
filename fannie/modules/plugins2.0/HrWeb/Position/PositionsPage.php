<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

use COREPOS\Fannie\Plugin\HrWeb\sql\PositionsModel;

class PositionsPage extends FannieRESTfulPage
{
    protected $header = 'Positions';
    protected $title = 'Positions';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'hr_viewer');

    protected function post_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new PositionsModel($dbc);
        $newName = trim(FormLib::get('name'));
        if (!empty($newName)) {
            $model->positionName($newName);
            $exists = $model->find();
            if (count($exists) == 0) {
                $model->reset();
                $model->positionName($newName);
                $model->save();
            }
        }

        return 'PositionsPage.php';
    }

    protected function get_view()
    {
        $editCSS = FannieAuth::validateUserQuiet('hr_editor') ? '' : 'collapse';
        $res = $this->connection->query('
            SELECT positionName
            FROM ' . FannieDB::fqn('Positions', 'plugin:HrWebDB') . '
            ORDER BY positionName');
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= "<tr><td>{$row['positionName']}</td></tr>";
        }

        return <<<HTML
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
<table class="table table-bordered table-striped">
    <tr><th>Name</th></tr>
    {$table}
</table>
<div class="panel panel-default {$editCSS}">
    <div class="panel-heading">Create Position</div>
    <div class="panel-body">
        <form method="post" action="PositionsPage.php">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Create Position</button>
            </div>
        </form>
    </div>
</div>
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
HTML;
    }
}

FannieDispatch::conditionalExec();

