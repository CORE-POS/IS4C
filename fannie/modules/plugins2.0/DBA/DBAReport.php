<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class DBAReport extends FannieReportPage
{
    protected $header = 'DBA Report';
    protected $title = 'DBA Report';
    public $description = '[DBA Report] is a tool for running ad hoc SQL queries';

    protected $required_fields = array('query');

    public function fetch_report_data()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        try {
            $sql = new SQLManager(
                $this->config->get('SERVER'),
                $this->config->get('SERVER_DBMS'),
                $this->config->get('OP_DB'),
                $settings['DbaUser'],
                $settings['DbaPassword']
            );
        } catch (Exception $ex) {
            return array(array($ex->getMessage()));
        }

        $first = true;
        $query = $this->form->query;
        if (!strstr($query, ' ')) {
            $query = base64_decode($query);
        }
        $res = $sql->query($query);
        if (!$res) {
            return array(array($sql->error()));
        }
        $data = array();
        while ($row = $sql->fetchRow($res)) {
            $cols = array_keys($row);
            $record = array();
            foreach ($cols as $c) {
                if (is_numeric($c)) continue;
                //$record[] = $row[$c];
                $record[] = (!is_null($row[$c])) ? $row[$c] : '';
                if ($first) {
                    $this->report_headers[] = $c;
                }
            }
            $data[] = $record;
            $first = false;
        }
        if ($res && FormLib::get('saveAs')) {
            $name = FormLib::get('saveAs');
            $max = $this->connection->prepare('SELECT MAX(reportID) AS rID FROM customReports');
            $next = $this->connection->getValue($max) + 1;
            $prep = $this->connection->prepare('INSERT INTO customReports (reportID, reportName, reportQuery) VALUES (?, ?, ?)');
            $this->connection->execute($prep, array($next, $name, base64_encode($query)));
        }

        return $data;
    }

    function javascriptContent()
    {
        return <<<JAVASCRIPT
function getQuery(id) {
    $.ajax({
        url: 'DBAjax.php',
        data: 'id='+id,
        dataType: 'json'
    }).success(function (resp) {
        if (resp.query) {
            editor.get().setValue(resp.query);
            $('#saveAs').val('');
        }
    });
}

var editor = (function () {
    var _editor = {};
    var mod = {};

    mod.init = function(elem, obj) {
        _editor = CodeMirror.fromTextArea(document.getElementById(elem), obj);
    };

    mod.get = function() {
        return _editor;
    };

    return mod;
})();
JAVASCRIPT;
    }

    public function form_content()
    {
        $this->addCssFile('node_modules/codemirror/lib/codemirror.css');
        $this->addScript('node_modules/codemirror/lib/codemirror.js');
        $this->addScript('node_modules/codemirror/mode/sql/sql.js');
        $this->addOnloadCommand('editor.init("queryTA", {
                lineNumbers: true,
                mode: "text/x-sql"
            });');

        $model = new CustomReportsModel($this->connection);
        $opts = $model->toOptions();
        $libAlert = !is_dir('node_modules/codemirror') ? '<div class="alert alert-danger">Missing CodeMirror library. Run npm install</div>' : '';

        return <<<HTML
{$libAlert}
<form method="post" action="DBAReport.php">
<div class="form-group">
    <label>Saved Reports</label>
    <select class="form-control" onchange="getQuery(this.value)">
        <option value="">Select one...</option>
        {$opts}
    </select>
</div>
<div class="form-group">
    <label>Query</label>
    <textarea name="query" rows="10" class="form-control" id="queryTA"
        style="border: solid 1px #000"></textarea>
</div>
<div class="form-group">
<div class="form-group">
    <label>Save as</label>
    <input type="text" name="saveAs" id="saveAs" class="form-control" />
</div>
    <button type="submit" class="btn btn-default btn-core">Run Report</button>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

