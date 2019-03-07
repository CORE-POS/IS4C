<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('CTDB')) {
    include(__DIR__ . '/CTDB.php');
}

class CTExplorer extends FannieRESTfulPage
{
    protected $header = 'CT Explorer';
    protected $title = 'CT Explorer';

    protected function get_view()
    {
        $dbc = CTDB::get();
        $res = $dbc->query('EXEC sp_tables');

        $ret = '';
        while ($row = $dbc->fetchRow($res)) {
            if ($row['TABLE_OWNER'] == 'dbo') {
                $table = $row['TABLE_QUALIFIER'] . '.' . $row['TABLE_OWNER'] . '.' . $row['TABLE_NAME'];
                $ret .= sprintf('<li><a href="CTTable.php?table=%s">%s</a></li>', $table, $table);
            }
        }

        return '<ul>' . $ret . '</ul>';
    }
}

FannieDispatch::conditionalExec();

