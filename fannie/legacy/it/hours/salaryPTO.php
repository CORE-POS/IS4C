<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtSalaryUploadPage.php');
exit;
/*
include('db.php');
$sql = hours_dbconnect();

if (isset($_POST["month"])){
	$ids = $_POST['ids'];
	$days = $_POST['days'];
	$datestamp = $_POST['year']."-".str_pad($_POST['month'],2,'0',STR_PAD_LEFT)."-01";
	for ($i=0; $i < count($ids); $i++){
		$insQ = "INSERT INTO salaryHours VALUES ($ids[$i],'$datestamp',$days[$i])";
		echo $insQ."<br />";
		$sql->query($insQ);
	}
	echo "Salary PTO added";
}
else {
	echo "<form action=salaryPTO.php method=post>";
	$fetchQ = "select empID,name from employees where department IN (999 ,998)
		and deleted=0 order by name";
	$fetchR = $sql->query($fetchQ);
	echo "<table cellpadding=4 cellspacing=0 border=1>";
	echo "<tr><th>Employee</th><th>Days taken</th></tr>";
	while($fetchW = $sql->fetch_row($fetchR)){
		echo "<tr><td>$fetchW[1]</td>";
		echo "<td><input type=text name=days[] size=4 value=0 /></td>";
		echo "<input type=hidden name=ids[] value=$fetchW[0] /></tr>";
	}
	echo "<tr><th>Month</th><th>Year</th></tr>";
	echo "<tr><td><select name=month>";
	for ($i=1;$i<=12;$i++){
		$stamp = mktime(0,0,0,$i,1);
		$mname = date('F',$stamp);
		echo "<option value=$i>$mname</option>";
	}
	echo "</select></td><td>";
	echo "<input type=text size=4 name=year value=".date("Y")." /></td></tr>";
	echo "</table>";
	echo "<br />";
	echo "<input type=submit value=Submit />";
	echo "</form>";
}
?>
*/
