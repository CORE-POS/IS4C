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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RoundUpReport extends FannieReportPage 
{
    public $description = '[Round Up Report] shows donation totals from round-up events';
    public $report_set = 'Cashiering';

    protected $report_headers = array('Emp#', 'Name', '# of Donations', '$ Total');
    protected $title = "Fannie : Round Up Report";
    protected $header = "Round Up Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = $this->form->store;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = $dbc->prepare("
            SELECT d.emp_no,
                e.FirstName,
                COUNT(*) AS qty,
                SUM(total) AS ttl
            FROM {$dlog} AS d
                INNER JOIN employees AS e ON d.emp_no=e.emp_no
            WHERE department=701
                AND tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
            GROUP BY d.emp_no,
                e.FirstName
        ");
        $res = $dbc->execute($query, array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $store));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['emp_no'],
                $row['FirstName'],
                $row['qty'],
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[2];
            $sums[1] += $row[3];
        }

        return array('Total', '', $sums[0], number_format($sums[1], 2));
    }

    public function form_content()
    {
        $dates = FormLib::dateRangePicker();
        $stores = FormLib::storePicker();

        return <<<HTML
<form method ="get" action="RoundUpReport.php">
<div class="col-sm-4">
    <div class="form-group">
        <label>Date Start</label>
        <input type=text id=date1 name=date1 class="form-control date-field" />
    </div>
    <div class="form-group">
        <label>Date End</label>
        <input type=text id=date2 name=date2 class="form-control date-field" />
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <p>
    <button type=submit name=submit class="btn btn-default btn-core">Submit</button>
    <button type=reset name=reset class="btn btn-default btn-reset">Start Over</button>
    </p>
</div>
<div class="col-sm-4">
    {$dates}
</div>
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>
            This report lists round-up donations for a given date
            range, subdivided by cashier.
            </p>';
    }
}

FannieDispatch::conditionalExec();

