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

class SalesByDay extends FannieReportPage 
{
    public $description = '[Sales by Day] lists daily totals for account(s)';
    public $report_set = 'Accounting';

    protected $report_headers = array('Date', 'Coding', 'Quantity', 'Cost ($)', 'Retail ($)', 'Est. Margin');
    protected $title = "Fannie : Sales by Day";
    protected $header = "Sales by Day";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = $this->form->store;
            $code = $this->form->code;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $query = "SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                t.salesCode,
                " . DTrans::sumQuantity('d') . " AS qty,
                SUM(CASE WHEN d.cost <> 0 THEN d.cost ELSE (1 - t.margin)*d.total END) AS cost,
                SUM(total) AS ttl
            FROM {$dlog} AS d
                LEFT JOIN departments AS t ON d.department=t.dept_no
            WHERE d.trans_type IN ('I', 'D')
                AND d.tdate BETWEEN ? AND ?
                AND d.total <> 0
                AND ". DTrans::isStoreID($store, '');
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $store);
        if ($code != '') {
            $query .= ' AND t.salesCode=? ';
            $args[] = $code;
        }
        $query .= "GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                t.salesCode
            ORDER BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                t.salesCode"; 
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', mktime(0,0,0,$row['month'],$row['day'],$row['year']));
            $data[] = array(
                $date,
                $row['salesCode'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['cost']),
                sprintf('%.2f', $row['ttl']),
                sprintf('%.2f', ($row['ttl'] - $row['cost']) / $row['ttl'] * 100),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0, 0);
        foreach ($data as $d) {
            $sums[0] += $d[2];
            $sums[1] += $d[3];
            $sums[2] += $d[4];
        }

        return array('Total', '', $sums[0], $sums[1], $sums[2], sprintf('%.2f', 100 * ($sums[2] - $sums[1]) / $sums[2]));
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $store = FormLib::storePicker();
        $opts = '';
        $res = $this->connection->query('SELECT salesCode FROM departments GROUP BY salesCode ORDER BY salesCode');
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option>%s</option>', $row['salesCode']);
        }

        return <<<HTML
<form method="get">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Account # (optional)</label>
            <select name="code" class="form-control">
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

