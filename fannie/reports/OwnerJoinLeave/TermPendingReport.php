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

class TermPendingReport extends FannieReportPage
{
    public $description = '[Term Pending] lists members pending termination.';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie :  Term Pending Report";
    protected $header = "Term Pending Report";
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Owner #', 'Date', 'Name', 'Equity', 'Reason');
    protected $sort_column = 1;

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }

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
        $termR = $dbc->execute($termP, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));

        $noteP = $dbc->prepare('
            SELECT n.note
            FROM memberNotes AS n
            WHERE cardno=?
            ORDER BY stamp DESC
        ');

        $data = array();
        while ($row = $dbc->fetchRow($termR)) {
            $record = array(
                $row['card_no'],
                date('Y-m-d', strtotime($row['suspDate'])),    
                $row['FirstName'] . ' ' . $row['LastName'],
                sprintf('%.2f', $row['payments']),
            );
            $noteR = $dbc->execute($noteP, array($row['card_no']));
            if ($noteR && $dbc->num_rows($noteR) > 0) {
                $noteW = $dbc->fetch_row($noteR);
                $note_pts = explode("<br />", $noteW['note']);
                $record[] = $note_pts[0];
            } else {
                $record[] = '?';
            }
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $picker = FormLib::dateRangePicker();
        $quarter = floor(date('n') / 3);
        $start = '';
        $end = '';
        switch ($quarter) {
            case 0:
                $start = date('Y-m-d', mktime(0,0,0,10,1,date('Y')-1));
                $end = date('Y-m-d', mktime(0,0,0,12,31,date('Y')-1));
                break;
            case 1:
                $start = date('Y-m-d', mktime(0,0,0,1,1,date('Y')));
                $end = date('Y-m-d', mktime(0,0,0,3,31,date('Y')));
                break;
            case 2:
                $start = date('Y-m-d', mktime(0,0,0,4,1,date('Y')));
                $end = date('Y-m-d', mktime(0,0,0,6,30,date('Y')));
                break;
            case 3:
                $start = date('Y-m-d', mktime(0,0,0,7,1,date('Y')));
                $end = date('Y-m-d', mktime(0,0,0,9,30,date('Y')));
                break;
        }

        return <<<HTML
<form method="get">
    <div class="col-sm-4">
        <div class="form-group">
            <label>Start Date</label>
            <input type="text" id="date1" name="date1" value="{$start}" class="form-control date-field" />
        </div>
        <div class="form-group">
            <label>End Date</label>
            <input type="text" id="date2" name="date2" value="{$end}" class="form-control date-field" />
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-core">Get Report</button>
        </div>
    </div>
    <div class="col-sm-4">
        {$picker}
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

