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
class StaffArAccountsPage extends FannieRESTfulPage 
{

    public $page_set = 'Plugin :: Payroll Deductions';
    public $description = '[Accounts] sets which accounts will have deductions for AR
    payments as well as the amounts.';

    public function preprocess()
    {
        $this->title = _('Payroll Deductions');
        $this->header = _('Payroll Deductions');
        $this->__routes[] = 'get<add><payid>';
        $this->__routes[] = 'get<delete>';
        $this->__routes[] = 'post<saveIds><saveAmounts>';

        return parent::preprocess();
    }

    public function get_add_payid_handler()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ROOT;
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

        $model = new StaffArAccountsModel($dbc);
        for($i=0; $i<count($ids); $i++) {
            if (!is_numeric($ids[$i]) || !isset($amounts[$i])) {
                continue;
            }
            $model->card_no($ids[$i]);
            $model->nextPayment($amounts[$i]);
            $model->save();
        }

        return false;
    }

    public function get_view()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $this->add_script('js/accounts.js');
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

        $ret = '<div id="mainDisplayDiv">';
        $query = 'SELECT tdate FROM StaffArDates WHERE tdate >= ' . $dbc->now();
        $next = 'Unknown';
        $result = $dbc->query($query);
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            $next = $row['tdate'];
        }
        $ret .= '<p style="font-size:120%;">Next deduction is scheduled for: ' . $next;
        $ret .= ' (<a href="StaffArDatesPage.php">View Schedule</a>)</p>';
        $ret .= '<form onsubmit="return false;">'; // for reset function
        $ret .= '<p>';
        $ret .= '<input type="submit" onclick="useCurrent(); return false;" value="Set New to Current Balance" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="reset" value="Reset" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" onclick="saveForm(); return false;" value="Save New as Next Deduction" />';
        $ret .= '</p>';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1" id="accountTable">';
        $ret .= '<tr><th>Mem#</th><th>PayrollID</th><th>Name</th><th>Current</th>
                <th>Next Deduction</th><th>New Deduction</th><th>&nbsp;</td></tr>';
        foreach($info as $card_no => $data) {
            $ret .= sprintf('<tr class="accountrow" id="row%d">
                            <td class="cardnotext">%d</td>
                            <td class="payidtext">%s</td>
                            <td class="nametext">%s</td>
                            <td class="currentbalance">%.2f</td>
                            <td>%.2f</td>
                            <td><input type="text" size="7" class="nextdeduct" value="%.2f" /></td>
                            <td><a href="" onclick="removeAccount(%d); return false;">Remove from List</a></td>
                            </tr>',
                            $card_no,
                            $card_no,
                            $data['payroll'],
                            $data['name'],
                            $data['balance'],
                            $data['amount'],
                            $data['amount'],
                            $card_no
            );
        }
        $ret .= '</table>';
        $ret .= '<p>';
        $ret .= '<input type="submit" onclick="useCurrent(); return false;" value="Set New to Current Balance" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="reset" value="Reset" />';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" onclick="saveForm(); return false;" value="Save New as Next Deduction" />';
        $ret .= '</form>';
        $ret .= '</div>';
        $ret .= '<hr />';
        $ret .= '<b>Add User To List</b><br />';
        $ret .= '<b>Mem#</b>: <input type="text" id="newMem" size="6" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Payroll#</b>: <input type="text" id="newPayID" size="6" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" onclick="addNew(); return false;" value="Add" />';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

