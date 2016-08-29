<?php
include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class WfcHtEvalReport extends FannieReportPage
{ 
    protected $title = 'Eval Report'; 
    protected $header = 'Eval Report'; 
    protected $must_authenticate = true;
    protected $auth_classes = array('evals');
    protected $report_headers = array('Name', 'ADP#', 'Date', 'Type', 'Score');
    public $discoverable = false;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $q = "SELECT s.month,s.year,t.title,s.evalScore,e.name,e.empID,e.adpID
            FROM employees as e left join evalScores as s ON s.empID=e.empID
            LEFT JOIN EvalTypes as t ON s.evalType=t.id    
            WHERE deleted=0 ORDER BY e.name,s.year desc, s.month desc";
        $r = $dbc->query($q);
        $data = array();
        $lastEID = -1;
        while ($w = $dbc->fetchRow($r)) {
            if ($w['empID'] == $lastEID) continue;
            else $lastEID = $w['empID'];
            $date = 'n/a';
            if (!empty($w['month']) && !empty($w['year']))
                $date = date("F Y",mktime(0,0,0,$w['month'],1,$w['year']));
            $score = 'n/a';
            if (!empty($w['evalScore'])){
                $score = str_pad($w['evalScore'],3,'0');
                $score = substr($score,0,strlen($score)-2).".".substr($score,-2);
            }
            $data[] = array(
                $w['name'],
                $w['adpID'] ? $w['adpID'] : 'n/a',
                $date,
                (!empty($w['title']) ? $w['title'] : 'n/a'),
                $score,
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

