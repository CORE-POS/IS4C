<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

    private function setDateID($sql)
    {
        $cols = $sql->tableDefinition('dtransactions');
        if (isset($cols['date_id'])){
            $sql->query("UPDATE dtransactions SET date_id=DATE_FORMAT(datetime,'%Y%m%d')");
        }
    }

    private function getDates($sql)
    {
        /* Find date(s) in dtransactions */
        $dates = array();
        $today = date('Y-m-d 00:00:00');
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
            $this->cronMsg('Failed to find dates in dtransactions. Details: '
                . $ex->getMessage(), FannieLogger::ALERT);
        }

        return $dates;
    }

    private function rotateQuarter($sql, $dates)
    {
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
                $this->cronMsg('Failed to archive ' . $date . ' in transarchive (last quarter table)
                    Details: ' . $ex->getMessage(), FannieLogger::ERROR);
                throw new Exception('Archive failed! Not safe to proceed');
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
            $this->cronMsg('Failed to trim transarchive (last quarter table)
                Details: ' . $ex->getMessage(), FannieLogger::ERROR);
        }
    }

    private function reloadDlog15($sql)
    {
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
            $this->cronMsg('Failed to reload dlog_15. Details: '
                . $ex->getMessage(), FannieLogger::ERROR);
        }
    }

    private function getBigArchiveSql($sql)
    {
        $bigArchive = $sql->query('SHOW CREATE TABLE bigArchive');
        $bigArchive = $sql->fetchRow($bigArchive);
        return $bigArchive['Create Table'];
    }

    private function createPartitionIfNeeded($sql, $date, $bigArchive)
    {
        $partition_name = "p" . date("Ym", strtotime($date)); 
        if (strstr($bigArchive, 'PARTITION ' . $partition_name . ' VALUES') === false) {
            $ts = strtotime($date);
            $boundary = date("Y-m-d", mktime(0,0,0,date("n", $ts)+1,1,date("Y", $ts)));
            // new partition named pYYYYMM
            // ends on first day of next month
            $newQ = sprintf("ALTER TABLE bigArchive ADD PARTITION 
                (PARTITION %s 
                VALUES LESS THAN (TO_DAYS('%s'))
                )",$partition_name,$boundary);
            try {
                $newR = $sql->query($newQ);
                /* refresh table definition after adding partition */
                $bigArchive = $this->getBigArchiveSql($sql);
            } catch (Exception $ex) {
                /**
                @severity lack of partitions will eventually
                cause performance problems in large data sets
                */
                $this->cronMsg("Error creating new partition $partition_name. Details: "
                    . $ex->getMessage(), FannieLogger::ERROR);
            }
        }

        return $bigArchive;
    }

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB, $FANNIE_ARCHIVE_METHOD;
        $sql = FannieDB::get($FANNIE_TRANS_DB);
        $sql->throwOnFailure(true);

        set_time_limit(0);

        $this->setDateID($sql);

        /* Find date(s) in dtransactions */
        $dates = $this->getDates($sql);
        if (count($dates) == 0) {
            $this->cronMsg('No data to rotate', FannieLogger::INFO);

            return true;
        }

        // should NOT catch the exception thrown if
        // rotating into quarterly table fails
        $this->rotateQuarter($sql, $dates);
        $this->reloadDlog15($sql);

        $added_partition = false;
        $created_view = false;
        $sql = FannieDB::get($FANNIE_ARCHIVE_DB);
        $sql->throwOnFailure(true);
        /* get table definition in partitioning mode to
           have a list of existing partitions */
        $bigArchive = $FANNIE_ARCHIVE_METHOD === 'partitions' ? $this->getBigArchiveSql($sql) : false;
        foreach ($dates as $date) {
            /* figure out which monthly archive dtransactions data belongs in */
            list($year, $month, $day) = explode('-', $date);
            $yyyymm = $year.$month;
            $table = 'transArchive'.$yyyymm;

            if ($FANNIE_ARCHIVE_METHOD == "partitions") {
                // we're just partitioning
                // create a partition if it doesn't exist
                $bigArchive = $this->createPartitionIfNeeded($sql, $date, $bigArchive);
        
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
                    $this->cronMsg('Failed to properly archive transaction data for ' . $date
                        . ' Details: ' . $ex->getMessage(), FannieLogger::ALERT);
                    throw new Exception('Archive failed! Not safe to proceed');
                }
            } elseif (!$sql->tableExists($table)) {
                $query = "CREATE TABLE $table LIKE $FANNIE_TRANS_DB.dtransactions";
                if ($sql->dbmsName() == 'mssql') {
                    $query = "SELECT * INTO $table FROM $FANNIE_TRANS_DB.dbo.dtransactions
                                WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
                }
                try {
                    $chk1 = $sql->query($query, $FANNIE_ARCHIVE_DB);
                    if ($sql->dbmsName() != 'mssql') {
                        // mysql doesn't create & populate in one step
                        $chk2 = $sql->query("INSERT INTO $table 
                                             SELECT * 
                                             FROM $FANNIE_TRANS_DB.dtransactions
                                             WHERE ".$sql->datediff('datetime', "'$date'")."= 0");
                    }
                    if (!$created_view) {
                        $model = new DTransactionsModel($sql);
                        $model->dlogMode(true);
                        $model->normalizeLog('dlog' . $yyyymm, $table, BasicModel::NORMALIZE_MODE_APPLY);
                        $created_view = true;
                    }
                } catch (Exception $ex) {
                    /**
                    @severity: missing monthly table will prevent
                    proper transaction archiving. absolutely needs
                    to be addressed.
                    */
                    $this->cronMsg("Error creating new archive structure $table Details: "
                        . $ex->getMessage(), FannieLogger::ALERT);
                    throw new Exception('Archive failed! Not safe to proceed');
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
                    $this->cronMsg('Failed to properly archive transaction data for ' . $date
                        . ' Details: ' . $ex->getMessage(), FannieLogger::ALERT);
                    throw new Exception('Archive failed! Not safe to proceed');
                }
            }

        } // for loop on dates in dtransactions

        /* drop dtransactions data 
           DO NOT TRUNCATE; that resets AUTO_INCREMENT column
        */
        $today = date('Y-m-d 00:00:00');
        $sql = FannieDB::get($FANNIE_TRANS_DB);
        $sql->throwOnFailure(true);
        try {
            $chk = $sql->query("DELETE FROM dtransactions WHERE datetime < '$today'");
        } catch (Exception $ex) {
            /**
            @severity: should not fail. could eventually
            create duplicate archive records if this is
            failing and queries above are not
            */
            $this->cronMsg("Error clearing dtransactions. Details: "
                . $ex->getMessage(), FannieLogger::ALERT);
        }
    }
}

