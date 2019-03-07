<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('CTDB')) {
    include(__DIR__ . '/CTDB.php');
}

class CTTable extends FannieReportPage
{
    protected $header = 'CT Explorer';
    protected $title = 'CT Explorer';
    protected $required_fields = array('table');
    protected $new_tablesorter = false;

    public function fetch_report_data()
    {
        $table = FormLib::get('table');
        $dbName = 'DataDir';
        if (strstr($table, '.dbo.')) {
            list($dbName, $table) = explode('.dbo.', $table, 2);
        }
        $dbc = CTDB::get($dbName);
        $def = $dbc->tableDefinition($table);
        $cols = array_keys($def);
        $this->report_headers = $cols;

        $query = 'SELECT ' . implode(',', $cols) . '
            FROM ' . $dbc->identifierEscape($table);
        $res = $dbc->query($query);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $record = array();
            foreach ($cols as $c) {
                $record[] = $row[$c] === null ? '(null)' : $row[$c];
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        return 'Invalid';
    }
}

FannieDispatch::conditionalExec();

