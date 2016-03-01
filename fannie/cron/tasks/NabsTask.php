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

class NabsTask extends FannieTask
{

    public $name = 'Nabs Task';

    public $description = 'Creates automatic AR payments for
"Nabs" accounts (some stores call these "Transfers"). Transactions
are dated on the last day of the month as a matter of practice.
Typically run on the first day of each month. Deprecates
monthly.nabs.php.';

    public $default_schedule = array(
        'min' => 5,
        'hour' => 0,
        'day' => '1',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        $nabQ = 'SELECT CardNo FROM ' . $this->config->get('OP_DB').$dbc->sep() . 'custdata
            WHERE memType=4 and personNum=1';
        $nabR = $dbc->query($nabQ);

        /**
          ar_history_today* views filter on tdate=current_date
          This means ar_live_balance is off from midnight until
          ArHistoryTask runs. Since this task runs in that window
          it needs to manually account for the current day's 
          activity.
        */
        $todayP = $dbc->prepare("
            SELECT SUM(CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END)
                - SUM(CASE WHEN department=990 THEN total ELSE 0 END) AS today
            FROM dlog WHERE (trans_subtype='MI' OR department=990)
                AND card_no=?");

        $balQ = 'SELECT balance FROM ar_live_balance where card_no=?';
        $balP = $dbc->prepare($balQ);
        $trans_no = 1;
        while ($nabW = $dbc->fetch_row($nabR)) {
            $balR = $dbc->execute($balP, array($nabW['CardNo']));
            if ($balW = $dbc->fetch_row($balR)) {
                if ($balW[0] > 0) {
                    $today = $dbc->getValue($todayP, array($nabW['CardNo']));
                    $balW[0] += $today;
                    $record = DTrans::defaults();
                    $datetime = date('\'Y-m-t 00:00:00\'', mktime(0,0,0,date('n')-1));
                    $record['emp_no'] = 1001;
                    $record['register_no'] = 20;
                    $record['upc'] = $balW[0] . 'DP990';
                    $record['description'] = 'AR Payment';
                    $record['department'] = 990;
                    $record['quantity'] = 1;
                    $record['ItemQtty'] = 1;
                    $record['card_no'] = $nabW['CardNo'];
                    $record['regPrice'] = $balW[0];
                    $record['total'] = $balW[0];
                    $record['unitPrice'] = $balW[0];
                    $record['trans_no'] = $trans_no;
                    $record['trans_id'] = 1;

                    $info = DTrans::parameterize($record, 'datetime', $datetime);
                    $query = 'INSERT INTO dtransactions (' . $info['columnString'] . ') VALUES (' . $info['valueString'] . ')';
                    $prep = $dbc->prepare($query);
                    $result = $dbc->execute($prep, $info['arguments']);

                    $trans_no++;
                }
            }
        }
    }
}

