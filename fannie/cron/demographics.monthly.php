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
include($FANNIE_ROOT.'src/select_dlog.php');

$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$end = date("Y-m-d",mktime(0,0,0,date("n")-1,28,date("Y")));
$start = date("Y-m-d",mktime(0,0,0,date("n"),1,date("Y")-1));
$dlog = select_dlog($start,$end);

$query = "INSERT INTO YTD_Patronage_Speedup 
	select d.card_no,datepart(mm,d.tdate) as month_no,
	sum(CASE WHEN d.trans_type='T' THEN d.total ELSE 0 END) as total
	from ".$dlog." as d
	LEFT JOIN custdata as c on c.cardno=d.card_no and c.personnum=1 
	LEFT JOIN suspensions as s on s.cardno = d.card_no 
	WHERE c.memtype=1 or s.memtype1=1 
	GROUP BY d.card_no,
	datepart(yy,d.tdate), datepart(mm,d.tdate),datepart(dd,d.tdate),d.trans_num";

$dbc->query("TRUNCATE TABLE YTD_Patronage_Speedup");
$dbc->query($query);

?>
