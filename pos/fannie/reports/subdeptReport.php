<?php
// /*******************************************************************************
// 
//     Copyright 2007 People's Food Co-op, Portland, Oregon.
// 
//     This file is part of Fannie.
// 
//     IS4C is free software; you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation; either version 2 of the License, or
//     (at your option) any later version.
// 
//     IS4C is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
// 
//     You should have received a copy of the GNU General Public License
//     in the file license.txt along with IS4C; if not, write to the Free Software
//     Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// 
// *********************************************************************************/
// 

include('../src/functions.php');
// mysql_select_db('is4c_op',$dbc);
require_once('../src/mysql_connect.php');


if (isset($_POST['submit'])) {

echo "<html><head>
	<script type=\"text/javascript\" src=\"../src/tablesort.js\"></script>
	<link rel='stylesheet' href='../src/tablesort.css' type='text/css' />
	<link rel='stylesheet' href='../src/style.css' type='text/css' /></head>";

if (isset($_GET['sort'])) {
	foreach ($_GET AS $key => $value) {
		$$key = $value;
		//echo $key ." : " .  $value."<br>";
	}
} else {
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}	
}
echo "<body>";

$today = date("F d, Y");	

if (isset($allDepts)) {
	$deptArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,40";
	$arrayName = "ALL DEPARTMENTS";
} else {
	if (isset($_POST['dept'])) {$deptArray = implode(",",$_POST['dept']);}
	elseif (isset($_GET['dept'])) {$deptArray = $_GET['dept'];}
	$arrayName = $deptArray;
}

// Check year in query, match to a dlog table
$year1 = idate('Y',strtotime($date1));
$year2 = idate('Y',strtotime($date2));

if ($year1 != $year2) {
	echo "<div id='alert'><h4>Reporting Error</h4>
		<p>Fannie cannot run reports across multiple years.<br>
		Please retry your query.</p></div>";
	exit();
}
//elseif ($year1 == date('Y')) { $table = 'dtransactions'; }
else { $table = 'dlog_' . $year1; }

// echo "<center><h1>Subdepartment Report</h1>
// 		<h4>Sales by category for $date1 thru $date2</h4></center>";
// echo "<p><font size=-1>Report sorted by " . $order_name . " on " . $today . "</br>Department range: " . $arrayName;
// echo "</font>";

$grossQ = "SELECT ROUND(sum(total),2) as GROSS_sales
		FROM is4c_log.$table 
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2' 
		AND department IN($deptArray)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

// echo "<br>".$grossQ."<br>";

		$grossR = mysql_query($grossQ);
		$row = mysql_fetch_row($grossR);
		$gross = $row[0];



function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

$time_start = getmicrotime();

// $subdeptQ = "SELECT s.subdept_name AS subdept,
// 		s.dept_name AS dept,
// 		ROUND(SUM(t.quantity),2) as qty,
// 		ROUND(SUM(t.total),2) as total
// 		FROM is4c_log.$table t, is4c_op.subdeptindex s
// 		WHERE t.upc = s.upc
// 		AND date(t.datetime) >= '$date1' AND date(t.datetime) <= '$date2'
// 		AND t.trans_type <> 'D'
// 		AND t.department IN($deptArray)
// 		GROUP BY s.subdept_name";


$subdeptQ = "SELECT s.subdept_name AS subdept,
		d.dept_name AS dept,
		ROUND(SUM(t.quantity),2) as qty,
		ROUND(SUM(t.total),2) as total
		FROM is4c_log.$table t, is4c_op.products p, is4c_op.departments d, is4c_op.subdepts s
		WHERE t.upc = p.upc AND p.department = d.dept_no AND p.subdept = s.subdept_no
		AND date(t.datetime) >= '$date1' AND date(t.datetime) <= '$date2'
		AND t.trans_type <> 'D'
		AND t.department IN($deptArray)
		GROUP BY s.subdept_name";

$result = mysql_query($subdeptQ);
$num = mysql_num_rows($result);
// echo $subdeptQ;
if (!$result) {
	$message  = 'Invalid query: ' . mysql_error() . "\n";
	$message .= 'Whole query: ' . $subdeptQ;
		die($message);
}

$time_end = getmicrotime();
$time_sec = ($time_end - $time_start);
$time_min = ($time_end - $time_start) / 60;

echo "<center><h1>Subdepartment Sales Report</h1>
	<h3>" . strftime('%D', strtotime($date1)) . " thru " . strftime('%D', strtotime($date2)) . "</h3></center>";

echo "<table id=\"output\" cellpadding=0 cellspacing=0 border=0 class=\"sortable-onload-3 rowstyle-alt colstyle-alt\">\n
  <caption>Dept.: ".$arrayName.". Yielded (".$num.") results. Run on " . date('n/j/y \a\t h:i A') . "</caption>\n
  <thead>\n
    <tr>\n
      <th class=\"sortable-text\">Subdepartment</th>\n
      <th class=\"sortable-text\">Department</th>\n
      <th class=\"sortable-numeric favour-reverse\">Qty.</th>\n
      <th class=\"sortable-currency favour-reverse\">Total</th>\n
    </tr>\n
  </thead>\n
  <tbody>\n";

while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
	echo "<td align=left>" . $row["subdept"] . "</td>
		<td align=left>" . $row["dept"] . "</td>
		<td align=right>" . $row["qty"] . "</td>
		<td align=right>" . money_format('%n',$row["total"]) . "</td>";
	echo "</tr>";
}

echo '</table>';

echo "<center>Query executed in <b>" . number_format($time,2) . "</b> minutes (<b>" . number_format($time_sec,2) . "</b> seconds)</center>";

//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//

// function debug_p($var, $title) 
// {
//     print "<p>$title</p><pre>";
//     print_r($var);
//     print "</pre>";
// }  
// 
// debug_p($_REQUEST, "all the data coming in");

} else {

$page_title = 'Fannie - Reporting';
$header = 'Subdepartment Report';
include('../src/header.html');

echo '<script src="../src/CalendarControl.js" language="javascript"></script>
	<script src="../src/putfocus.js" language="javascript"></script>
	<form method="post" action="subdeptReport.php" target="_blank">		
	
	<h2>Sub-Department report</h2>
	
	<table border="0" cellspacing="5" cellpadding="5">
		<tr> 
			<td align="right">
				<p><b>Date Start:</b></p>
		    	<p><b>End:</b></p>
		    </td>
			<td>
		    	<p><input type=text size=10 name=date1 onclick="showCalendarControl(this);"></p>
            	<p><input type=text size=10 name=date2 onclick="showCalendarControl(this);"></p>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
	<table border="0" cellspacing="5" cellpadding="5">
		<tr valign=top>';

include('../src/departments.php');

echo '</tr>
		<tr>
			<td>&nbsp;</td> 
			<td> 
				<input type=submit name=submit value="Submit"> 
			</td>
			<td>
				<input type=reset name=reset value="Start Over">
			</td> 
		</tr>
	</table>
</form>';
include('../src/footer.html');
}

?>
