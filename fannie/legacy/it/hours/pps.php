<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtPayPeriodsPage.php');
exit;
/*
require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('view_all_hours')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/pps.php");
	return;
}

require('db.php');
$db = hours_dbconnect();

$ppID = -1;
if (isset($_GET["id"]))
	$ppID = $_GET["id"];
$order = "e.name";
if (isset($_GET["order"]))
	$order = $_GET["order"];
$dir = "asc";
if (isset($_GET["dir"]))
	$dir = $_GET["dir"];
$otherdir = "desc";
if ($dir == "desc") $otherdir = "asc";

echo "<html><head><title>Pay Periods</title>
<style type=text/css>
.one {
	background: #ffffff;
}
.two {
	background: #ffffcc;
}
a {
	color: #0000ff;
}
</style>
</head><body>";

echo "<select onchange=\"top.location='pps.php?id='+this.value;\">";
$ppQ = "select periodID,dateStr from PayPeriods order by periodID desc";
$ppR = $db->query($ppQ);
while ($ppW = $db->fetch_row($ppR)){
	echo "<option value=$ppW[0]";
	if ($ppW[0] == $ppID) echo " selected";
	if ($ppID == -1) $ppID=$ppW[0];
	echo ">$ppW[1]</option>";
}
echo "</select>";

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr>";
if ($order == "e.name")
	echo "<th><a href=pps.php?id=$ppID&order=e.name&dir=$otherdir>Name</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=e.name&dir=asc>Name</a></th>";
if ($order == "e.adpid")
	echo "<th><a href=pps.php?id=$ppID&order=e.adpid&dir=$otherdir>ADP ID</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=e.adpid&dir=asc>ADP ID</a></th>";
if ($order == "i.hours")
	echo "<th><a href=pps.php?id=$ppID&order=i.hours&dir=$otherdir>Reg. Hours</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=i.hours&dir=asc>Reg. Hours</a></th>";
if ($order == "i.othours")
	echo "<th><a href=pps.php?id=$ppID&order=i.othours&dir=$otherdir>OT Hours</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=i.othours&dir=asc>OT Hours</a></th>";
if ($order == "i.ptohours")
	echo "<th><a href=pps.php?id=$ppID&order=i.ptohours&dir=$otherdir>PTO Hours</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=i.ptohours&dir=asc>PTO Hours</a></th>";
if ($order == "i.emergencyhours")
	echo "<th><a href=pps.php?id=$ppID&order=i.emergencyhours&dir=$otherdir>Emerg. Hours</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=i.emergencyhours&dir=asc>Emerg. Hours</a></th>";
if ($order == "i.secondratehours")
	echo "<th><a href=pps.php?id=$ppID&order=i.secondratehours&dir=$otherdir>Alt. Hours</a></th>";
else
	echo "<th><a href=pps.php?id=$ppID&order=i.secondratehours&dir=asc>Alt. Hours</a></th>";
echo "</tr>";
$dataQ = "select e.name,e.adpid,i.hours,i.othours,i.ptohours,i.emergencyhours,i.secondratehours
	from ImportedHoursData as i left join employees as e on i.empID=e.empID
	where periodID=$ppID
	order by $order $dir";
$dataR = $db->query($dataQ);
$class = array("one","two");
$c = 1;
while ($dataW = $db->fetch_row($dataR)){
	echo "<tr class=$class[$c]>";

	echo "<td>$dataW[0]</td>";
	echo "<td>$dataW[1]</td>";
	echo "<td>$dataW[2]</td>";
	echo "<td>$dataW[3]</td>";
	echo "<td>$dataW[4]</td>";
	echo "<td>$dataW[5]</td>";
	echo "<td>$dataW[6]</td>";

	echo "</tr>";
	$c = ($c+1)%2;
}
echo "</table>";

echo "</body></html>";

?>
*/
