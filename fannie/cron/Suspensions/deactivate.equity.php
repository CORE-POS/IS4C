<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (!chdir("Suspensions")){
    echo "Error: Can't find directory (suspensions)";
    exit;
}

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');

/* HELP

   This script activates members with equity paid in full
*/

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = $FANNIE_TRANS_DB . ($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

$custdata = $sql->table_definition('custdata');

$dStr = date("Y-m-01 00:00:00");

$susQ = "INSERT INTO suspensions
    select m.card_no,'I',c.memType,c.Type,'',
    ".$sql->now().",m.ads_OK,c.Discount,
    c.ChargeLimit,4
    from meminfo as m left join
    custdata as c on c.CardNo=m.card_no and c.personNum=1
    left join {$TRANS}equity_live_balance as n on m.card_no=n.memnum
    left join memDates AS d ON m.card_no=d.card_no
    WHERE 
    ( 
        (DATE_ADD(d.start_date, INTERVAL 2 YEAR) < '$dStr'
         AND YEAR(d.start_date) < 2013) 
        OR
        (DATE_ADD(d.start_date, INTERVAL 1 YEAR) < '$dStr'
         AND YEAR(d.start_date) >= 2013) 
    )
    and c.Type='PC' and n.payments < 100
    and c.memType in (1,3)
    and NOT EXISTS(SELECT NULL FROM suspensions as s
    WHERE s.cardno=m.card_no)";
if (!isset($custdata['ChargeLimit'])) {
    $susQ = str_replace('c.ChargeLimit', 'c.MemDiscountLimit', $susQ);
}
$sql->query($susQ);

$histQ = "INSERT INTO suspension_history
        select 'automatic',".$sql->now().",'',
        m.card_no,4
        from meminfo as m left join
        custdata as c on c.CardNo=m.card_no and c.personNum=1
        left join {$TRANS}equity_live_balance as n on m.card_no=n.memnum
        left join memDates AS d ON m.card_no=d.card_no
        WHERE
        ( 
            (DATE_ADD(d.start_date, INTERVAL 2 YEAR) < '$dStr'
             AND YEAR(d.start_date) < 2013) 
            OR
            (DATE_ADD(d.start_date, INTERVAL 1 YEAR) < '$dStr'
             AND YEAR(d.start_date) >= 2013) 
        )
        and c.Type='PC' and n.payments < 100
        and c.memType in (1,3)
        and NOT EXISTS(SELECT NULL FROM suspensions as s
        WHERE s.cardno=m.card_no)";
$sql->query($histQ);

$custQ = "UPDATE custdata as c LEFT JOIN
        suspensions as s on c.CardNo=s.cardno
        SET c.type='INACT',memType=0,c.Discount=0,
        c.ChargeLimit=0,MemDiscountLimit=0
        where c.type='PC' and s.cardno is not null";
if (!isset($custdata['ChargeLimit'])) {
    $custQ = str_replace('c.ChargeLimit=0,', '', $custQ);
}
$sql->query($custQ);

$memQ = "UPDATE meminfo as m LEFT JOIN
    suspensions as s ON m.card_no=s.cardno
    SET ads_OK=0
    where s.cardno is not null";
$sql->query($memQ);

?>
