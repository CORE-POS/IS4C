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
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

/**
*/
class GumCheckReport extends FannieReportPage 
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Check#', 'Issue Date', 'Amount', 'Additional Info');

    public function preprocess()
    {
        $this->header = 'Checks Report';
        $this->title = 'Checks Report';

        return parent::preprocess();
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $loanP = $dbc->prepare("
            SELECT card_no
            FROM GumLoanPayoffMap AS m
                INNER JOIN GumLoanAccounts AS l ON m.gumLoanAccountID=l.gumLoanAccountID
            WHERE gumPayoffID=?");

        $equityP = $dbc->prepare("
            SELECT card_no
            FROM GumEquityPayoffMap AS m
                INNER JOIN GumEquityShares AS l ON m.gumEquityShareID=l.gumEquityShareID
            WHERE gumPayoffID=?");

        $prep = $dbc->prepare("
            SELECT * FROM GumPayoffs
            WHERE issueDate BETWEEN ? AND ?");
        $res = $dbc->execute($prep, array($this->form->date1, $this->form->date2 . ' 23:59:59'));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            if (empty($row['reason'])) {
                $loan = $dbc->getValue($loanP, array($row['gumPayoffID']));
                if ($loan) {
                    $row['reason'] = 'LOAN ' . $loan;
                }
            }
            if (empty($row['reason'])) {
                $equity = $dbc->getValue($equityP, array($row['gumPayoffID']));
                if ($equity) {
                    $row['reason'] = 'C EQUITY ' . $equity;
                }
            }
            $data[] = array(
                $row['checkNumber'],
                $row['issueDate'],
                $row['amount'],
                $row['reason'] . ' ' . $row['alternateKey'],
            );
        }
        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <p><button type="submit" class="btn btn-default">List Checks</button></p>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

