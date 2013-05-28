<?php

require('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$DAYS = array("","Sun","Mon","Tue","Wed","Thu","Fri","Sat");

$subdeptsQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames ORDER BY super_name");
$subdeptsR = $dbc->exec_statement($subdeptsQ);
$options = "<option value=-1>All</option>";
while($subW = $dbc->fetch_row($subdeptsR))
	$options .= "<option value=$subW[0]>$subW[1]</option>";

if (!isset($_REQUEST['date1'])){
	$page_title = "Fannie : Transactions by Hour";
	$header = "Transactions by Hour";
	include($FANNIE_ROOT.'src/header.html');
?>
<link href="<?php echo $FANNIE_URL; ?>src/CalendarControl.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>

<form action="hourlyTrans.php" method="post">
<table>
<tr>
<th>Department</th><td colspan=3><select name=sub><?php echo $options; ?></select></td>
</tr><tr>
<th>Start</th><td><input type=text name=date1 onclick="showCalendarControl(this);" /></td>
<th>End</th><td><input type=text name=date2 onclick="showCalendarControl(this);" /></td>
</tr><tr>
<td colspan=2><input type=checkbox name=excel /> <b>Excel</b></td>
<td colspan=2><input type=checkbox name=group /> <b>Group by week day</b></td>
</tr><tr>
<td colspan=4><input type=submit value="Get Report" /></td>
</tr>
</table>
</form>
<?php
	include($FANNIE_ROOT.'src/footer.html');
}
else {
	if (isset($_REQUEST['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="hourlyTrans.xls"');
	}

	$date1 = $_REQUEST['date1'];
	$date2 = $_REQUEST['date2'];

	include($FANNIE_ROOT.'src/select_dlog.php');
	$dlog = select_dlog($date1,$date2);

	$group = "year(tdate),month(tdate),day(tdate),".$dbc->hour('tdate');
	$args = array($date1.' 00:00:00',$date2.' 23:59:59');

	$query = "select 
		$group,
		min(".$dbc->dayofweek('tdate')."),
		count(distinct trans_num),
		sum(case when trans_type in ('I','D') then total else 0 end)	
		from $dlog as d
		WHERE tdate BETWEEN ? AND ?
		group by $group
		order by $group";
	if ($_REQUEST['sub'] != -1){
		$query = "select 
			$group,
			min(".$dbc->dayofweek('tdate').") as day,
			count(distinct trans_num),
			sum(case when trans_type in ('I','D') then total else 0 end)	
			from $dlog as d LEFT JOIN
			departments as t on d.department = t.dept_no
			left join superdepts AS s ON t.dept_no=s.dept_ID
			WHERE tdate BETWEEN ? AND ?
			AND s.superID = ?
			group by $group
			order by $group";
		$args[] = $_REQUEST['sub'];
	}
	$prep = $dbc->prepare_statement($query);
	$result = $dbc->exec_statement($prep,$args);

	echo "<b>Transactions from $date1 to $date2</b>";
	if (isset($_REQUEST['group']))
		echo "<br />Grouped by week day";
	if ($_REQUEST['sub'] != -1)
		echo "<br />For subdepartment #".$_REQUEST['sub'];

	if (!isset($_REQUEST['group'])){
		echo "<table cellspacing=0 cellpadding=4 border=1>
			<tr><th>Date</th><th>Hour</th><th>Day</th><th># Trans</th><th>$</t></tr>";
		while($row = $dbc->fetch_row($result)){
			printf("<tr><td>%d/%d/%d</td><td>%s</td><td>%s</td>
				<td>%d</td><td>%.2f</td></tr>",
				$row[1],$row[2],$row[0],$row[3],$DAYS[$row[4]],$row[5],$row[6]);
		}
		echo "</table>";
	}
	else {
		$buckets = array();
		while($row = $dbc->fetch_row($result)){
			if (!isset($buckets[$row[4]])){
				$buckets[$row[4]] = array();
				$buckets[$row[4]]['trans'] = array();
				$buckets[$row[4]]['sales'] = array();
			}
			
			if (!isset($buckets[$row[4]]['trans'][$row[3]]))
				$buckets[$row[4]]['trans'][$row[3]] = 0;
			$buckets[$row[4]]['trans'][$row[3]] += $row[5];

			if (!isset($buckets[$row[4]]['sales'][$row[3]]))
				$buckets[$row[4]]['sales'][$row[3]] = 0;
			$buckets[$row[4]]['sales'][$row[3]] += $row[5];
		}
		echo "<table cellspacing=0 cellpadding=4 border=1>
			<tr><th>Day</th><th>Hour</th><th># Trans</th><th>$</t></tr>";
		foreach($buckets as $day => $data){
			foreach(array_keys($data['trans']) as $hour){
				printf("<tr><td>%s</td><td>%s</td><td>%d</td><td>%.2f</td></tr>",
					$DAYS[$day],$hour,$data['trans'][$hour],$data['sales'][$hour]);
			}
		}
		echo "</table>";
	}
}

?>
