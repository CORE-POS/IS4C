<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
class GumInterestReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array('month', 'year');
    protected $report_headers = array('Account#', 'Principal', 'Rate', 'Term', 'Loan Date',
                                'Begin Balance', 'End Balance', 'Interest This Month');

    public function preprocess()
    {
        $this->header = 'Active Loan Report';
        $this->title = 'Active Loan Report';

        return parent::preprocess();
    }

    public function report_description_content()
    {
        $month = FormLib::get('month', date('n'));
        $year = FormLib::get('year', date('Y'));
        return array('Loan interest earned ' . date('F, Y', mktime(0, 0, 0, $month, 1, $year)));
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $month = FormLib::get('month', date('n'));
        $year = FormLib::get('year', date('Y'));

        $end_of_last_month = mktime(0, 0, 0, $month, 0, $year);
        $ts = mktime(0, 0, 0, $month, 1, $year);
        $end_of_next_month = mktime(0, 0, 0, $month, date('t', $ts), $year);

        $end_last_dt = new DateTime(date('Y-m-d', $end_of_last_month));
        $end_next_dt = new DateTime(date('Y-m-d', $end_of_next_month));

        $loans = new GumLoanAccountsModel($dbc);
        $data = array();
        foreach($loans->find('loanDate') as $loan) {
            $record = array(
                $loan->accountNumber(),
                number_format($loan->principal(), 2),
                number_format($loan->interestRate()*100, 2) . '%',
                $loan->termInMonths(),
                date('Y-m-d', strtotime($loan->loanDate())),
            );
            $loanDT = new DateTime(date('Y-m-d', strtotime($loan->loanDate())));

            $days1 = $loanDT->diff($end_last_dt)->format('%r%a');
            $days2 = $loanDT->diff($end_next_dt)->format('%r%a');

            $bal_before = $loan->principal() * pow(1.0 + $loan->interestRate(), $days1/365.25);
            if ($days1 < 0) {
                $bal_before = $loan->principal();
            }
            $bal_after = $loan->principal() * pow(1.0 + $loan->interestRate(), $days2/365.25);
            if ($days2 < 0) {
                $bal_after = $loan->principal();
            }

            $record[] = number_format($bal_before, 2);
            $record[] = number_format($bal_after, 2);
            $record[] = number_format($bal_after - $bal_before, 2);

            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        foreach($data as $d) {
            $sum += $d[7];
        }
        return array('Total', '', '', '', '', '', '', $sum);
    }

    public function form_content()
    {
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= '<select name="month">';
        for($i=1;$i<=12;$i++) {
            $ts = mktime(0,0,0,$i,1,2000);
            $ret .= sprintf('<option value="%d">%s</option>',
                            $i, date('F', $ts));
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="text" name="year" size="4" value="' . date('Y') . '" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Get Report" />';
        $ret .= '</form>';

        return $ret;
    }

}

FannieDispatch::conditionalExec();

