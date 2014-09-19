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

   @deprecated. ReportDataCache task handles
   all cached data.

   manage.cache.php

   Delete daily & monthly cache files as needed
   Always clears daily. Clears monthly where
   current day == 1

*/

include('../config.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$path = 'cache/cachefiles/daily/';
$dh = opendir($FANNIE_ROOT.$path);
if (!$dh) {
    echo cron_msg("$FANNIE_ROOT.$path does not exist.");
    exit;
}
while ( ($file = readdir($dh)) !== False){
    if (is_file($FANNIE_ROOT.$path.$file))
        unlink($FANNIE_ROOT.$path.$file);
}
closedir($dh);
echo cron_msg("Cleared daily cache");

if (date('j') == 1){
    $path = 'cache/cachefiles/monthly/';
    $dh = opendir($FANNIE_ROOT.$path);
    if (!$dh) {
        echo cron_msg("$FANNIE_ROOT.$path does not exist.");
        exit;
    }
    while ( ($file = readdir($dh)) !== False){
        if (is_file($FANNIE_ROOT.$path.$file))
            unlink($FANNIE_ROOT.$path.$file);
    }
    closedir($dh);
    echo cron_msg("Cleared monthly cache");
}

?>
