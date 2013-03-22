<?php
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

$header = "Cashier Totals";
$page_title = "Fannie : Cashier Totals";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
<form method=get action=<?php echo $_SERVER["PHP_SELF"]; ?> >
<table cellspacing="4" cellpadding="0">
<tr>
<td>Start Date</td>
<td><input type=text onfocus="showCalendarControl(this);" name=date /></td>
<td rowspan="2"><input type=submit value=Generate /></td>
</tr><tr>
<td>End Date</td>
<td><input type=text onfocus="showCalendarControl(this);" name=date2 /></td>
</tr>
</table>
</form>

<?php
if (isset($_GET['date'])){
  $date = $_GET['date'];
  $date2 = $_GET['date2'];
  $dlog = select_dlog($date,$date2);

  $q = $dbc->prepare_statement("select emp_no,sum(-total),count(*)/2,
	year(tdate),month(tdate),day(tdate)
        from $dlog as d where
	tdate BETWEEN ? AND ?
	AND trans_type='T'
	GROUP BY year(tdate),month(tdate),day(tdate),emp_no
	ORDER BY sum(-total) DESC");
  $r = $dbc->exec_statement($q,array($date.' 00:00:00',$date2.' 23:59:59'));
?>
<table cellspacing="0" border="1" cellpadding="4"><tr><th>Emp#</th><th>Date</th><th>$</th><th># of Trans (approx)</th></tr>
<?php
  while($row = $dbc->fetch_array($r)){
    echo "<tr>";
    printf('<td>%d</td><td>%d/%d/%d</td><td>%.2f</td><td>%d</td>',
	$row['emp_no'],$row[4],$row[5],$row[3],$row[1],$row[2]);
    echo "</tr>";
  }
  echo "</table>";
}
include($FANNIE_ROOT.'src/footer.html');
?>
