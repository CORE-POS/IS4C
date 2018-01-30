<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class GumCheckReport extends FannieReportPage
{
    protected $report_headers = array('Issue Date', 'Amount', 'Check#', 'Memo', 'Program', 'Account#', 'Name');

    public function fetch_report_data()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['GiveUsMoneyDB'] . $this->connection->sep();

        $query = "
            SELECT p.issueDate,
                p.amount,
                p.checkNumber,
                p.reason as memo,
                CASE
                    WHEN d.gumPayoffID IS NOT NULL THEN 'DIVIDEND'
                    WHEN l.gumPayoffID IS NOT NULL THEN 'LOAN'
                    ELSE '?'
                END AS program,
                d.gumDividendID
            FROM {$prefix}GumPayoffs AS p
                LEFT JOIN {$prefix}GumDividendPayoffMap AS d ON p.gumPayoffID=d.gumPayoffID
                LEFT JOIN {$prefix}GumLoanPayoffMap AS l ON p.gumPayoffID=l.gumPayoffID
            WHERE reason LIKE '%DIVIDEND%'
            ORDER BY p.issueDate,
                p.checkNumber";
        $dividendP = $this->connection->prepare("
            SELECT c.FirstName,
                c.LastName,
                d.card_no
            FROM {$prefix}GumDividends AS d
                LEFT JOIN custdata AS c ON d.card_no=c.CardNo AND c.personNum=1
            WHERE d.gumDividendID=?");
        $res = $this->connection->query($query);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $record = array(
                $row['issueDate'],
                $row['amount'],
                $row['checkNumber'],
                $row['memo'],
                $row['program'],
            );
            if ($row['program'] === 'DIVIDEND') {
                $div = $this->connection->getRow($dividendP, array($row['gumDividendID']));
                $record[] = $div['card_no'];
                $record[] = $div['FirstName'] . ' ' . $div['LastName'];
            }
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

