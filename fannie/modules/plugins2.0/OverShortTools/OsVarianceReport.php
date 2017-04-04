<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OsVarianceReport extends FannieReportPage 
{
    // 10Nov13 EL Added title and header
    protected $title = 'Over/Short Variance Report';
    protected $header = 'Over/Short Variance Report';
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Variance Report] shows over/short variance info over time';
    public $report_set = 'Finance';
    protected $report_headers = array('Date', 'Lane', 'POS Total', 'Count Total', 'Variance', 'Emp#', 'Name', 'Share');
    protected $report_cache = 'day';
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = $this->form->store;
            $mode = $this->form->mode;
        } catch (Exception $ex) {
            return array();
        }

        return $mode == 'Details' ? $this->getDetails($date1, $date2, $store) : $this->getTotals($date1, $date2, $store);
    }

    private function getTotals($date1, $date2, $store)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = $this->connection;
        $dbc->selectDB($settings['OverShortDatabase']);
        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        
        $osP = $dbc->prepare("
            SELECT SUM(amt) AS ttl
            FROM dailyCounts
            WHERE date BETWEEN ? AND ?
                AND tender_type IN ('CA', 'SCA')
                AND emp_no=?
                AND storeID=?");

        $transP = $dbc->prepare("
            SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                d.register_no,
                d.emp_no,
                e.FirstName,
                e.LastName,
                SUM(-1*total) AS ttl
            FROM {$dlog} AS d
                LEFT JOIN " . $this->config->get('OP_DB') . $dbc->sep() . "employees AS e
                    ON d.emp_no=e.emp_no
            WHERE d.tdate BETWEEN ? AND ?
                AND trans_type='T'
                AND trans_subtype='CA'
                AND store_id=?
                AND total <> 0
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                d.register_no,
                d.emp_no,
                e.FirstName,
                e.LastName");
        $raw = array();
        $totals = array();
        $transR = $dbc->execute($transP, array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $store));
        // get date-register-employee data
        while ($transW = $dbc->fetchRow($transR)) {
            $date = date('Y-m-d', mktime(0,0,0, $transW['month'], $transW['day'], $transW['year']));
            $key = $date . '-' . $transW['register_no'];
            $raw[] = array(
                'date' => $date,
                'reg' => $transW['register_no'],
                'emp' => $transW['emp_no'],
                'name' => $transW['FirstName'] . ' ' . $transW['LastName'],
                'key' => $key,
            );
            if (!isset($totals[$key])) {
                $totals[$key] = 0;
            }
            $totals[$key] += $transW['ttl'];
        }
        
        // count how many employees were in a drawer each day
        for ($i=0; $i<count($raw); $i++) {
            $count = 0;
            for ($j=0; $j<count($raw); $j++) {
                if ($raw[$i]['key'] == $raw[$j]['key']) {
                    $count++;
                }
            }
            $raw[$i]['count'] = $count;
        }

        // find counted amounts for each drawer
        // avoid re-querying when possible
        for ($i=0; $i<count($raw); $i++) {
            $date = $raw[$i]['date'];
            $key = $raw[$i]['key'];
            if (!isset($raw[$i]['actual'])) {
                $actual = $dbc->getValue($osP, array($date, $date, $raw[$i]['reg'], $store));
                $raw[$i]['actual'] = $actual;
                for ($j=$i+1; $j<count($raw); $j++) {
                    if ($raw[$i]['key'] == $raw[$j]['key']) {
                        $raw[$j]['actual'] = $actual;
                    }
                }
            }
        }

        $data = array();
        foreach ($raw as $row) {
            $emp = $row['emp'];
            if (!isset($data[$emp])) {
                $data[$emp] = array($emp, $row['name'], 0);
            }
            $key = $row['key'];
            $variance = $totals[$key] - $row['actual'];
            if ($row['actual'] == 0) {
                $variance = 0;
            }
            $avg = $variance / $row['count'];
            $data[$emp][2] += $avg;
            if ($emp == 1) print_r($row);
        }

        $ret = array();
        foreach ($data as $emp => $row) {
            $row[2] = sprintf('%.2f', $row[2]);
            $ret[] = $row;
        }

        return $ret;
    }

    public function calculate_footers($data) 
    {
        if (count($data[0]) == 3) {
            $this->report_headers = array('Emp#', 'Name', 'Total Variance Share');
            $sum = array_reduce($data, function($c, $i) { return $c + $i[2]; });
            return array('Total', '', round($sum, 2));
        }

        return array();
    }

    private function getDetails($date1, $date2, $store)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = $this->connection;
        $dbc->selectDB($settings['OverShortDatabase']);

        $osP = $dbc->prepare('
            SELECT emp_no,
                date,
                storeID,
                SUM(amt) AS ttl
            FROM dailyCounts
            WHERE date BETWEEN ? AND ?
                AND tender_type IN (\'CA\', \'SCA\')
                AND storeID=?
            GROUP BY emp_no,
                date,
                storeID');
        $osR = $dbc->execute($osP, array($date1, $date2, $store));

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $transP = $dbc->prepare("
            SELECT SUM(-1*d.total) AS ttl,
                d.emp_no,
                e.LastName,
                e.FirstName
            FROM {$dlog} AS d
                LEFT JOIN " . $this->config->get('OP_DB') . $dbc->sep() . "employees AS e
                    ON d.emp_no=e.emp_no
            WHERE d.tdate BETWEEN ? AND ?
                AND d.trans_type='T'
                AND d.trans_subtype='CA'
                AND d.store_id=?
                AND d.register_no=?
                AND d.upc='0'
                AND total <> 0
            GROUP BY d.emp_no,
                e.LastName,
                e.FirstName");

        $data = array();
        while ($osW = $dbc->fetchRow($osR)) {
            $args = array(
                $osW['date'] . ' 00:00:00',
                $osW['date'] . ' 23:59:59',
                $osW['storeID'],
                $osW['emp_no'],
            );
            $ttl = 0;
            $emps = array();
            $transR = $dbc->execute($transP, $args);
            while ($transW = $dbc->fetchRow($transR)) {
                $ttl += $transW['ttl'];
                $emps[] = array(
                    $transW['emp_no'],
                    $transW['FirstName'] . ' ' . $transW['LastName'],
                );
            }
            $var = $osW['ttl'] - $ttl;
            foreach ($emps as $e) {
                $data[] = array(
                    $osW['date'],
                    $osW['emp_no'],
                    sprintf('%.2f', $ttl),
                    sprintf('%.2f', $osW['ttl']),
                    sprintf('%.2f', $var),
                    $e[0],
                    $e[1],
                    sprintf('%.2f', $var/count($emps)),
                );
            }
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();

        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Show</label>
            <select name="mode" class="form-control">
                <option>Cashier Totals</option>
                <option>Details</option>
            </select>
        </div>
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <button class="btn btn-default btn-core">Submit</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

