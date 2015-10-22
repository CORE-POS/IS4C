<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

   nightly.lanesync.api.php

   Retrieve to the server from the following tables on all lanes:
    valutecRequest, valutecRequestMod, valutecResponse
   Replace the following tables on all lanes with contents of server table:
    products, custdata, memberCards, employees, departments, custReceiptMessage
   Optionally also replace:
    productUser

   If you can use fannie/sync/special/generic.mysql.php
    the transfers will go much faster.

   Coordinate this with cronjobs such as nightly.batch.php
    that update the tables this is pushing to the lanes
    so that the lanes have the most current data.

  Replacement for nightly.lanesync.php using Fannie's API
  instead of cURL

*/

// set class alias so lines don't get too long
use \COREPOS\Fannie\API\data\SyncLanes as SyncLanes;

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!function_exists('cron_msg')) {
    include($FANNIE_ROOT.'src/cron_msg.php');
}

set_time_limit(0);

foreach (array('valutecRequest', 'valutecRequestMod', 'valutecResponse') as $table) {
    $result = SyncLanes::pull_table("$table", 'trans', SyncLanes::TRUNCATE_SOURCE);
    echo cron_msg($result['messages']);
}

$regularPushTables = array(
    'products',
    'custdata',
    'memberCards',
    'custReceiptMessage',
    'CustomerNotifications',
    'employees',
    'departments',
    'houseCoupons',
    'houseCouponItems',
    'houseVirtualCoupons'
);
foreach ($regularPushTables as $table) {
    $result = SyncLanes::push_table("$table", 'op', SyncLanes::TRUNCATE_DESTINATION);
    echo cron_msg($result['messages']);
}

if ( isset($FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION) && $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION == True ) {
    $result = SyncLanes::push_table('productUser', 'op', SyncLanes::TRUNCATE_DESTINATION);
    echo cron_msg($result['messages']);
}

if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' ) {
    $result = SyncLanes::push_table('tenders', 'op', SyncLanes::TRUNCATE_DESTINATION);
    echo cron_msg($result['messages']);
    $result = SyncLanes::push_table('memtype', 'op', SyncLanes::TRUNCATE_DESTINATION);
    echo cron_msg($result['messages']);
}


echo cron_msg(basename(__FILE__) ." done.");

