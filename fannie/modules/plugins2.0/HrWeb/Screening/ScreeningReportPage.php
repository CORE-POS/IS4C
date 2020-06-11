<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class ScreeningReportPage extends FannieReportPage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('illness_viewer');
    protected $header = 'Screening Logs';
    protected $title = 'Screening Logs';
    public $discoverable = false;
    protected $required_fields = array('date1', 'date2');

    protected $report_headers = array('Date', 'Name', 'Badge Number','High Temperature', 'Reported Symptoms');

    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $prep = $dbc->prepare("SELECT e.*, s.name, s.code
            FROM " . FannieDB::fqn('ScreeningEntries', 'plugin:HrWebDB') . " AS e
                LEFT JOIN " . FannieDB::fqn('ScreeningEmployees', 'plugin:HrWebDB') . " AS s
                ON e.screeningEmployeeID=s.screeningEmployeeID
            WHERE e.tdate BETWEEN ? AND ?
            ORDER BY e.tdate");
        $res = $dbc->execute($prep, array($this->form->date1, $this->form->date2 . ' 23:59:59'));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['name'],
                $row['code'],
                ($row['highTemp'] ? 'Yes' : 'No'),
                ($row['anySymptom'] ? 'Yes' : 'No'),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();

        return <<<HTML
<form method="get" action="ScreeningReportPage.php">
{$dates}
<p>
    <button type="submit" class="btn btn-default">View Logs</button>
</p>
<p>
    <a href="../HrMenu.php" class="btn btn-default">Main Menu</a>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

