<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('InstaWfcExport')) {
    include(__DIR__ . '/../InstaCart/InstaWfcExport.php');
}

class PushUpdate extends FannieRESTfulPage
{
    protected $header = 'Pickup Website Update';
    protected $title = 'Pickup Website Update';
    public $discoverable = false;

    protected function get_view()
    {
        $task = new InstaWfcExport(); 
        $task->setConfig($this->config);
        $task->setLogger($this->logger);
        $task->run();

        return <<<HTML
<div class="alert alert-success">Website Inventory Updated</div>
HTML;
    }
}

FannieDispatch::conditionalExec();

