<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class LogoutEndPoint extends JsonEndPoint
{
    protected function post($json)
    {
        $ret = array('error' => false);
        $dbc = $this->dbc;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $model = new MobileSessionsModel($dbc);
        $model->empNo($json['e']);
        $model->delete();

        return $ret;
    }
}

JsonEndPoint::dispatch('LogoutEndPoint');

