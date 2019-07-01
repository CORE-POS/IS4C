<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpFileManager extends FannieRESTfulPage
{
    protected $header = 'RP Data Importer';
    protected $title = 'RP Data Importer';

    protected function get_id_handler()
    {
        $cmd = 'php '
            . escapeshellarg(__DIR__ . '/RpImport.php') . ' '
            . escapeshellarg($this->id);
        exec($cmd, $output);

        echo $cmd;
        echo '<pre>' . implode("\n", $output) . '</pre>';

        return false;
    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $path = $settings['RpDirectory'];
        $dir = opendir($path);
        if (!$dir) {
            return '<div class="alert alert-danger">Filesystem permission error</div>';
        }
        $opts = array();
        while (($file=readdir($dir)) !== false) {
            if (substr($file, 0, 2) == 'RP') {
                $opts[] = $path . $file;
            }
        }

        if (count($opts) == 0) {
            return '<div class="alert alert-danger">No files found in ' . $path . '</div>';
        }

        $rps = '';
        foreach ($opts as $o) {
            $rps .= sprintf('<option value="%s">%s</option>', $o, basename($o));
        }

        $backLink = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'RpDirectPage.php')
            ? 'RpDirectPage.php'
            : 'RpOrderPage.php';

        return <<<HTML
<p>
    <label>Choose a file to import from</label>:
    <select id="infile" class="form-control">
        {$rps}
    </select>
    <p>
        <button type="button" class="btn btn-default" onclick="doImport(this);">Import</button>
        <em>This should take about 30 seconds</em>
    </p>
    <p>
        <a href="{$backLink}" class="btn btn-default">Back to Order Guide</a>
    </p>
    <div id="counter"></div>
    <div id="results"></div>
</p>
HTML;

    }

    protected function javascriptContent()
    {
        return <<<JAVASCRIPT
var token = false;
function doCount() {
    var cur = $('#counter').html();
    cur = (cur*1) + 1;
    $('#counter').html(cur);
    token = setTimeout(() => doCount(), 1000); 
}
function doImport(elem) {
    $(elem).prop('disabled', true);
    $('#counter').html(0);
    token = setTimeout(() => doCount(), 1000); 
    $.ajax({
        data: 'id=' + encodeURIComponent($('#infile').val())
    }).fail(function () {
        alert('Something went wrong!');
    }).done(function (resp) {
        $('#results').html(resp);
    }).always(function () {
        if (token) {
            clearTimeout(token);
        }
        $('#counter').html('');
        $(elem).prop('disabled', false);
    });
}
JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();

