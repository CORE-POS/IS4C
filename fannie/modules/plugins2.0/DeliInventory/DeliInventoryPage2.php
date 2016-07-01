<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('DeliInventoryPage')) {
    include(__DIR__ . '/DeliInventoryPage.php');
}

class DeliInventoryPage2 extends DeliInventoryPage
{
    public $page_set = 'Plugin :: DeliInventory';
    protected $window_dressing = false;
    protected $model_class = 'DeliInventoryCat2Model';
    protected $table_name = 'deliInventoryCat2';

    protected function currentlyLine()
    {
        return '<h3>Currently Denfeld - <a href="DeliInventoryPage.php">Switch</a></h3>';
    }

    public function body_content()
    {
        $ret = parent::body_content();
        $ret = str_replace('send.js', 'send2.js', $ret);
        $ret = str_replace('index.js', 'index2.js', $ret);

        return $ret;
    }
}

FannieDispatch::conditionalExec();

