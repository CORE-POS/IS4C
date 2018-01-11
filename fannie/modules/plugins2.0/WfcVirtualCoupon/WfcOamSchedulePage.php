<?php

use COREPOS\Fannie\API\FannieCRUDPage;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class WfcOamSchedulePage extends FannieCRUDPage
{
    protected $header = 'WFC OAM Schedule';
    protected $title = 'WFC OAM Schedule';
    public $description = '[WFC OAM Schedule] defines periods where OAM deals are active to setup appropriate lane-side notifications.';
    protected $model_name = 'WfcOamScheduleModel';
}

FannieDispatch::conditionalExec();

