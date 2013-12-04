<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/reports/WfcHtReport.php');
exit;
/*
require($FANNIE_ROOT.'auth/login.php');
$ALL = validateUserQuiet('view_all_hours');
if (!$ALL){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/report.php");
	return;
}

require('db.php');
$sql = hours_dbconnect();

$periods = "";
$periodQ = "SELECT periodID,dateStr from PayPeriods ORDER BY periodID desc";
$periodR = $sql->query($periodQ);
while($periodW = $sql->fetch_row($periodR))
	$periods .= "<option value=$periodW[0]>$periodW[1]</option>";

if (isset($_GET["startPeriod"])){
	$sp = $_GET["startPeriod"];
	$ep = $_GET["endPeriod"];

	$query = "SELECT e.name,e.adpID,sum(i.hours),sum(i.OTHours),
			sum(i.SecondRateHours),
			sum(i.PTOHours),
			sum(i.UTOHours),
			sum(i.hours+i.OTHours+i.SecondRateHours)
			FROM ImportedHoursData as i
			LEFT JOIN employees as e
			ON i.empID = e.empID
			WHERE (i.periodID BETWEEN $sp AND $ep
			OR i.periodID BETWEEN $ep AND $sp)
			AND e.deleted = 0
			GROUP BY i.empID,e.name,e.adpID
			ORDER BY e.name";
	$result = $sql->query($query);

	if (isset($_GET['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="hoursWorked.xls"');
	}
	else
		echo "<a href=report.php?startPeriod=$sp&endPeriod=$ep&excel=yes>Save to Excel</a><p />";

	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>Name</th><th>ADP ID#</th><th>Reg. Hours</th>";
	echo "<th>OT Hours</th><th>Alt. Rate</th><th>PTO</th><th>UTO</th><th>Total</th></tr>";
	$colors = array("#ffffcc","#ffffff");
	$c = 0;
	while($row = $sql->fetch_row($result)){
		echo "<tr>";
		for($i=0;$i<8;$i++)
			echo "<td bgcolor=$colors[$c]>$row[$i]</td>";
		echo "</tr>";
		$c = ($c+1)%2;
	}
	echo "</table>";

}
else {
?>
<html><head>Hours worked report</head>
<body>

<form method=get action=report.php>
<table><tr>
<td>
<b>Starting pay period</b>:
</td><td>
<select name=startPeriod><?php echo $periods; ?></select>
</td></tr>
<tr><td>
<b>Ending pay period</b>:
</td><td>
<select name=endPeriod><?php echo $periods; ?></select>
</td></tr>
</table>
<input type=submit value=Submit />
<input type=checkbox name=excel /> Excel
</form>

</body></html>
<?php
}
?>
*/
