<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/* HELP

   nightly.tablecache.php

   Something of a catch-all, this script is used generically
   to load data into lookup tables. Generally this means copying
   data from relatively slow views into tables so subesquent
   queries against that data will be faster.

   This currently affects cashier performance reporting and
   batch movement reporting.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$chk = $sql->query("TRUNCATE TABLE batchMergeTable");
if ($chk === False)
    echo cron_msg("Could not truncate batchMergeTable");
$chk = $sql->query("INSERT INTO batchMergeTable
                SELECT b.startDate,b.endDate,p.upc,p.description,b.batchID
                FROM batches AS b LEFT JOIN batchList AS l
                ON b.batchID=l.batchID INNER JOIN products AS p
                ON p.upc = l.upc");
if ($chk === False)
    echo cron_msg("Could not load batch reporting data for UPCs");
$chk = $sql->query("INSERT INTO batchMergeTable 
                SELECT b.startDate, b.endDate, p.upc, p.description, b.batchID
                FROM batchList AS l LEFT JOIN batches AS b
                ON b.batchID=l.batchID INNER JOIN upcLike AS u
                ON l.upc = " . $sql->concat('LC', $sql->convert('u.likeCode', 'CHAR'), '')
                . "INNER JOIN products AS p ON u.upc=p.upc
                WHERE p.upc IS NOT NULL");
if ($chk === False)
    echo cron_msg("Could not load batch reporting data for likecodes");

$sql->query("use $FANNIE_TRANS_DB");

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
    if ($chk === False)
        echo cron_msg("Could not truncate CashPerformDay");
    $chk = $sql->query("INSERT INTO CashPerformDay " . $cashierPerformanceSQL);
    if ($chk === False)
        echo cron_msg("Could not load data for CashPerformDay");
}
if ($sql->tableExists('CashPerformDay_cache')) {
    $chk = $sql->query("TRUNCATE TABLE CashPerformDay_cache");
    if ($chk === False)
        echo cron_msg("Could not truncate CashPerformDay_cache");
    $chk = $sql->query("INSERT INTO CashPerformDay_cache " . $cashierPerformanceSQL);
    if ($chk === False)
        echo cron_msg("Could not load data for CashPerformDay_cache");
}

$sql->query("USE ".$FANNIE_ARCHIVE_DB);
if ($sql->table_exists("reportDataCache")){
    $sql->query("DELETE FROM reportDataCache WHERE expires < ".$sql->now());
}

echo cron_msg("Success");
?>
