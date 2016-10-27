<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class LoginEndPoint extends JsonEndPoint
{
    protected function post($json)
    {
        $ret = array('error' => 'Invalid login');
        if (isset($json['passwd'])) {
            $dbc = $this->dbc;
            $dbc->selectDB($this->config->get('OP_DB'));
            $empP = $dbc->prepare('SELECT emp_no FROM employees WHERE CashierPassword=? OR AdminPassword=?');
            $emp = $dbc->getValue($empP, array($json['passwd'], $json['passwd']));
            if ($emp !== false) {
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
                
                $ret = array(
                    'emp' => $emp,
                    'reg' => $reg,
                    'error' => false,
                );
            } 
        }

        return $ret;
    }
}

JsonEndPoint::dispatch('LoginEndPoint');

