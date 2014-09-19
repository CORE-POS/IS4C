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

class ArHistoryTask extends FannieTask
{

    public $name = 'AR History';

    public $description = 'Extracts store charge transactions and
adds them to dedicated history tables. Fetches any new store
charge transactions in the previous 15 days. Can be safely run
repeatedly. Normally run after rotating dtransactions data.
Also updates custdata balances to current, "live" values.
Deprecates nightly.ar.php and arbalance.sanitycheck.php.';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_AR_DEPARTMENTS, $FANNIE_SERVER_DBMS;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        // build department list
        $ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
        $depts = array_pop($depts);
        $dlist = "(";
        $case_args = array();
        $where_args = array();
        foreach ($depts as $d) {
            $dlist .= "?,";
            $case_args[] = $d;
            $where_args[] = $d;
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";
        if ($dlist == ')') {
            // no configured departments
            return false;
        }
        
        // lookup AR transactions from past 15 days
        $lookupQ = "SELECT card_no,
                CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END AS charges,
                CASE WHEN department IN $dlist THEN total ELSE 0 END as payments,
                tdate,trans_num
                FROM dlog_15
                WHERE department IN $dlist OR trans_subtype='MI'";
        $lookupP = $dbc->prepare($lookupQ);
        $args = array();
        foreach($case_args as $ca) {
            $args[] = $ca;
        }
        foreach($where_args as $wa) {
            $args[] = $wa;
        }
        $lookupR = $dbc->execute($lookupP, $args);

        $checkP = $dbc->prepare('SELECT charges, payments FROM ar_history 
                    WHERE tdate=? AND trans_num=? AND card_no=?');
        $addP = $dbc->prepare('INSERT INTO ar_history (card_no, charges, payments, tdate, trans_num)
                            VALUES (?, ?, ?, ?, ?)');
        while($lookupW = $dbc->fetch_row($lookupR)) {
            // check whether transaction is already in ar_history
            $checkR = $dbc->execute($checkP, array($lookupW['tdate'], $lookupW['trans_num'], $lookupW['card_no']));
            if ($dbc->num_rows($checkR) != 0) {
                $exists = false;
                while($checkW = $dbc->fetch_row($checkR)) {
                    if ($checkW['charges'] == $lookupW['charges'] && $checkW['payments'] == $lookupW['payments']) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    continue;
                }
            }

            // add to ar history
            $try = $addR = $dbc->execute($addP, array($lookupW['card_no'], $lookupW['charges'], $lookupW['payments'],
                                                $lookupW['tdate'], $lookupW['trans_num']));
            if ($try === false) {
                echo $this->cronMsg('Error adding AR entry '.$lookupW['tdate']. ' '.$lookupW['trans_num']);
            }
        }

        // rebuild ar history sum table
        $dbc->query("TRUNCATE TABLE ar_history_sum");
        $query = "INSERT INTO ar_history_sum
            SELECT card_no,SUM(charges),SUM(payments),SUM(charges)-SUM(payments)
            FROM ar_history GROUP BY card_no";
        $try = $dbc->query($query);
        if ($try === false) {
            echo $this->cronMsg('Error rebuilding ar_history_sum table');
        }

        // update custdata balance fields
        $balQ = "UPDATE {$FANNIE_OP_DB}.custdata AS c LEFT JOIN 
            ar_live_balance AS n ON c.CardNo=n.card_no
            SET c.Balance = n.balance";
        if ($FANNIE_SERVER_DBMS == "MSSQL"){
            $balQ = "UPDATE {$FANNIE_OP_DB}.dbo.custdata SET Balance = n.balance
            FROM {$FANNIE_OP_DB}.dbo.custdata AS c LEFT JOIN
            ar_live_balance AS n ON c.CardNo=n.card_no";
        }
        $try = $dbc->query($balQ);
        if ($try === false) {
            echo $this->cronMsg('Error reloading custdata balances');
        }

        echo $this->cronMsg('Finished every-day tasks.');

        /* turnover view/cache base tables for WFC end-of-month reports */
        if (date('j') == 1) {

            if ($dbc->table_exists('ar_history_backup')) {
                $dbc->query("TRUNCATE TABLE ar_history_backup");
                $dbc->query("INSERT INTO ar_history_backup SELECT * FROM ar_history");
            }

            if ($dbc->table_exists('AR_EOM_Summary')) {
                $AR_EOM_Summary_Q = "
                INSERT INTO AR_EOM_Summary
                SELECT c.CardNo,"
                .$dbc->concat("c.FirstName","' '","c.LastName",'')." AS memName,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -4
                THEN charges ELSE 0 END)
                - SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -4
                THEN payments ELSE 0 END) AS priorBalance,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." = -3
                    THEN a.charges ELSE 0 END) AS threeMonthCharges,
                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." = -3
                    THEN a.payments ELSE 0 END) AS threeMonthPayments,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -3
                THEN charges ELSE 0 END)
                - SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -3
                THEN payments ELSE 0 END) AS threeMonthBalance,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." = -2
                    THEN a.charges ELSE 0 END) AS twoMonthCharges,
                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." = -2
                    THEN a.payments ELSE 0 END) AS twoMonthPayments,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -2
                THEN charges ELSE 0 END)
                - SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -2
                THEN payments ELSE 0 END) AS twoMonthBalance,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." = -1
                    THEN a.charges ELSE 0 END) AS lastMonthCharges,
                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." = -1
                    THEN a.payments ELSE 0 END) AS lastMonthPayments,

                SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -1
                THEN charges ELSE 0 END)
                - SUM(CASE WHEN ".$dbc->monthdiff('a.tdate',$dbc->now())." <= -1
                THEN payments ELSE 0 END) AS lastMonthBalance

                FROM ar_history_backup AS a LEFT JOIN "
                .$FANNIE_OP_DB.$dbc->sep()."custdata AS c 
                ON a.card_no=c.CardNo AND c.personNum=1
                GROUP BY c.CardNo,c.LastName,c.FirstName";

                $dbc->query("TRUNCATE TABLE AR_EOM_Summary");
                $dbc->query($AR_EOM_Summary_Q);
            }
            echo $this->cronMsg('Finished first-of-month tasks.');
        }
    }
}

