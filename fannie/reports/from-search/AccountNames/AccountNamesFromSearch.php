<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class AccountNamesFromSearch extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Account Name(s) Report";
    protected $header = "Fannie : Account Name(s) Report";
    protected $required_fields = array('id');
    protected $report_headers = array('#', 'Primary Last Name', 'Primary First Name');

    public function fetch_report_data()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->form->id);
        $prep = $this->connection->prepare("
            SELECT c.CardNo,
                c.LastName,
                c.FirstName,
                c.personNum
            FROM custdata AS c
            WHERE c.CardNo IN ({$inStr})
            ORDER BY c.CardNo, c.personNum");
        $res = $this->connection->execute($prep, $args);
        $data= array();
        $curNum = false;
        $curRecord = false;
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['CardNo'] != $curNum) {
                if ($curRecord) $data[] = $curRecord;
                $curRecord = array($row['CardNo'], $row['LastName'], $row['FirstName']);
                $curNum = $row['CardNo'];
            } else {
                $curRecord[] = $row['LastName'] . ', ' . $row['FirstName']; 
            }
        }
        $data[] = $curRecord;

        return $data;
    }

    public function calculate_footers($data)
    {
        $widest = 0;
        foreach ($data as $d) {
            if (count($d) > $widest) {
                $widest = count($d);
            }
        }
        for ($i=count($this->report_headers); $i<$widest; $i++) {
            $this->report_headers[] = 'Household Name';
        }

        return array();
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "Use <a href=\"{$FANNIE_URL}item/AdvancedItemSearch.php\">Search</a> to
            select items for this report";;
    }
}

FannieDispatch::conditionalExec();


