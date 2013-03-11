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

   nightly.hq.dtrans.php

   This script ships transaction data
   from local store dtransactions to the master
   store. Info is written directly to the master
   store's archive tables, not master.dtransactions.

   This job has to occur before local archiving
   via nightly.dtrans.php (which truncates
   dtransactions)
*/

include('../config.php');
include('../src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

// no other stores known
if ($FANNIE_NUM_STORES == 0) exit;

// no need to run this on master
if ($FANNIE_MASTER_STORE == 'me') exit;

// config problem; no data for master DB
if (!isset($FANNIE_STORES[$FANNIE_MASTER_STORE])) exit;

// connect to master and local DBs
$minfo = $FANNIE_STORES[$FANNIE_MASTER_STORE];
$sql = new SQLManager($minfo['host'],$minfo['type'],$FANNIE_MASTER_ARCH_DB,
		$minfo['user'],$minfo['pw']);
$sql->add_connection($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

// determine what month table dtransactions data goes in
$res = $sql->query("SELECT month(datetime),year(datetime) FROM dtransactions",$FANNIE_TRANS_DB);
$row = $sql->fetch_row($res);
$dstr = $row[1].(str_pad($row[0],2,'0',STR_PAD_LEFT));
$monthTable = "transArchive".$dstr;

if (!$sql->table_exists($monthTable,$FANNIE_MASTER_ARCH_DB)){
	if (strstr($minfo['type'], "MYSQL")){
		$createQ = "CREATE TABLE $monthTable LIKE ".$minfo['trans'].".dtransactions";
		$sql->query($createQ,$FANNIE_MASTER_ARCH_DB);
	}
	else {
		$createQ = "SELECT * INTO $monthTable FROM ".$minfo['trans'].".dbo.dtransactions WHERE 1=0";
		$sql->query($createQ,$FANNIE_MASTER_ARCH_DB);
	}
}

// create a temporary table on master so data only
// ships across the network once
$tempTable = "transTemp".str_replace(".","_",$FANNIE_SERVER);
if (strstr($minfo['type'], "MYSQL")){
	$tempQ = "CREATE TABLE $tempTable LIKE $monthTable";
	$sql->query($tempQ,$FANNIE_MASTER_ARCH_DB);
}
else {
	$tempQ = "SELECT * INTO $tempTable FROM $monthTable WHERE 1=0";
	$sql->query($tempQ,$FANNIE_MASTER_ARCH_DB);
}

/* actual transfer */
$sql->transfer($FANNIE_TRANS_DB,"SELECT * FROM dtransactions",
		$FANNIE_MASTER_ARCH_DB,"INSERT INTO $tempTable");

// copy data from temp table to transarchive and month snapshot
$insQ1 = "INSERT INTO $monthTable SELECT * FROM $tempTable";
$insQ2 = "INSERT INTO ".$minfo['trans'].".dbo.transarchive SELECT * FROM $tempTable";
if (strstr($minfo['type'], "MYSQL")){
	$insQ2 = str_replace("dbo.","",$insQ2);
}
$sql->query($insQ1,$FANNIE_MASTER_ARCH_DB);
$sql->query($insQ2,$FANNIE_MASTER_ARCH_DB);

// get rid of the temp table
$cleanQ = "DROP TABLE $tempTable";
$sql->query($cleanQ,$FANNIE_MASTER_ARCH_DB);

?>
