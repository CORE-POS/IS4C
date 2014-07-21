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

   nightly.clipboard.php

   @deprecated
   Not replaced. Updated batchCutPaste schema includes
   a timestamp so old entries can be removed as needed 
   without a scheduled task. Clearing the deleted
   entries from the shelftags table really isn't necessary.

   This script truncates the table batchCutPaste. This table
   acts as a clipboard so users can cut/paste items from
   on sales batch to another. The table must be truncated
   periodically or old data will linger indefinitely.   

   It also clears stale, deleted shelftags from the
   shelftags table. Entries hang around with a negative
   id for recovery in the case of mistakes.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$chk = $sql->query("TRUNCATE TABLE batchCutPaste");
if ($chk === false)
    echo cron_msg("Error clearing batch clipboard");
else
    echo cron_msg("Cleared batch clipboard");

$chk2 = $sql->query("DELETE FROM shelftags WHERE id < 0");
if ($chk2 === false)
    echo cron_msg("Error clearing deleted sheltags");
else
    echo cron_msg("Cleared deleted shelftags");

?>
