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
 
   memdates.fix.php

   Set start dates & mail flag
   For members that made their first
   equity purchase today

*/

/* why is this file such a mess?

   SQL for UPDATE against multiple tables is different 
   for MSSQL and MySQL. There's not a particularly clean
   way around it that I can think of, hence alternates
   for all queries.
*/

include('../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

set_time_limit(0);

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$TRANS = $FANNIE_TRANS_DB.($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

$miQ = "UPDATE meminfo AS m 
    INNER JOIN {$TRANS}equity_live_balance s
    ON m.card_no=s.memnum
    INNER JOIN custdata AS c ON c.CardNo=s.memnum
    LEFT JOIN memDates AS d ON d.card_no=s.memnum
    SET m.ads_OK=1
    WHERE (d.start_date IS null OR d.start_date = '0000-00-00 00:00:00')
    AND s.payments > 0
    AND c.Type='PC'";
if ($FANNIE_SERVER_DBMS == 'MSSQL'){
    $miQ = "UPDATE meminfo SET ads_OK=1
        FROM {$TRANS}equity_live_balance s
        left join meminfo m ON m.card_no=s.memnum
        left join custdata as c on c.cardno=s.memnum
        left join memDates as d on d.card_no=s.memnum
        where d.start_date is null and s.payments > 0
        and c.type='PC'";
}
$sql->query($miQ);

$mdQ = "UPDATE memDates AS d
    INNER JOIN {$TRANS}equity_live_balance AS s
    ON d.card_no=s.memnum
    INNER JOIN custdata AS c ON c.CardNo=s.memnum
    SET d.start_date=s.startdate,
    d.end_date=CASE WHEN s.payments >= 100 
        THEN '0000-00-00 00:00:00' 
        ELSE 
            CASE WHEN s.startdate < '2012-12-31 23:59:59'
            THEN DATE_ADD(s.startdate,INTERVAL 2 YEAR) 
            ELSE DATE_ADD(s.startdate,INTERVAL 1 YEAR) END
        END
    WHERE (d.start_date IS null OR d.start_date = '0000-00-00 00:00:00'
        OR (s.payments >= 100 AND d.end_date <> '0000-00-00 00:00:00')
    )
    AND s.payments > 0
    AND c.Type='PC'";
if ($FANNIE_SERVER_DBMS == 'MSSQL'){
    $mdQ = "UPDATE memDates SET start_date=s.startdate,
        end_date=CASE WHEN s.payments >=100 
            THEN '1900-01-01 00:00:00'
            ELSE dateadd(yy,1,s.startdate) END
        FROM {$TRANS}equity_live_balance s
        left join custdata as c on c.cardno=s.memnum
        left join memDates as d on d.card_no=s.memnum
        where d.start_date is null and s.payments > 0
        and c.type='PC'";
}
$sql->query($mdQ);

$sql->query("DELETE FROM custReceiptMessage WHERE msg_text LIKE 'EQUITY OWED% == %'");

$msgQ = "INSERT custReceiptMessage
    SELECT s.memnum,CONCAT('EQUITY OWED \$',100-s.payments,' == '
        ,'DUE DATE ',MONTH(d.end_date),'/',DAY(d.end_date),'/',YEAR(d.end_date)),
        'WfcEquityMessage'
    FROM {$TRANS}equity_live_balance AS s
    INNER JOIN memDates as d ON s.memnum=d.card_no
    WHERE s.payments < 100";
$msgR = $sql->query($msgQ);

