<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

   This script updates unpaid_ar_today.recent_payments
   based on activity today

   Should be run frequently during store open hours.

*/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!function_exists('cron_msg')) {
    include($FANNIE_ROOT.'src/cron_msg.php');
}

if (!chdir(dirname(__FILE__))){
    echo "Error: Can't find directory (lane push)";
    return;
}

if (!isset($FANNIE_LANES) || !is_array($FANNIE_LANES)) {
    $FANNIE_LANES = array();
}

set_time_limit(60);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

// get today's AR payments
$data = array();
$fetchQ = "SELECT card_no,recent_payments FROM unpaid_ar_today WHERE mark=1";
$fetchR = $sql->query($fetchQ);
if ($fetchR === False) {
    echo cron_msg("Failed: $fetchQ");
    return;
}
while($fetchW = $sql->fetch_row($fetchR))
    $data[$fetchW['card_no']] = $fetchW['recent_payments'];

$errors = False;
// connect to each lane and update payments
foreach($FANNIE_LANES as $lane){
    $db = new SQLManager($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);

    if ($db === False){
        echo cron_msg("Can't connect to lane: ".$lane['host']);
        $errors = True;
        continue;
    }

    foreach($data as $cn => $payment){
        $upQ = sprintf("UPDATE unpaid_ar_today SET recent_payments=%.2f WHERE card_no=%d",
                $payment,$cn);
        $rslt = $db->query($upQ);
        if ($rslt === False) {
            echo cron_msg("Update of member: $cn on lane: {$lane['host']} failed.");
            $errors = True;
        }
    }
}

if ($errors) {
    echo cron_msg("There was an error pushing unpaid AR info to the lanes.");
    flush();
}
else {
    //echo cron_msg("All OK.");
    $a=0;
}

