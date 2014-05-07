<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_AR_DEPARTMENTS, $FANNIE_SERVER_DBMS;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $date = date('Y-m-t 23:59:59', mktime(0,0,0,date('n')-1));

        $cn = 'SELECT CardNo FROM ' . $FANNIE_OP_DB.$dbc->sep() . 'custdata
            WHERE memType=4 and personNum=1';
        $r = $dbc->query($cn);

        $balQ = 'SELECT balance FROM ar_live_balance where card_no=?';
        $balP = $dbc->prepare($balQ);
        $tn = 1;
        while($w = $dbc->fetch_row($r)) {
            $balR = $dbc->execute($balP, array($w['CardNo']));
            if ($balW = $dbc->fetch_row($balR)) {
                if ($balW[0] > 0) {
                    $record = DTrans::$DEFAULTS;
                    $datetime = date('\'Y-m-t 00:00:00\'', mktime(0,0,0,date('n')-1));
                    $record['emp_no'] = 1001;
                    $record['register_no'] = 20;
                    $record['upc'] = $balW[0] . 'DP990';
                    $record['description'] = 'AR Payment';
                    $record['department'] = 990;
                    $record['quantity'] = 1;
                    $record['ItemQtty'] = 1;
                    $record['card_no'] = $w['CardNo'];
                    $record['regPrice'] = $balW[0];
                    $record['total'] = $balW[0];
                    $record['unitPrice'] = $balW[0];
                    $record['trans_no'] = $tn;
                    $record['trans_id'] = 1;

                    $info = DTrans::parameterize($record, 'datetime', $datetime);
                    $query = 'INSERT INTO dtransactions (' . $info['columnString'] . ') VALUES (' . $info['valueString'] . ')';
                    $prep = $dbc->prepare($query);
                    $result = $dbc->execute($prep, $info['arguments']);

                    $tn++;
                }
            }
        }
    }
}

