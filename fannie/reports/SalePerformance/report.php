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

require('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$table = "trans_archive.dbo.dlog".$_POST['year'].sprintf("%02d",$_POST['month']);

$bids = "(";
$args = array();
foreach ($_POST['ids'] as $id){
	$bids .= "?,";
	$args[] = $id;
}
$bids = substr($bids,0,strlen($bids)-1).")";

$q = $dbc->prepare_statement("SELECT min(tdate) as weekStart,max(tdate) as weekEnd,
	batchName,sum(total) as sales,sum(d.quantity) as qty 
	FROM $table AS d INNER JOIN batchList
	AS l ON d.upc=l.upc LEFT JOIN batches
	as b ON b.batchID=l.batchID
	WHERE l.batchID IN $bids
	AND d.tdate >= b.startDate
	AND d.tdate <= b.endDate
	GROUP BY datepart(wk,tdate),batchName
	ORDER BY batchName,min(tdate)");
$r = $dbc->exec_statement($q,$args);
$ttls = array();
$bttls = array();
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th colspan=2>Week</th><th>Batch</th><th>Sales (Qty)</th><th>Sales ($)</th></tr>";
while($w = $dbc->fetch_row($r)){
	$s = array_shift(explode(' ',$w[0]));
	$e = array_shift(explode(' ',$w[1]));
	printf("<tr><td>%s</td><td>%s</td><td>%s</td><td align=right>%.2f</td><td align=right>\$%.2f</td></tr>",
		$s,$e,
		$w[2],$w[4],$w[3]);
	if (!isset($ttls["$s to $e"])) $ttls["$s to $e"] = array(0.0,0.0);
	$ttls["$s to $e"][0] += $w[4];
	$ttls["$s to $e"][1] += $w[3];
	if (!isset($bttls[$w[2]])) $bttls[$w[2]] = array(0.0,0.0);
	$bttls[$w[2]][0] += $w[4];
	$bttls[$w[2]][1] += $w[3];
}
echo "</table>";

echo "<br />";

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Weekly totals<th>Sales (Qty)</th><th>Sales ($)</th></tr>";
foreach($ttls as $k=>$v){
	printf("<tr><td>%s</td><td align=right>%.2f</td><td align=right>\$%.2f</td></tr>",
		$k,$v[0],$v[1]);
}
echo "</table>";

echo "<br />";

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Batch totals<th>Sales (Qty)</th><th>Sales ($)</th></tr>";
foreach($bttls as $k=>$v){
	printf("<tr><td>%s</td><td align=right>%.2f</td><td align=right>\$%.2f</td></tr>",
		$k,$v[0],$v[1]);
}
echo "</table>";


?>
