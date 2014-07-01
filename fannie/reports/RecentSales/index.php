<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

    * 21Jan2013 Eric Lee table upcLike need database specified: core_op.upcLike

*/

require('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
require($FANNIE_ROOT."src/SQLManager.php");

$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
    $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$where = '';
$args = array();
if (isset($_GET['upc'])){
    $where = "WHERE upc=?";
    $args = array(BarcodeLib::padUPC($_GET['upc']));
}
else if (isset($_GET['likecode'])){
    $where = sprintf("LEFT JOIN %s%supcLike AS u ON d.upc=u.upc WHERE u.likecode=?",
        $FANNIE_OP_DB,$dbc->sep());
    $args = array($_GET['likecode']);
}
else
    exit;

echo "<table><th>&nbsp;<th align=right>Qty<th align=right>Sales<tr>";

$days = array();
$stamp = strtotime('yesterday');
for($i=1;$i<=3;$i++){
    $days[$i] = date("Y-m-d",$stamp);
    $stamp = mktime(0,0,0,date("n",$stamp),date("j",$stamp)-1,date("Y",$stamp));
}

$weeks[0] = array(date("Y-m-d",strtotime("monday this week")),
        date("Y-m-d",strtotime("sunday this week")));
$weeks[1] = array(date("Y-m-d",strtotime("monday last week")),
        date("Y-m-d",strtotime("sunday last week")));

$months[0] = array(date("Y-m-01"),date("Y-m-t"));
$stamp = mktime(0,0,0,date("n")-1,1,date("Y"));
$months[0] = array(date("Y-m-01",$stamp),date("Y-m-t",$stamp));

$q = "SELECT sum(case when ".$dbc->date_equals('tdate',$days[1])." AND trans_status<>'M' THEN quantity else 0 end) as qty1,
    sum(case when ".$dbc->date_equals('tdate',$days[1])." THEN total else 0 end) as total1,
    sum(case when ".$dbc->date_equals('tdate',$days[2])." AND trans_status<>'M' THEN quantity else 0 end) as qty2,
    sum(case when ".$dbc->date_equals('tdate',$days[2])." THEN total else 0 end) as total2,
    sum(case when ".$dbc->date_equals('tdate',$days[3])." AND trans_status<>'M' THEN quantity else 0 end) as qty3,
    sum(case when ".$dbc->date_equals('tdate',$days[3])." THEN total else 0 end) as total3,
    sum(case when (tdate BETWEEN '{$weeks[0][0]} 00:00:00' AND 
            '{$weeks[0][1]} 23:59:59') AND trans_status<>'M' THEN quantity else 0 end) as qtywk0,
    sum(case when (tdate BETWEEN '{$weeks[0][0]} 00:00:00' AND
            '{$weeks[0][1]} 23:59:59') THEN total else 0 end) as totalwk0,
    sum(case when (tdate BETWEEN '{$weeks[1][0]} 00:00:00' AND
            '{$weeks[1][1]} 23:59:59') AND trans_status<>'M' THEN quantity else 0 end) as qtywk1,
    sum(case when (tdate BETWEEN '{$weeks[1][0]} 00:00:00' AND
            '{$weeks[1][1]} 23:59:59') THEN total else 0 end) as totalwk1
    FROM dlog_15 as d $where";
$p = $dbc->prepare_statement($q);
$r = $dbc->exec_statement($p,$args);
$w = $dbc->fetch_row($r);

echo "<td><font color=blue>Yesterday</font></td>";
printf("<td style=\"padding-left: 20px;\" align=right>%.2f</td><td style=\"padding-left: 20px;\" align=right>$%.2f",
    $w['qty1'],$w['total1']);

echo "</td><tr><td><font color=blue>2 Days ago</font></td>";
printf("<td align=right>%.2f</td><td align=right>$%.2f",$w['qty2'],$w['total2']);

echo "</td><tr><td><font color=blue>3 Days ago</font></td>";
printf("<td align=right>%.2f</td><td align=right>$%.2f",$w['qty3'],$w['total3']);

echo "</td><tr><td><font color=blue>This Week</font></td>";
printf("<td align=right>%.2f</td><td align=right>$%.2f",$w['qtywk0'],$w['totalwk0']);

echo "</tr><tr><td><font color=blue>Last Week</font></td>";
printf("<td align=right>%.2f</td><td align=right>$%.2f",$w['qtywk1'],$w['totalwk1']);

$q = "SELECT sum(case when ".$dbc->monthdiff($dbc->now(),'tdate')."=0 AND trans_status<>'M' THEN quantity else 0 end) as qtym0,
    sum(case when ".$dbc->monthdiff($dbc->now(),'tdate')."=0 THEN total else 0 end) as totalm0,
    sum(case when ".$dbc->monthdiff($dbc->now(),'tdate')."=1 AND trans_status<>'M' THEN quantity else 0 end) as qtym1,
    sum(case when ".$dbc->monthdiff($dbc->now(),'tdate')."=1 THEN total else 0 end) as totalm1
    FROM dlog_90_view as d $where";
$p = $dbc->prepare_statement($q);
$r = $dbc->exec_statement($p,$args);
$w = $dbc->fetch_row($r);

echo "</td><tr><td><font color=blue>This Month</font></td>";
printf("<td align=right>%.2f</td><td align=right>$%.2f",$w['qtym0'],$w['totalm0']);

echo "</td><tr><td><font color=blue>Last Month</font></td>";
printf("<td align=right>%.2f</td><td align=right>$%.2f",$w['qtym1'],$w['totalm1']);

echo "</tr></table>";
if (isset($_REQUEST['upc'])) {
    printf('<br /><a href="../ItemLastQuarter/ItemLastQuarterReport.php?upc=%s">Weekly Sales Details</a>', $_REQUEST['upc']);
    printf('<br /><a href="../ItemOrderHistory/ItemOrderHistoryReport.php?upc=%s">Recent Order History</a>', $_REQUEST['upc']);
}
?>
