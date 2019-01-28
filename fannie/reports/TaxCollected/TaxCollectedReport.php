<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class TaxCollectedReport extends FannieReportPage 
{
    protected $header = 'Tax Collected Report';
    protected $title = 'Tax Collected Report';
    protected $required_fields = array('date1', 'date2');
    public $themed = true;
    public $description = '[Tax Collected] reports total taxes collected for a date range';
    public $report_set = 'Tax';
    protected $report_headers = array('Date');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;

        $rate_models = new TaxRatesModel($dbc);
        $rates = array();
        foreach ($rate_models->find() as $obj) {
            $rates[$obj->id()] = $obj->description();
            $this->report_headers[] = $obj->description();
        }
        $this->report_headers[] = 'Actual Collected';
        $this->report_headers[] = 'Diff';
        $data = array();
        $dlog = DTransactionsModel::selectDTrans($date1, $date2);
        $lineP = $dbc->prepare("
            SELECT YEAR(datetime) AS year, MONTH(datetime) AS month, DAY(datetime) AS day,
                numflag,
                SUM(regPrice) AS ttl
            FROM {$dlog} AS d
            WHERE datetime BETWEEN ? AND ?
                AND d.upc='TAXLINEITEM'
                AND d.trans_status NOT IN ('Z','X')
                AND " . DTrans::isNotTesting('d') . "
            GROUP BY YEAR(datetime), MONTH(datetime), DAY(datetime), numflag"
        );
        $lineR = $dbc->execute($lineP, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        while ($lineW = $dbc->fetchRow($lineR)) {
            $key = date('Y-m-d', mktime(0,0,0, $lineW['month'], $lineW['day'], $lineW['year']));
            if (!isset($data[$key])) {
                $data[$key] = array();
            }
            $data[$key][$lineW['numflag']] = $lineW['ttl'];
        }

        $allP = $dbc->prepare("
            SELECT YEAR(datetime) AS year, MONTH(datetime) AS month, DAY(datetime) AS day,
                SUM(total) AS ttl
            FROM {$dlog} AS d
            WHERE datetime BETWEEN ? AND ?
                AND d.upc='TAX'
                AND d.trans_status NOT IN ('Z','X')
                AND " . DTrans::isNotTesting('d') . "
            GROUP BY YEAR(datetime), MONTH(datetime), DAY(datetime)"
        );
        $allR = $dbc->execute($allP, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        while ($allW = $dbc->fetchRow($allR)) {
            $key = date('Y-m-d', mktime(0,0,0, $allW['month'], $allW['day'], $allW['year']));
            $data[$key]['actual'] = $allW['ttl'];
        }

        $ret = array();
        foreach ($data as $date => $info) {
            $record = array($date);
            $ttl = 0;
            foreach ($rates as $id => $name) {
                $val = isset($info[$id]) ? $info[$id] : 0;
                $record[] = sprintf('%.2f', $val);
                $ttl += $val;
            }
            $record[] = sprintf('%.2f', $info['actual']);
            $record[] = sprintf('%.2f', $info['actual'] - $ttl);
            $ret[] = $record;
        }

        return $ret;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }
        $sum = function($arr, $col) { 
            return array_reduce($arr, function ($c, $i) use ($col) { return $c + $i[$col]; });
        };
        return array('Total', $sum($data, 1), $sum($data, 2), $sum($data, 3), $sum($data, 4));
    }

    public function form_content()
    {
        return '<form method="get">
            <div class="row">'
                . FormLib::standardDateFields() . '
            </div>
            <p>
                <button type="submit" class="btn btn-default">Submit</button>
            </p>
            </form>';
    }
}

FannieDispatch::conditionalExec();

