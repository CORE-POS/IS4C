<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class B2BReport extends FannieReportPage
{
    protected $header = 'B2B Report';
    protected $title = 'B2B Report';
    public $disoverable = false;
    protected $required_fields = array();
    protected $report_headers = array('Email Address', 'Name', 'Invoice Item', 'Invoice Amount', 'Invoice Number', 'Payment URL');

    protected function defaultDescriptionContent($rowcount, $datefields=array())
    {
        return array();
    }

    public function fetch_report_data()
    {
        $query = 'SELECT
                b.cardNo,
                b.b2bInvoiceID,
                b.description,
                b.uuid,
                b.amount,
                c.FirstName,
                c.LastName,
                m.email_1
            FROM ' . FannieDB::fqn('B2BInvoices', 'trans') . ' AS b
                LEFT JOIN ' . FannieDB::fqn('custdata', 'op') . ' AS c ON b.cardNo=c.CardNo AND c.personNum=1
                LEFT JOIN ' . FannieDB::fqn('meminfo', 'op') . ' AS m ON b.cardNo=m.card_no
            WHERE b.isPaid=0';
        $res = $this->connection->query($query);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $name = $row['LastName'];
            if ($row['FirstName']) {
                $name = $row['FirstName'] . ' ' . $row['LastName'];
            }
            $data[] = array(
                $row['email_1'],
                $name,
                $row['description'],
                sprintf('%.2f', $row['amount']),
                $row['b2bInvoiceID'],
                'http://store.wholefoods.coop/invoice/' . $row['uuid'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();


