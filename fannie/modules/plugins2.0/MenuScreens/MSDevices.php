<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

if (!class_exists('MenuScreenDevicesModel')) {
    include(__DIR__ . '/models/MenuScreenDevicesModel.php');
}

class MSDevices extends FannieRESTfulPage
{
    protected $header = 'Menu Screen Devices';
    protected $title = 'Menu Screen Devices';

    public $discoverable = false;

    protected function post_id_handler()
    {
        if (FormLib::get('ip')) {
            $model = new MenuScreenDevicesModel($this->connection);
            $model->menuScreenDeviceID($this->id);
            $model->ip(FormLib::get('ip'));
            $model->save();

            echo 'OK';
            return false;
        }

        echo 'NOP';
        return false;
    }

    protected function get_view()
    {
        $model = new MenuScreenDevicesModel($this->connection);
        $ret = '<ul>';
        foreach ($model->find() as $obj) {
            $ret .= sprintf('<li>#%d <a href="http://%s/">%s</a></li>', $obj->menuScreenDeviceID(), trim($obj->ip()), $obj->ip());
        }
        $ret .= '</ul>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

