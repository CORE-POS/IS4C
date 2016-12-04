<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class TenderEndPoint extends JsonEndPoint
{
    protected function get()
    {
        $dbc = $this->dbc;
        $dbc->selectDB($this->config->get('OP_DB'));
        $ret = array('error' => false, 'tenders' => array());
        $res = $dbc->query('SELECT TenderCode, TenderName FROM tenders ORDER BY TenderName');
        while ($row = $dbc->fetchRow($res)) {
            $ret['tenders'][] = array(
                'code' => $row['TenderCode'],
                'name' => $row['TenderName'],
            );
        }

        return $ret;
    }

    protected function post($json)
    {
        $ret = array('error' => false, 'ended'=>false);
        $dbc = $this->dbc;
        $dbc->selectDB($this->config->get('OP_DB'));
        $tender = new TendersModel($dbc);
        $tender->TenderCode($json['type']);
        $tender->load();

        $due = $json['amt']Due($dbc);

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $mgr = new MobileTransManager($dbc, $this->config);
        $trans = $mgr->getTransNo($json['e'], $json['r']);
        $model = new MobileTransModel($dbc);
        $model->datetime(date('Y-m-d H:i:s'));
        $model->emp_no($json['e']); 
        $model->register_no($json['r']);
        $model->trans_no($trans);
        $model->trans_type('T');
        $model->description($tender->TenderName());
        $model->upc('0');
        $model->trans_subtype($json['type']);
        $model->total(sprintf('%.2f', -1*$json['amt']));
        $model->save();

        if ($due - $json['amt'] < 0.005) { // transaction ends
            $change = sprintf('%.2f', $json['amt'] - $due);
            $model = new MobileTransModel($dbc);
            $model->datetime(date('Y-m-d H:i:s'));
            $model->emp_no($json['e']); 
            $model->register_no($json['r']);
            $model->trans_no($trans);
            $model->trans_type('T');
            $model->description('Change');
            $model->upc('0');
            $model->trans_subtype('CA');
            $model->total(sprintf('%.2f', $change));
            $model->save();

            $mgr->endTransaction($json['e'], $json['r']);
            $ret['ended'] = true;
        }

        return $ret;
    }
}

JsonEndPoint::dispatch('TenderEndPoint');

