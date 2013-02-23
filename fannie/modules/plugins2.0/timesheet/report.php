<?php
include('../../../src/header.php');
include('./includes/header.html');

echo "<form action='report.php' method=GET>";

$currentQ = "SELECT periodID FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
$currentR = mysql_query($currentQ);
list($ID) = mysql_fetch_row($currentR);

$query = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodStart < now() ORDER BY periodID DESC";
$result = mysql_query($query);

echo '<p>Starting Pay Period: <select name="period">
    <option>Please select a starting pay period.</option>';

while ($row = mysql_fetch_array($result)) {
    echo "<option value=\"" . $row['periodID'] . "\"";
    if ($row['periodID'] == $ID) { echo ' SELECTED';}
    echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
}

echo '</select><br /><button value="export" name="Export">Export</button></p></form>';

if ($_GET['Export'] == 'export') {
	$periodID = $_GET['period'];
	
	$query = "SELECT s.ShiftID as id, IF(s.NiceName='', s.ShiftName, s.NiceName) as area
		FROM (SELECT ShiftID, NiceName, ShiftName, ShiftOrder FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts WHERE visible = 1) s 
		GROUP BY s.ShiftID ORDER BY s.ShiftOrder";
	// echo $query;
	$result = mysql_query($query);
	echo "<table cellpadding='5'><thead>\n<tr>
		<th>ID</th><th>Area</th><th>Total</th><th>wages</th></tr></thead>\n<tbody>\n";
	while ($row = mysql_fetch_assoc($result)) {

		echo "<tr><td>".$row['id']."</td><td>".$row['area']."</td><td align='right'>";
		
		$query1 = "SELECT SUM(IF(".$row['id']." = 31, t.vacation,t.hours)) as total FROM ". $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t WHERE t.periodID = $periodID AND t.area = " . $row['id'];
		// echo $query1;
		$result1 = mysql_query($query1);
		$totHrs = mysql_fetch_row($result1);
		$tot = ($totHrs[0]) ? $totHrs[0] : 0;
		
		echo $tot . "</td>";
				
		$query2 = "SELECT SUM(e.pay_rate) as agg FROM ".$FANNIE_OP_DB.".employees e, ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t WHERE t.emp_no = e.emp_no AND t.periodID = $periodID AND t.area = " . $row['id'];
		// echo $query2;
		$result2 = mysql_query($query2);
		$totAgg = mysql_fetch_row($result2);
		$agg = ($totAgg[0]) ? $totAgg[0] : 0;
		
		$wages = $tot * $agg;
				
		echo "<td align='right'>" . money_format('%#8n', $wages) . "</td></tr>\n";
	}
	echo "</tbody></table>\n";
}




include('../../../src/footer.php');

?>
