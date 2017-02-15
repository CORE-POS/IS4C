<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class WfcEquityEmailTask extends FannieTask
{
    public $name = 'WFC Equity Emails Task';

    public $description = 'Schedules equity notification emails';

    public $default_schedule = array(
        'min' => 10,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['ScheduledEmailDB']);
        
        // de-queue paid-in-full members
        // with pending messages
        $model = new ScheduledEmailQueueModel($dbc);
        $model->scheduledEmailTemplateID(2);
        $model->sent(0);
        $balP = $dbc->prepare('
            SELECT payments
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance
            WHERE memnum=?');
        foreach ($model->find() as $obj) {
            $balR = $dbc->execute($balP, array($obj->cardNo()));
            if (!$balR || $dbc->numRows($balR) == 0) {
                $obj->delete();
            }
            $balW = $dbc->fetchRow($balR);
            if ($balW['payments'] >= 100) {
                $obj->delete();
            }
        }

        // find everyone who owes equity
        $oweR = $dbc->query('
            SELECT m.end_date,
                e.payments,
                c.FirstName,
                c.LastName,
                m.card_no
            FROM ' . $this->config->get('OP_DB') . $dbc->sep() . 'memDates AS m
                INNER JOIN ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_live_balance AS e
                    ON m.card_no=e.memnum
                INNER JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'custdata AS c
                    ON m.card_no=c.CardNo AND c.personNum=1
            WHERE e.payments < 100
                AND e.payments > 0
                AND c.Type=\'PC\'
        ');
        while ($w = $dbc->fetchRow($oweR)) {
            $model->reset();
            $model->scheduledEmailTemplateID(2);
            $model->cardNo($w['card_no']);
            $model->sent(1);
            if (count($model->find()) > 0) {
                // already sent an equity message
                // do not queue another 
                continue;
            }
            $model->sent(0);
            $matches = $model->find();
            // update existing if present
            if (count($matches) > 0) {
                $model = array_pop($matches);
                // more than one equity notice queued; delete the rest
                if (count($matches) > 0) {
                    foreach ($matches as $obj) {
                        $obj->delete();
                    }
                }
            }
            $end = $w['end_date'];
            $endts = strtotime($end);
            $sendts = mktime(0, 0, 0, date('n', $endts)-2, date('j', $endts), date('Y', $endts));
            if ($sendts < time()) {
                // don't queue into the past
                continue;
            }
            $model->sendDate(date('Y-m-d', $sendts));
            $json = array(
                'first name' => $w['FirstName'],
                'last name' => $w['LastName'],
                'balance due' => sprintf('%.2f', 100-$w['payments']),
                'due date' => date('F j, Y', $endts),
            );
            $model->templateData(json_encode($json));
            $model->save();
        }
    }
}
