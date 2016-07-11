<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class SetMemDatesTask extends FannieTask
{
    public $name = 'Set Membership Dates Task';

    public $description = 'Assigns start and end dates to
accounts once the first equity payment is made and/or
equity reaches final required balance';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $equityP = $dbc->prepare('
            SELECT e.payments,
                e.startdate
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance AS e
            WHERE e.memnum = ?
        ');

        $accounts = \COREPOS\Fannie\API\member\MemberREST::get();
        foreach ($accounts as $account) {
            if ($account['startDate'] != '' && $account['startDate'] != '0000-00-00 00:00:00') {
                // date has been assigned already
                continue;
            }
            $equityR = $dbc->execute($equityP, array($account['cardNo']));
            if (!$equityR || $dbc->numRows($equityR) == 0) {
                // no equity 
                continue;
            }
            $equity = $dbc->fetchRow($equityR);
            /**
              If an equity payment has been made,
              assign start date to that equity payment's date.
              If equity is not fully paid, set the end
              date one year in the future.
            */
            if ($equity['payments'] > 0) {
                if ($account['startDate'] == '' || $account['startDate'] == '0000-00-00 00:00:00') {
                    $account['startDate'] = $equity['startdate'];
                }
                if ($equity['payments'] < 100) {
                    $ts = strtotime($equity['startdate']);
                    $next_year = date('Y-m-d', mktime(0, 0, 0, date('n', $ts), date('j', $ts), date('Y', $ts)+1));
                    $account['endDate'] = $next_year;
                }
                $account['contactAllowed'] = 1;

                $resp = \COREPOS\Fannie\API\member\MemberREST::post($account['cardNo'], $account);
                if ($resp['errors'] > 0) {
                    $this->cronMsg('Error setting account dates for account #' . $account['cardNo']);
                }
            }
        }

        /**
          Re-fetch accounts with update dates
          and set notification message in custReceiptMessages
          and/or CustomerNotifications
        */
        $accounts = \COREPOS\Fannie\API\member\MemberREST::get();
        $old_table = false;
        if ($dbc->tableExists('custReceiptMessage')) {
            $dbc->query("DELETE FROM custReceiptMessage WHERE msg_text LIKE 'EQUITY OWED% == %'");
            $old_table = true;
        }
        $new_table = false;
        if ($dbc->tableExists('CustomerNotifications')) {
            $dbc->query('DELETE FROM CustomerNotifications WHERE source=\'SetMemDatesTask\'');
            $new_table = true;
        }
        foreach ($accounts as $account) {
            if ($account['endDate'] == '' || $account['endDate'] == '0000-00-00 00:00:00') {
                // no due date
                continue;
            }
            if ($account['memberStatus'] != 'PC') {
                // not a member
                continue;
            }
            $equityR = $dbc->execute($equityP, array($account['cardNo']));
            if (!$equityR || $dbc->numRows($equityR) == 0) {
                // no equity 
                continue;
            }
            $equity = $dbc->fetchRow($equityR);
            if ($equity['payments'] >= 100) {
                // clear end date from paid-in-full
                $account['endDate'] = '0000-00-00 00:00:00';
                $resp = \COREPOS\Fannie\API\member\MemberREST::post($account['cardNo'], $account);
                continue;
            }

            $msg = 'EQUITY OWED $'
                . sprintf('%.2f', 100-$equity['payments'])
                . ' == DUE DATE '
                . date('m/d/Y', strtotime($account['endDate']));
            if ($old_table) {
                $model = new CustReceiptMessageModel($dbc);
                $model->card_no($account['cardNo']);
                $model->msg_text($msg);
                $model->modifier_module('WfcEquityMessage');
                $model->save();
            }
            if ($new_table) {
                $model = new CustomerNotificationsModel($dbc);
                $model->cardNo($account['cardNo']);
                $model->source('SetMemDatesTask');
                $model->type('receipt');
                $model->message($msg);
                $model->modifierModule('WfcEquityMessage');
                $model->save();
            }
        }
    }
}

