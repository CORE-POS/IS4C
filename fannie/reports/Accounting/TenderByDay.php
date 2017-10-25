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

class TenderByDay extends FannieReportPage 
{
    public $description = '[Tenders by Day] lists daily totals for tender type(s)';
    public $report_set = 'Accounting';

    protected $report_headers = array('Date', 'Tender', 'Coding', 'Count', 'Amount ($)');
    protected $title = "Fannie : Tenders by Day";
    protected $header = "Tenders by Day";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = $this->form->store;
            $tender = $this->form->tender;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $query = "SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                t.TenderName,
                t.SalesCode,
                SUM(CASE 
                    WHEN trans_subtype='CA' AND total > 0 THEN 1
                    WHEN trans_subtype <> 'CA' THEN 1
                    ELSE 0
                END) as qty,
                SUM(-total) AS ttl
            FROM {$dlog} AS d
                LEFT JOIN tenders AS t ON t.TenderCode=d.trans_subtype
            WHERE d.trans_type='T'
                AND d.tdate BETWEEN ? AND ?
                AND d.total <> 0
                AND ". DTrans::isStoreID($store, '');
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $store);
        if ($tender != '') {
            $query .= ' AND d.trans_subtype=? ';
            $args[] = $tender;
        }
        $query .= "GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                t.TenderName,
                t.SalesCode
            ORDER BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                t.SalesCode"; 
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', mktime(0,0,0,$row['month'],$row['day'],$row['year']));
            $data[] = array(
                $date,
                $row['TenderName'],
                $row['SalesCode'],
                sprintf('%d', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $store = FormLib::storePicker();
        $opts = array();
        $res = $this->connection->query('SELECT TenderCode, TenderName FROM tenders ORDER BY TenderName');
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option value="%s">%s</option>', $row['TenderCode'], $row['TenderName']);
        }

        return <<<HTML
<form method="get">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Tender Type (optional)</label>
            <select name="tender" class="form-control">
                <option value="">All</option>
                {$opts}
            </select>
        </div>
        <div class="form-group">
            <label>Store</label>
            {$store['html']}
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

