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
    protected $report_headers = array('Date/Time', 'User', 'Store', 'Action', 'Detail');

    protected $detail_translate = array(
        "Status #0" => "Ready to Order",
        "Status #1" => "Called/Waiting",
        "Status #2" => "Pending",
        "Status #3" => "Call before Ordering",
        "Status #4" => "Placed",
        "Status #5" => "Arrived",
        "Status #7" => "Completed",
        "Status #8" => "Canceled",
        "Status #9" => "Inquiry (i.e., closed as inquiry)",
       "Status #99" => "Auto-closed"
    );

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare('SELECT s.*, u.name, t.description AS storeName FROM ' . FannieDB::fqn('SpecialOrderEdits', 'trans') .
            ' AS s LEFT JOIN ' . FannieDB::fqn('Users', 'op') . ' AS u ON s.userID=u.uid 
                LEFT JOIN ' . FannieDB::fqn('Stores', 'op') . ' AS t on s.storeID=t.storeID
            WHERE specialOrderID=?');
        $res = $this->connection->execute($prep, array($this->form->id));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['name'],
                $row['storeName'],
                $row['action'],
                (array_key_exists($row['detail'], $this->detail_translate)) ? "[".$row['detail']."]: ".$this->detail_translate[$row['detail']] : $row['detail'],
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

