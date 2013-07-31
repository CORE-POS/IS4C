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

   lanesync.api.php

   Send the following tables to all lanes:
    products, custdata, memberCards, employees, departments, custReceiptMessage
   Optionally also send:
    productUser

  Replacement for nightly.lanesync.php using Fannie's API
  instead of cURL

*/

include('../config.php');
include($FANNIE_ROOT.'src/cron_msg.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

set_time_limit(0);

$result = SyncLanes::pull_table('valutecRequest', 'trans', SyncLanes::TRUNCATE_SOURCE);
echo cron_msg($result['messages']);
$result = SyncLanes::pull_table('valutecRequestMod', 'trans', SyncLanes::TRUNCATE_SOURCE);
echo cron_msg($result['messages']);
$result = SyncLanes::pull_table('valutecResponse', 'trans', SyncLanes::TRUNCATE_SOURCE);
echo cron_msg($result['messages']);

$result = SyncLanes::push_table('products', 'op', SyncLanes::TRUNCATE_DESTINATION);
echo cron_msg($result['messages']);
$result = SyncLanes::push_table('custdata', 'op', SyncLanes::TRUNCATE_DESTINATION);
echo cron_msg($result['messages']);
$result = SyncLanes::push_table('memberCards', 'op', SyncLanes::TRUNCATE_DESTINATION);
echo cron_msg($result['messages']);
$result = SyncLanes::push_table('custReceiptMessage', 'op', SyncLanes::TRUNCATE_DESTINATION);
echo cron_msg($result['messages']);
$result = SyncLanes::push_table('employees', 'op', SyncLanes::TRUNCATE_DESTINATION);
echo cron_msg($result['messages']);
$result = SyncLanes::push_table('departments', 'op', SyncLanes::TRUNCATE_DESTINATION);
echo cron_msg($result['messages']);

if ( isset($FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION == True ) {
	$result = SyncLanes::push_table('productUser', 'op', SyncLanes::TRUNCATE_DESTINATION);
	echo cron_msg($result['messages']);
}

?>
