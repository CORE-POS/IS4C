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

/* HELP
 
   chargelimit.fix.php

   Assign charge limit when equity
   is paid in full

*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$TRANS = $FANNIE_TRANS_DB.($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

$cutoff = date('Y-m-d 00:00:00',strtotime('yesterday'));
$cardsP = $sql->prepare_statement("select distinct card_no from {$TRANS}stockpurchases where tdate >= ?");
$cardsR = $sql->exec_statement($cardsP, array($cutoff));

$chkP = $sql->prepare_statement("SELECT c.memDiscountLimit FROM custdata AS c INNER JOIN
				{$TRANS}newBalanceStockToday_test AS s ON
				c.CardNo=s.memnum AND c.personNum=1
				WHERE c.Type='PC' AND s.payments >= 100
				AND c.memDiscountLimit=0 AND c.CardNo=?");
$upP = $sql->prepare_statement('UPDATE custdata SET memDiscountLimit=20 WHERE CardNo=?');
while($cardsW = $sql->fetch_row($cardsR)){
	$chkR = $sql->exec_statement($chkP, array($cardsW[0]));
	if ($sql->num_rows($chkR) == 0){
		continue;
	}
	else {
		$sql->exec_statement($upP, array($cardsW[0]));
	}
}

?>
