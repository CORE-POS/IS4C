<?php
require_once('../src/mysql_connect.php');

$page_title = 'Fannie - Administration';
$header = 'Patronage Redemption Report';
include('../src/header.html');

echo '<script src="../src/CalendarControl.js" language="javascript"></script>
	<SCRIPT TYPE="text/javascript">
	<!--
	function popup(mylink, windowname)
	{
	if (! window.focus)return true;
	var href;
	if (typeof(mylink) == "string")
	   href=mylink;
	else
	   href=mylink.href;
	window.open(href, windowname, "width=650,height=800,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes");
	return false;
	}
	//-->
	</SCRIPT>
	</HEAD><BODY>';

if (isset($_POST['submit']) && $_POST['check'] != TRUE) {
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}	
	$today = date('Y-m-d');	
	$dlog = "dlog_2008_pr";
	// $prt = "cust_pr_" . date('Y');
	$prt = "cust_pr_2008";
	if (!$date1) { 
		$dateR = mysql_query("SELECT MIN(DATE(datetime)) FROM is4c_log.$dlog");
		$row = mysql_fetch_row($dateR);
		$date1 = $row[0];
	}
	if (!$date2) { $date2 = $today; }

	
	echo "<center><h1>Patronage Redemption Report</h1><h2>$date1 thru $date2</h2></center><br>";
	
	$query = "SELECT COUNT(*) AS ct, -SUM(total) AS total FROM is4c_log.PR_redeemed WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'";
	$error = "SELECT DATE(p.datetime) as date, 
		p.card_no as card_no,
		-p.total as total,
		-r.paid as paid_out,
		(r.paid + p.total) as diff
		FROM is4c_log.PR_redeemed p, $prt r
		WHERE p.card_no = r.card_no
		HAVING diff <> 0
		ORDER BY p.datetime DESC";

	$result = mysql_query($query);
	$row = mysql_fetch_row($result);
	$rc = $row[0];
	$rt = $row[1];
	mysql_free_result($result);
	$result = mysql_query($error);
	$err = mysql_num_rows($result);
	mysql_free_result($result);
	echo "<table border=1 align=center><tr>\n<td>";
	echo "<table align=center border=1 cellspacing=0 cellpadding=0 width=300px><tr>\n
		<td colspan=2 valign=bottom align=left!><h3>VOUCHERS REDEEMED</h3>
		<a href='patronage_detail.php?popup=daily' onClick=\"return popup(this, 'patronage_detail')\";>Daily totals</a><br>
		<a href='patronage_detail.php?popup=redeemed' onClick=\"return popup(this, 'patronage_detail')\";>Show all</a></td></tr>
		<tr>\n<td width=100px>count</td><td width=200px>total</td></tr>
		<tr>\n<td><font size=5>$rc</font></td>
		<td><font size=5>" . money_format('%n',$rt) . "</font></td></tr>";
	if ($err > 0) { echo "<tr><td colspan=2>Patronage record errors detected: <font color=red size=4>$err</font></td></tr>";}
	echo "\n</table>\n</td></tr>";
	

	$query = "SELECT COUNT(*) AS ct, SUM(paid) AS total FROM $prt";

	$result = mysql_query($query);
	$row = mysql_fetch_row($result);
	$oc1 = $row[0];
	$ot1 = $row[1];
	mysql_free_result($result);
	
	$oc = $oc1 - $rc;
	$ot = $ot1 - $rt;
	
	echo "<tr>\n<td><table align=center border=1 cellspacing=0 cellpadding=0 width=300px><tr>\n
		<td colspan=2 valign=bottom align=left><h3>VOUCHERS OUTSTANDING</h3>
		<a href='patronage_detail.php?popup=outstanding' onClick=\"return popup(this, 'patronage_detail')\";>Show all</a></td></tr>
		<tr>\n<td width=100px>count</td><td width=200px>total</td></tr>
		<tr>\n<td><font size=5>$oc</font></td>
		<td><font size=5>" . money_format('%n',$ot) . "</font></td></tr>\n</table>\n";
		
	$query = "SELECT COUNT(*) as ct, SUM(total) as donations FROM is4c_log.$dlog WHERE department = 38";
	
	$result = mysql_query($query);
	$row = mysql_fetch_row($result);
	if (!$row[0]) { $dc = 0; } else { $dc = $row[0]; }
	if (!$row[1]) { $dt = 0; } else { $dt = $row[1]; }
	
	echo "<tr>\n<td><table align=center border=1 cellspacing=0 cellpadding=0 width=300px><tr>\n
		<td colspan=2 valign=bottom align=left><h3>GENERAL DONATIONS</h3></td></tr>\n
		<tr>\n<td width=100px>count</td><td width=200px>total</td></tr>\n
		<tr>\n<td><font size=5>$dc</font></td>\n
		<td><font size=5>" . money_format('%n',$dt) . "</font></td></tr>\n</table>\n";
	
	echo "</td></tr>\n</table><br><a href='patronage.php'>START OVER</a>";
	include('../src/footer.html');

} elseif (isset($_POST['submit']) && $_POST['check'] == TRUE) {
	if (!$_POST['card_no'] || !is_numeric($_POST['card_no'])) {
		$card_no = 0;
		echo "<div id=alert><p>INVALID ENTRY:  Please enter a valid member number -- 
			<font size=2><a href=patronage.php> start over</a></font></p></div>\n";
	} else {
		$card_no = $_POST['card_no'];
		$today = date('Y-m-d');	
		$prt = "cust_pr_" . date('Y');
		$result = mysql_query("SELECT card_no FROM $prt WHERE card_no = $card_no");
		$num = mysql_num_rows($result);
		$result1 = mysql_query("SELECT * FROM is4c_log.PR_redeemed WHERE card_no = $card_no");
		$num1 = mysql_num_rows($result1);

		if (is_null($num) || $num < 1) {
			echo "<div id=alert><p>INVALID ENTRY: There is no refund on file for that member # 
				-- <a href=patronage.php> start over</a></p></div>\n";
		} elseif ($num1) {
			echo "<div id=alert><p>WARNING! A voucher has already been redeemed for this member number.
				-- <a href=patronage.php> start over</a></p></div>\n";
		} else {
			$result = mysql_query("SELECT * FROM $prt p, custdata c WHERE p.card_no = $card_no AND p.card_no = c.CardNo");
			$row = mysql_fetch_assoc($result);
			$paid = money_format('%n',$row['paid']);
			$name = $row['FirstName'] . " " . $row['LastName'];
			echo "<form method=POST action='patronage.php' target=_self>";
			echo "<input type=hidden name=memtype value=".$row['memType'].">";
			echo "<input type=hidden name=staff value=".$row['staff'].">";
			echo "<h2>Member #: $card_no</h2><input type=hidden name=card_no value=$card_no>\n
				<h2>Name: $name</h2>\n
				<h2>Refund Amt: $paid</h2><input type=hidden name=paid value=$paid>\n
				<p>Please verify that the above information matches the actual patronage refund voucher.  By clicking 'commit' you will be applying
				a check tender to the transaction logs for the amount of $paid.  This action cannot be undone.</p>\n<p>If the total above does not 
				equal the total on the voucher please contact the <a href='mailto:admin@peoples.coop'>POS sysadmin</a> to proceed.</p>\n<br><br>\n";
			echo "<input type=submit name=commit value=COMMIT><a href='patronage.php'> cancel</a></form>\n";
		}
	}
	include('../src/footer.html');

} elseif ($_POST['commit']) {
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
	$dlog = "dlog_" . date('Y') . "_pr";
	$paid = substr($paid, 1);
	
	$result = mysql_query("SELECT * FROM is4c_log.PR_redeemed WHERE card_no = $card_no");
	$num = mysql_num_rows($result);
	if ($num) {
		$output = "WARNING! A voucher has already been redeemed for this member number.  Process aborted.";
		
	} else {
		$insert = "INSERT INTO is4c_log.$dlog (datetime, register_no, emp_no, trans_no, description, 
			trans_type, trans_subtype, trans_status, total, voided, memType, staff, card_no, trans_id) 
			values (now(), 9, 9, 0, 'Patronage Check Pay', 'T', 'PT', 0, -$paid, 55, $memtype, $staff, $card_no, 0)";
		$result = mysql_query($insert);
		if (!$result) {
			$output = "There was an error: " . mysql_error();
		} else {
			$result = mysql_query("SELECT * FROM is4c_log.$dlog WHERE voided = 55 ORDER BY datetime DESC LIMIT 1");
			$row = mysql_fetch_assoc($result);
			$output = "<h3>Payment successfully committed! </h3>time:" .$row['datetime']. "<br> description: " .$row['description']. "<br> total: "
				.$row['total']. "<br> card_no: " .$row['card_no'];
			$output .= "<p>To view the new entry click <a href='patronage_detail.php?popup=redeemed' onClick=\"return popup(this, 'patronage_detail')\";>HERE</a>.</p>";
			
		}
	}
	
	echo "<div id=alert>\n<p>";
	echo $output . "</p></div>\n<br><br><a href='patronage.php'>START OVER</a>";
	include("../src/footer.html");
	
} else {

	echo "<div id=box>\n<center><h3>Patronage Redemption Report</h3></center>\n
		<form method=POST action=patronage.php target=_self>\n
		<table border=0 cellspacing=3 cellpadding=5 align=center>\n
			<tr> \n
	            <th colspan=2 align=center> <p><b>Select Dates</b></p></th>\n
			</tr>\n
			<tr>\n 
				<td>
					<p><b>Date Start</b> </p>
			    	<p><b>End</b></p>
			    </td>
				<td>
			    	<p><input type=text size=10 name=date1 onclick=\"showCalendarControl(this);\"></p>
		        	<p><input type=text size=10 name=date2 onclick=\"showCalendarControl(this);\"></p>
			    </td>
			</tr>\n
	        <tr>\n
				<td colspan=2>\n
				<p><b>Leave date fields blank for YTD</b></p>\n
				</td>\n
			</tr>\n
		<tr> \n
				<td><input type=submit name=submit value=\"Submit\"> </td>\n
				<td><input type=reset name=reset value=\"Start Over\"> </td>\n
				<td>&nbsp;</td>\n
			</tr>\n
		</table>\n
	</form>\n</div>";

	echo "<div id=box>\n<center><h3>Check Request Entry Form</h3></center>\n
		<p>Please make sure that you process check request using this interface <i>BEFORE</i> cutting a check to ensure the voucher in 
		question has not already been redeemed</p>
		<form method=POST action=patronage.php target=_self>\n
		<table border=0 cellspacing=3 cellpadding=5 align=center>\n
			<tr><td><p><b>Member #</b></p></td><td><input type=text name=card_no size=5></td></tr>\n
			<tr><td><input type=hidden name=check value=TRUE>
			<input type=submit name=submit value=\"Submit\"></td></tr>\n
		</table></form>\n</div>";

	include('../src/footer.html');
}
?>
