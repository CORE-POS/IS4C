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

   This script activates members with store-charge account (ar)
   up-to-date, i.e.
   ar_live_balance.balance < AR_EOM_Summary.twoMonthBalance

   When/how-often can/should it be run? Daily?

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * 18Oct12 EL Keep this comment block from appearing in the Help popup.
 *             Reformat first SQL statement.
 * 17Jun12 EL Fix Help to make it appropriate to this program.
 *             Was a copy of reactivate.equity.php.
*/

set_time_limit(0);
ini_set('memory_limit','256M');

$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$TRANS = $FANNIE_TRANS_DB . ($FANNIE_SERVER_DBMS=="MSSQL" ? 'dbo.' : '.');

$custdata = $sql->table_definition('custdata');

$meminfoQ = "UPDATE meminfo AS m LEFT JOIN
        custdata AS c ON m.card_no=c.CardNo
        LEFT JOIN {$TRANS}ar_live_balance AS s
        ON c.cardno=s.card_no LEFT JOIN suspensions AS p
        ON c.cardno=p.cardno LEFT JOIN {$TRANS}AR_EOM_Summary AS a
        ON m.card_no=a.cardno
        SET m.ads_OK=p.mailflag
        WHERE c.Type = 'INACT' and p.reasoncode IN (1)
        AND s.balance < a.twoMonthBalance";
$sql->query($meminfoQ);

$custQ = "UPDATE custdata AS c LEFT JOIN {$TRANS}ar_live_balance AS s
        ON c.CardNo=s.card_no LEFT JOIN suspensions AS p
        ON c.CardNo=p.cardno LEFT JOIN {$TRANS}AR_EOM_Summary AS a
        ON c.CardNo=a.cardno
        SET c.Discount=p.discount,c.MemDiscountLimit=p.chargelimit,
        c.ChargeLimit=p.chargelimit,
        c.memType=p.memtype1,c.Type=p.memtype2,chargeOk=1
        WHERE c.Type = 'INACT' and p.reasoncode IN (1)
        AND s.balance < a.twoMonthBalance";
if (!isset($custdata['ChargeLimit'])) {
    $custQ = str_replace('c.ChargeLimit=p.chargelimit,', '', $custQ);
}
$sql->query($custQ);

$histQ = "insert into suspension_history
        select 'AR paid',".$sql->now().",
        'Account reactivated',c.CardNo,0 from
        suspensions as s left join
        custdata as c on s.cardno=c.CardNo
        and c.personNum=1
        where c.Type not in ('INACT','INACT2') and s.type='I'";
$sql->query($histQ);

$clearQ = "select c.CardNo from
        suspensions as s left join
        custdata as c on s.cardno=c.CardNo
        where c.Type not in ('INACT','INACT2') and s.type='I'
        AND c.personNum=1";
$clearR = $sql->query($clearQ);
$cns = "(";
while($clearW = $sql->fetch_row($clearR)){
    $cns .= $clearW[0].",";
}
$cns = rtrim($cns,",").")";

if (strlen($cns) > 2){
    $delQ = "DELETE FROM suspensions WHERE cardno IN $cns";
    $delR = $sql->query($delQ);
}

?>
