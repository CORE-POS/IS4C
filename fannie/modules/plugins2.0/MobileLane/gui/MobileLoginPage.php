<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileLanePage')) {
    include(__DIR__ . '/../lib/MobileLanePage.php');
}

class MobileLoginPage extends MobileLanePage
{
    protected function post_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $empP = $dbc->prepare('SELECT emp_no FROM employees WHERE CashierPassword=? OR AdminPassword=?');
        $emp = $dbc->getValue($empP, array($this->id, $this->id));
        if ($emp === false) {
            $this->err_msg = 'Invalid login';
            return true;
        }

        $settings= $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);

        $sessions = new MobileSessionsModel($dbc);
        $sessions->empNo($emp);
        $active = $sessions->load();
        if (!$active) {
            $regP = $dbc->prepare('SELECT MAX(registerNo) FROM MobileSessions');
            $reg = $dbc->getValue($regP);
            $reg = ($reg === false) ? 1000 : $reg+1;
        } else {
            $reg = $sessions->registerNo();
        }

        return "MobileMainPage.php?e={$emp}&r={$reg}";
    }

    protected function post_id_view()
    {
        return '<div class="alert alert-danger">' . $this->err_msg . '</div>'
            . $this->get_view();
    }

    protected function get_view()
    {
        $this->addOnloadCommand("\$('#login').focus();\n");
        return <<<HTML
<form method="post">
    <div class="form-group">
        Enter password:
        <input type="password" id="login" class="form-control" name="id" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-lg">Login</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

