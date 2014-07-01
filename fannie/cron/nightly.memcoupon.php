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

   nightly.memcoupon.php

   Update memcoupon table for WFC virtual coupon
   Adjust custdata settings to match

*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo." : $FANNIE_TRANS_DB.".";

$sql->query("TRUNCATE TABLE memcoupon");

$insQ = "INSERT INTO memcoupon
    select card_no, tdate, total,trans_num 
    from {$TRANS}dlog_90_view
    where (trans_subtype = 'MA'  or upc like '%MAD Coupon%') 
    and ceiling(month(tdate)/3.0) = ceiling(month(".$sql->now().")/3.0)
    and year(tdate) = year(".$sql->now().")
    and total > -2.52 and total < 2.52
    order by card_no";
$insR = $sql->query($insQ);

$resetQ = "update custdata set memCoupons=1 where Type='PC'";
$resetR = $sql->query($resetQ);

$bl = "CONCAT( CONVERT(CardNo,char), ' ', LastName )";
if ($FANNIE_SERVER_DBMS == "MSSQL")
    $bl = "RTRIM(CardNo) + ' ' + RTRIM(LastName)";
$resetQ = "update custdata set memCoupons=0,blueLine=$bl where Type<>'PC'";
$resetR = $sql->query($resetQ);

$usedQ = "SELECT cardno FROM memcoupon GROUP BY cardno HAVING SUM(total) <> 0";
$usedR = $sql->query($usedQ);
while($usedW = $sql->fetch_row($usedR)){
    $upR = $sql->query("UPDATE custdata SET memCoupons=0 WHERE CardNo=".$usedW['cardno']);
}

$bl = "CONCAT( CONVERT(CardNo,char), ' ', LastName, ' Coup(', CONVERT(memCoupons,char), ')' )";
if ($FANNIE_SERVER_DBMS == "MSSQL")
    $bl = "RTRIM(CardNo) + ' ' + RTRIM(LastName) + ' Coup(' + CONVERT(varchar,memCoupons) + ')'";
$blQ = "update custdata
    SET blueLine = $bl
    WHERE Type = 'PC'";
$blR = $sql->query($blQ);

?>
