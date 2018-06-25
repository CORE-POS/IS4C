<?php

use COREPOS\pos\plugins\Plugin;

class Intercept extends Plugin
{
    public $plugin_settings = array();
    public $plugin_description = 'Intercept end of transaction command to add extra steps';

    public function plugin_transaction_rest()
    {
        /**
         * Flag so input is only intercepted once
         * per transaction
         */
        CoreLocal::set('Intercepted', 0);
    }
}

