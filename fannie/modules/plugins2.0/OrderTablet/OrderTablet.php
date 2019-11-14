<?php

use COREPOS\Fannie\API\FanniePlugin;

class OrderTablet extends FanniePlugin
{
    public $plugin_settings = array(
    'OtPrintIP' => array('default'=>'','label'=>'Printer IP/Host',
            'description'=>'IP or hostname for printer'),
    );

    public $plugin_description = 'Tool to build, suspend, & print small orders';
}

