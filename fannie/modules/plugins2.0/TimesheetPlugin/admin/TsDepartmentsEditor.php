<?php
include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TsDepartmentsEditor extends \COREPOS\Fannie\API\FannieCRUDPage 
{
    public $page_set = 'Plugin :: TimesheetPlugin';
    protected $title = 'Shift Departments Admin';
    protected $header = 'Shift Departments Admin';

    protected $model_name = 'TimesheetDepartmentsModel';
}

FannieDispatch::conditionalExec();

