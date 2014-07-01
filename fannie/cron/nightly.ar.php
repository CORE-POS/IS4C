<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

   nightly.ar.php

   Copies AR transaction information:
   - trans_subtype='MI'
   - department in $FANNIE_AR_DEPARTMENTS
   for the previous day from dlog_15 into ar_history.

   turnover view/cache base tables for WFC end-of-month reports
     i.e. empty ar_history_backup and copy ar_history to it.
          re-populate AR_EOM_Summary

   Should be run after dtransaction rotation (nightly.dtrans.php)
   and after midnight.

   See also: arbalance.sanitycheck
   
   Deprecated in favour of cron/tasks/ArHistoryTask.php
   
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
$depts = array_pop($depts);
$dlist = "(";
foreach ($depts as $d){
    $dlist .= $d.",";   
}
$dlist = substr($dlist,0,strlen($dlist)-1).")";

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$query = "INSERT INTO ar_history
    SELECT card_no,
    CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END AS charges,
    CASE WHEN department IN $dlist THEN total ELSE 0 END as payments,
    tdate,trans_num
    FROM dlog_15
    WHERE "
    .$sql->datediff($sql->now(),'tdate')." = 1
    AND (department IN $dlist OR trans_subtype='MI')";  
$rslt = $sql->query($query);
if ($rslt === False) {
    echo cron_msg("Failed: $query");
}

$sql->query("TRUNCATE TABLE ar_history_sum");
$query = "INSERT INTO ar_history_sum
    SELECT card_no,SUM(charges),SUM(payments),SUM(charges)-SUM(payments)
    FROM ar_history GROUP BY card_no";
$rslt = $sql->query($query);
if ($rslt === False) {
    echo cron_msg("Failed: $query");
}

/* turnover view/cache base tables for WFC end-of-month reports */
if (date("j")==1 && $sql->table_exists("ar_history_backup")){
    $query="TRUNCATE TABLE ar_history_backup";
    $rslt = $sql->query($query);
    if ($rslt === False) {
        echo cron_msg("Failed: $query");
    }
    $query="INSERT INTO ar_history_backup SELECT * FROM ar_history";
    $rslt = $sql->query($query);
    if ($rslt === False) {
        echo cron_msg("Failed: $query");
    }

    $AR_EOM_Summary_Q = "
    INSERT INTO AR_EOM_Summary
    SELECT c.CardNo,"
    .$sql->concat("c.FirstName","' '","c.LastName",'')." AS memName,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -4
    THEN charges ELSE 0 END)
    - SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -4
    THEN payments ELSE 0 END) AS priorBalance,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." = -3
        THEN a.charges ELSE 0 END) AS threeMonthCharges,
    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." = -3
        THEN a.payments ELSE 0 END) AS threeMonthPayments,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -3
    THEN charges ELSE 0 END)
    - SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -3
    THEN payments ELSE 0 END) AS threeMonthBalance,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." = -2
        THEN a.charges ELSE 0 END) AS twoMonthCharges,
    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." = -2
        THEN a.payments ELSE 0 END) AS twoMonthPayments,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -2
    THEN charges ELSE 0 END)
    - SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -2
    THEN payments ELSE 0 END) AS twoMonthBalance,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." = -1
        THEN a.charges ELSE 0 END) AS lastMonthCharges,
    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." = -1
        THEN a.payments ELSE 0 END) AS lastMonthPayments,

    SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -1
    THEN charges ELSE 0 END)
    - SUM(CASE WHEN ".$sql->monthdiff('a.tdate',$sql->now())." <= -1
    THEN payments ELSE 0 END) AS lastMonthBalance

    FROM ar_history_backup AS a LEFT JOIN "
    .$FANNIE_OP_DB.$sql->sep()."custdata AS c 
    ON a.card_no=c.CardNo AND c.personNum=1
    GROUP BY c.CardNo,c.LastName,c.FirstName";

    if ($sql->table_exists("AR_EOM_Summary")){
        $query="TRUNCATE TABLE AR_EOM_Summary";
        $rslt = $sql->query($query);
        if ($rslt === False) {
            echo cron_msg("Failed: $query");
        }
        $rslt = $sql->query($AR_EOM_Summary_Q);
        if ($rslt === False) {
            echo cron_msg("Failed: $AR_EOM_Summary_Q");
        }
    }
}

echo cron_msg("Done.");

?>
