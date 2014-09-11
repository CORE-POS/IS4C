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
class GumReportIndex extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $discoverable = false;

    public function preprocess()
    {
        $this->header = 'Available Reporting';
        $this->title = 'Available Reporting';

        return parent::preprocess();
    }
    
    public function get_view()
    {
        return '
            <ul>
            <li><a href="GumLoanReport.php">Active Loans</a></li>
            <li><a href="GumInterestReport.php">Monthly Loan Interest</a></li>
            <li><a href="GumEquityReport.php">Active Equity</a></li>
            <li><a href="GumBothReport.php">Combined Report</a></li>
            <li><a href="GumPeopleReport.php">Owner Report</a></li>
            <li><a href="GumDividendReport.php">Projected Dividends</a></li>
            <li><a href="GumIssuedDividendReport.php">Issued Dividends</a></li>
            </ul>
        ';
    }
}

FannieDispatch::conditionalExec();

