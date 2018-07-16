<?php

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class SpoEditsPage extends FannieReportPage
{
    protected $must_authenticate = true;
    protected $header = 'Special Order Edits';
    protected $title = 'Special Order Edits';
    public $discoverable = false;
    public $page_set = 'Special Orders';
    protected $required_fields = array('id');

    protected $new_tablesorter = true;
    protected $report_headers = array('Date/Time', 'User', 'Action', 'Detail');

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare('SELECT s.*, u.name FROM ' . FannieDB::fqn('SpecialOrderEdits', 'trans') .
            ' AS s LEFT JOIN ' . FannieDB::fqn('Users', 'op') . ' AS u ON s.userID=u.uid WHERE specialOrderID=?');
        $res = $this->connection->execute($prep, array($this->form->id));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['name'],
                $row['action'],
                $row['detail'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<!-- intentionally blank -->';
    }
}

FannieDispatch::conditionalExec();

