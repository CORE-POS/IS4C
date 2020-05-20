<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class ScreeningPage extends FannieRESTfulPage
{

    public function preprocess()
    {
        $ret = parent::preprocess();

        return $ret;
    }

    public function getHeader()
    {
        $ignore = parent::getHeader();

        return <<<HTML
<html>
<head>
    <link rel="stylesheet" type="text/css" href="../../../../src/javascript/composer-components/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../../../../src/javascript/composer-components/bootstrap-default/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../../../../src/javascript/composer-components/bootstrap-default/css/bootstrap-theme.min.css">
</head>
<body>
    <div class="container">
        <div class="row" align="center">
            <div class="col-sm-10">
HTML;
    }

    public function getFooter()
    {
        return <<<HTML
</div>
</div>
</div>
HTML;
    }

    protected function post_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $temp = FormLib::get('temp');
        $cough = FormLib::get('cough') ? 1 : 0;
        $breath = FormLib::get('breath') ? 1 : 0;
        $fever = FormLib::get('fever') ? 1 : 0;
        $vomit = FormLib::get('vomit') ? 1 : 0;
        $taste = FormLib::get('taste') ? 1 : 0;
        $empID = $this->id;

        $prep = $dbc->prepare("INSERT INTO " . FannieDB::fqn('ScreeningEntries', 'plugin:HrWebDB') . "
            (screeningEmployeeID, tdate, temperature, dryCough, shortnessBreath, feverChills,
            vomitDiah, tasteSmell) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $args = array(
            $empID,
            date('Y-m-d H:i:s'),
            $temp,
            $cough,
            $breath,
            $fever,
            $vomit,
            $taste,
        );
        $dbc->execute($prep, $args);
        if ($temp >= 100.5 || $cough || $breath || $fever || $vomit || $taste) {
            return <<<HTML
<div style="font-size: 200% !important;">
    <div class="alert alert-danger">You've selected symptom(s)</div>
Please isolate yourself from others immediately, <b>do not clock in for work and go home</b>.
Contact your manager and HR at 218.491.4821.
</div>
<p style="font-size: 200%;">
    <a href="ScreeningPage.php" class="btn btn-default btn-lg btn-block">Clear This Screen</a>
</p>
HTML;
        }

        return '<div class="alert alert-success">Entry logged</div>' . $this->get_view();
    }

    protected function post_view()
    {
        $empID = FormLib::get('lookup', -1);
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $prep = $dbc->prepare("SELECT screeningEmployeeID, name FROM "
            . FannieDB::fqn('ScreeningEmployees', 'plugin:HrWebDB') . " WHERE code=? AND deleted=0");
        $info = $dbc->getRow($prep, array($empID));
        if ($info === false) {
            return '<div class="alert alert-danger">ID not found</div>' . $this->get_view();
        }

        $this->addOnloadCommand("\$('input[name=temp]').focus();");

        return <<<HTML
<form method="post" action="ScreeningPage.php">
<p>
    <input type="hidden" name="id" value="{$info['screeningEmployeeID']}" />
    <h3>Hi {$info['name']}</h3>
</p>
<p>
    <div class="input-group">
        <span class="input-group-addon">Today's Temperature</span>
        <input type="number" step="0.1" class="form-control input-lg" required name="temp" inputmode="decimal" id="temp" />
    </div>
</p>
<div class="row">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(1);" style="height: 100px; font-size: 300%;">1</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(2);" style="height: 100px; font-size: 300%;">2</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(3);" style="height: 100px; font-size: 300%;">3</button>
    </div>
</div>
<div class="row" style="margin-top: 1em;">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(4);" style="height: 100px; font-size: 300%;">4</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(5);" style="height: 100px; font-size: 300%;">5</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(6);" style="height: 100px; font-size: 300%;">6</button>
    </div>
</div>
<div class="row" style="margin-top: 1em;">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(7);" style="height: 100px; font-size: 300%;">7</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(8);" style="height: 100px; font-size: 300%;">8</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(9);" style="height: 100px; font-size: 300%;">9</button>
    </div>
</div>
<div class="row" style="margin-top: 1em;">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="clearDigits();" style="height: 100px; font-size: 300%;">CLEAR</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(0);" style="height: 100px; font-size: 300%;">0</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit('.');" style="height: 100px; font-size: 300%;">.</button>
    </div>
</div>
<p>
<h3>Are you experiencing any COVID-19 related symptoms?</h3>
</p>
<table class="table" style="font-size: 200%;">
<tr>
    <th>Dry Cough</th>
    <td><label class="radio-inline">
        <input type="radio" name="cough" value="1" required />
        Yes
    </label></td>
    <td><label class="radio-inline">
        <input type="radio" name="cough" value="0" required />
        No
    </label></td>
</tr>
<tr>
    <th>Shortness of Breath</th>
    <td><label class="radio-inline">
        <input type="radio" name="breath" value="1" required />
        Yes
    </label></td>
    <td><label class="radio-inline">
        <input type="radio" name="breath" value="0" required />
        No
    </label></td>
</tr>
<tr>
    <th>Fever of 100.5 or above / Chills</th>
    <td><label class="radio-inline">
        <input type="radio" name="fever" value="1" required />
        Yes
    </label></td>
    <td><label class="radio-inline">
        <input type="radio" name="fever" value="0" required />
        No
    </label></td>
</tr>
<tr>
    <th>Vomiting / Diarrhea</th>
    <td><label class="radio-inline">
        <input type="radio" name="vomit" value="1" required />
        Yes
    </label></td>
    <td><label class="radio-inline">
        <input type="radio" name="vomit" value="0" required />
        No
    </label></td>
</tr>
<tr>
    <th>Loss of Taste or Smell</th>
    <td><label class="radio-inline">
        <input type="radio" name="taste" value="1" required />
        Yes
    </label></td>
    <td><label class="radio-inline">
        <input type="radio" name="taste" value="0" required />
        No
    </label></td>
</tr>
</table>
<p style="font-size: 200%;">
    <button type="submit" class="btn btn-default btn-lg">Save Info</button>
</p>
<br /><br />
<p style="font-size: 200%;">
    <a href="ScreeningPage.php" class="btn btn-default btn-lg">Cancel</a>
</p>
<script type="text/javascript">
function addDigit(num) {
    var cur = $('#temp').val();
    $('#temp').val(cur + "" + num);
}
function clearDigits() {
    $('#temp').val('');
}
</script>
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post" action="ScreeningPage.php">
<p>
    <div class="input-group">
        <span class="input-group-addon">ID#</span>
        <input type="text" id="faker" class="form-control input-lg" />
    </div>
    <input type="hidden" id="real" value="" name="lookup" />
</p>
<div class="row">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(1);" style="height: 100px; font-size: 300%;">1</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(2);" style="height: 100px; font-size: 300%;">2</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(3);" style="height: 100px; font-size: 300%;">3</button>
    </div>
</div>
<div class="row" style="margin-top: 1em;">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(4);" style="height: 100px; font-size: 300%;">4</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(5);" style="height: 100px; font-size: 300%;">5</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(6);" style="height: 100px; font-size: 300%;">6</button>
    </div>
</div>
<div class="row" style="margin-top: 1em;">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(7);" style="height: 100px; font-size: 300%;">7</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(8);" style="height: 100px; font-size: 300%;">8</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(9);" style="height: 100px; font-size: 300%;">9</button>
    </div>
</div>
<div class="row" style="margin-top: 1em;">
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="clearDigits();" style="height: 100px; font-size: 300%;">CLEAR</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="button" 
            onclick="addDigit(0);" style="height: 100px; font-size: 300%;">0</button>
    </div>
    <div class="col-sm-4">
        <button class="btn btn-default btn-lg btn-block" type="submit" style="height: 100px; font-size: 300%;">ENTER</button>
    </div>
</div>
</form>
<script type="text/javascript">
function addDigit(num) {
    $('#faker').val($('#faker').val() + 'X');
    var cur = $('#real').val();
    $('#real').val(cur + "" + num);
}
function clearDigits() {
    $('#faker').val('');
    $('#real').val('');
}
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

