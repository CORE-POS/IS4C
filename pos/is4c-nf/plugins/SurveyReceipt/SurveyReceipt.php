<?php

use COREPOS\pos\plugins\Plugin;

class SurveyReceipt extends Plugin
{
    public $plugin_settings = array(
        'SurveyCode' => array(
            'label' => 'Survey Code',
            'description' => 'Location identifier for the survey',
            'default'=> '',
        ),
    );

    public $plugin_description = 'SMG Survey Receipts';
}

