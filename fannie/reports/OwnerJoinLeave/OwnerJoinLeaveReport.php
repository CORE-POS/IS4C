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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
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
        array('Ownership Report', null, null, null, null, null),
        array('Total Equity', null, null, null, null, null),
        array('Period', null, 'Number of Owners', '', null, null),
        array('New Owners', null, null, null, null, null),
        array('Number', 'Date', 'Name', 'Stock', 'Payment Plan', null),
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
                p.name
            FROM memDates AS m
                INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                LEFT JOIN EquityPaymentPlanAccounts AS a ON m.card_no=a.cardNo
                LEFT JOIN EquityPaymentPlans AS p ON a.equityPaymentPlanID=p.equityPaymentPlanID
            WHERE m.start_date BETWEEN ? AND ?
                AND c.Type=\'PC\'
            ORDER BY m.start_date
        ');

        $stockP = $dbc->prepare('SELECT SUM(stockPurchase)
            FROM ' . $FANNIE_TRANS_DB. $dbc->sep() . 'stockpurchases
            WHERE card_no=?
                AND tdate <= ?');

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
        $newCount = 0;
        while ($row = $dbc->fetch_row($joinR)) {
            $actual = $dbc->getValue($stockP, array($row['card_no'], $this->form->date2 . ' 23:59:59'));
            $data[] = array(
                $row['card_no'],
                date('Y-m-d', strtotime($row['start_date'])),    
                $row['FirstName'] . ' ' . $row['LastName'],
                sprintf('$%.2f', $actual),
                ($row['name'] ? $row['name'] : ''),
                null,
            );
            $totals['new']++;
            $totals['newStock'] += $actual;
            $newCount++;
        }
        $this->report_headers[3][0] .= ' (' . $newCount . ')';

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

        array_unshift($data, array(
            'Currently Active',
            null,
            number_format($totals['active']),
            '',
            null,
            null,
        ));

        if ($this->config->COOP_ID == 'WFC_Duluth') {
            array_unshift($data, array(
                'Annual New Owner Goal',
                null,
                '780',
                '',
                null,
                null,
            ));
        }

        array_unshift($data, array(
            'Year to Date: ' . date('Y-m-d', strtotime($ytdArgs[0])) . ' - ' . date('Y-m-d', strtotime($ytdArgs[1])),
            null,
            $ytd['numOwners'],
            '',
            null,
            null,
        ));

        array_unshift($data, array(
            'Current Report: ' . date('Y-m-d', strtotime($args[0])) . ' - ' . date('Y-m-d', strtotime($args[1])),
            null,
            $totals['new'],
            '',
            null,
            null,
        ));

        array_unshift($data, array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#ccc','meta_foreground'=>'#000'));
        array_unshift($data, array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
            'meta_background'=>'#000','meta_foreground'=>'#fff'));

        if ($this->config->COOP_ID == 'WFC_Duluth') {
            $this->report_headers[] = array('Term Pending', null, null, null, null, null);
            $this->report_headers[] = array('Number', 'Date', 'Name', 'Stock', 'Fran', 'Reason');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#000','meta_foreground'=>'#fff');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#ccc','meta_foreground'=>'#000');

            $termP = $dbc->prepare('
                SELECT s.cardno AS card_no,
                    c.FirstName,
                    c.LastName,
                    s.suspDate,
                    n.payments
                FROM suspensions AS s
                    INNER JOIN custdata AS c ON s.cardno=c.CardNo AND c.personNum=1
                    LEFT JOIN ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance AS n ON s.cardno=n.memnum
                WHERE c.Type=\'INACT2\'
                    AND (s.suspDate BETWEEN ? AND ?)
                ORDER BY s.suspDate
            ');
            $termR = $dbc->execute($termP, $args);
            $noteP = $dbc->prepare('
                SELECT n.note
                FROM memberNotes AS n
                WHERE cardno=?
                ORDER BY stamp DESC
            ');
            while ($row = $dbc->fetchRow($termR)) {
                $note = $dbc->getValue($noteP, array($row['card_no']));
                if (strstr(strtoupper($note), 'TRANSFER')) {
                    continue;
                }
                $fran = $this->franAmount($dbc, $row['card_no']);
                $record = array(
                    $row['card_no'],
                    date('Y-m-d', strtotime($row['suspDate'])),    
                    $row['FirstName'] . ' ' . $row['LastName'],
                    sprintf('%.2f', $row['payments'] - $fran),
                    sprintf('%.2f', $fran),
                );
                if ($note !== false) {
                    if (strstr($note, '<br />')) {
                        list($note,) = explode('<br />', $note, 2);
                    }
                    $record[] = $note;
                } else {
                    $record[] = '?';
                }
                $data[] = $record;
            }

            $this->report_headers[] = array('Fran Allocations', null, null, null, null, null);
            $this->report_headers[] = array('Number', 'Date', 'Name', 'Stock', null, 'Allocation');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#000','meta_foreground'=>'#fff');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#ccc','meta_foreground'=>'#000');

            $franP = $dbc->prepare('
                SELECT cardno
                FROM memberNotes AS n
                    LEFT JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS e ON n.cardno=e.memnum
                    LEFT JOIN memDates AS d ON n.cardno=d.card_no
                WHERE note LIKE \'%FUNDS REQ%\'
                    AND n.stamp >= ?
                    AND d.start_date < ?
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
            $franR = $dbc->execute($franP, $args);
            $franCount = 0;
            while ($w = $dbc->fetchRow($franR)) {
                $detailR = $dbc->execute($detailP, array($w['cardno']));
                $detailW = $dbc->fetchRow($detailR);
                $data[] = array(
                    $detailW['CardNo'],
                    date('Y-m-d', strtotime($detailW['stamp'])),
                    $detailW['FirstName'] . ' ' . $detailW['LastName'],
                    $detailW['payments'],
                    null,
                    $detailW['note'],
                );
                $franCount++;
            }
            $this->report_headers[5][0] .= ' (' . $franCount . ')';

            $this->report_headers[] = array('Transfer Requests', null, null, null, null, null);
            $this->report_headers[] = array('Owner #', 'Date', 'Name', 'Equity', null, 'Request');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#000','meta_foreground'=>'#fff');
            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS | FannieReportPage::META_COLOR, 
                'meta_background'=>'#000','meta_foreground'=>'#fff');
            $termP = $dbc->prepare('
                SELECT s.cardno AS card_no,
                    c.FirstName,
                    c.LastName,
                    s.suspDate,
                    n.payments
                FROM suspensions AS s
                    INNER JOIN custdata AS c ON s.cardno=c.CardNo AND c.personNum=1
                    LEFT JOIN ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance AS n ON s.cardno=n.memnum
                WHERE c.Type=\'INACT2\'
                    AND (s.suspDate >= ?)
                ORDER BY s.suspDate
            ');
            $noteP = $dbc->prepare('
                SELECT n.note
                FROM memberNotes AS n
                WHERE cardno=?
                ORDER BY stamp DESC
            ');
            $termR = $dbc->execute($termP, $args[0]);
            $termCount = 0;
            while ($termW = $dbc->fetchRow($termR)) {
                $note = $dbc->getValue($noteP, array($termW['card_no']));
                if (strstr(strtoupper($note), 'TRANSFER')) {
                    $data[] = array(
                        $termW['card_no'],
                        date('Y-m-d', strtotime($termW['suspDate'])),
                        $termW['LastName'] . ', ' . $termW['FirstName'],
                        sprintf('%.2f', $termW['payments']),
                        null,
                        $note,
                    );
                    $termCount++;
                }
            }
            $this->report_headers[9][0] .= ' (' . $termCount . ')';
        }

        return $data;
    }

    private function franAmount($dbc, $cardno)
    {
        if (!isset($this->stockP)) {
            $this->stockP = $dbc->prepare('SELECT tdate, stockPurchase, trans_num
                FROM ' . FannieDB::fqn('stockpurchases', 'trans') . '
                WHERE card_no=?');
        }
        if (!isset($this->commentP)) {
            $this->commentP = $dbc->prepare('SELECT trans_id
                FROM ' . FannieDB::fqn('bigArchive', 'arch') . '
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no <> 9999
                    AND register_no <> 99
                    AND trans_status NOT IN (\'X\',\'Z\')
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND trans_type=\'C\'
                    AND trans_subtype=\'CM\'
                    AND description LIKE \'%31130%\'');
        }
        $ret = 0;
        $stockR = $dbc->execute($this->stockP, array($cardno));
        while ($stockW = $dbc->fetchRow($stockR)) {
            list($date,) = explode(' ', $stockW['tdate']);
            $emp=$reg=$trans=0;
            if (strstr($stockW['trans_num'], '-')) {
                list($emp, $reg, $trans) = explode('-', $stockW['trans_num']);
            }
            $args = array(
                $date . ' 00:00:00',
                $date . ' 23:59:59',
                $emp,
                $reg,
                $trans,
            );
            if ($dbc->getValue($this->commentP, $args)) {
                $ret += $stockW['stockPurchase'];
            }
        }

        return $ret;
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

