<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileTransManager')) {
    include(__DIR__ . '/../../lib/MobileTransManager.php');
}

class CancelEndPoint extends JsonEndPoint
{
    protected function post($json)
    {
        $dbc = $this->dbc;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $ret = array('error' => false);
        
        $canP = $dbc->prepare("
            UPDATE MobileTrans
            SET trans_status='X'
            WHERE emp_no=?
                AND register_no=?"); 
        $dbc->execute($canP, array($json['e'], $json['r']));

        $mgr = new MobileTransManager($dbc, $this->config);
        $mgr->endTransaction($json['e'], $json['r']);

        return $ret;
    }
}

JsonEndPoint::dispatch('CancelEndPoint');

