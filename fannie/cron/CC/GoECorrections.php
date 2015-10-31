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

if (!chdir(dirname(__FILE__))){
    echo "Error: Can't find directory (CC)";
    return;
}

include('../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('xmlData')) {
    include($FANNIE_ROOT.'src/xmlData.php');
}
if (file_exists($FANNIE_ROOT.'src/Credentials/GoE.wfc.php')) {
    require_once($FANNIE_ROOT.'src/Credentials/GoE.wfc.php');
} else {
    // cannot continue
    echo 'missing credentials file';
    return;
}
if (!function_exists('getFailedTrans')) {
    include($FANNIE_ROOT.'src/fetchLib.php');
}

/* HELP

    Void GoE transactions from the previous
    hour that had communication errors

*/

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$stack = getFailedTrans(date("Y-m-d"),date("G")-1);
if (count($stack) != 0) {
    echo 'Voids may be needed; email should go out!';
}

$void_ids = array();
foreach($stack as $refNum){
    $vref = doquery(date("mdy"),$refNum);
    if ($vref != False)
        $void_ids[] = $vref;
}

if (count($void_ids) > 0){
    dovoid($void_ids);
}

