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

class WfcAccessEmailTask extends FannieTask
{
    public $name = 'WFC Access Emails Task';

    public $description = 'Schedules access expiration notification emails';

    public $default_schedule = array(
        'min' => 20,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['ScheduledEmailDB']);

        $last_year = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j'), date('Y')-1));
        $dlog_ly = DTransactionsModel::selectDlog($last_year, date('Y-m-d'));
        $accessQ = 'SELECT card_no,
                        MAX(tdate) AS renewed
                    FROM ' . $dlog_ly . '
                    WHERE trans_type=\'I\'
                        AND upc=\'ACCESS\'
                        AND tdate >= ?
                        AND memType <> 3
                    GROUP BY card_no
                    HAVING SUM(quantity) > 0
                    ORDER BY renewed';
        $accessP = $dbc->prepare($accessQ);
        $accessR = $dbc->execute($accessP, array($last_year));
        $queuedP = $dbc->prepare('
            SELECT scheduledEmailQueueID
            FROM ScheduledEmailQueue
            WHERE cardNo=?
                AND scheduledEmailTemplateID=3
                AND sent=0
        ');
        $fnP = $dbc->prepare('SELECT FirstName FROM ' . $this->config->get('OP_DB') . $dbc->sep() . 'custdata WHERE personNum=1 AND CardNo=?');
        $model = new ScheduledEmailQueueModel($dbc);
        while ($w = $dbc->fetchRow($accessR)) {
            $renew = $w['renewed'];
            $ts = strtotime($renew);
            // one year past last purchase
            $expire = date('F j, Y', mktime(0,0,0,date('n',$ts), date('j',$ts), date('Y',$ts)+1));
            // one month before expiration
            $sendDate = date('Y-m-d', mktime(0,0,0,date('n',$ts)+11, date('j',$ts), date('Y',$ts)));
            if (strtotime($sendDate) < time()) {
                // don't queue into the past
                continue;
            }
            $fn = $dbc->getValue($fnP, array($w['card_no']));
            $json = array(
                'first name' => $fn,
                'expiration date' => $expire,
            );
            /**
              If a pending email exists, update it.
              Otherwise queue a new one.
            */
            $queued = $dbc->execute($queuedP, array($w['card_no']));
            if ($queued && $dbc->numRows($queued)) {
                $qw = $dbc->fetchRow($queued);
                $model->reset();
                $model->scheduledEmailQueueID($qw['scheduledEmailQueueID']);
                $model->sendDate($sendDate);
                $model->templateData(json_encode($json));
                $model->save();
            } else {
                $model->reset();
                $model->cardNo($w['card_no']);
                $model->scheduledEmailTemplateID(3);
                $model->sendDate($sendDate);
                $model->templateData(json_encode($json));
                $model->sent(0);
                $model->save();
            }
        }
    }
}
