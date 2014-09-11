<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

   monthly.nabs.php

   Make AR payments on nabs accounts
   to clear end-of-month balance

   probably not relevant for anyone else

*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
    $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$insQ = "INSERT INTO dtransactions SELECT * FROM nabsAdjustView";
$insR = $sql->query($insQ);

// fix trans_no values
$tn = 1;
$fixQ = "SELECT card_no FROM dtransactions WHERE register_no=20
    AND emp_no=1001";
$fixR = $sql->query($fixQ);
while($fixW = $sql->fetch_row($fixR)){
    $transQ = "UPDATE dtransactions SET trans_no=$tn
        WHERE register_no=20 and emp_no=1001
        AND card_no=".$fixW['card_no'];
    $transR = $sql->query($transQ);
    $tn++;
}


?>
