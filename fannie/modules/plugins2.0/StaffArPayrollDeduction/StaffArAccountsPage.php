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
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

/**
*/
class StaffArAccountsPage extends FannieRESTfulPage 
{

    public $page_set = 'Plugin :: Payroll Deductions';
    public $description = '[Accounts] sets which accounts will have deductions for AR
    payments as well as the amounts.';
    public $themed = true;

    public function preprocess()
    {
        $this->title = _('Payroll Deductions');
        $this->header = _('Payroll Deductions');
        $this->__routes[] = 'get<add><payid>';
        $this->__routes[] = 'post<change><deduction>';
        $this->__routes[] = 'get<delete>';
        $this->__routes[] = 'post<saveIds><saveAmounts>';
        $this->__routes[] = 'get<excel>';

        return parent::preprocess();
    }

    public function get_excel_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['StaffArPayrollDB']);

        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment; filename="epiU8U16.csv"');
        $res = $dbc->query("
            SELECT s.payrollIdentifier AS adpID,
                c.lastName,
                c.firstName,
                s.nextPayment AS adjust
            FROM StaffArAccounts AS s
                LEFT JOIN " . FannieDB::fqn('custdata', 'op') . " AS c ON s.card_no=c.CardNo AND c.personNum=1
            ORDER BY c.lastName");
        echo "Employee ID (Clock Sequence),\"1 = Earning, 3 = Deduction\",Paycom Deduction Code,adjust ded amount\r\n";
        while ($row = $dbc->fetchRow($res)) {
            printf('"%s",3,IOU,%.2f' . "\r\n",
                $row['adpID'],
                $row['adjust']
            );
        }

        return false;
    }

    protected function post_change_deduction_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['StaffArPayrollDB']);
        $upP = $dbc->prepare('UPDATE StaffArAccounts SET nextPayment=? WHERE card_no=?');
        $res = $dbc->execute($upP, array($this->deduction, $this->change));
        $ret = array('error'=>false);
        if ($res === false) {
            $ret['error'] = 'Save failed!';
        }
        $ret['deduct'] = sprintf('%.2f', $this->deduction);
        echo json_encode($ret);

        return false;
    }

    public function get_add_payid_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $ret = array();

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new CustdataModel($dbc);
        $model->CardNo($this->add);
        if (count($model->find()) == 0) {
            $ret['error'] = 'Mem# ' . $this->add . ' does not exist!';
        } else {
            $model->personNum(1);
            $model->load();
            $ret['name'] = $model->LastName() . ', ' . $model->FirstName();

            $dbc = FannieDB::get($FANNIE_TRANS_DB);
            $model = new ArLiveBalanceModel($dbc);
            $model->card_no($this->add);
            $balance = 0;
            if ($model->load()) {
                $balance = $model->balance();
            }
            $ret['balance'] = $balance;

            $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);
            $model = new StaffArAccountsModel($dbc);
            $model->card_no($this->add);
            $model->nextPayment(0);
            $model->payrollIdentifier($this->payid);
            $model->save();
        }

        echo json_encode($ret);

        return false;
    }

    public function get_delete_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);
        $model = new StaffArAccountsModel($dbc);
        $model->card_no($this->delete);
        $model->delete();

        return false;
    }

    public function post_saveIds_saveAmounts_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);
        $ids = json_decode($this->saveIds);
        $amounts = json_decode($this->saveAmounts);

        $upP = $dbc->prepare('UPDATE StaffArAccounts SET nextPayment=? WHERE card_no=?');

        $dbc->startTransaction();
        for($i=0; $i<count($ids); $i++) {
            if (!is_numeric($ids[$i]) || !isset($amounts[$i])) {
                continue;
            }
            $upR = $dbc->execute($upP, array($amounts[$i], $ids[$i]));
        }
        $dbc->commitTransaction();

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $this->addScript('js/accounts.js?changed=20180104');
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);

        $model = new StaffArAccountsModel($dbc);
        $info = array();
        $in = '';
        $args = array();
        foreach($model->find('card_no') as $obj) {
            $info[$obj->card_no()] = array(
                'id' => $obj->staffArAccountID(),
                'payroll' => $obj->payrollIdentifier(),
                'amount' => $obj->nextPayment(),
            );
            $in .= '?,';
            $args[] = $obj->card_no();
        }
        $in = substr($in, 0, strlen($in)-1);

        $custdata = $FANNIE_OP_DB . $dbc->sep() . 'custdata';
        $balances = $FANNIE_TRANS_DB . $dbc->sep() . 'ar_live_balance';

        $query = "SELECT c.CardNo, c.LastName, c.FirstName, n.balance
                FROM $custdata AS c
                    LEFT JOIN $balances as n ON c.CardNo=n.card_no
                WHERE
                    c.personNum=1
                    AND c.CardNo IN ($in)";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        while($row = $dbc->fetch_row($result)) {
            $info[$row['CardNo']]['name'] = $row['LastName'] . ', ' . $row['FirstName'];
            $info[$row['CardNo']]['balance'] = $row['balance'];
        }
        uasort($info, function ($a, $b) {
            if ($a['name'] == $b['name']) return 0;
            return ($a['name'] < $b['name']) ? -1 : 1;
        });

        $ret = '<div id="mainDisplayDiv">';
        $query = 'SELECT tdate FROM StaffArDates WHERE tdate >= ' . $dbc->now();
        $next = 'Unknown';
        $result = $dbc->query($query);
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            $next = $row['tdate'];
        }
        $ret .= '<h4>Next deduction is scheduled for: ' . $next;
        $ret .= ' (<a href="StaffArDatesPage.php">View Schedule</a>)</h4>';
        $ret .= '<form onsubmit="return false;">'; // for reset function
        $ret .= '<p>';
        $ret .= '<button type="button" onclick="useCurrent(); return false;" class="btn btn-default">
            Set New to Current Balance</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="reset" class="btn btn-default">Reset</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" onclick="saveForm(); return false;" class="btn btn-default">
            Save New as Next Deduction</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<a href="?excel=1" class="btn btn-default">Download to Excel</a>';
        $ret .= '</p>';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Mem#</th><th>PayrollID</th><th>Name</th><th>Current</th>
                <th>Next Deduction</th><th>New Deduction</th><th>&nbsp;</td></tr>';
        $ttl = 0;
        foreach($info as $card_no => $data) {
            $ret .= sprintf('<tr class="accountrow" id="row%d">
                            <td><a class="cardnotext" href="" onclick="jumpToChange(%d); return false;">%d</td>
                            <td class="payidtext">%s</td>
                            <td class="nametext">%s</td>
                            <td class="currentbalance">%.2f</td>
                            <td class="nextdeduction">%.2f</td>
                            <td><div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" class="nextdeduct form-control" value="%.2f" />
                            </div></td>
                            <td><a href="" onclick="removeAccount(%d); return false;">Remove from List</a></td>
                            </tr>',
                            $card_no,
                            $card_no, $card_no,
                            $data['payroll'],
                            $data['name'],
                            $data['balance'],
                            $data['amount'],
                            $data['amount'],
                            $card_no
            );
            $ttl += $data['amount'];
        }
        $ret .= '<tr><th colspan="5">Total</th><th>$' . number_format($ttl, 2) . '</th></tr>';
        $ret .= '</table>';
        $ret .= '<p>';
        $ret .= '<button type="button" onclick="useCurrent(); return false;" class="btn btn-default">
            Set New to Current Balance</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="reset" class="btn btn-default">Reset</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" onclick="saveForm(); return false;" class="btn btn-default">
            Save New as Next Deduction</button>';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<hr />';
        $ret .= '<h4>Add User To List</h4>';
        $ret .= '<div class="form-group form-inline">';
        $ret .= '<label>Mem#</label>: <input type="text" id="newMem" class="form-control" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>Payroll#</label>: <input type="text" id="newPayID" class="form-control" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" onclick="addNew(); return false;" 
            class="btn btn-default">Add</button>';
        $ret .= '</div>';
        $ret .= '<hr />';
        $ret .= '<h4>Change Single Deduction</h4>';
        $ret .= '<div class="form-group form-inline">';
        $ret .= '<label>Mem#</label>: <input type="text" id="changeMem" class="form-control" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>Deduction Amount</label>: <input type="text" id="changeAmount" class="form-control" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" onclick="changeAmount(); return false;" 
            class="btn btn-default">Change Deduction</button>';
        $ret .= '<hr />';
        $ret .= '<a href="StaffArDiscrepancies.php" class="btn btn-default">Check for Account Discrepancies</a>';
        $ret .= '</div>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>This tool schedules automatic payments to
            accounts\' store charge balance. Its primary purpose is to
            address the gap in time between entering deduction information
            into the payroll system software and actual pay day.</p>
            <p>The <em>Current</em> column shows each account\'s current
            store charge balance. The <em>Next Deduction</em> column
            shows the balance payment that will occur on the next 
            scheduled pay day. In a typical payroll cycle, the user will
            first click <em>Set New to Current Balances</em> and then
            <em>Save New as Next Deduction</em> to lock in the current
            balance as the next scheduled payment. The <em>Next Deduction</em>
            amounts should then be entered into the payroll system software.
            On payday the accounts\' balances will be reduced by the
            specified amounts</p>
            <p>It is important not to alter the <em>Next Deduction</em>
            amounts in the time between entering the deduction information
            in the payroll system and pay day or the amount deducted via payroll
            will not match the balance payment made in POS.
            </p>
            <p>Account balance often will not be exactly zero on the morning
            after payday. This is because if accounts continue to make 
            charges during this time period between setting the deduction amounts
            and issuing paychecks their balance will not be reduced all the way 
            to zero.</p>
            <p>Use the form at the bottom of the page to add new employees to
            this cycle. The Owner/member# is required but the payroll identifier
            is entirely optional.</p>
            <p>The <em>View Schedule</em> link at the top of the page also lets
            you add or adjust future payroll dates.</p>';
    }
}

FannieDispatch::conditionalExec();

