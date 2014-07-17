<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class TransArchiveTask extends FannieTask
{
    public $name = 'Transaction Archiving';

    public $description = 'Archive current transaction data.
    Replaces the old nightly.dtrans.php script.';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 0,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB, $FANNIE_ARCHIVE_METHOD;
        $sql = FannieDB::get($FANNIE_TRANS_DB);
        $sql->throwOnFailure(true);
        $today = date('Y-m-d 00:00:00');

        set_time_limit(0);

        $cols = $sql->table_definition('dtransactions');
        if (isset($cols['date_id'])){
            $sql->query("UPDATE dtransactions SET date_id=DATE_FORMAT(datetime,'%Y%m%d')");
        }

        /* Find date(s) in dtransactions */
        $dates = array();
        try {
            $datesP = $sql->prepare('
                SELECT YEAR(datetime) AS year, 
                    MONTH(datetime) as month, 
                    DAY(datetime) as day
                FROM dtransactions
                WHERE datetime < ?
                GROUP BY YEAR(datetime), 
                    MONTH(datetime), 
                    DAY(datetime)
                ORDER BY YEAR(datetime), 
                    MONTH(datetime), 
                    DAY(datetime)
            ');
            $datesR = $sql->execute($datesP, array($today));
            while ($datesW = $sql->fetch_row($datesR)) {
                $dates[] = sprintf('%d-%02d-%02d', $datesW['year'], $datesW['month'], $datesW['day']);
            }
        } catch (Exception $ex) {
            /**
            @severity: this query should not fail unless
            the database server is down or inaccessible
            */
            echo $this->cronMsg('Failed to find dates in dtransactions. Details: '
                . $ex->getMessage(), FannieTask::TASK_WORST_ERROR);
        }

        if (count($dates) == 0) {
            echo $this->cronMsg('No data to rotate');

            return true;
        }

        /* Load dtransactions into the archive, trim to 90 days */
        $chkP = $sql->prepare("INSERT INTO transarchive 
                               SELECT * 
                               FROM dtransactions 
                               WHERE " . $sql->datediff('datetime','?').'= 0');
        foreach ($dates as $date) {
            try {
                $chk1 = $sql->execute($chkP, array($date));
            } catch (Exception $ex) {
                /**
                @severity: generally should not fail, but this isn't
                as important as the long-term archive table(s)
                */
                echo $this->cronMsg('Failed to archive ' . $date . ' in transarchive (last quarter table)
                    Details: ' . $ex->getMessage(), FannieTask::TASK_MEDIUM_ERROR);
            }
        }
        try {
            $chk2 = $sql->query("DELETE FROM transarchive WHERE ".$sql->datediff($sql->now(),'datetime')." > 92");
        } catch (Exception $ex) {
            /**
            @severity: should not happen, but impact is limited.
            performance issues may eventually crop up if the table
            gets very large.
            */
            echo $this->cronMsg('Failed to trim transarchive (last quarter table)
                Details: ' . $ex->getMessage(), FannieTask::TASK_MEDIUM_ERROR);
        }

        /* reload all the small snapshot */
        try {
            $chk1 = $sql->query("TRUNCATE TABLE dlog_15");
            $chk2 = $sql->query("INSERT INTO dlog_15 
                                 SELECT * 
                                 FROM dlog_90_view 
                                 WHERE " . $sql->datediff($sql->now(),'tdate') . " <= 15");
        } catch (Exception $ex) {
            /**
            @severity: no long term impact but may lead
            to reporting oddities
            */
            echo $this->cronMsg('Failed to reload dlog_15. Details: '
                . $ex->getMessage(), FannieTask::TASK_MEDIUM_ERROR);
        }

        $added_partition = false;
        $created_view = false;
        $sql = FannieDB::get($FANNIE_ARCHIVE_DB);
        $sql->throwOnFailure(true);
        foreach ($dates as $date) {
            /* figure out which monthly archive dtransactions data belongs in */
            list($year, $month, $day) = explode('-', $date);
            $table = 'transArchive'.$year.$month;

            if ($FANNIE_ARCHIVE_METHOD == "partitions") {
                // we're just partitioning
                // make a new partition if it's a new month
                if (date('j') == 1 && !$added_partition) {
                    $partition_name = "p" . date("Ym"); 
                    $boundary = date("Y-m-d", mktime(0,0,0,date("n")+1,1,date("Y")));
                    // new partition named pYYYYMM
                    // ends on first day of next month
                    $newQ = sprintf("ALTER TABLE bigArchive ADD PARTITION 
                        (PARTITION %s 
                        VALUES LESS THAN (TO_DAYS('%s'))
                        )",$partition_name,$boundary);
                    try {
                        $newR = $sql->query($newQ);
                        $added_partition = true;
                    } catch (Exception $ex) {
                        /**
                        @severity lack of partitions will eventually
                        cause performance problems in large data sets
                        */
                        echo $this->cronMsg("Error creating new partition $partition_name. Details: "
                            . $ex->getMessage(), FannieTask::TASK_MEDIUM_ERROR);
                    }
                }
        
                // now just copy rows into the partitioned table
                $loadQ = "INSERT INTO bigArchive 
                          SELECT * 
                          FROM {$FANNIE_TRANS_DB}.dtransactions
                          WHERE " . $sql->datediff('datetime', "'$date'") . "= 0";
                try {
                    $loadR = $sql->query($loadQ);
                } catch (Exception $ex) {
                    /**
                    @severity: transaction data was not archived.
                    absolutely needs to be addressed.
                    */
                    echo $this->cronMsg('Failed to properly archive transaction data for ' . $date
                        . ' Details: ' . $ex->getMessage(), FannieTask::TASK_WORST_ERROR);
                }
            } else if (!$sql->table_exists($table)) {
                // 20Nov12 EL Add "TABLE".
                $query = "CREATE TABLE $table LIKE $FANNIE_TRANS_DB.dtransactions";
                if ($sql->dbms_name() == 'mssql') {
                    $query = "SELECT * INTO $table FROM $FANNIE_TRANS_DB.dbo.dtransactions
                                WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
                }
                try {
                    $chk1 = $sql->query($query, $FANNIE_ARCHIVE_DB);
                    if ($sql->dbms_name() != 'mssql') {
                        // mysql doesn't create & populate in one step
                        $chk2 = $sql->query("INSERT INTO $table 
                                             SELECT * 
                                             FROM $FANNIE_TRANS_DB.dtransactions
                                             WHERE ".$sql->datediff('datetime', "'$date'")."= 0");
                    }
                    if (!$created_view) {
                        $model = new DTransactionsModel($sql);
                        $model->normalizeLog('dlog' . $str, $table, BasicModel::NORMALIZE_MODE_APPLY);
                        $created_view = true;
                    }
                } catch (Exception $ex) {
                    /**
                    @severity: missing monthly table will prevent
                    proper transaction archiving. absolutely needs
                    to be addressed.
                    */
                    echo $this->cronMsg("Error creating new archive structure $table Details: "
                        . $ex->getMessage(), FannieTask::TASK_WORST_ERROR);
                }
            } else {
                $query = "INSERT INTO " . $table . "
                          SELECT * FROM " . $FANNIE_TRANS_DB . $sql->sep() . "dtransactions
                          WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
                try {
                    $chk = $sql->query($query, $FANNIE_ARCHIVE_DB);
                } catch (Exception $ex) {
                    /**
                    @severity: transaction data was not archived.
                    absolutely needs to be addressed.
                    */
                    echo $this->cronMsg('Failed to properly archive transaction data for ' . $date
                        . ' Details: ' . $ex->getMessage(), FannieTask::TASK_WORST_ERROR);
                }
            }

            /* summary table stuff */
            if ($sql->dbms_name() == 'mssql') {
                // summaries are mysql only
                continue;
            }

            $summary_source = DTransactionsModel::selectDlog($date);
            if ($sql->table_exists("sumUpcSalesByDay")) { 
                try {
                    $sql->query("DELETE FROM sumUpcSalesByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumUpcSalesByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            upc,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                            WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                        FROM $summary_source AS t
                        WHERE trans_type IN ('I') 
                            AND upc <> '0'
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY upc
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumUpcSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
            if ($sql->table_exists("sumRingSalesByDay")) {
                try {
                    $sql->query("DELETE FROM sumRingSalesByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumRingSalesByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            upc, 
                            department,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                        FROM $summary_source AS t
                        WHERE trans_type IN ('I','D') 
                            AND upc <> '0'
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY upc, 
                            department
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumRingSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
            if ($sql->table_exists("sumDeptSalesByDay")) {
                try {
                    $sql->query("DELETE FROM sumDeptSalesByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumDeptSalesByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            department,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                        FROM $summary_source AS t
                        WHERE trans_type IN ('I','D') 
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY department
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumDeptSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
            if ($sql->table_exists("sumMemSalesByDay")) {
                try {
                    $sql->query("DELETE FROM sumMemSalesByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumMemSalesByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            card_no,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
                            COUNT(DISTINCT trans_num) AS transCount
                        FROM $summary_source AS t
                        WHERE trans_type IN ('I','D')
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY card_no
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumMemSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
            if ($sql->table_exists("sumMemTypeSalesByDay")) {
                try {
                    $sql->query("DELETE FROM sumMemTypeSalesByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumMemTypeSalesByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            c.memType,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
                            COUNT(DISTINCT trans_num) AS transCount
                        FROM $summary_source AS t
                            LEFT JOIN $FANNIE_OP_DB.custdata AS c ON t.card_no=c.CardNo AND c.personNum=1 
                        WHERE trans_type IN ('I','D')
                            AND upc <> 'RRR' 
                            AND card_no <> 0
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY c.memType
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumMemTypeSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
            if ($sql->table_exists("sumTendersByDay")) {
                try {
                    $sql->query("DELETE FROM sumTendersByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumTendersByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            trans_subtype,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            COUNT(*) AS quantity
                        FROM $summary_source AS t
                        WHERE trans_type IN ('T')
                            AND total <> 0
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY trans_subtype
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumTendersSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
            if ($sql->table_exists("sumDiscountsByDay")) {
                try {
                    $sql->query("DELETE FROM sumDiscountsByDay WHERE tdate='$date'");
                    $sql->query("
                        INSERT INTO sumDiscountsByDay
                        SELECT DATE(MAX(tdate)) AS tdate, 
                            c.memType,
                            CONVERT(SUM(total),DECIMAL(10,2)) as total,
                            COUNT(DISTINCT trans_num) AS transCount
                        FROM $summary_source AS t
                            LEFT JOIN $FANNIE_OP_DB.custdata AS c ON t.card_no=c.CardNo AND c.personNum=1
                        WHERE trans_type IN ('S') 
                            AND total <> 0
                            AND upc = 'DISCOUNT' 
                            AND card_no <> 0
                            AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                        GROUP BY c.memType
                    ");
                } catch (Exception $ex) {
                    /**
                    @severity: tables aren't used for antying
                    */
                    echo $this->cronMsg('Summary error with sumDiscountSalesByDay. Details: '
                        . $ex->getMessage(), FannieTask::TASK_TRIVIAL_ERROR);
                }
            }
        } // for loop on dates in dtransactions

        /* drop dtransactions data 
           DO NOT TRUNCATE; that resets AUTO_INCREMENT column
        */
        $sql =  FannieDB::get($FANNIE_TRANS_DB);
        $sql->throwOnFailure(true);
        try {
            $chk = $sql->query("DELETE FROM dtransactions WHERE datetime < '$today'");
        } catch (Exception $ex) {
            /**
            @severity: should not fail. could eventually
            create duplicate archive records if this is
            failing and queries above are not
            */
            echo $this->cronMsg("Error clearing dtransactions. Details: "
                . $ex->getMessage(), FannieTask::TASK_LARGE_ERROR);
        }
    }
}

