<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class AccessReport extends FannieReportPage
{
    protected $header = 'Access Expiration Report';
    protected $title = 'Access Expiration Report';
    protected $required_fields = array('date1', 'date2');

    protected $report_headers = array('Expires', '#', 'Last Name', 'First Name', 'Phone', 'Email', 'Contact Preference');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare("
SELECT a.expires, a.cardNo, c.LastName, c.FirstName, m.phone, m.email_1, a.contactMethod
FROM AccessDiscounts AS a
    LEFT JOIN custdata AS c ON a.cardNo=c.CardNo AND c.personNum=1
    LEFT JOIN meminfo AS m ON a.cardNo=m.card_no
WHERE a.expires BETWEEN ? AND ?");
        $res = $this->connection->execute($prep, array($this->form->date1, $this->form->date2 . ' 23:59:59'));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['expires'],
                $row['cardNo'],
                $row['LastName'],
                $row['FirstName'],
                $row['phone'],
                $row['email_1'],
                $row['contactMethod'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;

    }
}

FannieDispatch::conditionalExec();

