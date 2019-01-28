<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class FranReport extends FannieReportPage
{
    protected $header = 'WFC Fran Skinner Reporting';
    protected $title = 'WFC Fran Skinner Reporting';
    protected $required_fields = array('submit');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        if ($date1 && $date2) {
            return $this->detailResults($date1, $date2);
        }

        return $this->fyResults();
    }

    private function detailResults($date1, $date2)
    {
        $this->report_headers = array('Date/time', 'Owner#');
        $query = "SELECT cardno, MIN(stamp)
            FROM memberNotes
            WHERE (note like '%FRAN%' OR note like '%FUNDS REQ%')
            GROUP BY cardno
            HAVING MIN(stamp) BETWEEN ? AND ?";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($query, array($date1, $date2 . ' 23:59:59'));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array($row[1], $row['cardno']);
        }

        return $data;
    }

    private function fyResults()
    {
        $this->report_headers = array('FY', 'Start', 'End', 'Applicants');
        $data = array();
        $query = "SELECT cardno, MIN(stamp)
            FROM memberNotes
            WHERE (note like '%FRAN%' OR note like '%FUNDS REQ%')
            GROUP BY cardno";
        $res = $this->connection->query($query);
        while ($row = $this->connection->fetchRow($res)) {
            $stamp = strtotime($row[1]);
            $fy = date('n', $stamp) < 7 ? date('Y', $stamp) : date('Y', $stamp) + 1;
            if (!isset($data[$fy])) {
                $data[$fy] = array(
                    $fy, 
                    ($fy-1) . '-07-01',
                    $fy . '-06-30',
                    0,
                );
            }
            $data[$fy][3]++;
        }

        return $this->dekey_array($data);
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $this->addOnloadCommand("\$('input').prop('required', false);");
        return <<<HTML
<div class="well">Omit dates for total by fiscal year</div>
<form method="get" action="FranReport.php">
    {$dates}
    <p>
        <button type="submit" name="submit" value="1" class="btn btn-default btn-core">Submit</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

