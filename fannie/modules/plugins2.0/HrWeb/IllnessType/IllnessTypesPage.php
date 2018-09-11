<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

use COREPOS\Fannie\Plugin\HrWeb\sql\IllnessTypesModel;

class IllnessTypesPage extends FannieRESTfulPage
{
    protected $header = 'IllnessTypes';
    protected $title = 'IllnessTypes';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'illness_editor');

    protected function post_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $model = new IllnessTypesModel($dbc);
        $newName = trim(FormLib::get('name'));
        if (!empty($newName)) {
            $model->illnessType($newName);
            $exists = $model->find();
            if (count($exists) == 0) {
                $model->reset();
                $model->illnessType($newName);
                $model->exclusionary(FormLib::get('ex') ? 1 : 0);
                $model->save();
            }
        }

        return 'IllnessTypesPage.php';
    }

    protected function get_view()
    {
        $res = $this->connection->query('
            SELECT illnessType, exclusionary
            FROM ' . FannieDB::fqn('IllnessTypes', 'plugin:HrWebDB') . '
            ORDER BY illnessType');
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= "<tr><td>{$row['illnessType']}</td><td>
                " . ($row['exclusionary'] ? 'Yes' : 'No') . "
                </td></tr>";
        }

        return <<<HTML
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
<table class="table table-bordered table-striped">
    <tr><th>Name</th><th>Exclusionary</th></tr>
    {$table}
</table>
<div class="panel panel-default">
    <div class="panel-heading">Create Position</div>
    <div class="panel-body">
        <form method="post" action="IllnessTypesPage.php">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" />
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="ex" value="1" />
                    Exclusionary
                </label>
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

