<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class ArByDay extends FannieReportPage 
{
    public $description = '[AR by Day] lists daily AR totals for customer type(s)';
    public $report_set = 'Accounting';

    protected $report_headers = array('Date', 'Tender', 'Coding', 'Count', 'Amount ($)');
    protected $title = "Fannie : AR by Day";
    protected $header = "AR by Day";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $type = $this->form->type;
        } catch (Exception $ex) {
            return array();
        }

        $ret = preg_match_all("/[0-9]+/",$this->config->get('AR_DEPARTMENTS'),$depts);
        if ($ret == 0) {
            $arDepts = array();
        } else {
            $arDepts = array_pop($depts);
        }
        list($inStr, $args) = $this->connection->safeInClause($arDepts);

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $query = "SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                CASE WHEN d.trans_subtype='MI' THEN 'Charge' ELSE 'Payment' END AS transType,
                CASE WHEN d.memType=0 THEN z.memDesc ELSE m.memDesc END AS memDesc,
                COUNT(*) AS qty,
                SUM(total) AS ttl
            FROM {$dlog} AS d
                LEFT JOIN memtype AS m ON d.memType=m.memtype
                LEFT JOIN custdata AS c ON d.card_no=c.CardNo AND c.personNum=1
                LEFT JOIN memtype AS z ON c.memType=z.memtype
            WHERE ((d.trans_type='T' AND trans_subtype='MI') OR d.department IN ({$inStr}))
                AND d.tdate BETWEEN ? AND ?
                AND d.total <> 0 ";
        $args[] = $date1 . ' 00:00:00';
        $args[] = $date2 . ' 23:59:59';
        if ($type >= 0) {
            $query .= ' AND d.memType=? ';
            $args[] = $type;
        }
        $query .= "GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                CASE WHEN d.trans_subtype='MI' THEN 'Charge' ELSE 'Payment' END,
                CASE WHEN d.memType=0 THEN z.memDesc ELSE m.memDesc END
            ORDER BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                CASE WHEN d.trans_subtype='MI' THEN 'Charge' ELSE 'Payment' END,
                CASE WHEN d.memType=0 THEN z.memDesc ELSE m.memDesc END";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', mktime(0,0,0,$row['month'],$row['day'],$row['year']));
            $data[] = array(
                $date,
                $row['transType'],
                $row['memDesc'],
                sprintf('%d', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $memtype = new MemtypeModel($this->connection);
        $opts = $memtype->toOptions(-1);

        return <<<HTML
<form method="get">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Customer Type (optional)</label>
            <select name="type" class="form-control">
                <option value="-1">All</option>
                {$opts}
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-core">Submit</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

