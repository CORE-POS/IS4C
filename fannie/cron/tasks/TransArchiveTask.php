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
        $today = date('Y-m-d 00:00:00');

        set_time_limit(0);

        $cols = $sql->table_definition('dtransactions');
        if (isset($cols['date_id'])){
            $sql->query("UPDATE dtransactions SET date_id=DATE_FORMAT(datetime,'%Y%m%d')");
        }

        /* Find date(s) in dtransactions */
        $datesP = $sql->prepare('SELECT YEAR(datetime) AS year, MONTH(datetime) as month, DAY(datetime) as day
                            FROM dtransactions
                            WHERE datetime < ?
                            GROUP BY YEAR(datetime), MONTH(datetime), DAY(datetime)
                            ORDER BY YEAR(datetime), MONTH(datetime), DAY(datetime)');
        $datesR = $sql->execute($datesP, array($today));
        $dates = array();
        while($datesW = $sql->fetch_row($datesR)) {
            $dates[] = sprintf('%d-%02d-%02d', $datesW['year'], $datesW['month'], $datesW['day']);
        }

        if (count($dates) == 0) {
            echo $this->cronMsg('No data to rotate');
            return true;
        }

        /* Load dtransactions into the archive, trim to 90 days */
        $chkP = $sql->prepare("INSERT INTO transarchive SELECT * FROM dtransactions WHERE ".$sql->datediff('datetime','?').'= 0');
        $chk1 = false;
        foreach($dates as $date) {
            $chk1 = $sql->execute($chkP, array($date));
        }
        $chk2 = $sql->query("DELETE FROM transarchive WHERE ".$sql->datediff($sql->now(),'datetime')." > 92");
        if ($chk1 === false) {
            echo $this->cronMsg("Error loading data into transarchive");
        } else if ($chk2 === false) {
            echo $this->cronMsg("Error trimming transarchive");
        } else {
            echo $this->cronMsg("Data rotated into transarchive");
        }

        /* reload all the small snapshot */
        $chk1 = $sql->query("TRUNCATE TABLE dlog_15");
        $chk2 = $sql->query("INSERT INTO dlog_15 SELECT * FROM dlog_90_view WHERE ".$sql->datediff($sql->now(),'tdate')." <= 15");
        if ($chk1 === false || $chk2 === false) {
            echo $this->cronMsg("Error reloading dlog_15");
        } else {
            echo $this->cronMsg("Success reloading dlog_15");
        }

        $added_partition = false;
        $created_view = false;
        $sql = FannieDB::get($FANNIE_ARCHIVE_DB);
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
                    $newR = $sql->query($newQ);
                    if ($newR === false) {
                        echo $this->cronMsg("Error creating new partition $partition_name");
                    } else {
                        $added_partition = true;
                    }
                }
        
                // now just copy rows into the partitioned table
                $loadQ = "INSERT INTO bigArchive SELECT * FROM {$FANNIE_TRANS_DB}.dtransactions
                            WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
                $loadR = $sql->query($loadQ);
            } else if (!$sql->table_exists($table)) {
                // 20Nov12 EL Add "TABLE".
                $query = "CREATE TABLE $table LIKE $FANNIE_TRANS_DB.dtransactions";
                if ($sql->dbms_name() == 'mssql') {
                    $query = "SELECT * INTO $table FROM $FANNIE_TRANS_DB.dbo.dtransactions
                                WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
                }
                $chk1 = $sql->query($query, $FANNIE_ARCHIVE_DB);
                $chk2 = true;
                if ($sql->dbms_name() != 'mssql') {
                    // mysql doesn't create & populate in one step
                    $chk2 = $sql->query("INSERT INTO $table SELECT * FROM $FANNIE_TRANS_DB.dtransactions
                                        WHERE ".$sql->datediff('datetime', "'$date'")."= 0");
                }
                if ($chk1 === false || $chk2 === false) {
                    echo $this->cronMsg("Error creating new archive $table");
                } else {
                    echo $this->cronMsg("Created new table $table and archived dtransactions");
                    if (!$created_view) {
                        $model = new DTransactionsModel($sql);
                        $model->normalizeLog('dlog' . $str, $table, BasicModel::NORMALIZE_MODE_APPLY);
                        $created_view = true;
                    }
                }
            } else {
                $query = "INSERT INTO " . $table . "
                          SELECT * FROM " . $FANNIE_TRANS_DB . $sql->sep() . "dtransactions
                          WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
                $chk = $sql->query($query, $FANNIE_ARCHIVE_DB);
                if ($chk === false) {
                    echo $this->cronMsg("Error archiving dtransactions");
                } else {
                    echo $this->cronMsg("Success archiving dtransactions");
                }
            }

            /* summary table stuff */

            $summary_source = DTransactionsModel::selectDlog($date);
            if ($sql->table_exists("sumUpcSalesByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumUpcSalesByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumUpcSalesByDay
                    SELECT DATE(MAX(tdate)) AS tdate, upc,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                    WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                    FROM $summary_source AS t
                    trans_type IN ('I') AND upc <> '0'
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY upc");
            }
            if ($sql->table_exists("sumRingSalesByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumRingSalesByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumRingSalesByDay
                    SELECT DATE(MAX(tdate)) AS tdate, upc, department,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                        WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                    FROM $summary_source AS t
                    trans_type IN ('I','D') AND upc <> '0'
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY upc, department");
            }
            if ($sql->table_exists("sumDeptSalesByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumDeptSalesByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumDeptSalesByDay
                    SELECT DATE(MAX(tdate)) AS tdate, department,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                        WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                    FROM $summary_source AS t
                    trans_type IN ('I','D') 
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY department");
            }
            if ($sql->table_exists("sumMemSalesByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumMemSalesByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumMemSalesByDay
                    SELECT DATE(MAX(tdate)) AS tdate, card_no,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                        WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
                    COUNT(DISTINCT trans_num) AS transCount
                    FROM $summary_source AS t
                    trans_type IN ('I','D')
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY card_no");
            }
            if ($sql->table_exists("sumMemTypeSalesByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumMemTypeSalesByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumMemTypeSalesByDay
                    SELECT DATE(MAX(tdate)) AS tdate, c.memType,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                        WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
                    COUNT(DISTINCT trans_num) AS transCount
                    FROM $summary_source AS t
                    $FANNIE_OP_DB.custdata AS c ON d.card_no=c.CardNo
                    AND c.personNum=1 WHERE
                    trans_type IN ('I','D')
                    AND upc <> 'RRR' AND card_no <> 0
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY c.memType");
            }
            if ($sql->table_exists("sumTendersByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumTendersByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumTendersByDay
                    SELECT DATE(MAX(tdate)) AS tdate, trans_subtype,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    COUNT(*) AS quantity
                    FROM $summary_source AS t
                    trans_type IN ('T')
                    AND total <> 0
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY trans_subtype");
            }
            if ($sql->table_exists("sumDiscountsByDay") && $sql->dbms_name() != 'mssql') {
                $sql->query("DELETE FROM sumDiscountsByDay WHERE tdate='$date'");
                $sql->query("INSERT INTO sumDiscountsByDay
                    SELECT DATE(MAX(tdate)) AS tdate, c.memType,
                    CONVERT(SUM(total),DECIMAL(10,2)) as total,
                    COUNT(DISTINCT trans_num) AS transCount
                    FROM $summary_source AS t
                    $FANNIE_OP_DB.custdata AS c ON d.card_no=c.CardNo
                    AND c.personNum=1 WHERE
                    trans_type IN ('S') AND total <> 0
                    AND upc = 'DISCOUNT' AND card_no <> 0
                    AND tdate BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                    GROUP BY c.memType");
            }

        } // for loop on dates in dtransactions

        /* drop dtransactions data 
           DO NOT TRUNCATE; that resets AUTO_INCREMENT column
        */
        $sql =  FannieDB::get($FANNIE_TRANS_DB);
        $chk = $sql->query("DELETE FROM dtransactions WHERE datetime < '$today'");
        if ($chk === false) {
            echo $this->cronMsg("Error truncating dtransactions");
        } else {
            echo $this->cronMsg("Success truncating dtransactions");
        }
    }
}

