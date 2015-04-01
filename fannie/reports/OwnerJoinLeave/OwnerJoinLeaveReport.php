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
    public $description = '[Owner Status] lists new members, inactive members, and members pending termination.';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie :  Ownersship Status Report";
    protected $header = "Ownership Status Report";
    protected $required_fields = array('date1', 'date2');

    protected $report_headers = array(
        array('New Owners', null, null, null, null),
        array('Number', 'Date', 'Name', 'Stock', ''),
        array('Inactive Owners', null, null, null, null),
        array('Number', 'Date', 'Name', 'Stock', 'Reason'),
        array('Termination Pending', null, null, null, null),
        array('Number', 'Date', 'Name', 'Stock', 'Reason'),
        array('Total Equity', null, null, null, null),
        array('Period', 'Number of Owners', 'Stock', '', ''),
        array('Total Active/Inactive', null, null, null, null),
        array('Period', 'Active/Inactive', 'Number of Owners', 'Reason', ''),
    );

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

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

        $inactP = $dbc->prepare('
            SELECT s.cardno AS card_no,
                c.FirstName,
                c.LastName,
                s.suspDate,
                s.reasoncode
            FROM suspensions AS s
                INNER JOIN custdata AS c ON s.cardno=c.CardNo AND c.personNum=1
            WHERE c.Type=\'INACT\'
                AND s.suspDate BETWEEN ? AND ?
            ORDER BY s.suspDate
        ');

        $reasonP = $dbc->prepare('
            SELECT r.textStr
            FROM reasoncodes AS r
            WHERE r.mask & ? <> 0
        ');

        $termP = $dbc->prepare('
            SELECT s.cardno AS card_no,
                c.FirstName,
                c.LastName,
                s.suspDate,
                n.payments
            FROM suspensions AS s
                INNER JOIN custdata AS c ON s.cardno=c.CardNo AND c.personNum=1
                LEFT JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS n ON s.cardno=n.memnum
            WHERE c.Type=\'INACT2\'
                AND s.suspDate BETWEEN ? AND ?
            ORDER BY s.suspDate
        ');

        $noteP = $dbc->prepare('
            SELECT n.note
            FROM memberNotes AS n
            WHERE cardno=?
            ORDER BY stamp DESC
        ');

        $data = array();
        $totals = array();
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

        $args = array(
            FormLib::getDate('date1', date('Y-m-d')) . ' 00:00:00',
            FormLib::getDate('date2', date('Y-m-d')) . ' 23:59:59',
        );

        $joinR = $dbc->execute($joinP, $args);
        $totals['new'] = 0;
        $totals['newStock'] = 0.00;
        while ($row = $dbc->fetch_row($joinR)) {
            $data[] = array(
                $row['card_no'],
                date('Y-m-d', strtotime($row['start_date'])),    
                $row['FirstName'] . ' ' . $row['LastName'],
                sprintf('%.2f', $row['payments']),
                '',
            );
            $totals['new']++;
            $totals['newStock'] += $row['payments'];     
        }

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        
        $inactR = $dbc->execute($inactP, $args);
        $totals['inact'] = 0;
        $totals['inactReasons'] = array('Term Pending'=>0);
        while ($row = $dbc->fetch_row($inactR)) {
            $record = array(
                $row['card_no'],
                date('Y-m-d', strtotime($row['suspDate'])),    
                $row['FirstName'] . ' ' . $row['LastName'],
                sprintf('%.2f', $row['payments']),
            );
            $totals['inact']++;
            $reasonR = $dbc->execute($reasonP, array($row['reasoncode']));
            $reason = '';
            while ($w = $dbc->fetch_row($reasonR)) {
                $reason .= $w['textStr'] . ', ';
                if (!isset($totals['inactReasons'][$w['textStr']])) {
                    $totals['inactReasons'][$w['textStr']] = 0;
                }
                $totals['inactReasons'][$w['textStr']]++;
            }
            if ($reason === '') {
                $reason = '?';
            } else {
                $reason = substr($reason, 0, strlen($reason)-2);
            }
            $record[] = $reason;
            $data[] = $record;
        }

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        
        $termR = $dbc->execute($termP, $args);
        while ($row = $dbc->fetch_row($termR)) {
            $record = array(
                $row['card_no'],
                date('Y-m-d', strtotime($row['suspDate'])),    
                $row['FirstName'] . ' ' . $row['LastName'],
                sprintf('%.2f', $row['payments']),
            );
            $totals['inact']++;
            $totals['inactReasons']['Term Pending']++;
            $noteR = $dbc->execute($noteP, array($row['card_no']));
            if ($noteR && $dbc->num_rows($noteR) > 0) {
                $noteW = $dbc->fetch_row($noteR);
                $record[] = $noteW['note'];
            } else {
                $record[] = '?';
            }
            $data[] = $record;
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
        $ytdArgs = array(
            date('Y-01-01 00:00:00', strtotime($args[0])),
            $args[1],
        );
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

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

        $data[] = array(
            date('Y-m-d', strtotime($args[0])) . ' - ' . date('Y-m-d', strtotime($args[1])),
            $totals['new'],
            $totals['newStock'],
            '',
            '',
        );
        $data[] = array(
            date('Y-m-d', strtotime($ytdArgs[0])) . ' - ' . date('Y-m-d', strtotime($ytdArgs[1])),
            $ytd['numOwners'],
            $ytd['stock'],
            '',
            '',
        );

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

        $data[] = array(
            '(the big bang) - ' . date('Y-m-d', strtotime($args[1])),
            'Active',
            $totals['active'],
            'n/a',
            ''
        );
        $data[] = array(
            date('Y-m-d', strtotime($args[0])) . ' - ' . date('Y-m-d', strtotime($args[1])),
            'Inactive',
            $totals['inact'],
            'any',
            '',
        );
        foreach ($totals['inactReasons'] as $reason => $num) {
            $data[] = array(
                date('Y-m-d', strtotime($args[0])) . ' - ' . date('Y-m-d', strtotime($args[1])),
                'Inactive',
                $num,
                $reason,
                '',
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '
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
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
            </div>
            </div>
            <p><button type="submit" class="btn btn-default">Get Report</button></p>
            </form>';
    }
}

FannieDispatch::conditionalExec();

