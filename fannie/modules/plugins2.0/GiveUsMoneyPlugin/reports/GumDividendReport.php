<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumDividendReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array('endDate');
    protected $report_headers = array('Account#', 'Purchase Date', 'Shares', 'Value', 'Days Held',
                                    '1% Rate', '2% Rate', '3% Rate', '4% Rate', '5% Rate');

    public function preprocess()
    {
        $this->header = 'Projected Dividend Report';
        $this->title = 'Projected Dividend Report';

        return parent::preprocess();
    }

    public function report_description_content()
    {
        $dt = FormLib::get('endDate', date('Y-m-d'));
        return array('FY ending ' . $dt);
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $dt = FormLib::get('endDate', date('Y-m-d'));

        $end = strtotime($dt);
        $endDT = new DateTime(date('Y-m-d', $end));
        $start = mktime(0, 0, 0, date('n', $end), date('j', $end)+1, date('Y', $end)-1);
        $startDT = new DateTime(date('Y-m-d', $start));
        $num_days = $startDT->diff($endDT)->format('%r%a') + 1;

        $shares = new GumEquitySharesModel($dbc);
        // accumulate per-member purchases and payoffs
        // in date order
        $buys = array();
        $sells = array();
        foreach ($shares->find(array('card_no', 'tdate')) as $share) {
            $cn = $share->card_no();
            if (!isset($buys[$cn])) {
                $buys[$cn] = array();
            }
            if (!isset($sells[$cn])) {
                $sells[$cn] = array();
            }
            if ($share->shares() > 0) {
                $buys[$cn][] = $share;
            } else {
                $sells[$cn][] = $share;
            }
        }

        // go through payoffs and decrement oldest
        // purchases
        foreach ($sells as $cn => $sales) {
            foreach ($sales as $sale) {
                $num_sold = abs($sale->shares());
                $amt_sold = abs($sale->value());
                while ($num_sold > 0) {
                    for ($i=0; $i<count($buys[$cn]); $i++) {
                        if ($num_sold > $buys[$cn][$i]->shares()) {
                            $num_sold -= $buys[$cn][$i]->shares();
                            $buys[$cn][$i]->shares(0);
                            $amt_sold -= $buys[$cn][$i]->value();
                            $buys[$cn][$i]->value(0);
                        } else {
                            $buys[$cn][$i]->shares($buys[$cn][$i]->shares() - $num_sold);
                            $num_sold = 0;
                            $buys[$cn][$i]->value($buys[$cn][$i]->value() - $amt_sold);
                            $amt_sold = 0;
                            break;
                        }
                    }
                    // if number sold isn't zero after
                    // examining all purchases, there's a data
                    // problem. force while loop to end.
                    $num_sold = 0;
                }
            }
        }

        $data = array();
        foreach ($buys as $cn => $shares) {
            foreach ($shares as $share) {
                $record = array($cn);
                $purchased = strtotime($share->tdate());
                $record[] = date('Y-m-d', $purchased);
                $record[] = $share->shares();
                $record[] = number_format($share->value(), 2);
                $tempDT = new DateTime(date('Y-m-d', $purchased));
                $held = $num_days;
                if ($startDT->diff($tempDT)->format('%r%a') > 0) {
                    $held = $tempDT->diff($endDT)->format('%r%a') + 1;
                    if ($held <= 0) {
                        continue;
                    }
                }
                $record[] = $held;

                for ($i=1; $i<=5; $i++) {
                    $dividend = $share->value() * ($i/100.0) * ($held/((float)$num_days));
                    $record[] = number_format($dividend, 2);
                }

                $data[] = $record;
            }
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        $sums = array(0.0, 0.0, 0.0, 0.0, 0.0);
        foreach ($data as $d) {
            for ($i=0; $i<5; $i++) {
                $sums[$i] += $d[$i+5];
            }
        }
        $ret = array('Total', '', '', '', '');
        foreach ($sums as $s) {
            $ret[] = number_format($s, 2);
        }

        return $ret;
    }

    public function form_content()
    {
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= 'FY ending: ';
        $ret .= '<input type="text" name="endDate" id="endDate" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Get Report" />';
        $ret .= '</form>';

        $this->add_onload_command('$(\'#endDate\').datepicker();');

        return $ret;
    }

}

FannieDispatch::conditionalExec();

