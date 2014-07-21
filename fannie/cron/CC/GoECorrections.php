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

if (!chdir("CC")){
    echo "Error: Can't find directory (CC)";
    exit;
}

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/xmlData.php');
include($FANNIE_ROOT.'src/fetchLib.php');

/* HELP

    Void GoE transactions from the previous
    hour that had communication errors

*/

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$stack = getFailedTrans(date("Y-m-d"),date("G")-1);

$void_ids = array();
foreach($stack as $refNum){
    $vref = doquery(date("mdy"),$refNum);
    if ($vref != False)
        $void_ids[] = $vref;
}

if (count($void_ids) > 0){
    dovoid($void_ids);
}


?>
