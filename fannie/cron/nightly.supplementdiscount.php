<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
 
   nightly.supplementdiscount.php

	Create or remove AutoCoupon.
	Customize this script with your store's discount day.

	This script must be run after midnight.

   This script does not update the lanes, therefore
   it should be run before lane syncing.
*/

// ************************************
// ***         SETTINGS             ***
// ************************************
// NOTE: Simply uncomment your desired supplement discount day.
// $discount_day = "Sunday";
$discount_day = "Monday";
// $discount_day = "Tuesday";
// $discount_day = "Wednesday";
// $discount_day = "Thursday";
// $discount_day = "Friday";
// $discount_day = "Saturday";
// ************************************


include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');
set_time_limit(0);

$today = date('l');

$dday = date_create($discount_day);
date_add($dday, date_interval_create_from_date_string('1 days'));
$discount_day_after = date_format($dday, 'l');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
	
if ($today == $discount_day) {
	$sql->query("INSERT INTO autoCoupons VALUES(999,'Supplement Discount')");
	echo cron_msg("It's $discount_day. Supplement discount applied.")
} elseif ($today == $discount_day_after) {
	$sql->query("DELETE FROM autoCoupons WHERE coupID = 999");
	echo cron_msg("It's $discount_day_after.  Supplement discount removed.")
} else {
	echo cron_msg("No discounts to apply.");
}


?>
