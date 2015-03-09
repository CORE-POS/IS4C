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
class GumMainPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('GiveUsMoney');

    public $page_set = 'Plugin :: Give Us Money';
    public $description = '[Account Editor] creates loan and equity accounts for a given member.';

    public function preprocess()
    {
        $this->header = 'Loans & Equity';
        $this->title = 'Loans & Equity';
        $this->__routes[] = 'get<rateForAmount>';
        $this->__routes[] = 'post<id><principal><term><rate><loandate>';
        $this->__routes[] = 'post<id><fn><city><ph1><ln><state><ph2><addr1><zip><email><addr2>';
        $this->__routes[] = 'post<id><shares><type>';

        return parent::preprocess();
    }

    public function get_rateForAmount_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $query = 'SELECT interestRate 
                  FROM GumLoanDefaultInterestRates
                  WHERE ? BETWEEN lowerBound AND upperBound
                  ORDER BY interestRate DESC';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->rateForAmount));
        if ($dbc->num_rows($result) == 0) {
            echo '0.00';
        } else {
            $row = $dbc->fetch_row($result);
            printf('%.2f', $row['interestRate'] * 100);
        }

        return false;
    }

    public function post_id_principal_term_rate_loandate_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
        $model = new GumLoanAccountsModel($dbc);

        $existing_accounts = array();
        $next_account = 1;
        $model->card_no($this->id);
        foreach($model->find() as $obj) {
            $existing_accounts[] = $obj->accountNumber();
            $next_account++;
        }

        $new = sprintf("%09d-%03d", $this->id, $next_account);
        while(in_array($new, $existing_accounts)) {
            $next_account++;
            $new = sprintf("%09d-%03d", $this->id, $next_account);
        }

        $model->accountNumber($new);
        $model->loanDate($this->loandate);
        $model->principal($this->principal);
        $model->termInMonths($this->term);
        $model->interestRate($this->rate / 100.00);
        $newid = $model->save();

        $model->gumLoanAccountID($newid);
        $model->accountNumber($new);
        $model->load();
        $emp = GumLib::getSetting('emp_no', 1001);
        $reg = GumLib::getSetting('register_no', 30);
        $dept = GumLib::getSetting('loanPosDept', 993);
        $desc = GumLib::getSetting('loanDescription', 'Member Loan');
        $offset = GumLib::getSetting('offsetPosDept', 800);
        $bridge = GumLib::getSetting('posLayer', 'GumCoreLayer');
        if (class_exists($bridge)) {
            $line1 = array(
                'department' => $dept,
                'description' => $desc,
                'amount' => $model->principal(),
                'card_no' => $model->card_no(),
            );
            $line2 = array(
                'department' => $offset,
                'description' => 'OFFSET ' . $desc,
                'amount' => -1 * $model->principal(),
                'card_no' => $model->card_no(),
            );
            $trans_identifier = $bridge::writeTransaction($emp, $reg, array($line1, $line2));

            if ($trans_identifier !== true && $trans_identifier !== false) {
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
                $ledger = new GumLoanLedgerModel($dbc);
                $ledger->accountNumber($model->accountNumber());
                $ledger->amount($model->principal());
                $ledger->tdate(date('Y-m-d H:i:s'));
                $ledger->trans_num($trans_identifier);
                $ledger->save();
            }
        }

        header('Location: GumMainPage.php?id=' . $this->id);

        return false;
    }

    public function post_id_fn_city_ph1_ln_state_ph2_addr1_zip_email_addr2_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $cust = new CustdataModel($dbc);
        $cust->CardNo($this->id);
        $cust->personNum(1);
        $cust->FirstName($this->fn);
        $cust->LastName($this->ln);
        $cust->save();

        $mem = new MeminfoModel($dbc);
        $mem->card_no($this->id);
        $mem->city($this->city);
        $mem->state($this->state);
        $mem->zip($this->zip);
        $mem->phone($this->ph1);
        $mem->email_1($this->email);
        $mem->email_2($this->ph2);
        $street = $this->addr1;
        if (!empty($this->addr2)) {
            $street .= "\n" . $this->addr2;
        }
        $mem->street($street);
        $mem->save();

        header('Location: GumMainPage.php?id=' . $this->id);

        return false;
    }

    public function post_id_shares_type_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_URL;

        $bridge = GumLib::getSetting('posLayer');
        $meminfo = $bridge::getMeminfo($this->id);

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $settings = new GumSettingsModel($dbc);
        $settings->key('equityShareSize');
        $settings->load();

        if ($this->shares != 0) {

            $model = new GumEquitySharesModel($dbc); 
            $model->card_no($this->id);

            $bal = 0.0;
            foreach($model->find() as $obj) {
                $bal += $obj->value();
            }

            if (strtolower($this->type) == 'payoff') {
                $this->shares *= -1;
            }
            $model->shares($this->shares);
            $model->value($this->shares * $settings->value());
            $model->tdate(date('Y-m-d H:i:s'));
            // payoff cannot exceed balance
            if ($model->value() > 0 || ($model->value() < 0 && abs($model->value()) <= $bal)) {
                $newid = $model->save();
                
                // share purchase & email exists
                // use curl to call email page's request handler
                if ($this->shares > 0 && $meminfo->email_1() != '') {

                    $url = 'http://localhost' . $FANNIE_URL . 'modules/plugins2.0/GiveUsMoneyPlugin/GumEmailPage.php?id=' . $this->id . '&creceipt=1&cid=' . $newid;
                    $handle = curl_init($url);
                    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                    $res = curl_exec($handle);
                    curl_close($handle);
                }

                $model->gumEquityShareID($newid);
                $model->load();
                $emp = GumLib::getSetting('emp_no', 1001);
                $reg = GumLib::getSetting('register_no', 30);
                $dept = GumLib::getSetting('equityPosDept', 993);
                $desc = GumLib::getSetting('equityDescription', 'Class C Stock');
                $offset = GumLib::getSetting('offsetPosDept', 800);
                $bridge = GumLib::getSetting('posLayer', 'GumCoreLayer');
                if (class_exists($bridge)) {
                    $line1 = array(
                        'department' => $dept,
                        'description' => $desc,
                        'amount' => $model->value(),
                        'card_no' => $model->card_no(),
                    );
                    $line2 = array(
                        'department' => $offset,
                        'description' => 'OFFSET ' . $desc,
                        'amount' => -1 * $model->value(),
                        'card_no' => $model->card_no(),
                    );
                    $trans_identifier = $bridge::writeTransaction($emp, $reg, array($line1, $line2));

                    if ($trans_identifier !== true && $trans_identifier !== false) {
                        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);
                        $model->trans_num($trans_identifier);
                        $model->save();
                    }
                }
            }
        }

        header('Location: GumMainPage.php?id=' . $this->id);

        return false;
    }

    public function get_id_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $bridge = GumLib::getSetting('posLayer');
        $this->custdata = $bridge::getCustdata($this->id);
        if ($this->custdata === false) {
            echo _('Error: member') . ' ' . $this->id . ' ' . _('does not exist');
            return false;
        }

        $this->meminfo = $bridge::getMeminfo($this->id);

        // bridge may change selected database
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['GiveUsMoneyDB']);

        $this->loans = new GumLoanAccountsModel($dbc);
        $this->loans->card_no($this->id);

        $this->equity = new GumEquitySharesModel($dbc);
        $this->equity->card_no($this->id);

        $this->taxid = new GumTaxIdentifiersModel($dbc);
        $this->taxid->card_no($this->id);

        $this->terms = new GumLoanValidTermsModel($dbc);

        $this->settings = new GumSettingsModel($dbc);

        return true;
    }

    public function css_content()
    {
        return '
            .redtext {
                color: red;
            }
            a.redtext {
                text-decoration: underline;
            }
            td.blackfield {
                font-weight: bold;
                background-color: black;
                color: white;
            }
            td.greenfield {
                font-weight: bold;
                background-color: #090;
                color: black;
            }
            td.greenfield a {
                color: white;
            }
            table.bordered td {
                border-left: solid 1px black;
                border-top: solid 1px black;
            }
            table.bordered td.lborder {
                border-left: solid 1px black;
                border-right: 0;
                border-top: 0;
                border-bottom: 0;
                text-align: center;
            }
            table.bordered tr.bborder td {
                border-bottom: solid 1px black;
            }
            table.bordered tr td:last-child {
                border-right: solid 1px black;
            }
            table.bordered td.rborder {
                border-right: solid 1px black;
                border-left: 0;
                border-top: 0;
                border-bottom: 0;
                text-align: center;
            }
            table.bordered td.nborder {
                border: 0;
                text-align: center;
            }
            table.bordered td.tborder {
                border-top: solid 1px black;
                border-left: 0;
                border-right: 0;
                border-bottom: 0;
            }
        ';
    }

    public function get_id_view()
    {
        global $FANNIE_URL;
        $this->add_script('js/main.js');
        $ret = '';

        $ret .= '<form id="piForm" action="GumMainPage.php" method="post">';
        $ret .= '<input type="hidden" name="id" value="' . $this->id . '" />';
        $ret .= '<table cellspacing="0" cellpadding="4" class="bordered">';
        $ret .= '<tr>';
        $ret .= '<td class="blackfield">### Owner Financing ###</td>';
        $ret .= '<td>First Name: <input type="text" name="fn" value="' . $this->custdata->FirstName() . '" /></td>';
        $ret .= '<td>City: <input type="text" name="city" value="' . $this->meminfo->city() . '" /></td>';
        $ret .= '<td class="greenfield"><a href="GumSearchPage.php">Home</a></td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>Primary Ph: <input type="text" name="ph1" value="' . $this->meminfo->phone() . '" /></td>';
        $ret .= '<td>Last Name: <input type="text" name="ln" value="' . $this->custdata->LastName() . '" /></td>';
        $ret .= '<td>State: <input type="text" name="state" value="' . $this->meminfo->state() . '" /></td>';
        $ret .= '<td class="greenfield"><a href="" onclick="$(\'#piForm\').submit(); return false;">Save</a></td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td>Alternate Ph: <input type="text" name="ph2" value="' . $this->meminfo->email_2() . '" /></td>';
        $addr = $this->meminfo->street();
        $addr2 = '';
        if (strstr($addr, "\n")) {
            list($addr, $addr2) = explode("\n", $addr, 2);
        }
        $ret .= '<td>Address: <input type="text" name="addr1" value="' . $addr . '" /></td>';
        $ret .= '<td>Zip: <input type="text" name="zip" value="' . $this->meminfo->zip() . '" /></td>';
        $ret .= '<td><input type="text" size="5" id="nextMem" placeholder="Mem#" /></td>';
        $ret .= '</tr>';
        $ret .= '<tr class="bborder">';
        $ret .= '<td>Email: <input type="text" name="email" value="' . $this->meminfo->email_1() . '" /></td>';
        $ret .= '<td>Address: <input type="text" name="addr2" value="' . $addr2 . '" /></td>';
        $ssn = 'Unknown';
        if ($this->taxid->load()) {
            $ssn = 'Ends In ' . $this->taxid->maskedTaxIdentifier();
        }
        $ret .= sprintf('<td>SSN: %s (<a href="GumTaxIdPage.php?id=%d">View/Edit</a>)</td>',
                        $ssn, $this->id);
        $ret .= '<td class="greenfield"><a href="" onclick="goToNext(); return false;">Next</a></td>';
        $ret .= '</tr>';
        $ret .= '</table>';
        $ret .= '</form>';

        $ret .= sprintf('<a href="GumEmailPage.php?id=%d">View & Send Emails</a>', $this->id);
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<a href="reports/GumReportIndex.php">Reporting</a>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<a href="../CalendarPlugin/CalendarMainPage.php?calID=69&view=week">Weekly Schedule</a>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= sprintf('<a href="../PIKiller/PIDocumentsPage.php?id=%d">Reference</a>', $this->id);

        $ret .= '<hr />';

        $ret .= '<form id="loanform" action="GumMainPage.php" method="post">';
        $ret .= '<table cellspacing="0" cellpadding="4" class="bordered">';
        $ret .= '<tr><td>Loan Amount</td><td>Term</td><td>Loan Date</td><td>Interest Rate</td><td>Maturity Date</td>';
        $ret .= '<td class="blackfield" colspan="4">###### Owner Loans ######</td></tr>';
        foreach($this->loans->find('loanDate') as $obj) {
            $ld = strtotime($obj->loanDate());
            $ed = mktime(0, 0, 0, date('n', $ld) + $obj->termInMonths(), date('j', $ld), date('Y', $ld));
            $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td>%d Years</td>
                            <td>%s</td>
                            <td>%.2f%%</td>
                            <td>%s</td>
                            <td class="greenfield"><a href="GumPromissoryPage.php?id=%s">Note</td>
                            <td class="greenfield"><a href="GumSchedulePage.php?id=%s">Schedule</a></td>
                            <td class="greenfield"><a href="GumLoanPayoffPage.php?id=%s">Payoff</a></td>
                            </tr>',
                            number_format($obj->principal(), 2),
                            $obj->termInMonths() / 12,
                            date('m/d/Y', $ld),
                            $obj->interestRate() * 100,
                            date('m/d/Y', $ed),
                            $obj->accountNumber(),
                            $obj->accountNumber(),
                            $obj->accountNumber()
            );
        }
        $ret .= '<tr class="bborder">';
        $ret .= '<td>$<input type="text" size="8" id="principal" name="principal" onchange="getDefaultRate(this.value);" /></td>';
        $ret .= '<td><select name="term" id="term" onchange="getEndDate();">';
        $default_term = false;
        foreach($this->terms->find('termInMonths') as $obj) {
            $ret .= sprintf('<option value="%d">%d Years</option>',
                        $obj->termInMonths(), $obj->termInMonths() / 12);
            if ($default_term === false) {
                $default_term = $obj->termInMonths();
            }
        }
        $ret .= '</select></td>';
        $ldate = date('Y-m-d');
        $ret .= '<td><input type="text" size="10" id="loandate" name="loandate" 
                        onchange="getEndDate();" value="'.$ldate.'" /></td>';
        $this->add_onload_command("\$('#loandate').datepicker();\n");
        $ret .= '<td><input type="text" size="4" id="rate" name="rate" onchange="validateRate();" />%
                <input type="hidden" id="maxrate" value="0" />
                </td>';
        $enddate = date('Y-m-d', mktime(0, 0, 0, date('n')+$default_term, date('j'), date('Y')));
        $ret .= '<td id="enddate">' . $enddate . '</td>';
        $ret .= '<input type="hidden" name="id" value="' . $this->id . '" />';
        $ret .= '<td colspan="3"><input type="button" onclick="confirmNewLoan(); return false;" value="Create New Loan" /></td>';
        $ret .= '</tr>';
        $ret .= '</table>';
        $ret .= '</form>';

        $ret .= '<hr />';

        $ret .= '<form id="equityForm" method="post" >';
        $ret .= '<input type="hidden" name="id" value="' . $this->id . '" />';
        $ret .= '<table class="bordered" cellspacing="0" cellpadding="4">';
        $bal_num = 0;
        $bal_amount = 0;
        foreach($this->equity->find() as $obj) {
            $bal_num += $obj->shares();
            $bal_amount += $obj->value();
        }
        $ret .= '<tr class="bborder"><td>Balance</td>';
        $ret .= '<td>' . $bal_num . '</td>';
        $ret .= '<td>' . number_format($bal_amount, 2) . '</td>';
        $ret .= '<td colspan="2" class="blackfield"># Class C Stock #</td>';
        $ret .= '<td><input type="text" size="6" placeholder="Shares" name="shares" onchange="updateEquityTotal(this.value); "/></td>';
        $ret .= '<td id="totalForShares">$0.00</td>';
        $ret .= '<input type="hidden" id="equityType" name="type" value="" />';
        // html is idiotic and treats the enter key differently
        // with exactly one text input
        $ret .= '<input type="text" style="display:none;" />';
        $this->settings->key('equityShareSize');
        $this->settings->load();
        $ret .= '<input type="hidden" id="shareSize" value="' . $this->settings->value() . '" />';
        $ret .= '<td class="greenfield">
                    <a href="" onclick="$(\'#equityType\').val(\'purchase\');$(\'#equityForm\').submit();return false;">Purchase</a>
                    </td>';
        $ret .= '<td class="greenfield">
                    <a href="" onclick="$(\'#equityType\').val(\'payoff\');$(\'#equityForm\').submit();return false;">Payoff</a>
                    </td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $ret .= '<td class="lborder">Date</td>';
        $ret .= '<td class="nborder">Shares</td>';
        $ret .= '<td class="nborder">Total</td>';
        $ret .= '<td class="lborder">Date</td>';
        $ret .= '<td class="nborder">Shares</td>';
        $ret .= '<td class="nborder">Total</td>';
        $ret .= '<td class="lborder">Date</td>';
        $ret .= '<td class="nborder">Shares</td>';
        $ret .= '<td class="rborder">Total</td>';
        $ret .= '</tr>';
        $ret .= '<tr>';
        $i=0;
        foreach($this->equity->find('tdate') as $obj) {
            $amount = number_format($obj->value(), 2);
            if ($obj->shares() < 0) {
                $amount = sprintf('<a class="redtext" href="GumEquityPayoffPage.php?id=%d">%s</a>',
                                $obj->gumEquityShareID(), $amount);
            }
            $ret .= sprintf('<td class="lborder %s">%s</td>
                            <td class="nborder %s">%d</td>
                            <td class="%s %s">%s</td>',
                            ($obj->shares() < 0 ? 'redtext' : ''), 
                            date('m/d/Y', strtotime($obj->tdate())),
                            ($obj->shares() < 0 ? 'redtext' : ''), 
                            $obj->shares(),
                            (($i+1)%3 == 0 ? 'rborder' : 'nborder'),
                            ($obj->shares() < 0 ? 'redtext' : ''), 
                            $amount
            );
            $i++;
            if ($i % 3 == 0) {
                $ret .= '</tr><tr>';
            }
        }
        while($i % 3 != 0) {
            $ret .= '<td class="lborder" colspan="2">&nbsp;</td><td class="'.($i+1%3==0?'rborder':'nborder').'">&nbsp;</td>';
            $i++;
        }
        $ret .= '</tr>';
        $ret .= '<tr><td colspan="9" class="tborder" style="border-right: 0;"></td></tr>';
        $ret .= '</table>';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

