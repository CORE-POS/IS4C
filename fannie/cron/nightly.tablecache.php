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
    in the file license.txt along with IS4C; if not, write to the Free Software
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

$chk = $sql->query("TRUNCATE TABLE CashPerformDay_cache");
if ($chk === False)
	echo cron_msg("Could not truncate CashPerformDay_cache");
$chk = $sql->query("INSERT INTO CashPerformDay_cache SELECT * FROM CashPerformDay");
if ($chk === False)
	echo cron_msg("Could not load data for CashPerformDay_cache");

$chk = $sql->query("TRUNCATE TABLE batchMergeTable");
if ($chk === False)
	echo cron_msg("Could not truncate batchMergeTable");
$chk = $sql->query("INSERT INTO batchMergeTable SELECT * FROM batchMergeProd");
if ($chk === False)
	echo cron_msg("Could not load data from batchMergeProd");
$chk = $sql->query("INSERT INTO batchMergeTable SELECT * FROM batchMergeLC");
if ($chk === False)
	echo cron_msg("Could not load data from batchMergeLC");

echo cron_msg("Success");
?>
