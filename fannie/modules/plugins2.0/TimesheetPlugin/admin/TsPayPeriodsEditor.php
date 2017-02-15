<?php
include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TsPayPeriodsEditor extends \COREPOS\Fannie\API\FannieCRUDPage 
{
    public $page_set = 'Plugin :: TimesheetPlugin';
    protected $title = 'Shifts Admin';
    protected $header = 'Shifts Admin';

    protected $model_name = 'PayPeriodsModel';
}

FannieDispatch::conditionalExec();

