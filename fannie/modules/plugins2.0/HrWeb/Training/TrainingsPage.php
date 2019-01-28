<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

use COREPOS\Fannie\Plugin\HrWeb\sql\TrainingsModel as TrainingsModel;

class TrainingsPage extends FannieRESTfulPage
{
    protected $header = 'Trainings';
    protected $title = 'Trainings';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'hr_viewer');

    protected function post_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $training = new TrainingsModel($dbc);
        $newName = trim(FormLib::get('name'));
        if (!empty($newName)) {
            $training->trainingName($newName);
            $exists = $training->find();
            if (count($exists) == 0) {
                $training->reset();
                $training->trainingName($newName);
                $training->save();
           }
        }

        return 'TrainingsPage.php';
     }

     protected function get_view()
     {
         $editCSS = FannieAuth::validateUserQuiet('hr_editor') ? '' : 'collapse';
         $res = $this->connection->query('
            Select trainingID,
            trainingName
            FROM ' . FannieDB::fqn('Trainings', 'plugin:HrWebDB') . '
            ORDER BY trainingName');
         $table = '';
         while ($row = $this->connection->fetchRow($res)) {
            $table .= "<tr><td>{$row['trainingName']}</td><td>
            <a href=\"TrainingsPage.php?id={$row['trainingID']}\">Edit</a>
            </td></tr>";
           
         }

         return <<<HTML
<p><a href="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
<table class="table table=bordered table-striped">
    <tr>
    <th>Name</th>
    <th>Edit</th>
    </tr>
    {$table}
</table>
<div class="panel panel-default ($editCSS}">
    <div class="panel-heading">Create Training<div>
    <div class="panel-body">
        <form method="post" action="TrainingsPage.php">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Create Training</button>
            </div>
        </form>
    </div>
</div>
<p><a hre="../HrMenu.php" class="btn btn-default">Main Menu</a></p>
HTML;
    }
}

FannieDispatch::conditionalExec();

