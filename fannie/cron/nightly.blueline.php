<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/* HELP

   nightly.blueline.php

   Update CustomerNotifications table with equity paid data
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo." : $FANNIE_TRANS_DB.".";

$sql->query("DELETE FROM CustomerNotifications WHERE source = 'nightly.blueline.php'"); 

$upQ = "INSERT INTO CustomerNotifications (cardNo, source, type, message) 
	SELECT card_no, 'nightly.blueline.php', 'blueline', 
	CONCAT('EqPaid: $', payments)
	FROM core_trans.equity_history_sum";

$sql->query($upQ);

?>

