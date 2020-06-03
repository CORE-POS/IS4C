<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class ScreeningPage extends FannieRESTfulPage
{

    public function preprocess()
    {
        $this->addRoute('get<finish>');
        $ret = parent::preprocess();

        return $ret;
    }

    public function getHeader()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        $ignore = parent::getHeader();

        return <<<HTML
<html>
<head>
    <link rel="stylesheet" type="text/css" href="../../../../src/javascript/composer-components/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../../../../src/javascript/composer-components/bootstrap-default/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../../../../src/javascript/composer-components/bootstrap-default/css/bootstrap-theme.min.css">
    <meta name="viewport" content="width=device-width, user-scalable=no" />
</head>
<body>
    <div class="container">
        <div class="row" align="center">
            <div class="col-sm-12">
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

        $any = FormLib::get('any') ? 1 : 0;
        $highTemp = FormLib::get('highTemp') ? 1 : 0;
        $empID = $this->id;

        $prep = $dbc->prepare("INSERT INTO " . FannieDB::fqn('ScreeningEntries', 'plugin:HrWebDB') . "
            (screeningEmployeeID, tdate, highTemp, anySymptom) 
            VALUES (?, ?, ?, ?)");
        $args = array(
            $empID,
            date('Y-m-d H:i:s'),
            $highTemp,
            $any,
        );
        $dbc->execute($prep, $args);

        $this->addOnloadCommand("setTimeout(function() { \$('#finishForm').submit(); }, 250);");

        if ($highTemp || $any) {

            $prep = $dbc->prepare("SELECT screeningEmployeeID, name FROM "
                . FannieDB::fqn('ScreeningEmployees', 'plugin:HrWebDB') . " WHERE screeningEmployeeID=? AND deleted=0");
            $info = $dbc->getRow($prep, array($empID));
            $subject = 'Screening Positive Notification';
            $body = $info['name'] . ' reported symptoms at the screening station.';
            $to = 'hr@wholefoods.coop, shannigan@wholefoods.coop';
            $headers = "From: hillside@wholefoods.coop\r\n";
            mail($to, $subject, $body, $headers);

            return <<<HTML
<div style="font-size: 200% !important;">
    <div class="alert alert-danger">You've selected symptom(s)</div>
Please isolate yourself from others immediately, <b>do not clock in for work and go home</b>.
Contact your manager and HR at 218.491.4821.
</div>
<p style="font-size: 200%;">
    <a href="ScreeningPage.php" class="btn btn-default btn-lg btn-block">Clear This Screen</a>
</p>
<form id="finishForm" action="ScreeningPage.php"><input type="hidden" name="finish" value="1" /></form>
HTML;
        }

        return '<div class="alert alert-success">Entry logged</div>' . $this->get_view()
            . '<form id="finishForm" action="ScreeningPage.php"><input type="hidden" name="finish" value="0" /></form>';
    }

    protected function get_finish_view()
    {
        if ($this->finish) {
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

        $prep = $dbc->prepare("SELECT nonce FROM " . FannieDB::fqn('ScreeningNonce', 'plugin:HrWebDB'));
        $nonce = $dbc->getValue($prep);
        $dbc->query("UPDATE " . FannieDB::fqn('ScreeningNonce', 'plugin:HrWebDB') . " SET nonce=''");
        if ($nonce === false || strlen($nonce) == 0 || $nonce != FormLib::get('nonce')) {
            return '<div class="alert alert-danger">Session expired</div>' . $this->get_view();
        }

        $this->addOnloadCommand("\$('input[name=temp]').focus();");

        return <<<HTML
<div id="hideAll" style="display: none;">
<form method="post" action="ScreeningPage.php"
    onsubmit="document.getElementById('hideAll').style.display='none';">
<p>
    <input type="hidden" name="id" value="{$info['screeningEmployeeID']}" />
    <h3>Hi {$info['name']}</h3>
</p>
<p>
<h3>Is your temperature above 100.4 degrees?</h3>
</p>
<p style="font-size: 200%;">
    <label class="radio-inline">
        <input type="radio" name="highTemp" value="1" required />
        Yes
    </label>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <label class="radio-inline">
        <input type="radio" name="highTemp" value="0" required />
        No
    </label>
</p>
<hr />
<p>
<h3>Are you experiencing any COVID-19 related symptoms?</h3>
</p>
<table class="table" style="font-size: 200%;">
<tr>
    <th>Dry Cough</th>
</tr>
<tr>
    <th>Shortness of Breath</th>
</tr>
<tr>
    <th>Fever of 100.5 or above / Chills</th>
</tr>
<tr>
    <th>Vomiting / Diarrhea</th>
</tr>
<tr>
    <th>Loss of Taste or Smell</th>
</tr>
</table>
<p style="font-size: 200%;">
    <label class="radio-inline">
        <input type="radio" name="any" value="1" required />
        Yes
    </label>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <label class="radio-inline">
        <input type="radio" name="any" value="0" required />
        No
    </label>
</p>
<hr />
<p style="font-size: 200%;">
    <button type="submit" class="btn btn-default btn-lg">Save Info</button>
</p>
<br /><br />
<p style="font-size: 200%;">
    <a href="ScreeningPage.php" class="btn btn-default btn-lg">Cancel</a>
</p>
</div>
<script type="text/javascript">
function addDigit(num) {
    console.log(num);
    var cur = $('#temp').val();
    $('#temp').val(cur + "" + num);
}
function clearDigits() {
    $('#temp').val('');
}
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    } else {
        document.getElementById('hideAll').style.display = 'block';
    }
}
</script>
HTML;
    }

    protected function get_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $nonce = uniqid('', true);
        $setP = $dbc->prepare("UPDATE " . FannieDB::fqn('ScreeningNonce', 'plugin:HrWebDB') . " SET nonce=?");
        $dbc->execute($setP, array($nonce));

        return <<<HTML
<form method="post" action="ScreeningPage.php">
<input type="hidden" name="nonce" value="{$nonce}" />
<p>
    <div class="input-group">
        <span class="input-group-addon">ID#</span>
        <input type="text" id="faker" class="form-control input-lg" disabled style="background: white;" />
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
<h2>ATTENTION ALL EMPLOYEES COVID-19 DAILY SCREENING</h2>
<p style="text-align:left; font-size: 150%; color: red; background-color: #fdb900;">
For Use Only During an International Pandemic as Declared by The World Health Organization
</p>
<p style="text-align: left; font-size: 150%;">
This is to assure the safety of all Whole Foods Co-op (WFC) team members and our customers and any private health information you share will be safeguarded under HIPPA.
</p>
<p style="text-align: left; font-size: 150%;">
In order for you to work in-person at WFC you must complete this screening DAILY before entering the workspace.   Failure to do so or failure to accurately report will be considered employee misconduct that could lead to disciplinary action up to and including termination of employment. 
</p>
<p style="text-align: left; font-size: 150%;">
<span style="background-color: #fdb900;">If you answer YES to any question, you are NOT to report to work.</span> Contact your supervisor and HR at 218.491.4821 immediately.
</p>
<p style="text-align: left; font-size: 150%;">
If you answer NO to all of the questions and you have taken your temperature you are good to go!  - Just remember to wash your hands directly after clocking in.
</p>
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

