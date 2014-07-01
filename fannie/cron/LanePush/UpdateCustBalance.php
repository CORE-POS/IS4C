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

   This script updates lane custdata balances based on
   activity today.

   Should be run frequently during store open hours.

   See also: ar.sanitycheck.php

*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

if (!chdir("LanePush")){
    echo cron_msg("Error: Can't find directory (lane push)");
    exit;
}

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

// get balances that changed today
$data = array();
$fetchQ = "SELECT CardNo, balance FROM memChargeBalance WHERE mark=1";
$fetchR = $sql->query($fetchQ);
if ($fetchR === False) {
    echo cron_msg("Failed: $fetchQ");
    exit;
}
while($fetchW = $sql->fetch_row($fetchR))
    $data[$fetchW['CardNo']] = $fetchW['balance'];

$errors = False;
// connect to each lane and update balances
foreach($FANNIE_LANES as $lane){
    $db = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);

    if ($db === False){
        echo cron_msg("Can't connect to lane: ".$lane['host']);
        $errors = True;
        continue;
    }

    foreach($data as $cn => $bal){
        $upQ = sprintf("UPDATE custdata SET Balance=%.2f WHERE CardNo=%d",
                $bal,$cn);
        $rslt = $db->query($upQ);
        if ($rslt === False) {
            echo cron_msg("Update of member: $cn on lane: {$lane['host']} failed.");
            $errors = True;
        }
    }
}

if ($errors) {
    echo cron_msg("There was an error pushing balances to the lanes.");
    flush();
}
else {
    //echo cron_msg("All OK.");
    $a=0;
}

?>
