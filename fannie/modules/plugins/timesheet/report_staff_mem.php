<? ob_start(); ?>
<?php
//	FULL TIME: Number of hours per week
$ft = 40;


$header = "Timeclock - Staff Member Totals Report";
include('../../../src/header.php');
include('./includes/header.html');
if ($_GET['login'] == 1 || $_SESSION['logged_in'] == True) include("/pos/fannie/src/passwd.php");
?>

<style>
table th {
	font-size: 8px;
	text-transform: uppercase;
}
</style>

<?php
echo "<form action='report_staff_mem.php' method=GET>";

$stored = ($_COOKIE['timesheet']) ? $_COOKIE['timesheet'] : '';


if ($_SESSION['logged_in'] == True) {
	echo '<p>Name: <select name="emp_no">
		<option value="error">Select staff member</option>' . "\n";
	
	$query = "SELECT FirstName, IF(LastName='','',CONCAT(SUBSTR(LastName,1,1),\".\")), emp_no FROM ".DB_NAME.".employees where EmpActive=1 ORDER BY FirstName ASC";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		echo "<option value=\"$row[2]\">$row[0] $row[1]</option>\n";
	}
	echo '</select>&nbsp;&nbsp;*</p>';
} else {
	echo "<p>Employee Number*: <input type='text' name='emp_no' value='$stored' size=4 autocomplete='off' /></p>";
}


// echo "<p>Employee number*: <input type='text' name='emp_no' size=4 autocomplete=off value=".$_COOKIE['timesheet']." /></p>";

$currentQ = "SELECT periodID FROM is4c_log.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
$currentR = mysql_query($currentQ);
list($ID) = mysql_fetch_row($currentR);

$query = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID FROM is4c_log.payperiods WHERE periodStart < now() ORDER BY periodID DESC";
$result = mysql_query($query);

echo '<p>Starting Pay Period: <select name="period">
    <option>Please select a starting pay period.</option>';

while ($row = mysql_fetch_array($result)) {
    echo "<option value=\"" . $row['periodID'] . "\"";
    if ($row['periodID'] == $ID) { echo ' SELECTED';}
    echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
}

echo "</select><br />";
echo '<p>Ending Pay Period: <select name="end">
    <option value=0>Please select an ending pay period.</option>';
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
    echo "<option value=\"" . $row['periodID'] . "\"";
    if ($row['periodID'] == $ID) { echo ' SELECTED';}
    echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
}
echo '</select><button value="run" name="run">Run</button></p></form>';
if ($_GET['run'] == 'run') {
	
	$namesq = "SELECT e.emp_no, e.FirstName, e.LastName, e.pay_rate, JobTitle FROM employees e WHERE e.emp_no = ". $_GET['emp_no'] ." AND e.empActive = 1";
	$namesr = mysql_query($namesq);
	
	if (!$namesr) {
		echo "<div id='alert'><h1>Error!</h1><p>Incorrect, invalid, or inactive employee number entered.</p>
			<p><a href='".$_SERVER['PHP_SELF']."'>Please try again</a></p></div>";
	} else {
		$name = mysql_fetch_assoc($namesr);
		
		setcookie("timesheet", $_GET['emp_no'], time()+60*3);
		
		$emp_no = $_GET['emp_no'];
		$periodID = $_GET['period'];
		$end = ($_GET['end']== 0) ? $periodID : $_GET['end'];

		$query1 = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, periodID as pid FROM is4c_log.payperiods WHERE periodID = $periodID";
		$result1 = mysql_query($query1);
		$periodStart = mysql_fetch_row($result1);

		$query2 = "SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID as pid FROM is4c_log.payperiods WHERE periodID = $end";
		$result2 = mysql_query($query2);
		$periodEnd = mysql_fetch_row($result2);
		for ($i = $periodStart[1]; $i < $periodEnd[1]; $i++) {
			$p[] = $i;
		}

		$firstppR = mysql_query("SELECT MIN(periodID) FROM is4c_log.payperiods WHERE YEAR(periodStart) = YEAR(CURDATE())");
		$firstpp = mysql_fetch_row($firstppR);
		for ($i = $firstpp[0]; $i <= $periodEnd[1]; $i++) {
			$y[] = $i;
		}

		$emp_no = $_GET['emp_no'];

		// $sql_incl = "";
		// $sql_excl = "AND emp_no <> 9999";
		$staffQ = "SELECT * FROM employees WHERE emp_no = $emp_no";
		$staffR = mysql_query($staffQ);
		$staff = mysql_fetch_assoc($staffR);

		echo "<h2>$emp_no &mdash; ".$staff['FirstName']." ". $staff['LastName']."</h2>";

		// BEGIN TITLE
		// 
		$query1 = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, periodID as pid, DATE(periodStart) FROM is4c_log.payperiods WHERE periodID = $periodID";
		$result1 = mysql_query($query1);
		$periodStart = mysql_fetch_row($result1);

		$query2 = "SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID as pid, DATE(periodEnd) FROM is4c_log.payperiods WHERE periodID = $end";
		$result2 = mysql_query($query2);
		$periodEnd = mysql_fetch_row($result2);

		// $periodct = ($end !== $periodID) ? $end - $periodID : 1;
		for ($i = $periodStart[1]; $i <= $periodEnd[1]; $i++) {
			// echo $i;
			$periodct++;
			$p[] = $i;
		}
		echo "<h3>" . $periodStart[0] . " &mdash; " . $periodEnd[0] . "</h3>\n";
		echo "Number of payperiods: " . $periodct . "\n";
		// 
		// END TITLE	
		echo "<br />";

		$areasq = "SELECT ShiftName, ShiftID FROM ".DB_LOGNAME.".shifts WHERE visible = 1 ORDER BY ShiftOrder";
		$areasr = mysql_query($areasq);

		echo "<table border='1' cellpadding='5' cellspacing=0><thead>\n<tr><th>Week</th><th>Name</th><th>Wage</th>";
		while ($areas = mysql_fetch_array($areasr)) {
			echo "<div id='vth'><th>" . substr($areas[0],0,6) . "</th></div>";	// -- TODO vertical align th, static col width
		}
		echo "</th><th>PTO new</th><th>Total</th><th>OT</th></tr></thead>\n<tbody>\n";
		
		$weekQ = "SELECT emp_no, area, date, periodID, hours, WEEK(date) as week_number FROM ".DB_LOGNAME.".timesheet WHERE emp_no = $emp_no 
			AND date >= '" . $periodStart[2] . "' AND date <= '" . $periodEnd[2] . "' 
			GROUP BY WEEK(date)";
		$weekR = mysql_query($weekQ);
		echo $weekQ;
		while ($row = mysql_fetch_assoc($weekR)) {
			$week_no = $row['week_number'];
			
			$totalq = "SELECT SUM(hours) FROM ".DB_LOGNAME.".timesheet WHERE periodID >= $periodID AND periodID <= $end AND emp_no = $emp_no";
			$totalr = mysql_query($totalq);
			$total = mysql_fetch_row($totalr);
			$color = ($total[0] > (80 * $periodct)) ? "FF0000" : "000000";
			echo "<tr><td>$week_no</td>";
			echo "<td>".ucwords($name['FirstName'])." - " . ucwords(substr($name['FirstName'],0,1)) . ucwords(substr($name['LastName'],0,1)) . "</td><td align='right'>$" . $name['pay_rate'] . "</td>";
			$total0 = (!$total[0]) ? 0 : number_format($total[0],2);


			//
			//	LABOR DEPARTMENT TOTALS

			$areasq = "SELECT ShiftName, ShiftID FROM ".DB_LOGNAME.".shifts WHERE visible = 1 ORDER BY shiftOrder";
			$areasr = mysql_query($areasq);
			while ($areas = mysql_fetch_array($areasr)) {
				$emp_no = $row['emp_no'];
				$area = $areas[1];
				$depttotq = "SELECT SUM(t.hours) FROM is4c_log.timesheet t WHERE WEEK(t.date) = $week_no AND t.emp_no = $emp_no AND t.area = $area";
				// echo $depttotq;
				$depttotr = mysql_query($depttotq);
				$depttot = mysql_fetch_row($depttotr);
				$depttotal = (!$depttot[0]) ? 0 : number_format($depttot[0],2);
				echo "<td align='right'>" . $depttotal . "</td>";
			}
			//	END LABOR DEPT. TOTALS


			//	TOTALS column
			// echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";

			//
			//	PTO CALC
			$nonPTOtotalq = "SELECT SUM(hours) FROM ".DB_LOGNAME.".timesheet WHERE periodID >= $periodID AND periodID <= $end AND area <> 31 AND emp_no = ".$row['emp_no'];
			$nonPTOtotalr = mysql_query($nonPTOtotalq);
			$nonPTOtotal = mysql_fetch_row($nonPTOtotalr);
			$ptoAcc = ($row['JobTitle'] == 'STAFF') ? $nonPTOtotal[0] * 0.075 : 0;
			echo "<td align='right'>" . number_format($ptoAcc,2) . "</td>";


			echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";

			// 
			//	OVERTIME
			// 
			$otime1 = array();
			$otime2 = array();
			foreach ($p as $v) {
				$weekoneQ = "SELECT ROUND(SUM(hours), 2) FROM is4c_log.timesheet AS t
			        INNER JOIN is4c_log.payperiods AS p ON (p.periodID = t.periodID)
			        WHERE t.emp_no = " . $row['emp_no'] . "
			        AND t.periodID = $v
			        AND t.area <> 31
			        AND t.date >= DATE(p.periodStart)
			        AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))";

			    $weektwoQ = "SELECT ROUND(SUM(hours), 2)
			        FROM is4c_log.timesheet AS t
			        INNER JOIN is4c_log.payperiods AS p
			        ON (p.periodID = t.periodID)
			        WHERE t.emp_no = " . $row['emp_no'] . "
			        AND t.periodID = $v
			        AND t.area <> 31
			        AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)";

			    $weekoneR = mysql_query($weekoneQ);
			    $weektwoR = mysql_query($weektwoQ);

			    list($weekone) = mysql_fetch_row($weekoneR);
			    if (is_null($weekone)) $weekone = 0;
			    list($weektwo) = mysql_fetch_row($weektwoR);
			    if (is_null($weektwo)) $weektwo = 0;

				if ($weekone > $ft) $otime1[] = $weekone - $ft;
				if ($weektwo > $ft) $otime2[] = $weektwo - $ft;
				// $otime = $otime + $otime1 + $otime2;

			}
			$ot1 = array_sum($otime1);
			$ot2 = array_sum($otime2);
			$otime = $ot1 + $ot2;
			// print_r($p);
			echo "<td align='right'>" . $otime . "</td>";
			$otime = 0;
			$otime1 = array();
			$otime2 = array();
			// 	END OVERTIME
			echo "</tr>";

		}
		echo "</tbody></table>\n";
		
	}
	
	
}


if ($_SESSION['logged_in'] == True) {
	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?logout=1'>logout</a></div>";
} else {
	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>login</a></div>";  //   class='loginbox'
}

include('../../../src/footer.php');
// echo "<script>$('#vth th').html($('#vth th').text().replace(/(.)/g,\"$1<br />\"));</script>";
?>
<? ob_flush(); ?>