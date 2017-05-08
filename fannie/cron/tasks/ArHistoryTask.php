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
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        // build department list
        $ret = preg_match_all("/[0-9]+/",$this->config->get('AR_DEPARTMENTS'),$depts);
        $depts = array_pop($depts);
        $errors = $this->logActivity($dbc, $depts);
        foreach ($errors as $error) {
            $this->cronMsg($error, FannieLogger::ERROR);
        }

        // temporary; don't want to hang on details with normal overnight run
        $this->logDetails($dbc);

        // rebuild ar history sum table
        if ($this->rebuildCacheTable($dbc) === false) {
            $this->cronMsg('Error rebuilding ar_history_sum table', FannieLogger::ERROR);
        }

        // update custdata balance fields
        if ($this->updateBalances($dbc) === false) {
            $this->cronMsg('Error reloading custdata balances', FannieLogger::ERROR);
        }

        $this->cronMsg('Finished every-day tasks.', FannieLogger::INFO);

        /* turnover view/cache base tables for WFC end-of-month reports */
        if (date('j') == 1) {
            $this->endOfMonthCaches($dbc);
            $this->cronMsg('Finished first-of-month tasks.', FannieLogger::INFO);
        }
    }

    private function logActivity($dbc, $depts)
    {
        $errors = array();
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
            $errors[] = 'No configured AR departments';
            return $errors;
        }
        
        // lookup AR transactions from past 15 days
        $lookupQ = "SELECT card_no,
                CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END AS charges,
                CASE WHEN department IN $dlist THEN total ELSE 0 END as payments,
                tdate,trans_num
                FROM dlog_15
                WHERE (department IN $dlist OR trans_subtype='MI')
                    AND tdate < " . $dbc->curdate();
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
        while ($lookupW = $dbc->fetch_row($lookupR)) {
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
                $errors[] = 'Error adding AR entry '.$lookupW['tdate']. ' '.$lookupW['trans_num'];
            }
        }

        return $errors;
    }

    private function logDetails($dbc)
    {
        // get transactions w/o details
        $res = $dbc->query("
            SELECT ar_history_id,
                card_no,
                tdate,
                trans_num
            FROM ar_history AS a
                LEFT JOIN ArHistoryDetails AS d ON a.ar_history_id=d.arHistoryID
            WHERE d.arHistoryID IS NULL
                AND (a.charges <> 0 OR a.payments <> 0)");
        $addP = $dbc->prepare("
            INSERT INTO ArHistoryDetails
            (arHistoryID, description, amount)
            VALUES (?, ?, ?)");
        $names = $this->config->get('OP_DB') . $dbc->sep() . 'productUser';
        $dbc->startTransaction();
        $count = 0;
        while ($row = $dbc->fetchRow($res)) {
            $dlog = DTransactionsModel::selectDlog($row['tdate']);
            $prep = $dbc->prepare("
                SELECT COALESCE(n.brand, '') AS brand,
                    CASE WHEN n.description IS NULL OR n.description='' 
                        THEN d.description ELSE n.description
                    END AS description,
                    SUM(quantity) AS quantity,
                    SUM(total) AS total
                FROM {$dlog} AS d
                    LEFT JOIN {$names} AS n ON d.upc=n.upc
                WHERE d.tdate BETWEEN ? AND ?
                    AND d.trans_num=?
                    AND d.card_no=?
                    AND d.trans_type <> 'T'
                    AND d.total <> 0
                GROUP BY d.upc,
                    n.brand,
                    n.description,
                    d.description
                ORDER BY d.trans_id");
            list($date, ) = explode(' ', $row['tdate'], 2);
            $args = array(
                $date . ' 00:00:00',
                $date . ' 23:59:59',
                $row['trans_num'],
                $row['card_no'],
            );
            $detailR = $dbc->execute($prep, $args);
            while ($detailW = $dbc->fetchRow($detailR)) {
                $item = sprintf('%.2f', $detailW['quantity']);
                if (substr($item, -3) == '.00') {
                    $item = substr($item, 0, strlen($item)-3);
                }
                $item .= ' ';
                if ($detailW['brand']) {
                    $item .= $detailW['brand'] . ' ';
                }
                $detailW['description'] = str_replace("\n", " ", $detailW['description']);
                $item .= $detailW['description'];
                if ($detailW['description'] == 'Tax' || $detailW['description'] == 'Discount') {
                    $item = $detailW['description'];
                }
                $dbc->execute($addP, array($row['ar_history_id'], $item, $detailW['total']));
            }
            $count++;
            if ($count > 500) {
                $dbc->commitTransaction();
                $dbc->startTransaction();
            }
        }
        $dbc->commitTransaction();
    }

    private function rebuildCacheTable($dbc)
    {
        // rebuild ar history sum table
        $dbc->query("TRUNCATE TABLE ar_history_sum");
        $query = "INSERT INTO ar_history_sum
            SELECT card_no,SUM(charges),SUM(payments),SUM(charges)-SUM(payments)
            FROM ar_history GROUP BY card_no";
        return $dbc->query($query);
    }

    private function updateBalances($dbc)
    {
        $FANNIE_OP_DB = $this->config->get('OP_DB');
        $balQ = "UPDATE {$FANNIE_OP_DB}.custdata AS c LEFT JOIN 
            ar_live_balance AS n ON c.CardNo=n.card_no
            SET c.Balance = n.balance";
        if ($this->config->get('SERVER_DBMS') == "MSSQL"){
            $balQ = "
                UPDATE {$FANNIE_OP_DB}.dbo.custdata 
                    SET Balance = n.balance
                FROM {$FANNIE_OP_DB}.dbo.custdata AS c 
                LEFT JOIN ar_live_balance AS n ON c.CardNo=n.card_no";
        }
        return $dbc->query($balQ);
    }

    private function endOfMonthCaches($dbc)
    {
        if ($dbc->tableExists('ar_history_backup')) {
            $dbc->query("TRUNCATE TABLE ar_history_backup");
            $dbc->query("INSERT INTO ar_history_backup SELECT * FROM ar_history");
        }

        if ($dbc->tableExists('AR_EOM_Summary')) {
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
            .$this->config->get('OP_DB').$dbc->sep()."custdata AS c 
            ON a.card_no=c.CardNo AND c.personNum=1
            GROUP BY c.CardNo,c.LastName,c.FirstName";

            $dbc->query("TRUNCATE TABLE AR_EOM_Summary");
            $dbc->query($AR_EOM_Summary_Q);
        }
    }
}

