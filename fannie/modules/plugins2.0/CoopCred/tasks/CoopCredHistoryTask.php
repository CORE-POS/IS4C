<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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

class CoopCredHistoryTask extends FannieTask
{

    public $name = 'Coop Cred History';
    public $pluginName = 'CoopCred';

    /* Keep lines to 60 chars for the cron manager popup window.
     *                             --------------  60 edge-v */
    public $description = '
- Extracts Coop Cred charge and payment transactions in the
  previous 15 days and adds new ones to dedicated history
  tables.
- Updates the history summary table.
- Can be safely run repeatedly without repeating
  transactions.
- Normally run after rotating dtransactions data
  (Transation Archiving/nightly.dtrans.php) and before
  syncing CCredMemberships to lanes.

- Updates Fannie CCredMemberships creditBalances to current,
  "live" values.
';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 0,
        'day' => '1',
        'month' => '1',
        'weekday' => '*',
    );

    public function __construct() {
                $this->description = $this->name . "\n" . $this->description;
        //parent::__construct();
    }

    public function run()
    {

        global $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS;
        global $FANNIE_PLUGIN_LIST, $FANNIE_PLUGIN_SETTINGS;

        if (!FanniePlugin::isEnabled($this->pluginName)) {
            echo $this->cronMsg("Plugin '{$this->pluginName}' is not enabled.");
            return False;
        }
        if (!array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) ||
            empty($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'])) {
                echo $this->cronMsg("No Coop Cred Database is named");
                return False;
        }
        $coopCredDB = $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'];

        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $dbc = FannieDB::get($coopCredDB);
        if ($dbc === False || !is_object($dbc)) {
            echo $this->cronMsg("Failed connecting to CoopCredDatabase: " .
                $coopCredDB);
            return False;
        }
        $programQ = "SELECT programID, paymentDepartment, tenderType
            FROM {$coopCredDB}.CCredPrograms";
        $programS = $dbc->prepare($programQ);
        if ($programS === False) {
            echo $this->cronMsg("prepare() failed: $programQ");
            return False;
        }
        $args = array();
        $programR = $dbc->execute($programS,$args);
        if ($programR === False) {
            echo $this->cronMsg("execute() failed: $programQ: " . implode('::',$args));
            return False;
        }
        if ($dbc->numRows($programR) == 0) {
            echo $this->cronMsg("No Programs defined.");
            return False;
        }

        /* Build the argument sets for the transaction query. */
        $dlist = '';
        $tlist = '';
        $sep = '';
        $case_args = array();
        $where_args = array();
        //c: t,t,d
        //w: d,t
        $inc=$dbc->numRows($programR);
        // Empty argument placeholders.
        for ($cr=0 ; $cr<($inc*3);$cr++) {
            $case_args[] = 0;
        }
        for ($wr=0 ; $wr<($inc*2);$wr++) {
            $where_args[] = 0;
        }
        // Actual arguments.
        $r=0;
        $cr=0;
        $wr=0;
        while($programW = $dbc->fetch_row($programR)) {
            $tlist .= "{$sep}?";
            $dlist .= "{$sep}?";
            $sep = ',';

            $cr=$r;
            $case_args[$cr] = $programW['tenderType'];
            $cr += $inc;
            $case_args[$cr] = $programW['tenderType'];
            $cr += $inc;
            $case_args[$cr] = $programW['paymentDepartment'];
            //
            $wr=$r;
            $where_args[$wr] = $programW['paymentDepartment'];
            $wr += $inc;
            $where_args[$wr] = $programW['tenderType'];
            $r++;
        }

        if ($dlist == '') {
            echo $this->cronMsg("No payment departments defined.");
            return False;
        }
        $dlist = "({$dlist})";
        if ($tlist == '') {
            echo $this->cronMsg("No tenders defined.");
            return False;
        }
        $tlist = "({$tlist})";

        $lookupQ = "SELECT
                    CASE WHEN t.trans_subtype in $tlist
                        THEN p.programID
                        ELSE q.programID END
                        AS programID,
                    card_no,
                    CASE WHEN t.trans_subtype IN $tlist
                        THEN -total ELSE 0 END
                        AS charges,
                    CASE WHEN t.department IN $dlist
                        THEN total ELSE 0 END
                        AS payments,
                    t.tdate,
                    t.trans_num
                FROM {$FANNIE_TRANS_DB}.dlog_15 t
                    LEFT JOIN {$coopCredDB}.CCredPrograms p
                        ON t.trans_subtype = p.tenderType
                    LEFT JOIN {$coopCredDB}.CCredPrograms q
                        ON t.department = q.paymentDepartment
                WHERE t.department IN $dlist OR t.trans_subtype IN $tlist";

        $lookupS = $dbc->prepare($lookupQ);
        $args = array();
        foreach($case_args as $ca) {
            $args[] = $ca;
        }
        foreach($where_args as $wa) {
            $args[] = $wa;
        }
        $lookupR = $dbc->execute($lookupS, $args);

        /* Statements used in each iteration of seeing whether an
         * item should be added to the History table.
         */
        /* Look for one transaction in History */
        $checkS = $dbc->prepare("SELECT charges, payments
                    FROM {$coopCredDB}.CCredHistory 
                    WHERE tdate=? AND transNum=? AND cardNo=? AND programID=?");
        /* Add a dlog_15 transaction to History */
        $addS = $dbc->prepare("INSERT INTO {$coopCredDB}.CCredHistory
                    (programID, cardNo, charges, payments, tdate, transNum)
                            VALUES (?, ?, ?, ?, ?, ?)");

        // foreach dlog_15 item
        $added = 0;
        $skipped = 0;
        while($lookupW = $dbc->fetch_row($lookupR)) {

            // check whether dlog_15 transaction is known in CCredHistory
            $checkR = $dbc->execute($checkS,
                array($lookupW['tdate'],
                        $lookupW['trans_num'],
                        $lookupW['card_no'],
                        $lookupW['programID']
                    ));
            /* The transaction is there.  Check further for amount match.
             * When would there be more than one History record? Tender and Change.
             * Allows for both charge and payment in same item?
             * Allows for multiple same-type tenders in a item?
             * Allows for multiple tender types in a item?
            */
            if ($dbc->numRows($checkR) != 0) {
                $exists = False;
                while($checkW = $dbc->fetch_row($checkR)) {
                    if ($checkW['charges'] == $lookupW['charges'] &&
                        $checkW['payments'] == $lookupW['payments']) {
                        // Amount matches. Prevent adding again.
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            // Not already in Coop Cred History so add it.
            $try = $addR = $dbc->execute($addS,
                            array(
                                $lookupW['programID'],
                                $lookupW['card_no'],
                                $lookupW['charges'],
                                $lookupW['payments'],
                                $lookupW['tdate'],
                                $lookupW['trans_num']
                                ));
            /* Debug.
            */
            $added++;
            if ($try === False) {
                echo $this->cronMsg('Error adding Coop Cred History entry >'.
                                    $lookupW['programID']. '< '.
                                    $lookupW['tdate']. ' '.
                                    $lookupW['trans_num']);
            }
        }

        /* Debug
         */
        echo $this->cronMsg("Done adding: $added to CCredHistory skipped: $skipped ");

        /* Rebuild Coop Cred history sum table
         */
        $dbc->query("TRUNCATE TABLE CCredHistorySum");
        $query = "INSERT INTO CCredHistorySum
            SELECT programID, cardNo, SUM(charges), SUM(payments),
                SUM(charges)-SUM(payments),
                NULL
            FROM CCredHistory
            GROUP BY programID, cardNo";
        $try = $dbc->query($query);
        if ($try === False) {
            echo $this->cronMsg('Error rebuilding CCredHistorySum table');
        }

        /* Debug
         */
        echo $this->cronMsg('Done rebuilding CCredHistorySum table');

        /* Update Member creditBalance field
        */
        $balQ = "UPDATE CCredMemberships AS m
            LEFT JOIN CCredLiveBalance AS n
                ON m.programID=n.programID AND m.cardNo=n.cardNo
            SET m.creditBalance = n.balance";
        if ($FANNIE_SERVER_DBMS == "MSSQL"){
            $balQ = "UPDATE CCredMemberships
                SET m.creditBalance = n.balance
                FROM CCredMemberships AS m
                LEFT JOIN CCredLiveBalance AS n
                    ON m.programID=n.programID AND m.cardNo=n.cardNo";
        }
        $try = $dbc->query($balQ);
        if ($try === False) {
            echo $this->cronMsg('Error reloading Member balances');
        }

        /* Debug
         */
        echo $this->cronMsg('Done updating CCredMemberships balances');

        echo $this->cronMsg('Finished every-day tasks.');

        /* turnover view/cache base tables for WFC end-of-month reports */
        if (date('j') == 1) {

            if ($dbc->table_exists('CCredHistoryBackup')) {
                $dbc->query("TRUNCATE TABLE CCredHistoryBackup");
                $dbc->query("INSERT INTO CCredHistoryBackup SELECT * FROM CCredHistory");
            }

        echo $this->cronMsg('First of month: Done rebuilding CCredHistoryBackup');


        // First-of-month operations.
        }

    }

// CoopCredHistoryTask class
}

