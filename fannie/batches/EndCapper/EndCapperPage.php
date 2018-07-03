<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EndCapperPage extends FannieRESTfulPage
{
    protected $header = 'End Capper';
    protected $title = 'End Capper';

    protected function get_view()
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/build/asset-manifest.json'), true);
        $this->addScript('build/' . $manifest['main.js']);
        $this->addCssFile('build/' . $manifest['main.css']);

        return '<div id="end-capper"></div>';
    }
}

FannieDispatch::conditionalExec();

