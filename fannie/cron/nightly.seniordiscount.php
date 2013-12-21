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
 
   nightly.seniordiscount.php

	Update custdata.discount on senior discount days.
	Customize this script with your store's discount day.

	This script must be run after midnight.

   This script does not update the lanes, therefore
   it should be run before lane syncing.
*/

// ************************************
// ***         SETTINGS             ***
// ************************************
// Discount value is the percent * 100, e.g. 10 = 10%
$discount_value = 10;
// NOTE: Simply uncomment your desired senior discount day.
// $discount_day = "Sunday";
// $discount_day = "Monday";
// $discount_day = "Tuesday";
$discount_day = "Wednesday";
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

$toggle = ($today == $discount_day) ? "+" : "-";
	
if (($today == $discount_day) || ($today == $discount_day_after)) {
	$sql->query("UPDATE custdata SET discount = (discount $toggle $discount_value) WHERE SSI = 1");
} else {
	echo cron_msg("nightly.seniordiscount.php: Discount active on " . $discount_day . ".<br /> No discounts to apply");
}


?>
