<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PcFailReport extends FannieReportPage 
{
    public $description = '[Integrated Card Fails] lists all integrated payment card transactions that failed strangely.';
    public $report_set = 'Tenders';
    protected $required_fields = array('date1', 'date2');
    protected $header = 'Paycard Failures Report';
    protected $title = 'Paycard Failures Report';
    protected $report_headers = array('Date', 'Receipt', 'Amount');

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare('
            SELECT requestDatetime, empNo, registerNo, transNo, amount
            FROM ' . FannieDB::fqn('PaycardTransactions', 'trans') . '
            WHERE dateID BETWEEN ? AND ?
                AND processor=\'MercuryE2E\'
                AND (xResultMessage IS NULL OR xResultMessage=\'\')');
        $args = array(
            date('Ymd', strtotime($this->form->date1)),
            date('Ymd', strtotime($this->form->date2)),
        );
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                date('Y-m-d', strtotime($row['requestDatetime'])),
                $row['empNo'] . '-' . $row['registerNo'] . '-' . $row['transNo'],
                $row['amount'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get" action="PcFailReport.php">
    {$dates}
    <p>
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

