<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class VoidEndPoint extends JsonEndPoint
{
    protected function post($json)
    {
        $dbc = $this->dbc;
        $ret = array('error' => false);
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        $model = new MobileTransModel($dbc);
        $model->store_row_id($json['id']);
        $exists = $model->load();
        if ($exists === false) {
            $ret['error']  = 'Item not found';
        } elseif ($model->voided() == 1) {
            $ret['error'] = 'Item already voided';
        } else {
            $mgr = new MobileTransManager($dbc, $this->config);
            $model->voided(1);
            $model2 = new MobileTransModel($dbc);
            foreach ($model->getColumns as $name => $info) {
                if ($name !== 'store_row_id') {
                    $model2->$name($model->$name());
                }
            }
            $model2->trans_status('V');
            $model2->quantity(-1*$model->quantity);
            $model2->ItemQtty(-1*$model->ItemQtty);
            $model2->total(-1*$model->total);
            $model2->unitPrice(-1*$model->unitPrice);
            $model2->regPrice(-1*$model->regPrice);
            $saved = $model2->save();
            if ($saved === false) {
                $ret['error'] = 'Error voiding item';
            } else {
                $model->save();
                $ret['item'] = array(
                    'id' => $saved,
                    'upc' => $model2->upc(),
                    'description' => $model2->description(),
                    'total' => $model2->total(),
                );
            }
        }

        return $ret;
    }
}

JsonEndPoint::dispatch('VoidEndPoint');

