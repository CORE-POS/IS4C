<?php
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="projectedHours.xls"');

include('../../../config.php');

require("db.php");

$sql = hours_dbconnect();

$query = "SELECT e.empID,avg(hours+OTHours+SecondRateHours)
	FROM Employees as e LEFT JOIN ImportedHoursData as i
	on e.empID=i.empID WHERE i.periodID in (28,27)
	group by e.empID";
$result = $sql->query($query);
$avgs = array();
while($row = $sql->fetch_row($result)){
	$avgs["$row[0]"] = $row[1];
}

$query = "SELECT e.empID,e.name,h.totalHours FROM
	Employees as e LEFT JOIN hoursalltime AS h
	on e.empID=h.empID
	WHERE e.deleted = 0 and h.totalHours > 0
	ORDER BY e.name";
$result = $sql->query($query);

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Name</th><th>Hours</th><th>4/1</th><th>6/1</th></tr>";
while($row = $sql->fetch_row($result)){
	$avg = $avgs["$row[0]"];
	printf("<tr><td>%s</td><td>%.2f</td>
		<td>%.2f</td><td>%.2f</td></tr>",
		$row[1],$row[2],($row[2]+(2*$avg)),
		($row[2]+(6*$avg)));
}
echo "</table>";

?>
