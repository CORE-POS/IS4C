<?php

class CTDB
{
    private static $instance = null;
    private static function init()
    {
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        return new SQLManager($settings['CTHost'],'mssqlnative','DataDir',$settings['CTUser'],$settings['CTPassword']);
    }

    public static function get($name='DataDir')
    {
        if (self::$instance === null) {
            self::$instance = self::init();
        }

        self::$instance->selectDB($name);

        return self::$instance;
    }
}

