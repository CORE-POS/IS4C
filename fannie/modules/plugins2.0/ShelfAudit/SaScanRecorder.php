<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaScanRecorder extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Scan Recorder] is a tool that simply records scans';
    protected $title = 'ShelfAudit Inventory';
    protected $header = '';

    protected function post_handler()
    {
        $items = json_decode(FormLib::get('json'), true);
        $csv = '';
        foreach ($items as $i) {
            $csv .= $i['upc'] . "," . $i['count'] . "\r\n";
        }
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $addr = isset($settings['ShelfAuditEmail']) ? $settings['ShelfAuditEmail'] : '';
        if ($addr) {
            mail($addr, 'Scan Results', $csv);
        }

        return false;
    }

    protected function get_view()
    {
        $this->addScript('js/scanRecorder.js');
        $this->addOnloadCommand('scanRecorder.redisplay();');
        return <<<HTML
<form onsubmit="scanRecorder.scan(); return false;">
    <input type="text" id="upc_in" name="upc" placeholder="UPC" class="form-control" />
    <input type="text" id="socketm" class="collapse" />
    <input type="submit" class="collapse" />
</form>
<p />
<div class="row">
    <div class="col-sm-3">
        <button type="button" class="btn btn-lg" onclick="scanRecorder.clear();">Clear</button>
    </div>
    <div class="col-sm-3">
        <button type="button" class="btn btn-lg" onclick="scanRecorder.emit();">Export</button>
    </div>
</div>
<div id="scan-data">
</div>
<p />
HTML;
    }
}

FannieDispatch::conditionalExec();

