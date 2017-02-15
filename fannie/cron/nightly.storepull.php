<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

   nightly.storepull.php

   Uses curl to call Fannie's web-based sync routines

   I'm pulling data for the moment; push will make more
   sense (or replication) when WFC has two fully functional
   servers

*/

include(dirname(__FILE__) . '/../config.php');
if (!function_exists('cron_msg')) {
    include($FANNIE_ROOT.'src/cron_msg.php');
}

set_time_limit(0);

$url = "http://".php_uname('n').$FANNIE_URL."/sync/store/tablesync.php";

$products = curl_init($url."?tablename=products&othertable=");
curl_setopt($products, CURLOPT_RETURNTRANSFER, True);
$r1 = curl_exec($products);
curl_close($products);

$custdata = curl_init($url."?tablename=custdata&othertable=");
curl_setopt($custdata, CURLOPT_RETURNTRANSFER, True);
$r2 = curl_exec($custdata);
curl_close($custdata);

$custdata = curl_init($url."?tablename=&othertable=likeCodes");
curl_setopt($custdata, CURLOPT_RETURNTRANSFER, True);
$r2 = curl_exec($custdata);
curl_close($custdata);

