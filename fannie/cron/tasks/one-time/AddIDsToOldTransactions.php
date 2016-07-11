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

class AddIDsToOldTransactions extends FannieTask
{

    public $name = 'One-time: Fix store_row_id';

    public $description =
'Goes through archived transactions and assigns
values where store_row_id is NULL.
You may want to change the date-based range of
tables checked before running this.';

    public $schedulable = false;

    public function run()
    {
        global $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB, $FANNIE_ARCHIVE_METHOD;

        /**
          Find current maximum assigned store_row_id
          Depending what time this is run, that might be
          found in today's transactions or yesterday's transactions
        */
        $currentMax = 0;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $maxR = $dbc->query('SELECT MAX(store_row_id) FROM dtransactions');
        if ($dbc->num_rows($maxR) > 0) {
            $maxW = $dbc->fetch_row($maxR);
            if (!empty($maxW[0])) {
                $currentMax = $maxW[0];
            }
        }
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $dtrans = DTransactionsModel::selectDtrans($yesterday);
        $maxR = $dbc->query("SELECT MAX(store_row_id) FROM $dtrans WHERE datetime >= '$yesterday 00:00:00'");
        if ($dbc->num_rows($maxR) > 0) {
            $maxW = $dbc->fetch_row($maxR);
            if (!empty($maxW[0]) && $maxW[0] > $currentMax) {
                $currentMax = $maxW[0];
            }
        }
        /* If for some reason you are not running this in the normal way,
         * where currentMax is in dtransactions or yesterday's trans_archive,
         * assign currentMax here.
         * It may be in the possibly empty dtransactions in
         * PhpMyAdmin > Operations > Table Options
         * where AUTO_INCREMENT is the next value to be assigned,
         *  i.e. $currentMax + 1
         * or in the last table effectively updated.
        $currentMax = 9999;
         */

        echo $this->cronMsg("Current maximum is ".$currentMax);

        /* oldest known transaction data
         * Adjust $year and $month for your database.
         */
        $year = 2004;
        $month = 9;
        $new_id = $currentMax + 1;
        // work in one month chunks
        while($year <= date('Y')) {
            if ($year == date('Y') && $month > date('n')) {
                break;
            }

            echo $this->cronMsg('Processing: '.$year.' '.$month);
            $table = $FANNIE_ARCHIVE_DB . $dbc->sep();
            if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
                $table .= 'bigArchive';
            } else {
                $table .= 'transArchive' . $year . str_pad($month, 2, '0', STR_PAD_LEFT);
            }

            if ($dbc->table_exists("$table")) {
                $lowerBound = date('Y-m-01 00:00:00', mktime(0,0,0,$month,1,$year));
                $upperBound = date('Y-m-t 23:59:59', mktime(0,0,0,$month,1,$year));
                $prep = $dbc->prepare('UPDATE ' . $table . ' SET store_row_id = ?
                                    WHERE datetime = ?
                                    AND emp_no = ?
                                    AND register_no = ?
                                    AND trans_no = ?
                                    AND trans_id = ?');
                $lookupQ = "SELECT datetime, emp_no, register_no, trans_no, trans_id
                            FROM $table 
                            WHERE datetime BETWEEN '$lowerBound' AND '$upperBound'
                            AND store_row_id IS NULL
                            ORDER BY datetime";
                $lookupR = $dbc->query($lookupQ);
                $num_records = $dbc->num_rows($lookupR);
                $count = 1;
                // update records one at a time with incrementing IDs
                while($row = $dbc->fetch_row($lookupR)) {
                    // Original monitor interval: 100
                    if ($count == 1 || $count % 1000 == 0) {
                        echo $this->cronMsg(date('F Y', mktime(0,0,0,$month,1,$year)) . ' ' . $count . '/' . $num_records);
                    }

                    $args = array(
                        $new_id,
                        $row['datetime'],
                        $row['emp_no'],
                        $row['register_no'],
                        $row['trans_no'],
                        $row['trans_id'],
                    );
                    $dbc->execute($prep, $args);

                    $count++;
                    $new_id++;
                }
            } else {
                echo $this->cronMsg("$table doesn't exist.");
            }

            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }

        }

        // advance dtransaction's increment counter so it will resume
        // beyond all the IDs that were just used on archives 
        $alterQ = 'ALTER TABLE dtransactions AUTO_INCREMENT = ' . $new_id;
        $rslt = $dbc->query($alterQ);
        if ($rslt === False) {
            echo $this->cronMsg("***Error: Attempt to: $alterQ failed.");
        } else {
            echo $this->cronMsg("Next dtransactions.store_row_id will be $new_id");
        }

        echo $this->cronMsg("Done.");
    }
}

