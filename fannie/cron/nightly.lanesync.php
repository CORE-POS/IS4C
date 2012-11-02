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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 22Sep2012 Eric Lee Annotate, esp re cURL.
	*           http://curl.haxx.se/
	*           http://ca.php.net/manual/en/function.curl-setopt.php
	*           See www-data's crontab for where error reports go, e.g.
	*            30 1 * * * cd /var/www/IS4C/fannie/cron && php ./nightly.lanesync.php >> /var/www/IS4C/fannie/logs/dayend.log

*/

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

// curl_init():
//  Initializes a new session and return a cURL handle for use with the curl_setopt(), curl_exec(), and curl_close() functions.
$products = curl_init($url."?tablename=products&othertable=");
/* CURLOPT_RETURNTRANSFER:
	* TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
*/
curl_setopt($products, CURLOPT_RETURNTRANSFER, True);
// r1 is apparently never used.
$r1 = curl_exec($products);
echo "Result of tablesync of products: >{$r1}<";
curl_close($products);

// Other tables are done the same way, except as noted.

$custdata = curl_init($url."?tablename=custdata&othertable=");
curl_setopt($custdata, CURLOPT_RETURNTRANSFER, True);
$r2 = curl_exec($custdata);
curl_close($custdata);

// Note use of othertable.
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
