<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('COREPOS\\Fannie\\Plugin\\HrWeb\\sql\\ScreeningEmployeesModel')) {
    include(__DIR__ . '/../sql/ScreeningEmployeesModel');
}

class ScreeningManualEntry extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('illness_editor', 'hr_editor', 'illness_viewer');
    protected $header = 'Screening Logins';
    protected $title = 'Screening Logins';
    public $discoverable = false;

    protected function post_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $prep = $dbc->prepare("INSERT INTO " . FannieDB::fqn('ScreeningEntries', 'plugin:HrWebDB') . "
            (screeningEmployeeID, tdate, highTemp, anySymptom) 
            VALUES (?, ?, ?, ?)");
        $args = array(
            FormLib::get('emp'),
            FormLib::get('tdate'),
            FormLib::get('temp'),
            FormLib::get('sym'),
        );
        $dbc->execute($prep, $args);

        return '<div class="alert alert-success">Entry Added</div>' . $this->get_view();
    }

    protected function get_view()
    {
        $this->addScript('../../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen({search_contains: true});");

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $res = $dbc->query("SELECT screeningEmployeeID AS id, name FROM ScreeningEmployees ORDER BY name");
        $opts = '';
        while ($row = $dbc->fetchRow($res)) {
            $opts .= sprintf('<option value="%d">%s</option>', $row['id'], $row['name']);
        }

        return <<<HTML
<form method="post" action="ScreeningManualEntry.php">
<div class="form-group">
    <label>Employee</label>
    <select name="emp" class="form-control chosen">{$opts}</select>
</div>
<div class="form-group">
    <label>Date</label>
    <input type="text" name="tdate" required class="form-control date-field" />
</div>
<div class="form-group">
    <label>Temperature</label>
    <label><input type="radio" required name="temp" value="1" /> Yes</label>
    <label><input type="radio" required name="temp" value="0" /> No</label>
</div>
<div class="form-group">
    <label>Symptoms</label>
    <label><input type="radio" required name="sym" value="1" /> Yes</label>
    <label><input type="radio" required name="sym" value="0" /> No</label>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Add Entry</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="../HrMenu.php" class="btn btn-default">Main Menu</a>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

