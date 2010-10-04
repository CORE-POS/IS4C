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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../../config.php');
require($FANNIE_ROOT."src/SQLManager.php");

$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
	$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

$where = '';
if (isset($_GET['upc'])){
	$where = sprintf("WHERE upc='%s'",str_pad($_GET['upc'],13,'0',STR_PAD_LEFT));
}
else if (isset($_GET['likecode'])){
	$where = "LEFT JOIN upclike AS u ON d.upc=u.upc WHERE u.likecode=".$_GET['likecode'];
}
else
	exit;

echo "<table><th>&nbsp;<th>Qty<th>Sales<tr>";

echo "<td><font color=blue>Yesterday</font></td>";
$query = "SELECT sum(quantity),sum(total) FROM dlog_15 as d $where
	AND ".$dbc->datediff($dbc->now(),"tdate")." = 1";
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

echo "</td><tr><td><font color=blue>2 Days ago</font></td>";
$query = "SELECT sum(quantity),sum(total) FROM dlog_15 as d $where
	AND ".$dbc->datediff($dbc->now(),"tdate")." = 2";
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

echo "</td><tr><td><font color=blue>3 Days ago</font></td>";
$query = "SELECT sum(quantity),sum(total) FROM dlog_15 as d $where
	AND ".$dbc->datediff($dbc->now(),"tdate")." = 3";
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

echo "</td><tr><td><font color=blue>This Week</font></td>";
$query = "SELECT sum(quantity),sum(total) FROM dlog_15 as d $where
	AND ".$dbc->weekdiff($dbc->now(),"tdate")." = 0";
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

echo "</tr><tr><td><font color=blue>Last Week</font></td>";
$query = "SELECT sum(quantity),sum(total) FROM dlog_15 as d $where
	AND ".$dbc->weekdiff($dbc->now(),"tdate")." = 1";
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

if ($FANNIE_ARCHIVE_REMOTE){
	$dbc = new SQLManager($FANNIE_ARCHIVE_SERVER,$FANNIE_ARCHIVE_DBMS,
		$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_USER,$FANNIE_ARCHIVE_PW);
}

echo "</td><tr><td><font color=blue>This Month</font></td>";
$month = date('m');
$year = date('Y');
$query = "SELECT sum(quantity),sum(total) FROM $FANNIE_ARCHIVE_DB.dlog$year$month
	as d $where";
if ($FANNIE_ARCHIVE_DBMS == "MSSQL" || (!$FANNIE_ARCHIVE_REMOTE && $FANNIE_SERVER_DBMS == "MSSQL") ){
	$query = "SELECT sum(quantity),sum(total) FROM $FANNIE_ARCHIVE_DB.dbo.dlog$year$month
		as d $where";
}
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

echo "</td><tr><td><font color=blue>Last Month</font></td>";
$stamp = mktime(0,0,0,$month-1,1,$year);
$dstr = date("Ym",$stamp);
$query = "SELECT sum(quantity),sum(total) FROM $FANNIE_ARCHIVE_DB.dlog$dstr
	as d $where";
if ($FANNIE_ARCHIVE_DBMS == "MSSQL" || (!$FANNIE_ARCHIVE_REMOTE && $FANNIE_SERVER_DBMS == "MSSQL") ){
	$query = "SELECT sum(quantity),sum(total) FROM $FANNIE_ARCHIVE_DB.dbo.dlog$dstr
		as d $where";
}
$row = $dbc->fetch_row($dbc->query($query));
printf("<td align=right>%.2f</td><td align=right>$%.2f",$row[0],$row[1]);

echo "</tr></table>";
?>
