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
class GumPeopleReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array();
    protected $report_headers = array('Mem#', 'First Name', 'Last Name', 'Loan Date', 'Principal', 'Interest Rate', 'Term (Months)', 
                                    'Maturity Date', 'MaturityAmount', 'C Shares'); 

    public function preprocess()
    {
        $this->header = 'Active Loan Report';
        $this->title = 'Active Loan Report';

        return parent::preprocess();
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        // compound interest calculation is MySQL-specific
        $query = 'SELECT c.CardNo AS card_no,
                    c.FirstName, 
                    c.LastName,
                    l.loanDate,
                    CASE WHEN l.principal IS NULL THEN 0 ELSE l.principal END as principal,
                    CASE WHEN l.termInMonths IS NULL THEN 0 ELSE l.termInMonths END as termInMonths,
                    CASE WHEN l.termInMonths IS NULL THEN \'\' ELSE DATE_ADD(loanDate, INTERVAL termInMonths MONTH) END as maturityDate,
                    CASE WHEN l.interestRate IS NULL THEN 0 ELSE l.interestRate END as interestRate,
                    CASE WHEN e.shares IS NULL THEN 0 ELSE e.shares END as shares,
                    CASE WHEN l.principal IS NULL THEN 0
                        ELSE principal * POW(1+interestRate, DATEDIFF(DATE_ADD(loanDate, INTERVAL termInMonths MONTH), loanDate)/365.25)
                    END as maturityAmount
                  FROM ' . $FANNIE_OP_DB . $dbc->sep() . 'custdata AS c
                        LEFT JOIN GumLoanAccounts AS l 
                            ON l.card_no=c.CardNo AND c.personNum=1
                        LEFT JOIN (
                            SELECT card_no, SUM(shares) as shares
                            FROM GumEquityShares
                            GROUP BY card_no
                        ) AS e ON c.cardNo=e.card_no AND c.personNum=1
                  WHERE l.card_no IS NOT NULL OR e.card_no IS NOT NULL
                  ORDER BY l.card_no, l.loanDate';
        $result = $dbc->query($query);

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
               $row['card_no'],
               $row['LastName'],
               $row['FirstName'],
               ($row['loanDate'] == '' ? 'n/a' : date('Y-m-d', strtotime($row['loanDate']))),
               sprintf('%.2f', $row['principal']), 
               sprintf('%.2f', $row['interestRate'] * 100), 
               $row['termInMonths'],
               ($row['maturityDate'] == '' ? 'n/a' : date('Y-m-d', strtotime($row['maturityDate']))),
               sprintf('%.2f', $row['maturityAmount']),
               $row['shares'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        $c = 0.0;
        $mat = 0.0;
        foreach($data as $row) {
            $sum += $row[4];
            $mat += $row[8];
            $c += $row[9];
        }

        return array('Total', '', '', '', sprintf('%.2f', $sum), '', '', '', sprintf('%.2f', $mat), sprintf('%.2f', $c));
    }


}

FannieDispatch::conditionalExec();

