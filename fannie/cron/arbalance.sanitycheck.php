<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

   arbalance.sanitycheck.php

   Sync Fannie custdata balance with live table.
   Usually once a day is enough.

   Do before syncing lane custdata with Fannie's.
   See also: LanePush/UpdateCustBalance.php
   
   Run either after nightly.dtrans and nightly.ar, not between them,
    and before [nightly.]lanesync.api or nightly.lanesync
   Deprecated in favour of cron/tasks/ArHistoryTask.php

*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$query = "UPDATE {$FANNIE_OP_DB}.custdata AS c
    LEFT JOIN ar_live_balance AS n ON c.CardNo=n.card_no
    SET c.Balance = n.balance";

if ($FANNIE_SERVER_DBMS == "MSSQL"){
    $query = "UPDATE {$FANNIE_OP_DB}.dbo.custdata SET Balance = n.balance
        FROM {$FANNIE_OP_DB}.dbo.custdata AS c LEFT JOIN
        ar_live_balance AS n ON c.CardNo=n.card_no";
}

$rslt = $sql->query($query);

if ($rslt === False)
    echo cron_msg("Failed.");
else
    echo cron_msg("OK.");

?>
