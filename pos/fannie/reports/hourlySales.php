<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
require_once('../src/mysql_connect.php');


if(isset($_POST['submit'])){

	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}

	if($date0 != '') {
		$date1 = $date0;
		$date2 = $date0;
		$date0a = $date0 . " 00:00:00";

		// $day = date("l",$date0a);
		$day = idate('w',strtotime($date0));
		switch($day) {
			case '1':
			$day = 'Monday';
			break;
			case '2':
			$day = 'Tuesday';
			break;
			case '3':
			$day = 'Wednesday';
			break;
			case '4':
			$day = 'Thursday';
			break;
			case '5':
			$day = 'Friday';
			break;
			case '6':
			$day = 'Saturday';
			break;
			case '7':
			$day = 'Sunday';
			break;
		}
		$title = $day."s Hourly Sales For ".$date0;
	} else {
		$title = $day."s Hourly Sales For ".$date1." thru ".$date2;	
	}

	echo "<HEAD>";
	echo "<link href='../src/style.css' rel='stylesheet'' type='text/css' />";
	echo "<title>".$title."</title>";
	echo "</HEAD>";
	echo "<BODY>";

	// Check year in query, match to a dlog table
	$year1 = idate('Y',strtotime($date1));
	$year2 = idate('Y',strtotime($date2));

	if ($year1 != $year2) {
		echo "<div id='alert'><h4>Reporting Error</h4>
			<p>Fannie cannot run reports across multiple years.<br>
			Please retry your query.</p></div>";
		exit();
	}
//	elseif ($year1 == date('Y')) { $table = 'dtransactions'; }
	else { $table = 'dlog_' . $year1; }

	$date1a = $date1 . " 00:00:00";
	$date2a = $date2 . " 23:59:59";

	$num1 = 0;
	$num2 = 0;

	$query1="SELECT date_format(datetime,'%H') AS hour,ROUND(sum(total),2) as Sales
		FROM is4c_log.$table
		WHERE date_format(datetime,'%W') = '$day'
		AND datetime > '$date1a'
		AND datetime < '$date2a'
		AND department <= 13
		AND department <> 0
		AND trans_status <> 'X'
		AND emp_no <> 9999
		GROUP BY hour
		ORDER BY hour";

	$query2="SELECT ROUND(sum(total),2) as TotalSales
		FROM is4c_log.$table
		WHERE datetime > '$date1a'
		AND datetime < '$date2a'
		AND date_format(datetime,'%W') = '$day'
		AND trans_status <> 'X'
		AND department <= 13
		AND department <> 0
		AND emp_no <> 9999";

	$transCountQ = "SELECT date_format(datetime,'%H') AS hour,COUNT(total) as transactionCount
		FROM is4c_log.$table
		WHERE date_format(datetime,'%W') = '$day'
		AND datetime > '$date1a'
		AND datetime < '$date2a'
		AND trans_status <> 'X'
		AND emp_no <> 9999
		AND upc = 'DISCOUNT'
		GROUP BY hour
		ORDER BY hour";

	// echo $query1;
	// echo "<br>";
	// echo $query2;
	// echo "<br>";
	// echo $transCountQ;

	$result1 = mysql_query($query1);
	$result2 = mysql_query($query2);
	$result3 = mysql_query($transCountQ);
	$num1 = mysql_num_rows($result1);
	$num2 = mysql_num_rows($result3);
	$row2 = mysql_fetch_row($result2);

	echo "<center><h2>";
	echo $title;
	echo "</h2>";
	echo "<table>";
	echo "<tr align='center'><td><b>Hour</b></td><td><b>Sales</b></td><td>&nbsp</td><td><b>Pct.</b></td><td><b>Count</b></tr>";
	while(($row1 = mysql_fetch_row($result1)) && ($row3 = mysql_fetch_row($result3))){	
		$sales = $row1[1];
		$gross = $row2[0];
		$count = $row3[1];
	    $portSales = $sales/$gross;
	    $twoperSales = $portSales * 200;
		$percentage = number_format(100 * $portSales,2);  
		echo "<tr><td align='center'>".$row1[0]."</td><td align='right'>".$row1[1]."</td>";
	    echo "<td><img src=../src/image.php?size=$twoperSales></td>";
	    echo "<td align='right'>".$percentage." %</td>";
		echo "<td align='right'>".$count."</td></tr>";
	}
	echo "<tr><td>&nbsp</td></tr><tr><td><b>Gross Total:</b></td>";
	echo "<td><p><b>".$gross."</b></p></td></tr>";
	echo "</table></center>";

	//
	// PHP INPUT DEBUG SCRIPT  -- very helpful!
	//
/*
	function debug_p($var, $title) 
	{
	    print "<p>$title</p><pre>";
	    print_r($var);
	    print "</pre>";
	}  

	debug_p($_REQUEST, "all the data coming in");
*/
} else{ 

$page_title = 'Fannie - Reporting';
$header = 'Hourly Sales Report';
include('../src/header.html');	

echo '<script src="../src/CalendarControl.js" language="javascript"></script>

	<form method="post" action="hourlySales.php" target="_blank">	
		<h2>Hourly Sales Report</h2>

		<div id="box">	
			<table border="0" cellspacing="5" cellpadding="5">
				<tr>
					<td><p>Date:</p></td>
					<td><input type="text" size="10" name="date0" onclick="showCalendarControl(this);"></td>
					<td>Pull hourly sales for any one date since 2007-01-04</td>
				</tr>
			</table>
		</div>
		<div id="box">
			<table border="0" cellspacing="5" cellpadding="5">
				<tr> 
					<td><p>Sunday</p></td><td><input type="radio" name="day" value="Sunday"></td>
				</tr>
				<tr>	
					<td><p>Monday</p></td><td><input type="radio" name="day" value="Monday"></td>
				</tr>
				<tr>	
					<td><p>Tuesday</p></td><td><input type="radio" name="day" value="Tuesday"></td>
				</tr>
				<tr>	
					<td><p>Wednesday</p></td><td><input type="radio" name="day" value="Wednesday"></td>
				</tr>
				<tr>
					<td><p>Thursday</p></td><td><input type="radio" name="day" value="Thursday"></td>
				</tr>
				<tr>
					<td><p>Friday</p></td><td><input type="radio" name="day" value="Friday"></td>
				</tr>
				<tr>	
					<td><p>Saturday</p></td><td><input type="radio" name="day" value="Saturday"></td>
				</tr>
				<tr>
					<td><p>Start Date:</p></td><td><input type=text size=10 name=date1 onfocus="showCalendarControl(this);"></td>
		        </tr>
				<tr>
					<td><p>End Date:</p></td><td><input type=text size=10 name=date2 onfocus="showCalendarControl(this);"></td>
				</tr>	
				<tr>
					<td> 
						<input type=submit name=submit value="Submit"> 
					</td>
					<td>
						<input type=reset name=reset value="Start Over">
					</td> 
				</tr>
			</table>
		</div>
	</form>';
include('../src/footer.html');	
}
?>
