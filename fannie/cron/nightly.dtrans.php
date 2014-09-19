<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

   nightly.dtrans.php

   This script archives transaction data. The main
   reason for rotating transaction data into
   multiple snapshot tables is speed. A single large
   transaction table eventually becomes slow. The 
   rotation applied here is as follows:

   dtransactions is copied into transarchive, then
   transarchive is trimmed so it contains the previous
   90 days of transactions.

   dlog_15, a lookup table of the past 15 days'
   transaction data, is reloaded using transarchive

   dtransactions is also copied to a monthly snapshot,
   transarchiveYYYYMM on the archive database. Support
   for archiving to a remote server is theoretical and
   should be thoroughly tested before being put into
   production. Archive tables are created automatically
   as are corresponding dlog and receipt views.

   After dtransactions has been copied to these two
   locations, it is truncated. This script is meant to
   be run nightly so that dtransactions always holds
   just the current day's data. 
*/

include('../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
include('../src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$cols = $sql->table_definition('dtransactions');
if (isset($cols['date_id'])){
    $sql->query("UPDATE dtransactions SET date_id=DATE_FORMAT(datetime,'%Y%m%d')");
}

/* Find date(s) in dtransactions */
$datesR = $sql->query('SELECT YEAR(datetime) AS year, MONTH(datetime) as month, DAY(datetime) as day
                    FROM dtransactions
                    GROUP BY YEAR(datetime), MONTH(datetime), DAY(datetime)
                    ORDER BY YEAR(datetime), MONTH(datetime), DAY(datetime)');
$dates = array();
while($datesW = $sql->fetch_row($datesR)) {
    $dates[] = sprintf('%d-%02d-%02d', $datesW['year'], $datesW['month'], $datesW['day']);
}

$UPDATED_DLOG_SCHEMA = false;
$table_def = $sql->table_definition('dlog');
if (isset($table_def['description'])) {
    // most likely
    $UPDATED_DLOG_SCHEMA = true;
}

/* Load dtransactions into the archive, trim to 90 days */
$chkP = $sql->prepare("INSERT INTO transarchive SELECT * FROM dtransactions WHERE ".$sql->datediff('datetime','?').'= 0');
$chk1 = false;
foreach($dates as $date) {
    $chk1 = $sql->execute($chkP, array($date));
}
$chk2 = $sql->query("DELETE FROM transarchive WHERE ".$sql->datediff($sql->now(),'datetime')." > 92");
if ($chk1 === false) {
    echo cron_msg("Error loading data into transarchive");
} elseif ($chk2 === false) {
    echo cron_msg("Error trimming transarchive");
} else {
    echo cron_msg("Data rotated into transarchive");
}

/* reload all the small snapshot */
$chk1 = $sql->query("TRUNCATE TABLE dlog_15");
$chk2 = $sql->query("INSERT INTO dlog_15 SELECT * FROM dlog_90_view WHERE ".$sql->datediff($sql->now(),'tdate')." <= 15");
if ($chk1 === false || $chk2 === false)
    echo cron_msg("Error reloading dlog_15");
else
    echo cron_msg("Success reloading dlog_15");

$added_partition = false;
foreach($dates as $date) {
    /* figure out which monthly archive dtransactions data belongs in */
    list($year, $month, $day) = explode('-', $date);
    $dstr = $year.$month;
    $table = 'transArchive'.$dstr;

    /* store monthly archive locally or remotely as needed 
       remote archiving is very beta
    */
    if ($FANNIE_ARCHIVE_REMOTE){
        $sql = new SQLManager($FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_DBMS,
            $FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_USER,$FANNIE_ARCHIVE_PW);
        if (!$sql->table_exists($table)){
            createArchive($table,$sql);
            if ($UPDATED_DLOG_SCHEMA) {
                $model = new DTransactionsModel($sql);
                $model->normalizeLog('dlog' . $str, $table, BasicModel::NORMALIZE_MODE_APPLY);
            } else {
                createViews($dstr,$sql);
            }
        }
        $sql->add_connection($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
            $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
        $sql->transfer($FANNIE_TRANS_DB,
            "select * from dtransactions WHERE ".$sql->datediff('datetime',"'$date'")."= 0",
            $FANNIE_ARCHIVE_DB,"insert into $table");
    } else {
        $sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
        if ($FANNIE_ARCHIVE_METHOD == "partitions" && strstr($FANNIE_SERVER_DBMS, "MYSQL")) {
            // we're just partitioning
            // make a new partition if it's a new month
            if (date('j') == 1 && !$added_partition){
                $p = "p".date("Ym"); 
                $boundary = date("Y-m-d",mktime(0,0,0,date("n")+1,1,date("Y")));
                // new partition named pYYYYMM
                // ends on first day of next month
                $newQ = sprintf("ALTER TABLE bigArchive ADD PARTITION 
                    (PARTITION %s 
                    VALUES LESS THAN (TO_DAYS('%s'))
                    )",$p,$boundary);
                $newR = $sql->query($newQ);
                if ($newR === false) {
                    echo cron_msg("Error creating new partition $p");
                } else {
                    $added_partition = true;
                }
            }
        
            // now just copy rows into the partitioned table
            $loadQ = "INSERT INTO bigArchive SELECT * FROM {$FANNIE_TRANS_DB}.dtransactions
                        WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
            $loadR = $sql->query($loadQ);
        } else if (!$sql->table_exists($table)){
            // 20Nov12 EL Add "TABLE".
            $query = "CREATE TABLE $table LIKE $FANNIE_TRANS_DB.dtransactions";
            if ($FANNIE_SERVER_DBMS == 'MSSQL') {
                $query = "SELECT * INTO $table FROM $FANNIE_TRANS_DB.dbo.dtransactions
                            WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
            }
            $chk1 = $sql->query($query,$FANNIE_ARCHIVE_DB);
            $chk2 = true;
            if (strstr($FANNIE_SERVER_DBMS,"MYSQL")) {
                // mysql doesn't create & populate in one step
                $chk2 = $sql->query("INSERT INTO $table SELECT * FROM $FANNIE_TRANS_DB.dtransactions
                                    WHERE ".$sql->datediff('datetime', "'$date'")."= 0");
            }
            if ($chk1 === false || $chk2 === false) {
                echo cron_msg("Error creating new archive $table");
            } else {
                echo cron_msg("Created new table $table and archived dtransactions");
            }
            if ($UPDATED_DLOG_SCHEMA) {
                $model = new DTransactionsModel($sql);
                $model->normalizeLog('dlog' . $str, $table, BasicModel::NORMALIZE_MODE_APPLY);
            } else {
                createViews($dstr,$sql);
            }
        } else {
            $query = "INSERT INTO $table SELECT * FROM $FANNIE_TRANS_DB.dtransactions
                        WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
            if ($FANNIE_SERVER_DBMS == 'MSSQL') {
                $query = "INSERT INTO $table SELECT * FROM $FANNIE_TRANS_DB.dbo.dtransactions
                        WHERE ".$sql->datediff('datetime', "'$date'")."= 0";
            }
            $chk = $sql->query($query,$FANNIE_ARCHIVE_DB);
            if ($chk === false) {
                echo cron_msg("Error archiving dtransactions");
            } else {
                echo cron_msg("Success archiving dtransactions");
            }
        }

        /* summary table stuff */

        if ($sql->table_exists("sumUpcSalesByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){
            $sql->query("INSERT INTO sumUpcSalesByDay
                SELECT DATE(MAX(tdate)) AS tdate, upc,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                FROM $FANNIE_TRANS_DB.dlog WHERE
                trans_type IN ('I') AND upc <> '0'
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY upc");
        }
        if ($sql->table_exists("sumRingSalesByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){    
            $sql->query("INSERT INTO sumRingSalesByDay
                SELECT DATE(MAX(tdate)) AS tdate, upc, department,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                    WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                FROM $FANNIE_TRANS_DB.dlog WHERE
                trans_type IN ('I','D') AND upc <> '0'
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY upc, department");
        }
        if ($sql->table_exists("sumDeptSalesByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){
            $sql->query("INSERT INTO sumDeptSalesByDay
                SELECT DATE(MAX(tdate)) AS tdate, department,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                    WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
                FROM $FANNIE_TRANS_DB.dlog WHERE
                trans_type IN ('I','D') 
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY department");
        }
        if ($sql->table_exists("sumMemSalesByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){ 
            $sql->query("INSERT INTO sumMemSalesByDay
                SELECT DATE(MAX(tdate)) AS tdate, card_no,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                    WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
                COUNT(DISTINCT trans_num) AS transCount
                FROM $FANNIE_TRANS_DB.dlog WHERE
                trans_type IN ('I','D')
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY card_no");
        }
        if ($sql->table_exists("sumMemTypeSalesByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){ 
            $sql->query("INSERT INTO sumMemTypeSalesByDay
                SELECT DATE(MAX(tdate)) AS tdate, c.memType,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                    WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
                COUNT(DISTINCT trans_num) AS transCount
                FROM $FANNIE_TRANS_DB.dlog AS d LEFT JOIN
                $FANNIE_OP_DB.custdata AS c ON d.card_no=c.CardNo
                AND c.personNum=1 WHERE
                trans_type IN ('I','D')
                AND upc <> 'RRR' AND card_no <> 0
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY c.memType");
        }
        if ($sql->table_exists("sumTendersByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){  
            $sql->query("INSERT INTO sumTendersByDay
                SELECT DATE(MAX(tdate)) AS tdate, trans_subtype,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                COUNT(*) AS quantity
                FROM $FANNIE_TRANS_DB.dlog WHERE
                trans_type IN ('T')
                AND total <> 0
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY trans_subtype");
        }
        if ($sql->table_exists("sumDiscountsByDay") && strstr($FANNIE_SERVER_DBMS,"MYSQL")){    
            $sql->query("INSERT INTO sumDiscountsByDay
                SELECT DATE(MAX(tdate)) AS tdate, c.memType,
                CONVERT(SUM(total),DECIMAL(10,2)) as total,
                COUNT(DISTINCT trans_num) AS transCount
                FROM $FANNIE_TRANS_DB.dlog AS d LEFT JOIN
                $FANNIE_OP_DB.custdata AS c ON d.card_no=c.CardNo
                AND c.personNum=1 WHERE
                trans_type IN ('S') AND total <> 0
                AND upc = 'DISCOUNT' AND card_no <> 0
                AND ".$sql->datediff('tdate',"'$date'")."= 0
                GROUP BY c.memType");
        }
    }
} // for loop on dates in dtransactions

/* drop dtransactions data 
   DO NOT TRUNCATE; that resets AUTO_INCREMENT column
*/
$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$chk = $sql->query("DELETE FROM dtransactions");
if ($chk === false)
    echo cron_msg("Error truncating dtransactions");
else
    echo cron_msg("Success truncating dtransactions");

function createArchive($name,$db){
    global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_REMOTE,
        $FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_DB;;

    $dbms = $FANNIE_ARCHIVE_REMOTE?$FANNIE_ARCHIVE_DBMS:$FANNIE_SERVER_DBMS;
    
    $trans_columns = "(
      `datetime` datetime default NULL,
      `register_no` smallint(6) default NULL,
      `emp_no` smallint(6) default NULL,
      `trans_no` int(11) default NULL,
      `upc` varchar(255) default NULL,
      `description` varchar(255) default NULL,
      `trans_type` varchar(255) default NULL,
      `trans_subtype` varchar(255) default NULL,
      `trans_status` varchar(255) default NULL,
      `department` smallint(6) default NULL,
      `quantity` double default NULL,
      `scale` tinyint(4) default NULL,
      `cost` double default 0.00 NULL,
      `unitPrice` double default NULL,
      `total` double default NULL,
      `regPrice` double default NULL,
      `tax` smallint(6) default NULL,
      `foodstamp` tinyint(4) default NULL,
      `discount` double default NULL,
      `memDiscount` double default NULL,
      `discountable` tinyint(4) default NULL,
      `discounttype` tinyint(4) default NULL,
      `voided` tinyint(4) default NULL,
      `percentDiscount` tinyint(4) default NULL,
      `ItemQtty` double default NULL,
      `volDiscType` tinyint(4) default NULL,
      `volume` tinyint(4) default NULL,
      `VolSpecial` double default NULL,
      `mixMatch` smallint(6) default NULL,
      `matched` smallint(6) default NULL,
      `memType` tinyint(2) default NULL,
      `staff` tinyint(4) default NULL,
      `numflag` smallint(6) default 0 NULL,
      `charflag` varchar(2) default '' NULL,
      `card_no` varchar(255) default NULL,
      `trans_id` int(11) default NULL
    )";

    if ($dbms == 'MSSQL'){
        $trans_columns = "([datetime] [datetime] NOT NULL ,
            [register_no] [smallint] NOT NULL ,
            [emp_no] [smallint] NOT NULL ,
            [trans_no] [int] NOT NULL ,
            [upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [description] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [department] [smallint] NULL ,
            [quantity] [float] NULL ,
            [scale] [tinyint] NULL ,
            [cost] [money] NULL ,
            [unitPrice] [money] NULL ,
            [total] [money] NOT NULL ,
            [regPrice] [money] NULL ,
            [tax] [smallint] NULL ,
            [foodstamp] [tinyint] NOT NULL ,
            [discount] [money] NOT NULL ,
            [memDiscount] [money] NULL ,
            [discountable] [tinyint] NULL ,
            [discounttype] [tinyint] NULL ,
            [voided] [tinyint] NULL ,
            [percentDiscount] [tinyint] NULL ,
            [ItemQtty] [float] NULL ,
            [volDiscType] [tinyint] NOT NULL ,
            [volume] [tinyint] NOT NULL ,
            [VolSpecial] [money] NOT NULL ,
            [mixMatch] [smallint] NULL ,
            [matched] [smallint] NOT NULL ,
            [memType] [smallint] NULL ,
            [isStaff] [tinyint] NULL ,
            [numflag] [smallint] NULL ,
            [charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
            [trans_id] [int] NOT NULL )";
    }

    $db->query("CREATE TABLE $table $trans_columns",$FANNIE_ARCHIVE_DB);
}

function createViews($dstr,$db){
    global $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_REMOTE,
        $FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_DB,
        $FANNIE_SERVER,$FANNIE_SERVER_PW,$FANNIE_SERVER_USER,
        $FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_USER,
        $FANNIE_ARCHIVE_PW; 

    if ($FANNIE_ARCHIVE_REMOTE){
        $db->add_connection($FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_DBMS,
            $FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_USER,
            $FANNIE_ARCHIVE_PW);
    }
    else {
        $db->add_connection($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
            $FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
    }

    $dbms = $FANNIE_ARCHIVE_REMOTE?$FANNIE_ARCHIVE_DBMS:$FANNIE_SERVER_DBMS;

    $table_def = $db->table_definition('transArchive' . $str);

    $dlogQ = "CREATE  view dlog$dstr as
        select 
        d.datetime as tdate, 
        d.register_no, 
        d.emp_no, 
        d.trans_no, 
        d.upc, 
        d.description,
        CASE WHEN (d.trans_subtype IN ('CP','IC') OR d.upc like('%000000052')) then 'T' 
            WHEN d.upc = 'DISCOUNT' then 'S' else d.trans_type end as trans_type, 
        CASE WHEN d.upc = 'MAD Coupon' THEN 'MA' ELSe 
           case when d.upc like('%00000000052') then 'RR' else d.trans_subtype end end as trans_subtype, 
        d.trans_status, 
        d.department, 
        d.quantity, 
        d.scale,
        d.cost,
        d.unitPrice, 
        d.total, 
        d.regPrice,
        d.tax, 
        d.foodstamp, 
        d.discount,
        d.memDiscount,
        d.discountable,
        d.discounttype,
        d.voided,
        d.percentDiscount,
        d.itemQtty, 
        d.memType,
        d.volDiscType,
        d.volume,
        d.VolSpecial,
        d.mixMatch,
        d.matched,
        d.staff,
        d.numflag,
        d.charflag,
        d.card_no, 
        d.trans_id, ";
    if (isset($table_def['pos_row_id'])) {
        $dlogQ .= "d.pos_row_id,";
    }
    if (isset($table_def['store_row_id'])) {
        $dlogQ .= "d.store_row_id,";
    }
    $dlogQ .= "concat(convert(d.emp_no,char), '-', convert(d.register_no,char), '-',
        convert(d.trans_no,char)) as trans_num

        from transArchive$dstr as d
        where d.trans_status not in ('D','X','Z') and d.emp_no not in (9999,56) and d.register_no  <> 99";

    if ($dbms == "MSSQL"){
        $dlogQ = "CREATE  view dlog$dstr as
            select 
            d.datetime as tdate, 
            d.register_no, 
            d.emp_no, 
            d.trans_no, 
            d.upc, 
            d.description,
            CASE WHEN (d.trans_subtype IN ('CP','IC') OR d.upc like('%000000052')) then 'T' 
                WHEN d.upc = 'DISCOUNT' then 'S' else d.trans_type end as trans_type, 
            CASE WHEN d.upc = 'MAD Coupon' THEN 'MA' ELSe 
               case when d.upc like('%00000000052') then 'RR' else d.trans_subtype end end as trans_subtype, 
            d.trans_status, 
            d.department, 
            d.quantity, 
            c.scale,
            d.cost,
            d.unitPrice, 
            d.total, 
            d.regPrice,
            d.tax, 
            d.foodstamp, 
            d.discount,
            d.memDiscount,
            d.discountable,
            d.discounttype,
            d.voided,
            d.percentDiscount,
            d.itemQtty, 
            d.volDiscType,
            d.volume,
            d.VolSpecial,
            d.mixMatch,
            d.matched,
            d.memType,
            d.isStaff,
            d.numflag,
            d.charflag,
            d.card_no, 
            d.trans_id,";
            if (isset($table_def['pos_row_id'])) {
                $dlogQ .= "d.pos_row_id,";
            }
            if (isset($table_def['store_row_id'])) {
                $dlogQ .= "d.store_row_id,";
            }
            $dlogQ .= "(convert(varchar,d.emp_no) +  '-' + convert(varchar,d.register_no) + '-' + 
            convert(varchar,d.trans_no)) as trans_num

            from transArchive$dstr as d
            where d.trans_status not in ('D','X','Z') and d.emp_no not in (9999,56) and d.register_no  <> 99";
    }
    $chk = $db->query($dlogQ,$FANNIE_ARCHIVE_DB);
    if ($chk === false)
        echo cron_msg("Error creating dlog view for new archive table");
}

?>
