<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class HouseholdReport extends FannieReportPage
{
    protected $title = 'Household Report';
    protected $header = 'Household Report';
    public $description = '[Household Report] lists names on customer accounts';
    protected $required_fields = array('mt');
    protected $report_headers = array('#', 'Type', 'Primary');

    public function fetch_report_data()
    {
        try {
            list($inStr, $args) = $this->connection->safeInClause($this->form->mt);
        } catch (Exception $ex) {
            return array();
        }

        $prep = $this->connection->prepare("
            SELECT c.CardNo, c.FirstName, c.LastName, m.memDesc
            FROM custdata AS c
                LEFT JOIN memtype AS m ON c.memType=m.memtype
            WHERE c.memType IN ({$inStr})
            ORDER BY c.CardNo, c.personNum");
        $res = $this->connection->execute($prep, $args);
        $data = array();
        $cur = false;
        $record = array();
        $curPeople;
        $maxPeople = 1;
        while ($row = $this->connection->fetchRow($res)) {
            if ($cur != $row['CardNo']) {
                if (count($record) > 0) {
                    $data[] = $record;
                }
                $record = array(
                    $row['CardNo'],
                    $row['memDesc'],
                    $row['FirstName'] . ' ' . $row['LastName'],
                );
                $cur = $row['CardNo'];
                $curPeople = 1;
            } else {
                $record[] = $row['FirstName'] . ' ' . $row['LastName'];
                $curPeople++;
                if ($curPeople > $maxPeople) {
                    $maxPeople = $curPeople;
                }
            }
        }
        if (count($record) > 0) {
            $data[] = $record;
        }
        for ($i=2; $i<=$maxPeople; $i++) {
            $this->report_headers[] = 'Household';
        }
        for ($i=0; $i<count($data); $i++) {
            for ($j=2; $j<=$maxPeople; $j++) {
                if (!isset($data[$i][$j+1])) {
                    $data[$i][$j+1] = '';
                }
            }
        }

        return $data;
    }

    public function form_content()
    {
        $mt = new MemtypeModel($this->connection);
        $checks = '';
        foreach ($mt->find() as $m) {
            $checks .= sprintf('<label>
                <input type="checkbox" name="mt[]" value="%d" /> %s
                </label><br />',
                $m->memtype(), $m->memDesc());
        }

        return <<<HTML
<form method="get" action="HouseholdReport.php">
    <div class="form-group">
        {$checks}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

