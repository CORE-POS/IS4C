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

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$dbc->query("TRUNCATE TABLE YTD_Patronage_MiddleStep");
$ts = mktime(0,0,0,date("n"),1,date("Y")-1);
for($i=0;$i<12;$i++){
    $start = date("Y-m-d",$ts);
    $end = date("Y-m-t",$ts);
    $dlog = DTransactionsModel::selectDtrans($start,$end);
    $ts = mktime(0,0,0,date("n",$ts)+1,1,date("Y",$ts));
    $query = "INSERT INTO YTD_Patronage_MiddleStep 
        select d.card_no,MONTH(d.datetime) as month_no,
        total,
        YEAR(d.datetime) AS year_no,
        DAY(d.datetime) AS day_no,
        ".$dbc->concat(
            $dbc->convert('emp_no','char'),"'-'",
            $dbc->convert('register_no','char'),"'-'",
            $dbc->convert('trans_no','char'),'')
        ." as trans_num
        from ".$dlog." as d
        WHERE datetime BETWEEN '$start 00:00:00' AND '$end 23:59:59'
        AND d.trans_type = 'T' AND total <> 0
        AND emp_no <> 9999 and register_no <> 99 AND trans_status NOT IN ('Z','X')";
    $dbc->query($query);
}

$dbc->query("TRUNCATE TABLE YTD_Patronage_Speedup");
$query = "INSERT INTO YTD_Patronage_Speedup
    SELECT card_no,month_no,SUM(total) as total,year_no
    FROM YTD_Patronage_MiddleStep AS d
    LEFT JOIN custdata as c on c.CardNo=d.card_no and c.personNum=1 
    LEFT JOIN suspensions as s on s.cardno = d.card_no 
    WHERE c.memType=1 or s.memtype1=1 
    GROUP BY d.card_no,
    year_no, month_no, day_no, trans_num";
$dbc->query($query);

?>
