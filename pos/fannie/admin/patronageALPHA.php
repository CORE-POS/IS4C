<?php
require_once('../src/mysql_connect.php');
$page_title = 'Fannie - Administrative Area';
$header = 'Patronage Tools';
include('../src/header.html');

if(isset($_POST['submit'])) {  // was the form submitted?

	if(isset($_POST['submit'])){
		foreach ($_POST AS $key => $value) {
			$$key = $value;
		}
	}else{
	      foreach ($_GET AS $key => $value) {
	          $$key = $value;
	      }
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
	elseif ($year1 == date('Y')) { $table = 'dtransactions'; }
	else { $table = 'dlog_' . $year1; }

	if (!$date1 && !$date2) {
		$date1 = date('Y').'-01-01';
		$date2 = date('Y-m-d');
	}

	if (!$card_no) {
	
		$query = "SELECT c.CardNo AS cardno, c.LastName AS lastname, c.FirstName AS firstname, ROUND(SUM(d.total),2) AS patronage 
			FROM custdata c, is4c_log.$table d 
			WHERE d.card_no = c.CardNo 
			AND date(d.datetime) >= '$date1' 
			AND date(d.datetime) <= '$date2'
			AND d.memType IN(1,2) 
			AND d.department < 20 AND d.department <> 0 
			AND d.trans_status NOT IN('X','D') 
			AND d.card_no NOT IN (9999,99999,0)
			AND d.emp_no <> 9999 
			GROUP BY c.CardNo
			ORDER BY patronage DESC";
		
		$title = "Patronage Total For ALL MEMBERS";

		echo "<HEAD><title>".$title."</title>";
		echo "<link rel='STYLESHEET' href='../src/style.css' type='text/css'></HEAD>";
		echo "<BODY>";
		echo "<h3>";
		echo $title." For Period ";
		echo $date1." thru ".$date2;
		echo "</h3>";

		echo '<table border=0 cellpadding=4 cellspacing=0>'; 
		echo "<tr><th>Card #</th><th>Last name, First name</th><th>Patronage points</th></tr>";

		$result = mysql_query($query);

		$bg = '#eeeeee'; // Set background color.
		while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
			$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
			echo '<tr bgcolor="' . $bg . '">';
			echo "<td>" . $row['cardno'] . "</td>";	
			echo "<td>" . $row['lastname'] . ", " . $row['firstname'] . "</td>";
			echo "<td align=right>" . number_format($row['patronage'],2) . "</td></tr>";
		}

		// select_to_table($query,1,'FFFFFF');

	} else {

		$query = "SELECT ROUND(SUM(d.total),2) AS patronage
			FROM is4c_log.$table d
			WHERE d.card_no = $card_no
			AND date(d.datetime) >= '$date1'
			AND date(d.datetime) <= '$date2'
			AND d.memType IN(1,2)
			AND d.department >= 1 AND d.department <= 20
			AND d.trans_status NOT IN('X','D')
			AND d.emp_no <> 9999";

		$result = mysql_query($query);
		$row = mysql_fetch_row($result);

		$title = "Patronage Total For Member #".$card_no;

		echo "<HEAD>";
		echo "<title>".$title."</title>";
		echo "<link rel='STYLESHEET' href='../src/style.css' type='text/css'></HEAD>";
		echo "<BODY>";
		echo "<h3>";
		echo $title." For Period ";
		echo $date1." thru ".$date2;
		echo "</h3><div id=box>";

		echo "<font size=7><center>";
		echo money_format('%n',$row[0]);

		echo "</div></center></font>";
		echo "<br><br><br>";	
	}
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
} //end 'submit' conditional


echo '<head>
	<script src="../src/CalendarControl.js" language="javascript"></script>
	</head><body>';

echo '<form method="post" action="patronageALPHA.php">
	
		<h3>PATRONAGE REFUND REPORTING v0.3.14a</h3>
	
		<table border="0" cellspacing="5" cellpadding="5">
			<tr> 
				<td>
					Member #: <input type=text name="card_no" size="6">
				</td>
				<td>
					Start Date: <input type=text size=10 name=date1 onfocus="showCalendarControl(this);">
				</td>
				<td>
	            	End Date: <input type=text size=10 name=date2 onfocus="showCalendarControl(this);">
				</td>
				<td> 
					<input type=submit name=submit value="Submit"> 
				</td>
				<td>
					<input type=reset name=reset value="Start Over">
				</td> 
			</tr>
			<tr>
				<td colspan=5 align=center><font size=-1>If you leave the member # field blank the report will pull patronage totals for ALL members (may take a while).</font></td>
			</tr>
			<tr>
				<td colspan=5 align=center><font size=-1>If you leave the date fields blank the report will start at '.date('Y').'-01-01 and end on yesterdays date</font></td>
			</tr>
		</table>
	</form>';

include('../src/footer.html');
	
?>
