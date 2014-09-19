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

   monthly.virtualcoupon.php

    Reset monthly virtual coupons:
    -- re-populate houseVirtualCoupons
    -- reset custdata: memCoupons and blueLine values

    NOTE:  Pretty much MUST be run on the first day of the month (and
    BEFORE nightly.virtualCoupons) to work as intended.  
    And don't forget to push to lanes overnight!
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo." : $FANNIE_TRANS_DB.".";

// clear out houseVirtualCoupons
$sql->query("TRUNCATE TABLE houseVirtualCoupons");

echo cron_msg("Truncate table houseVirtualCoupons<br />");

// re-populate houseVirtualCoupons
$insQ = "INSERT INTO houseVirtualCoupons (card_no,coupID,description,start_date,end_date) 
    SELECT c.CardNo, '00001', 'Owner Appreciation 10% Discount', DATE_FORMAT(NOW() ,'%Y-%m-01 00:00:00'), 
    CONCAT(LAST_DAY(NOW()),' 23:59:59') FROM custdata AS c WHERE c.memType <> 0";
$insR = $sql->query($insQ);
echo cron_msg("Re-populate houseVirtualCoupons");

// set custdata.memcoupons equal to the number
// of available coupons (in theory)
$sql->query("UPDATE custdata SET memCoupons = 0");  // set memCoupons = 0 first

$upQ = "UPDATE custdata AS c, houseVirtualCoupons AS h
    SET c.memCoupons=1
    WHERE c.CardNo=h.card_no 
    AND ".$sql->now()." >= h.start_date 
    AND ".$sql->now()."<= h.end_date
    AND c.memType <> 0";

$sql->query($upQ);

// update blueline to match memcoupons
$blueLineQ = "UPDATE custdata SET blueLine="
    .$sql->concat($sql->convert('CardNo','CHAR'),"' '",'LastName',"' Coup('",
        $sql->convert('memCoupons','CHAR'),"')'",'')
    . "WHERE memType <> 0";
$sql->query($blueLineQ);

echo cron_msg("Updated values in core_op.custdata");
?>
