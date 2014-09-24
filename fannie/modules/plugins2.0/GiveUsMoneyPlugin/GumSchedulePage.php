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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class GumSchedulePage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Loan Schedule] lists annual interest accrued for a loan account.';

    public function preprocess()
    {
        $acct = FormLib::get('id');
        $this->header = 'Loan Schedule' . ' : ' . $acct;
        $this->title = 'Loan Schedule' . ' : ' . $acct;

        return parent::preprocess();
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $this->loan = new GumLoanAccountsModel($dbc);
        $this->loan->accountNumber($this->id);
        if (!$this->loan->load()) {
            echo _('Error: account') . ' ' . $this->id . ' ' . _('does not exist');
            return false;
        }

        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->loan->card_no());
        $this->meminfo = $bridge::getMeminfo($this->loan->card_no());

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $this->taxid = new GumTaxIdentifiersModel($dbc);
        $this->taxid->card_no($this->loan->card_no());

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function css_content()
    {
        return '
            table#scheduleTable {
                width: 100%;
            }
            td.header {
                font-weight: bold;
                text-align: center;
                color: white;
                background-color: black;
            }
            tr.subheader td {
                font-weight: bold;
                text-align: center;
                color: black;
                background-color: #ccc;
            }
            table#scheduleTable td.textfield {
                text-align: center;
            }
            table#scheduleTable td.moneyfield {
                text-align: right;
            }
            table#infoTable {
                width: 100%;
            }
            table#infoTable tr td:nth-child(2) {
                text-align:left;
            }
            table#infoTable tr td:nth-child(4) {
                text-align:left;
            }
            table#infoTable tr td:nth-child(1) {
                text-align:right;
            }
            table#infoTable tr td:nth-child(3) {
                text-align:right;
            }
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $ret = '';

        $ret .= '<table id="infoTable" cellspacing="0" cellpadding="4">';
        $ret .= '<tr>';
        $ret .= '<td class="right">First Name</td><td class="left">' . $this->custdata->FirstName() . '</td>';
        $ssn = 'Unknown';
        if ($this->taxid->load()) {
            $ssn = 'xxx-xx-' . $this->taxid->maskedTaxIdentifier();
        }
        $ret .= '<td class="right">Social Security Number</td><td class="left">' . $ssn . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>Last Name</td><td>' . $this->custdata->LastName() . '</td>';
        $ret .= '<td>Loan Amount</td><td>' . number_format($this->loan->principal(), 2) . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>Address</td><td>' . $this->meminfo->street() . '</td>';
        $ld = strtotime($this->loan->loanDate());
        $ret .= '<td>Loan Date</td><td>' . date('m/d/Y', $ld) . '</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>City</td><td>' . $this->meminfo->city() . '</td>';
        $ret .= '<td>Term</td><td>' . ($this->loan->termInMonths() / 12) . ' Years</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>State</td><td>' . $this->meminfo->state() . '</td>';
        $ret .= '<td>Interest Rate</td><td>' . number_format($this->loan->interestRate() * 100, 2) . '%</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>Zip Code</td><td>' . $this->meminfo->zip() . '</td>';
        $ed = mktime(0, 0, 0, date('n', $ld)+$this->loan->termInMonths(), date('j', $ld), date('Y', $ld));
        $ret .= '<td>Maturity Date</td><td>' . date('m/d/Y', $ed) . '</td>';
        $ret .= '</tr>';
        $ret .= '</table>';

        $ret .= '<hr />';

        $ret .= '<table id="scheduleTable" cellspacing="0" cellpadding="4">';
        $ret .= '<tr><td class="header"colspan="4">Schedule</td></tr>';
        $ret .= '<tr class="subheader">';
        $ret .= '<td>Year Ending</td><td>Days</td><td>Interest</td><td>Balance</td>';
        $ret .= '</tr>';

        $loan_info = GumLib::loanSchedule($this->loan);
        foreach($loan_info['schedule'] as $period) {
            $ret .= sprintf('<tr>
                            <td class="textfield">%s</td>
                            <td class="textfield">%s</td>
                            <td class="moneyfield">%s</td>
                            <td class="moneyfield">%s</td>
                            </tr>',
                            $period['end_date'],
                            $period['days'],
                            number_format($period['interest'], 2),
                            number_format($period['balance'], 2)
            );
        }

        $ret .= '<tr class="subheader">';
        $ret .= '<td>Balance</td>';
        $ret .= '<td>' . number_format($this->loan->principal(), 2) . '</td>';
        $ret .= '<td class="moneyfield">' . number_format($loan_info['total_interest'], 2) . '</td>';
        $ret .= '<td class="moneyfield">' . number_format($loan_info['balance'], 2) . '</td>';
        $ret .= '</tr>';

        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

