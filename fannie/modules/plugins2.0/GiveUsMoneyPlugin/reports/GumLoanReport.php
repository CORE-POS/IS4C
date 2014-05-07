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
class GumLoanReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array();
    protected $report_headers = array('Term (Months)', 'Total Principal', 'Avg. Rate (%)', 'Approx. Maturity Value', 'Nearest Due Date', 'Farthest Due Date');

    public function preprocess()
    {
        $this->header = 'Active Loan Report';
        $this->title = 'Active Loan Report';

        return parent::preprocess();
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        // compound interest calculation is MySQL-specific
        $query = 'SELECT termInMonths, 
                    SUM(principal) as totalP,
                    AVG(interestRate) as avgR,
                    SUM(principal * POW(1+interestRate, DATEDIFF(DATE_ADD(loanDate, INTERVAL termInMonths MONTH), loanDate)/365.25)) as totalM,
                    MIN(loanDate) AS nearest,
                    MAX(loanDate) as farthest
                  FROM GumLoanAccounts
                  GROUP BY termInMonths
                  ORDER BY termInMonths';
        $result = $dbc->query($query);

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
               $row['termInMonths'],
               sprintf('%.2f', $row['totalP']), 
               sprintf('%.2f', $row['avgR'] * 100), 
               sprintf('%.2f', $row['totalM']), 
               date('Y-m-d', strtotime($row['nearest'])),
               date('Y-m-d', strtotime($row['farthest'])),
            );
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        $due = 0.0;
        foreach($data as $row) {
            $sum += $row[1];
            $due += $row[3];
        }

        return array('Total', sprintf('%.2f', $sum), '', sprintf('%.2f', $due), '', '');
    }


}

FannieDispatch::conditionalExec();

