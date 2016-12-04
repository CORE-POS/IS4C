<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileTransManager')) {
    include(__DIR__ . '/../../lib/MobileTransManager.php');
}

class SuspendEndPoint extends JsonEndPoint
{
    protected function post($json)
    {
        $ret = array('error' => false);
        $dbc = $this->dbc;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);

        $canP = $dbc->prepare("
            UPDATE MobileTrans
            SET trans_status='X',
                charflag='S'
            WHERE emp_no=?
                AND register_no=?"); 
        $dbc->execute($canP, array($json['e'], $json['r']));

        $model = new MobileTransModel($dbc);
        $cols = array_keys($model->getColumns());
        $cols = implode(',', $colums);
        $sus_cols = str_replace('pos_row_id', 'trans_id', $cols);

        $xfer = $dbc->prepare("
            INSERT INTO " . $this->config->get('TRANS_DB') . $dbc->sep() . "suspended
                ({$sus_cols})
            SELECT {$cols}
            FROM MobileTrans
            WHERE emp_no=?
                AND register_no=?");
        $dbc->execute($xfer, array($json['e'], $json['r']));

        $mgr = new MobileTransManager($dbc, $this->config);
        $mgr->endTransaction($json['e'], $json['r']);

        return $ret;
    }
}

JsonEndPoint::dispatch('SuspendEndPoint');

