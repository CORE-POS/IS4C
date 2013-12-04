<?php

include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtListPage.php');
exit;
/*
require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('edit_employees')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/editEmployee.php?id=".$_GET["id"]);
	return;
}

require('db.php');
$db = hours_dbconnect();

$empID = $_GET["id"];
if (!is_numeric($empID)){
	echo "<b>Error: no employee ID specified</b>";
	return;
}

echo "<html><head><title>Edit</title>";

echo "</head><body bgcolor=#bbbbbb>";
$fetchQ = "select adpid,name,department from employees where empID=$empID";
$fetchR = $db->query($fetchQ);
$fetchW = $db->fetch_row($fetchR);
echo "<form action=list.php method=post>";
echo "<input type=hidden name=action value=update />";
echo "<input type=hidden name=id value=$empID />";
echo "<table cellspacing=4 cellpadding=0>";
echo "<tr><th>ADP ID#</th><td><input type=text name=adpid value=\"$fetchW[0]\" /></td></tr>";
echo "<tr><th>Name</th><td><input type=text name=name value=\"$fetchW[1]\" /></td></tr>";
echo "<tr><th>Department</th><td>";
$deptsQ = "select name,deptID from Departments order by name";
$deptsR = $db->query($deptsQ);
echo "<select name=dept>";
echo "<option value=\"\"></option>";
while ($deptsW = $db->fetch_row($deptsR)){
	if ($deptsW[1] == $fetchW[2])
		echo "<option value=$deptsW[1] selected>$deptsW[0]</option>";
	else
		echo "<option value=$deptsW[1]>$deptsW[0]</option>";
}
echo "</select>";
echo "</td></tr>";
echo "<tr><td><input type=submit value=\"Save Changes\" /></td>";
echo "<td><input type=submit value=Cancel onclick=\"window.location = 'list.php'; return false;\" /></td></tr>";
echo "</table>";
echo "</form>";
echo "</body></html>";

?>
*/
