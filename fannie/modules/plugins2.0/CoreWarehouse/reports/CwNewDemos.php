<?php

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class CwNewDemos extends FannieRESTfulPage
{
    public $discoverable = false;
    public $themed = true;

    protected $header = '2020 Demographics';
    protected $title = '2020 Demographics';

    protected function get_view()
    {
        $dbc = $this->connection;
        $activeP = $dbc->prepare("
            SELECT COUNT(*) AS num
            FROM custdata
            WHERE Type='PC'
                AND LastName <> 'NEW MEMBER'
                AND LastName <> 'NEW WEB MEMBER'
                AND personNum=1");
        $active = $dbc->getValue($activeP, array());

        $zipR = $dbc->query("
            SELECT LEFT(m.zip, 5) AS zip, COUNT(*) AS num,
                MIN(state), MAX(state)
            FROM meminfo AS m
                INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
            WHERE c.Type='PC'
                AND LastName <> 'NEW MEMBER'
                AND LastName <> 'NEW WEB MEMBER'
            GROUP BY LEFT(m.zip, 5)
            ORDER BY COUNT(*) desc");
        $zips = "";
        while ($zipW = $dbc->fetchRow($zipR)) {
            $zips .= sprintf('<tr><td>%s</td><td>%d</td></tr>',
                $zipW['zip'],
                $zipW['num']);
        }

        $joinR = $dbc->query("
            SELECT CASE WHEN YEAR(d.start_date) >= 1999 THEN YEAR(d.start_date) ELSE 'Unknown' END AS start, COUNT(*) AS num
            FROM custdata AS c
                INNER JOIN memDates AS d ON c.CardNo=d.card_no
            WHERE c.Type='PC'
                AND c.personNum=1
                AND LastName <> 'NEW MEMBER'
                AND LastName <> 'NEW WEB MEMBER'
            GROUP BY start
            ORDER BY COUNT(*) DESC");
        $join = "";
        while ($row = $dbc->fetchRow($joinR)) {
            $join .= sprintf('<tr><td>%s</td><td>%d</td></tr>',
                $row['start'],
                $row['num']);
        }

        $investR = $dbc->query("
            SELECT distinct t.card_no FROM (
                SELECT card_no FROM GiveUsMoneyDB.GumLoanAccounts
                UNION ALL
                SELECT card_no FROM GiveUsMoneyDB.GumEquityShares
            ) AS t
        ");
        $investors = $dbc->numRows($investR);

        $accessP = $dbc->prepare("
            SELECT COUNT(CardNo) AS num
            FROM custdata
            WHERE Type='PC'
                AND personNum=1
                AND memType=5
                AND LastName <> 'NEW MEMBER'
                AND LastName <> 'NEW WEB MEMBER'
        ");
        $access = $dbc->getValue($accessP, array());

        $franR = $dbc->query("
            SELECT m.cardno
            FROM memberNotes AS m
                INNER JOIN custdata AS c ON m.cardno=c.CardNo
            WHERE c.Type='PC'
                AND c.personNum=1
                AND (
                    m.note LIKE '%FUNDS REQ%'
                    OR m.note LIKE '%FRAN%'
                )
            GROUP BY m.cardno");
        $fran = $dbc->numRows($franR);

        $start = date('Y-m-d', strtotime('1 year ago'));
        $end = date('Y-m-d', strtotime('yesterday'));

        return <<<HTML
<p>
    <b>Active count:</b> {$active}
</p>
<p>
    <b>Investors (Denfeld):</b> {$investors}
</p>
<p>
    <b>Access Owners (Current):</b> {$access}
</p>
<p>
    <b>Fran Owners (Active):</b> {$fran}
</p>
<b>By Year Joined</b>
<table class="table table-bordered table-striped">
    <tr><th>Year</th><th>Number</th>
    {$join}
</table>
</p>
<p>
<b>By Zip Code</b>
<table class="table table-bordered table-striped">
    <tr><th>Zip</th><th>Number</th>
    {$zips}
</table>
</p>
<p>
HTML;
    }
}

FannieDispatch::conditionalExec();

