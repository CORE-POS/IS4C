<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsEmpCountsPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;

    protected $header = 'Daily Employee Counts';
    protected $title = 'Daily Employee Counts';

    protected function post_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['OverShortDatabase']);
        $dateID = date('Ymd', strtotime($this->id));
        $storeID = FormLib::get('store');
        $empID = FormLib::get('emp');
        $type = FormLib::get('submit');
        $amt = FormLib::get('amt');
        if ($type == 'drop') {
            $model = new DailyTillCountsModel($dbc);
            $model->dateID($dateID);
            $model->storeID($storeID);
            $model->registerNo($empID);
            $model->dropAmount($amt);
            $model->save();
        } elseif ($type == 'adv') {
            $model = new DailyEmployeeCountsModel($dbc);
            $model->dateID($dateID);
            $model->storeID($storeID);
            $model->empNo($empID);
            $model->dropAmount($amt);
            $model->countType('advance');
            $model->save();
        } elseif ($type == 'mod') {
            $model = new DailyEmployeeCountsModel($dbc);
            $model->dateID($dateID);
            $model->storeID($storeID);
            $model->empNo($empID);
            $model->dropAmount($amt);
            $model->countType('mod');
            $model->save();
        }

        return '<div class="alert alert-success">Saved</div>'
            . $this->get_id_view();
    }

    protected function get_id_view()
    {
        $dateID = date('Ymd', strtotime($this->id));
        $storeID = FormLib::get('store');
        $empID = FormLib::get('emp');
        $type = FormLib::get('submit');
        $empP = $this->connection->prepare("SELECT FirstName, LastName FROM employees WHERE emp_no=?");
        $emp = $this->connection->getRow($empP, array($empID));
        if ($type == 'drop') {
            $prep = $this->connection->prepare("SELECT dropAmount 
                FROM " . FannieDB::fqn('DailyTillCounts', 'plugin:OverShortDatabase') . "
                WHERE dateID=?
                    AND storeID=?
                    AND registerNo=?");
            $amount = $this->connection->getValue($prep, array($dateID, $storeID, $empID));
            $label = 'Drop Entry';
        } elseif ($type == 'adv') {
            $prep = $this->connection->prepare("SELECT dropAmount 
                FROM " . FannieDB::fqn('DailyEmployeeCounts', 'plugin:OverShortDatabase') . "
                WHERE dateID=?
                    AND storeID=?
                    AND empNo=?
                    AND countType='advance'");
            $amount = $this->connection->getValue($prep, array($dateID, $storeID, $empID));
            $label = 'Advance Entry';
        } elseif ($type == 'mod') {
            $prep = $this->connection->prepare("SELECT dropAmount 
                FROM " . FannieDB::fqn('DailyEmployeeCounts', 'plugin:OverShortDatabase') . "
                WHERE dateID=?
                    AND storeID=?
                    AND empNo=?
                    AND countType='mod'");
            $amount = $this->connection->getValue($prep, array($dateID, $storeID, $empID));
            $label = 'MOD Entry';
        }
        $this->addOnloadCommand("\$('input[name=amt]').focus();");
        return <<<HTML
<form method="post" action="OsEmpCountsPage.php">
    <h4>$label for {$this->id} {$emp['FirstName']} {$emp['LastName']}</h4>
    <div class="form-group">
        <label>Total</label>
        <input type="text" class="form-control" value="{$amount}" name="amt" />
    </div>
    <div class="form-group">
        <input type="hidden" name="id" value="{$this->id}" />
        <input type="hidden" name="store" value="{$storeID}" />
        <input type="hidden" name="emp" value="{$empID}" />
        <button type="submit" class="btn btn-default" name="submit" value="{$type}">Save</button>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="OsEmpCountsPage.php" class="btn btn-default">Back</a>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        $date = date('Y-m-d');
        $this->addOnloadCommand("\$('input[name=emp]').focus();");
        return <<<HTML
<form method="get" action="OsEmpCountsPage.php">
<p class="form-inline">
    <label>Cashier #</label>
    <input type="text" name="emp" class="form-control" value="" />
    <label>Date</label>
    <input type="text" name="id" class="form-control date-field" value="{$date}" />
    <label>Store</label>
    {$stores['html']}
</p>
<p class="form-inline">
    <button type="submit" class="btn btn-default" name="submit" value="adv">Advance</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button type="submit" class="btn btn-default" name="submit" value="drop">Drop</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button type="submit" class="btn btn-default" name="submit" value="mod">MOD</button>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

