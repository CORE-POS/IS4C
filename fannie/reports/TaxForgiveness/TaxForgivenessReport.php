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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TaxForgivenessReport extends FannieReportPage 
{
    protected $header = 'Tax Forgiveness Report';
    protected $title = 'Tax Forgiveness Report';
    protected $required_fields = array('date1', 'date2');
    public $themed = true;
    public $description = '[Tax Forgiveness] reports transactions where tax was fully or partially forgiven';
    public $report_set = 'Tax';
    protected $report_headers = array('Date', 'Receipt', '%Discount', 'FS Tender');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;

        $rate_models = new TaxRatesModel($dbc);
        $rates = array();
        foreach ($rate_models->find() as $obj) {
            $rates[$obj->id()] = $obj;
        }
        $perRateSQL = '';
        $collectedSQL = '';
        foreach ($rates as $id => $obj) {
            $perRateSQL .= sprintf('
                SUM(CASE WHEN discountable=0 AND tax=%d THEN total ELSE 0 END) AS noDisc%d,
                SUM(CASE WHEN discountable=1 AND tax=%d THEN total ELSE 0 END) AS yesDisc%d,',
                $id, $id,
                $id, $id);
            $collectedSQL .= sprintf('
                SUM(CASE WHEN upc=\'TAXLINEITEM\' AND numflag=%d THEN regPrice ELSE 0 END) AS collected%d,',
                $id, $id);
            $this->report_headers[] = $obj->description() . ' Expected';
            $this->report_headers[] = $obj->description() . ' Net Discount';
            $this->report_headers[] = $obj->description() . ' Actual';
            $this->report_headers[] = $obj->description() . ' Forgiven';
        }
        $this->report_headers[] = 'Total Expected';
        $this->report_headers[] = 'Total Net Discount';
        $this->report_headers[] = 'Total Actual';
        $this->report_headers[] = 'Total Forgiven';

        $dtrans = DTransactionsModel::selectDtrans($date1, $date2);
        $query = '
            SELECT YEAR(datetime) AS year,
                MONTH(datetime) AS month,
                DAY(datetime) AS day,
                register_no,
                emp_no,
                trans_no,
                ' . $perRateSQL . '
                MAX(percentDiscount) AS pd,
                SUM(CASE WHEN trans_subtype IN (\'EF\',\'FS\') THEN -total ELSE 0 END) AS fsTender,
                ' . $collectedSQL . '
                SUM(CASE WHEN upc=\'TAX\' THEN total ELSE 0 END) AS totalTax
            FROM ' . $dtrans . ' AS d
                LEFT JOIN taxrates AS t ON d.tax=t.id
            WHERE datetime BETWEEN ? AND ?
                AND trans_status NOT IN (\'X\',\'Z\')
                AND emp_no <> 9999
                AND register_no <> 99
            GROUP BY 
                YEAR(datetime),
                MONTH(datetime),
                DAY(datetime),
                register_no,
                emp_no,
                trans_no
            HAVING SUM(CASE WHEN d.tax > 0 THEN 1 ELSE 0 END) <> 0
                AND SUM(CASE WHEN trans_subtype IN (\'EF\',\'FS\') THEN -total ELSE 0 END) <> 0';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        $data = array();
        while ($w = $dbc->fetchRow($res)) {
            $record = array(
                date('Y-m-d', mktime(0, 0, 0, $w['month'], $w['day'], $w['year'])),
                $w['emp_no'] . '-' . $w['register_no'] . '-' . $w['trans_no'],
                sprintf('%d%%', $w['pd']),
                sprintf('%.2f', $w['fsTender']),
            );
            $all = new stdClass();
            $all->total = 0;
            $all->net = 0;
            $all->actual = 0;
            $all->forgiven = 0;
            foreach ($rates as $id => $obj) {
                $total = sprintf('%.2f', ($w['noDisc' . $id] + $w['yesDisc' . $id]) * $obj->rate());
                $net = sprintf('%.2f', ($w['noDisc' . $id] + ((1-($w['pd']/100.00)) * $w['yesDisc' . $id])) * $obj->rate());
                $actual = sprintf('%.2f', $w['collected' . $id]);
                $forgiven = $net - $actual;
                $record[] = $total;
                $record[] = $net;
                $record[] = $actual;
                $record[] = $forgiven;
                $all->total += $total;
                $all->net += $net;
                $all->actual += $actual;
                $all->forgiven += $forgiven;
            }
            $record[] = $all->total;
            $record[] = $all->net;
            $record[] = $all->actual;
            $record[] = $all->forgiven;
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }
        $sums = array_map(function($v){ return 0; }, $data[0]);
        foreach ($data as $row) {
            for ($i=3; $i<count($row); $i++) {
                $sums[$i] += $row[$i];
            }
        }
        return $sums;
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

