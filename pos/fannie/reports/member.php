<?php
include('../src/functions.php');
$page_title = 'Fannie - Reports Module';
$header = 'Membership Sales & Statistics';
include ('../src/includes/header.html');
// require_once ('../src/mysql_connect.php');
if ((!isset($_POST['submit'])) && (!isset($_POST['upc'])) && (!isset($_GET['upc']))) {

	?>
	<head>
	<title>Membership Reporting</title>
	<link href="../src/style.css" rel="stylesheet" type="text/css" />
	<script src="../src/CalendarControl.js" language="javascript"></script>
	<script src="../src/putfocus.js" language="javascript"></script>
	</head>
	<body onLoad='putFocus(0,0);'>
	<link href="../src/style.css" rel="stylesheet" type="text/css">
	<script src="../src/CalendarControl.js" language="javascript"></script>

	<form method="post" action="member.php" target="_blank">	
		<h2>Membership Report</h2>
		<div id="box">
			<table border="0" cellspacing="5" cellpadding="5" width=100%>
				<tr>
					<td colspan=3><p>You can chose a year.  Or a year and a month.  Or a year and a quarter.  Or pick your own dates. </p>
					<p>Month and quarter will default to this year.  So pick July and the report will be run for
					</td>
				</tr>
				<tr>
					<td>Pick a year:</td>
					<td colspan=2><select name=year>
						<option value=0></option>
						<option value=2008>2008</option>
						<option value=2007>2007</option>
						<option value=2006>2006</option>
					</td>
				</tr>
				<tr>
					<td>Pick a month:</td>
					<td colspan=2><select name=month>
						<option value=00></option>
						<option value=01>January</option>
						<option value=02>February</option>
						<option value=03>March</option>
						<option value=04>April</option>
						<option value=05>May</option>
						<option value=06>June</option>
						<option value=07>July</option>
						<option value=08>August</option>
						<option value=09>September</option>
						<option value=10>October</option>
						<option value=11>November</option>
						<option value=12>December</option>
					</td>
				</tr>
				<tr>
					<td>Pick a quarter:</td>
					<td colspan=2><select name=quarter>
						<option value=0></option>
						<option value=01>Q1: 1/1/XX -- 3/31/XX</option>
						<option value=02>Q2: 4/1/XX -- 6/30/XX</option>
						<option value=03>Q3: 7/1/XX -- 9/30/XX</option>
						<option value=04>Q4: 10/1/XX -- 12/31/XX</option>
					</td>
				</tr>			
				<tr>
					<td>Pick your own dates:</td>
					<td>Start Date:</td>
					<td><input type=text size=10 name=date1 onfocus="showCalendarControl(this);"></td>
		        </tr>
				<tr>
					<td>&nbsp;</td>
					<td>End Date:</td>
					<td><input type=text size=10 name=date2 onfocus="showCalendarControl(this);"></td>
				</tr>	
				<tr>
					<td> 
						<input type=submit name=submit value="Submit"> 
					</td>
					<td>
						<input type=reset name=reset value="Start Over">
					</td> 
					<td>&nbsp;</td>
				</tr>
			</table>
		</div>
	</form>
	</body>
	<?php
}

if(isset($_POST['submit'])){
	foreach ($_POST AS $key => $value) {
		$$key = $value;
		//echo $key ." : " .  $value"<br>";
	}

	//$order = "ROUND(SUM(t.total),2) DESC";
}else{
      foreach ($_GET AS $key => $value) {
          $$key = $value;
	      //echo $key ." : " .  $value."<br>";
      }
}

echo "<body>";

if (isset($_POST['submit'])) {
	//	REFERENCE mktime()
	// int mktime ([ int $hour [, int $minute [, int $second [, int $month [, int $day [, int $year [, int $is_dst ]]]]]]] )
	if (($year != 0) && ($month == 00) && ($quarter == 0)) {
		if ($year == date('Y')) { $table = 'dtransactions'; }
		else { $table = 'dlog_' . $year; }
	
		$db_date = 'YEAR(d.datetime) = ' . $year;
		$db_desc = 'the entire year of ' . $year;
	} elseif ($month != 00) {
		if($quarter != 0) {
			echo "<div id='alert'><p>Cannot select a month AND a quarter.  One or the other please.</p></div>";
			// exit();
		} elseif ($year == 0) {
			if (date('m') < $month) {
				$year = date('Y') - 1;
			} else {
				$year = date('Y');
			}	
		}
		
		if ($year == date('Y')) { $table = 'dtransactions'; }
		else { $table = 'dlog_' . $year; } 
		
		$db_date = 'YEAR(d.datetime) = ' . $year . ' AND MONTH(d.datetime) = ' . $month;
		$db_desc = 'the month of ' . date('F, Y',mktime(0,0,0,$month,0,$year));
	} elseif ($quarter != 0) {
		if($month != 00) {
			echo "<div id='alert'><p>Cannot select a month AND a quarter.  One or the other please.</p></div>";
			// exit();
		} else {
		 
			switch ($quarter) {
				case '01':
					$md1 = '01-01';
					$md2 = '03-31';
					$qn = 'first';
					break;
				case '02':
					$md1 = '04-01';
					$md2 = '06-31';
					$qn = 'second';
					break;
				case '03':
					$md1 = '07-01';
					$md2 = '09-30';
					$qn = 'third';
					break;
				case '04':
					$md1 = '10-01';
					$md2 = '12-31';
					$qn = 'fourth';
					break;
			}
		
			if ($year == 0) {
				if (date('m-d') < $md1) {
					$year = date('Y') - 1;
				} else {
					$year = date('Y');
				}	
			}
			
			$date1 = '"' . $year . '-' . $md1 . '"';
			$date2 = '"' . $year . '-' . $md2 . '"';
			
		}
		
		if ($year == date('Y')) { $table = 'dtransactions'; }
		else { $table = 'dlog_' . $year; }
		
		$db_date = 'DATE(d.datetime) >= ' . $date1 . ' AND DATE(d.datetime) <= ' . $date2;
		$db_desc = 'the ' . $qn . ' quarter of ' . $year;
	} elseif ((isset($date1)) && (isset($date2))) {
		$year1 = idate('Y',strtotime($date1));
		$year2 = idate('Y',strtotime($date2));

		if ($year1 != $year2) {
			echo "<div id='alert'><h4>Reporting Error</h4>
				<p>Fannie cannot run reports across multiple years.<br>
				Please retry your query.</p></div>";
			exit();
		}
		elseif ($year1 == date('Y')) { $table = 'dtransactions'; }
		else { $table = 'dlog_' . $year1; }
		
		$db_date = 'DATE(d.datetime) >= ' . $date1 . ' AND DATE(d.datetime) <= ' . $date2;
		$db_desc = $date1 . ' through ' . $date2;
	}
	
	$grossQ = "SELECT ROUND(sum(d.total),2) as GROSS_sales
		FROM is4c_log.$table AS d
		WHERE $db_date 
		AND d.department < 20
		AND d.department <> 0
		AND d.trans_status <> 'X'
		AND d.emp_no <> 9999";

	$results = mysql_query($grossQ);
	$row = mysql_fetch_row($results);
	$gross = $row[0];

	if (!$gross) $gross = 0;

	$equity = "SELECT SUM(d.total) AS total, COUNT(*) AS ct
		FROM is4c_log.$table d
		WHERE $db_date
		AND d.department = 36
		AND emp_no <> 9999 AND d.trans_status <> 'X'";
	$results = mysql_query($equity);
	$row = mysql_fetch_array($results);
	$eq_tot = $row['total'];
	$eq_ct = $row['ct'];
	
	echo "<font size=4><center><b>Sales of Equity</b></center></font>";
	echo "<p>Total sales of equity: " . money_format('%n',$eq_tot);
	echo "<br>Average investment amount: " . money_format('%n',($eq_tot / $eq_ct));
	echo "</p><br>";
	
	$pmts = "SELECT d.unitPrice as pmt, SUM(d.total) AS total, COUNT(*) AS ct
		FROM is4c_log.$table d
		WHERE $db_date
		AND d.department = 36
		AND emp_no <> 9999 AND d.trans_status <> 'X'
		GROUP BY d.unitPrice ORDER BY d.unitPrice";

//	echo "<font size=4><center><b>Sales by member status</b></center></font>";
	echo "<table border=0 cellpadding=5 cellspacing=0 width=40%>";
	$result = mysql_query($pmts,$dbc);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $pmts;
		die($message);
	}
   
	$bg = '#eeeeee'; // Set background color.
	while ($row = mysql_fetch_array($result)) { //create array from query
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo '<tr bgcolor="' . $bg . '">';
		echo '<td>' . money_format('%n',$row['pmt']) . '</td>
			<td align=right>' . money_format('%n',$row['total']) . '</td>
			<td align=right>' . $row['ct'] . '</td></tr>';
	}
		
	echo "</table><br>";

	$memstatus = "SELECT m.memDesc as memStatus,ROUND(SUM(d.total),2) AS Sales,ROUND((SUM(d.total)/$gross*100),2) AS pct
		FROM is4c_log.$table d, is4c_op.memtype m
		WHERE d.memType = m.memtype
		AND $db_date
	  	AND d.trans_type IN('I','D')
	  	AND d.trans_status <>'X'
	  	AND d.department <= 35 AND d.department <> 0
	  	AND d.upc <> 'DISCOUNT'
	  	AND d.emp_no <> 9999
		GROUP BY m.memtype";
		
	echo "<font size=4><center><b>Sales by member status</b></center></font>
		<table border=0 cellpadding=5 cellspacing=0 width=100%>";
	$result = mysql_query($memstatus,$dbc);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $memstatus;
		die($message);
	}
   
	$bg = '#eeeeee'; // Set background color.
	while ($row = mysql_fetch_array($result)) { //create array from query
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo '<tr bgcolor="' . $bg . '">';
		echo '<td>' . $row['memStatus'] . '</td>
			<td align=right>' . money_format('%n',$row['Sales']) . '</td>
			<td align=right>' . number_format($row['pct'],2) . '%</td></tr>';
	}
		
	echo "</table><br>";
	
	$memtype = "SELECT s.staff_desc as memType,ROUND(SUM(d.total),2) AS Sales,ROUND((SUM(d.total)/$gross*100),2) AS pct 
		FROM is4c_log.$table d,	is4c_op.staff s 
		WHERE d.staff = s.staff_no 
		AND $db_date
	  	AND d.trans_type IN('I','D')
	  	AND d.trans_status <>'X'
	  	AND d.department <= 35 AND d.department <> 0
	  	AND d.upc <> 'DISCOUNT'
	  	AND d.emp_no <> 9999
		GROUP BY s.staff_no";

	echo "<font size=4><center><b>Sales by member type</b></center></font>
		<table border=0 cellpadding=5 cellspacing=0 width=100%>";

	$result = mysql_query($memtype,$dbc);
	if (!$result) {
		$message  = 'Invalid query: ' . mysql_error() . "\n";
		$message .= 'Whole query: ' . $memtype;
		die($message);
	}
   
	$bg = '#eeeeee'; // Set background color.
	while ($row = mysql_fetch_array($result)) { //create array from query
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo '<tr bgcolor="' . $bg . '">';
		echo '<td>' . $row['memType'] . '</td>
			<td align=right>' . money_format('%n',$row['Sales']) . '</td>
			<td align=right>' . number_format($row['pct'],2) . '%</td></tr>';
	}	
	echo "</table>";
	echo "<p>Report run for " . $db_desc;
	echo "</p><p>Generated on " . date('Y-m-d H:i:s');
	echo "</p><center><a href='" . $_SERVER['PHP_SELF'] . "'><h4>Start Over</h4></a></center>";
}


//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//
// print_r ($_POST);
function debug_p($var, $title) 
{
    print "<p>$title</p><pre>";
    print_r($var);
    print "</pre>";
}  

debug_p($_REQUEST, "all the data coming in");


include ('../src/includes/footer.html');
?>