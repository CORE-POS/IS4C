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

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'auth/login.php');
include($FANNIE_ROOT.'src/select_dlog.php');

$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];
$buyer = $_REQUEST['buyer'];


$dDiffStart = $startDate.' 00:00:00';
$dDiffEnd = $endDate.' 23:59:59';

echo "Hourly Sales Report<br>";
echo "From $startDate to $endDate";

$dlog = select_dlog($startDate,$endDate);

$dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

$hourlySalesQ = '';
$args = array();
if(!isset($_REQUEST['weekday'])){
   if($buyer != -1){
     $hourlySalesQ = "SELECT year(d.tdate),month(d.tdate),day(d.tdate),".$dbc->hour('d.tdate').",
		 sum(d.total),avg(d.total)
                 FROM $dlog as d left join 
		 {$FANNIE_OP_DB}{$dbconn}superdepts as t on d.department = t.dept_ID
                 WHERE (d.trans_type = 'I' or d.trans_type = 'D') AND
                 d.tdate BETWEEN ? AND ?
                 AND t.superID = ?
                 GROUP BY year(d.tdate),month(d.tdate),day(d.tdate), ".$dbc->hour('d.tdate')."
                 ORDER BY year(d.tdate),month(d.tdate),day(d.tdate), ".$dbc->hour('d.tdate');
     $args = array($dDiffStart,$dDiffEnd,$buyer);
   }else{
    $hourlySalesQ = "SELECT year(d.tdate),month(d.tdate),day(tdate),".$dbc->hour('tdate').",
		 sum(total),avg(total)
                 FROM $dlog as d
                 WHERE (trans_type = 'I' or trans_type = 'D') AND
                 tdate BETWEEN ? AND ?
                 GROUP BY year(d.tdate),month(d.tdate),day(d.tdate), ".$dbc->hour('d.tdate')."
                 ORDER BY year(d.tdate),month(d.tdate),day(d.tdate), ".$dbc->hour('d.tdate');
     $args = array($dDiffStart,$dDiffEnd);
    }
}else{
echo "<br>Grouped by weekday";
   if($buyer != -1){
      $hourlySalesQ = "SELECT 
		 ".$dbc->dayofweek('tdate').",
		 ".$dbc->hour('d.tdate').",
		 sum(d.total),avg(total)
                 FROM $dlog as d LEFT JOIN 
		 {$FANNIE_OP_DB}{$dbconn}superdepts as t on d.department = t.dept_ID
                 WHERE (d.trans_type = 'I' or d.trans_type = 'D') AND
                 d.tdate BETWEEN ? AND ?
 		 AND t.superID = ?
                 GROUP BY ".$dbc->dayofweek('tdate').",".$dbc->hour('tdate')."
                 ORDER BY ".$dbc->dayofweek('tdate').",".$dbc->hour('tdate');
     $args = array($dDiffStart,$dDiffEnd,$buyer);
   }else{
      $hourlySalesQ = "SELECT 
		 ".$dbc->dayofweek('tdate').",
		 ".$dbc->hour('tdate').",
		 sum(total),avg(total)
                 FROM $dlog as d
                 WHERE (trans_type = 'I' or trans_type = 'D') AND
                 tdate BETWEEN ? AND ?
                 GROUP BY ".$dbc->dayofweek('tdate').",".$dbc->hour('tdate')."
                 ORDER BY ".$dbc->dayofweek('tdate').",".$dbc->hour('tdate');
     $args = array($dDiffStart,$dDiffEnd);
    }
}

//echo $hourlySalesQ;
if (isset($_REQUEST['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="hourlySales.xls"');
}
else {
	if(isset($_REQUEST['weekday'])){
	   echo "<br><a href=hourlySalesAuth.php?endDate=$endDate&startDate=$startDate&buyer=$buyer&weekday=$weekday&excel=yes>Click here to dump to Excel File</a>";
	}else{
	   echo "<br><a href=hourlySalesAuth.php?endDate=$endDate&startDate=$startDate&buyer=$buyer&excel=yes>Click here to dump to Excel File</a>";
	}
}
$sum = 0;
$prep = $dbc->prepare_statement($hourlySalesQ);
$result = $dbc->exec_statement($prep,$args);
echo "<table cellspacing=0 cellpadding=4 border=1>";
$minhour = 24;
$maxhour = 0;
$acc = array();
$sums = array();
if (!isset($_REQUEST['weekday'])){
	while($row=$dbc->fetch_row($result)){
		$hour = (int)$row[3];
		$date = $row[1]."/".$row[2]."/".$row[0];
		if (!isset($acc[$date])) $acc[$date] = array();
		if ($hour < $minhour) $minhour = $hour;
		if ($hour > $maxhour) $maxhour = $hour;
		$acc[$date][$hour] = $row[4];
		if (!isset($sums[$hour])) $sums[$hour] = 0;
		$sums[$hour] += $row[4];
	}
}
else {
	$days = array('','Sun','Mon','Tue','Wed','Thu','Fri','Sat');
	while($row = $dbc->fetch_row($result)){
		$hour = (int)$row[1];
		$date = $days[$row[0]];
		if (!isset($acc[$date])) $acc[$date] = array();
		if (!isset($sums[$hour])) $sums[$date] = 0;
		if ($hour < $minhour) $minhour = $hour;
		if ($hour > $maxhour) $maxhour = $hour;
		$acc[$date][$hour] = $row[2];
		$sums[$hour] += $row[2];
	}
}
echo "<tr><th>".(isset($_REQUEST['weekday'])?'Day':'Date')."</th>";
foreach($acc as $date=>$data){
	echo "<th>";
	echo $date;
	echo "</th>";
}
echo "<td>Totals</td></tr>";

for($i=$minhour;$i<=$maxhour;$i++){
	echo "<tr>";
	echo "<td>";
	if ($i < 12) echo $i."AM";
	elseif($i==12) echo $i."PM";
	else echo ($i-12)."PM";
	echo "</td>";
	foreach($acc as $date=>$data){
		if (isset($data[$i])){
			printf("<td>%.2f</td>",$data[$i]);
			if (!isset($sums[$i])) $sums[$i] = 0;
			$sums[$date] += $data[$i];
		}
		else
			echo "<td>&nbsp;</td>";
	}
	printf("<td>%.2f</td>",$sums[$i]);
	echo "</tr>";
}
$sum=0;
echo "<tr><td>Totals</td>";
foreach($acc as $date=>$data){
	printf("<td>%.2f</td>",$sums[$date]);
	$sum += $sums[$date];
}
echo "<td>&nbsp;</td></tr>";

echo "</table>";

echo "<p />Total: $sum"
?>
