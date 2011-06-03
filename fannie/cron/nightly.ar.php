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

   nightly.equity.php

   Copies equity transaction information
   for the previous day from dlog_15 into
   stockpurchases.

   Should be run after dtransaction rotation
   and after midnight.

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
	FROM dlog_15 WHERE "
	.$sql->datediff($sql->now(),'tdate')." = 1
	AND (department IN $dlist OR trans_subtype='MI')";	
$sql->query($query);

?>
