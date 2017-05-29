<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OwnerJoinLeaveReport extends FannieReportPage
{
    public $description = '[Owner Status] lists new members, inactive members, and overall status info';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie :  Ownership Status Report";
    protected $header = "Ownership Status Report";
    protected $required_fields = array('date1', 'date2');

    protected $new_tablesorter = false;

    protected $report_headers = array(
        array('Ownership Report', null, null, null, null),
        array('Total Equity', null, null, null, null),
        array('Period', null, 'Number of Owners', 'Stock', null),
        array('New Owners', null, null, null, null),
        array('Number', 'Date', 'Name', 'Stock', null),
        array('Inactives', null, null, null, null),
        array('Description', null, 'Current', 'Year to Date', 'Life to Date'),
    );

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $this->report_headers[0][0] .= ' '
            . date('M j, Y', strtotime($this->form->date1))
            . ' through ' 
            . date('M j, Y', strtotime($this->form->date2));
        if ($this->report_format == 'html') {
            echo '<style type="text/css">
                thead th {
                    background-color: #000 !important;
                    color: #fff;
                }
                @media print {
                    #pre-report-content { display: none; }
                }
                </style>';
            $this->add_onload_command("\$('.tablesorter td').dblclick(highlightCell);");
        }

        $joinP = $dbc->prepare('
            SELECT m.card_no,
                c.FirstName,
                c.LastName,
                m.start_date,
                n.payments
            FROM memDates AS m
                INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                LEFT JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS n ON m.card_no=n.memnum
            WHERE m.start_date BETWEEN ? AND ?
                AND c.Type=\'PC\'
            ORDER BY m.start_date
        ');

        $data = array();
        $totals = array();
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#000','meta_foreground'=>'#fff');
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#ccc','meta_foreground'=>'#000');

        $args = array(
            $this->form->date1 . ' 00:00:00',
            $this->form->date2 . ' 23:59:59',
        );

        $joinR = $dbc->execute($joinP, $args);
        $totals['new'] = 0;
        $totals['newStock'] = 0.00;
        while ($row = $dbc->fetch_row($joinR)) {
            $data[] = array(
                $row['card_no'],
                date('Y-m-d', strtotime($row['start_date'])),    
                $row['FirstName'] . ' ' . $row['LastName'],
                sprintf('$%.2f', $row['payments']),
                null,
            );
            $totals['new']++;
            $totals['newStock'] += $row['payments'];     
        }

        $ytdArgs = array(
            date('Y-01-01 00:00:00', strtotime($args[0])),
            $args[1],
        );
        if ($this->config->COOP_ID == 'WFC_Duluth') {
            $ts = strtotime($args[0]);
            if (mktime(0, 0, 0, 7, 1, date('Y',$ts)) <= $ts) {
                $ts = mktime(0, 0, 0, 7, 1, date('Y', $ts));
            } else {
                $ts = mktime(0, 0, 0, 7, 1, date('Y', $ts)-1);
            }
            $ytdArgs = array(
                date('Y-m-d', $ts),
                $args[1],
            );
        }
        
        $ytdP = $dbc->prepare('
            SELECT SUM(payments) AS stock,
                COUNT(*) AS numOwners
            FROM memDates AS m
                INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                LEFT JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS n ON m.card_no=n.memnum
            WHERE m.start_date BETWEEN ? AND ?
                AND c.Type=\'PC\'
        ');
        $ytdR = $dbc->execute($ytdP, $ytdArgs);
        $ytd = $dbc->fetch_row($ytdR);

        $activeP = $dbc->prepare('
            SELECT COUNT(*) AS activeTotal
            FROM memDates AS m
                INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
            WHERE m.start_date <= ?
                AND c.Type=\'PC\'
        ');
        $activeR = $dbc->execute($activeP, array($args[1]));
        $totals['active'] = '?';
        if ($activeR && $dbc->num_rows($activeR) > 0) {
            $activeW = $dbc->fetch_row($activeR);
            $totals['active'] = $activeW['activeTotal'];
        }

        $allTimeR = $dbc->query('
            SELECT COUNT(DISTINCT memnum) AS members,
                SUM(payments) AS equity
            FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance'); 
        $allTimeW = $dbc->fetchRow($allTimeR);

        array_unshift($data, array(
            'Still Active',
            null,
            number_format($totals['active']),
            number_format($allTimeW['members'] == 0 ? 0 : $totals['active'] / $allTimeW['members'] * 100) . '%',
            null,
        ));

        array_unshift($data, array(
            'Life to Date',
            null,
            number_format($allTimeW['members']),
            '$' . number_format($allTimeW['equity'], 2),
            null,
        ));

        if ($this->config->COOP_ID == 'WFC_Duluth') {
            array_unshift($data, array(
                'Yearly Budget',
                null,
                '1,500',
                '$95,000.00',
                null,
            ));
        }

        array_unshift($data, array(
            'Year to Date: ' . date('Y-m-d', strtotime($ytdArgs[0])) . ' - ' . date('Y-m-d', strtotime($ytdArgs[1])),
            null,
            $ytd['numOwners'],
            '$' . number_format($ytd['stock'], 2),
            null,
        ));

        array_unshift($data, array(
            'Current Report: ' . date('Y-m-d', strtotime($args[0])) . ' - ' . date('Y-m-d', strtotime($args[1])),
            null,
            $totals['new'],
            '$' . number_format($totals['newStock'], 2),
            null,
        ));

        array_unshift($data, array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#ccc','meta_foreground'=>'#000'));
        array_unshift($data, array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#000','meta_foreground'=>'#fff'));
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#000','meta_foreground'=>'#fff');
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#ccc','meta_foreground'=>'#000');

        $inactP = $dbc->prepare('
            SELECT COUNT(*),
                r.textStr,
                r.mask 
            FROM suspensions AS s
                INNER JOIN custdata AS c ON s.cardno=c.CardNo AND c.personNum=1
                LEFT JOIN reasoncodes AS r ON (r.mask & s.reasoncode) <> 0
            WHERE s.suspDate BETWEEN ? AND ?
                AND c.type=\'INACT\'
            GROUP BY r.textStr,
                r.mask
            ORDER BY r.textStr
        ');
        $reasons = array();
        $specific_reasons = FormLib::get('reasons', array());
        $inactR = $dbc->execute($inactP, $args);
        while ($w = $dbc->fetchRow($inactR)) {
            if (!in_array($w['mask'], $specific_reasons)) {
                $w['textStr'] = 'Other';
            }
            if (!isset($reasons[$w['textStr']])) {
                $reasons[$w['textStr']] = array(
                    'current' => 0,
                    'ytd' => 0,
                    'all' => 0,
                );
            }
            $reasons[$w['textStr']]['current'] += $w[0];
        }
        $inactR = $dbc->execute($inactP, $ytdArgs);
        while ($w = $dbc->fetchRow($inactR)) {
            if (!in_array($w['mask'], $specific_reasons)) {
                $w['textStr'] = 'Other';
            }
            if (!isset($reasons[$w['textStr']])) {
                $reasons[$w['textStr']] = array(
                    'current' => 0,
                    'ytd' => 0,
                    'all' => 0,
                );
            }
            $reasons[$w['textStr']]['ytd'] += $w[0];
        }
        $inactR = $dbc->execute($inactP, array('1900-01-01', '2999-12-31'));
        while ($w = $dbc->fetchRow($inactR)) {
            if (!in_array($w['mask'], $specific_reasons)) {
                $w['textStr'] = 'Other';
            }
            if (!isset($reasons[$w['textStr']])) {
                $reasons[$w['textStr']] = array(
                    'current' => 0,
                    'ytd' => 0,
                    'all' => 0,
                );
            }
            $reasons[$w['textStr']]['all'] += $w[0];
        }
        $totals = array('current'=>0,'ytd'=>0,'all'=>0);
        ksort($reasons);
        foreach ($reasons as $reason => $counts) {
            if (empty($reason)) {
                $reason = 'n/a';
            }
            $data[] = array(
                $reason,
                null,
                $counts['current'],
                $counts['ytd'],
                $counts['all'],
            );
            $totals['current'] += $counts['current'];
            $totals['ytd'] += $counts['ytd'];
            $totals['all'] += $counts['all'];
        }
        $data[] = array(
            'Total',
            null,
            $totals['current'],
            $totals['ytd'],
            $totals['all'],
        );

        if ($this->config->COOP_ID == 'WFC_Duluth') {
            $this->report_headers[] = array('Fran Allocations', null, null, null, null);
            $this->report_headers[] = array('Date', 'Number', 'Name', 'Stock', 'Allocation');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#000','meta_foreground'=>'#fff');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#ccc','meta_foreground'=>'#000');

            $franP = $dbc->prepare('
                SELECT cardno
                FROM memberNotes AS n
                    LEFT JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS e ON n.cardno=e.memnum
                WHERE note LIKE \'%FUNDS REQ%\'
                    AND stamp >= ?
                    AND e.payments <= 100
                GROUP BY cardno
                ORDER BY cardno');
            $detailP = $dbc->prepare('
                SELECT c.CardNo,
                    c.FirstName,
                    c.LastName,
                    e.payments,
                    n.stamp,
                    n.note
                FROM custdata AS c
                    LEFT JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS e ON c.CardNo=e.memnum
                    LEFT JOIN memberNotes AS n ON c.CardNo=n.cardno
                WHERE c.CardNo=?
                    AND c.personNum=1
                ORDER BY n.stamp DESC');
            $franR = $dbc->execute($franP, $args[0]);
            while ($w = $dbc->fetchRow($franR)) {
                $detailR = $dbc->execute($detailP, array($w['cardno']));
                $detailW = $dbc->fetchRow($detailR);
                $data[] = array(
                    $detailW['stamp'],
                    $detailW['CardNo'],
                    $detailW['FirstName'] . ' ' . $detailW['LastName'],
                    $detailW['payments'],
                    $detailW['note'],
                );
            }
        }

        return $data;
    }

    public function form_content()
    {
        $ret = '
            <form method="get" action="' . $_SERVER['PHP_SELF'] . '">
            <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="date1">Start Date</label>
                    <input type="text" name="date1" id="date1" 
                        class="form-control date-field" required />
                </div>
                <div class="form-group">
                    <label for="date2">End Date</label>
                    <input type="text" name="date2" id="date2"
                        class="form-control date-field" required />
                </div>
                <div class="panel panel-default">
                    <div class="panel panel-heading">List Subtotals for some Inactive account reasons</div>
                    <div class="panel panel-body">';
        $reasons = new ReasoncodesModel($this->connection);
        foreach ($reasons->find('textStr') as $r) {
            $ret .= sprintf('<p><label>
                <input type="checkbox" name="reasons[]" value="%d" />
                %s</label></p>',
                $r->mask(), $r->textStr());
        }
        $ret .= '   </div>
                </div>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
                <p>
                    <a href="TermPendingReport.php">Separate Term Pending Report</a>
                </p>
            </div>
            </div>
            <p><button type="submit" class="btn btn-default">Get Report</button></p>
            </form>';
        
        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            List information about owners that joined the co-op
            in a given range. Information about owners who are inactive
            or leaving the co-op is also available if that information
            is managed via CORE.
            </p>';
    }
}

FannieDispatch::conditionalExec();

