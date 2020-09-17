<?php

class MenuScreenDevicesModel extends BasicModel
{

    protected $name = "MenuScreenDevices";

    protected $columns = array(
    'menuScreenDeviceID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'ip' => array('type'=>'VARCHAR(255)'),
    );

}

