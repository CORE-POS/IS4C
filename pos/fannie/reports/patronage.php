<?php
require_once('../src/mysql_connect.php');

echo "<html><head><title>Patronage Redemption Report -- " . date('Y-m-d') . "</title>";
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
		
echo "<link rel='stylesheet' href='../src/style.css' type='text/css' /></head><body>";

if (isset($_POST['submit'])) {
	
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
	
	$today = date('Y-m-d');	
	
	if (!$date1) { $date1 = date('Y') . "-01-01"; }
	if (!$date2) { $date2 = $today; }
	$dlog = "dlog_" . date('Y');
	$prt = "cust_pr_" . date('Y');
	
	echo "<h1>Patronage Redemption Report</h1><h2>$date1 thru $date2</h2><br><br>";
	
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
	echo "<table border=1 align=center><tr><td>";
	echo "<table align=center border=1 cellspacing=0 cellpadding=0 width=300px><tr>
		<td colspan=2 valign=bottom align=left!><h3>VOUCHERS REDEEMED</h3>
		<a href='patronage_detail.php?popup=daily' onClick=\"return popup(this, 'patronage_detail')\";>Daily totals</a><br>
		<a href='patronage_detail.php?popup=redeemed' onClick=\"return popup(this, 'patronage_detail')\";>Show all</a></td></tr>
		<tr><td width=100px>count</td><td width=200px>total</td></tr>
		<tr><td><font size=5>$rc</font></td>
		<td><font size=5>" . money_format('%n',$rt) . "</font></td></tr>";
	if ($err > 0) { echo "<tr><td colspan=2>Patronage record errors detected: <font color=red size=4>$err</font></td></tr>";}
	echo "</table></td></tr>";
	

	$query = "SELECT COUNT(*) AS ct, SUM(paid) AS total FROM $prt";

	$result = mysql_query($query);
	$row = mysql_fetch_row($result);
	$oc1 = $row[0];
	$ot1 = $row[1];
	mysql_free_result($result);
	
	$oc = $oc1 - $rc;
	$ot = $ot1 - $rt;
	
	echo "<tr><td><table align=center border=1 cellspacing=0 cellpadding=0 width=300px><tr>
		<td colspan=2 valign=bottom align=left><h3>VOUCHERS OUTSTANDING</h3>
		<a href='patronage_detail.php?popup=outstanding' onClick=\"return popup(this, 'patronage_detail')\";>Show all</a></td></tr>
		<tr><td width=100px>count</td><td width=200px>total</td></tr>
		<tr><td><font size=5>$oc</font></td>
		<td><font size=5>" . money_format('%n',$ot) . "</font></td></tr></table>";
		
	$query = "SELECT COUNT(*) as ct, SUM(total) as donations FROM is4c_log.$dlog WHERE department = 38";
	
	$result = mysql_query($query);
	$row = mysql_fetch_row($result);
	$dc = $row[0];
	$dt = $row[1];
	
	echo "<tr><td><table align=center border=1 cellspacing=0 cellpadding=0 width=300px><tr>
		<td colspan=2 valign=bottom align=left><h3>GENERAL DONATIONS</h3></td></tr>
		<tr><td width=100px>count</td><td width=200px>total</td></tr>
		<tr><td><font size=5>$dc</font></td>
		<td><font size=5>" . money_format('%n',$dt) . "</font></td></tr></table>";
	
	echo "</td></tr></table>";

} else {
	
$page_title = 'Fannie - Reporting';
$header = 'Patronage Redemption Report';
include('../src/header.html');

echo "<form method=POST action=patronage.php target=_blank>\n
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
</form>\n";

include('../src/footer.html');
}
?>
