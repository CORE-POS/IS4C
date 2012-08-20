<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

   nightly.lanesync.php

   Send the following tables to all lanes:
	products, custdata, employees, departments

   Uses curl to call Fannie's web-based sync routines

*/

include('../config.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$url = "http://".php_uname('n').$FANNIE_URL."/sync/tablesync.php";

$products = curl_init($url."?tablename=products&othertable=");
curl_setopt($products, CURLOPT_RETURNTRANSFER, True);
$r1 = curl_exec($products);
curl_close($products);

$custdata = curl_init($url."?tablename=custdata&othertable=");
curl_setopt($custdata, CURLOPT_RETURNTRANSFER, True);
$r2 = curl_exec($custdata);
curl_close($custdata);

$memcards = curl_init($url."?tablename=&othertable=memberCards");
curl_setopt($memcards, CURLOPT_RETURNTRANSFER, True);
$r2 = curl_exec($memcards);
curl_close($memcards);

$employees = curl_init($url."?tablename=employees&othertable=");
curl_setopt($employees, CURLOPT_RETURNTRANSFER, True);
$r3 = curl_exec($employees);
curl_close($employees);

$departments = curl_init($url."?tablename=departments&othertable=");
curl_setopt($departments, CURLOPT_RETURNTRANSFER, True);
$r4 = curl_exec($departments);
curl_close($departments);

?>
