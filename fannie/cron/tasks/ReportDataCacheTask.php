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

class ReportDataCacheTask extends FannieTask
{

    public $name = 'Cache Report Data';

    public $description = 'Certain queries are cached on a regular
basis to make reports perform faster.

Replaces nightly.tablecache.php';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 5,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);

        $chk = $sql->query("TRUNCATE TABLE batchMergeTable");
        if ($chk === false) {
            echo $this->cronMsg("Could not truncate batchMergeTable");
        }
        $chk = $sql->query("INSERT INTO batchMergeTable
                        SELECT b.startDate,b.endDate,p.upc,p.description,b.batchID
                        FROM batches AS b LEFT JOIN batchList AS l
                        ON b.batchID=l.batchID INNER JOIN products AS p
                        ON p.upc = l.upc");
        if ($chk === false) {
            echo $this->cronMsg("Could not load batch reporting data for UPCs");
        }
        $chk = $sql->query("INSERT INTO batchMergeTable 
                        SELECT b.startDate, b.endDate, p.upc, p.description, b.batchID
                        FROM batchList AS l LEFT JOIN batches AS b
                        ON b.batchID=l.batchID INNER JOIN upcLike AS u
                        ON l.upc = " . $sql->concat("'LC'", $sql->convert('u.likeCode', 'CHAR'), '')
                        . " INNER JOIN products AS p ON u.upc=p.upc
                        WHERE p.upc IS NOT NULL");
        if ($chk === false) {
            echo $this->cronMsg("Could not load batch reporting data for likecodes");
        }

        $sql = FannieDB::get($FANNIE_TRANS_DB);

        $cashierPerformanceSQL = "
            SELECT
            min(tdate) as proc_date,
            max(emp_no) as emp_no,
            max(trans_num) as Trans_Num,
            min(tdate) as startTime,
            max(tdate) as endTime,
            CASE WHEN ".$sql->seconddiff('min(tdate)', 'max(tdate)')." =0 
                then 1 else 
                ".$sql->seconddiff('min(tdate)', 'max(tdate)') ."
            END as transInterval,
            sum(CASE WHEN abs(quantity) > 30 THEN 1 else abs(quantity) END) as items,
            Count(upc) as rings,
            SUM(case when trans_status = 'V' then 1 ELSE 0 END) AS Cancels,
            max(card_no) as card_no
            from dlog_90_view 
            where trans_type IN ('I','D','0','C')
            group by year(tdate),month(tdate),day(tdate),trans_num";

        if (!$sql->isView('CashPerformDay')) {
            $chk = $sql->query("TRUNCATE TABLE CashPerformDay");
            if ($chk === false) {
                echo $this->cronMsg("Could not truncate CashPerformDay");
            }
            $chk = $sql->query("INSERT INTO CashPerformDay " . $cashierPerformanceSQL);
            if ($chk === false) {
                echo $this->cronMsg("Could not load data for CashPerformDay");
            }
        }
        if ($sql->tableExists('CashPerformDay_cache')) {
            $chk = $sql->query("TRUNCATE TABLE CashPerformDay_cache");
            if ($chk === false) {
                echo $this->cronMsg("Could not truncate CashPerformDay_cache");
            }
            $chk = $sql->query("INSERT INTO CashPerformDay_cache " . $cashierPerformanceSQL);
            if ($chk === false) {
                echo $this->cronMsg("Could not load data for CashPerformDay_cache");
            }
        }

        $sql = FannieDB::get($FANNIE_ARCHIVE_DB);

        if ($sql->table_exists("reportDataCache")){
            $sql->query("DELETE FROM reportDataCache WHERE expires < ".$sql->now());
        }

        $daily = DataCache::fileCacheDir('daily');
        if ($daily) {
            $dh = opendir($daily);
            while ( ($file = readdir($dh)) !== false) {
                if (is_file($daily . '/' . $file)) {
                    unlink($daily . '/' . $file);
                }
            }
            closedir($dh);
        }

        $monthly = DataCache::fileCacheDir('monthly');
        if ($monthly && date('j') == 1) {
            $dh = opendir($monthly);
            while ( ($file = readdir($dh)) !== false) {
                if (is_file($monthly . '/' . $file)) {
                    unlink($monthly . '/' . $file);
                }
            }
            closedir($dh);
        }
    }
}

