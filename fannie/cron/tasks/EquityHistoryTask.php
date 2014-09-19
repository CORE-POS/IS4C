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

class EquityHistoryTask extends FannieTask
{

    public $name = 'Equity History';

    public $description = 'Extracts equity transactions and
adds them to dedicated history tables. Fetches any new 
transactions in the previous 15 days. Can be safely run
repeatedly. Normally run after rotating dtransactions data.
Deprecates nightly.equity.php.';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_EQUITY_DEPARTMENTS, $FANNIE_SERVER_DBMS;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        // build department list
        $ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS, $depts);
        $depts = array_pop($depts);
        $dlist = "(";
        $where_args = array();
        foreach ($depts as $d) {
            $dlist .= "?,";
            $where_args[] = $d;
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";
        if ($dlist == ')') {
            // no configured departments
            return false;
        }
        
        // lookup AR transactions from past 15 days
        $lookupQ = "SELECT card_no,
                department, total,
                tdate, trans_num
                FROM dlog_15
                WHERE department IN $dlist"; 
        $lookupP = $dbc->prepare($lookupQ);
        $lookupR = $dbc->execute($lookupP, $where_args);

        $checkP = $dbc->prepare('SELECT stockPurchase FROM stockpurchases 
                    WHERE tdate=? AND trans_num=? AND card_no=? AND dept=?');
        $addP = $dbc->prepare('INSERT INTO stockpurchases (card_no, stockPurchase, tdate, trans_num, dept)
                            VALUES (?, ?, ?, ?, ?)');
        while($lookupW = $dbc->fetch_row($lookupR)) {
            // check whether transaction is already in stockpurchases
            $checkR = $dbc->execute($checkP, array($lookupW['tdate'], $lookupW['trans_num'], $lookupW['card_no'], $lookupW['department']));
            if ($dbc->num_rows($checkR) != 0) {
                $exists = false;
                while($checkW = $dbc->fetch_row($checkR)) {
                    if ($checkW['stockPurchase'] == $lookupW['total']) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    continue;
                }
            }

            // add to equity history
            $try = $dbc->execute($addP, array($lookupW['card_no'], $lookupW['total'], $lookupW['tdate'],
                                                $lookupW['trans_num'], $lookupW['department']));
            if ($try === false) {
                echo $this->cronMsg('Error adding equity entry '.$lookupW['tdate']. ' '.$lookupW['trans_num']);
            }
        }

        // rebuild ar history sum table
        $dbc->query("TRUNCATE TABLE equity_history_sum");
        $query = "INSERT INTO equity_history_sum
            SELECT card_no, SUM(stockPurchase), MIN(tdate)
            FROM stockpurchases GROUP BY card_no";
        $try = $dbc->query($query);
        if ($try === false) {
            echo $this->cronMsg('Error rebuilding equity_history_sum table');
        }
    }
}

