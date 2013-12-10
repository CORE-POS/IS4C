<?php

include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtMenuPage.php');
exit;

/*
require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('view_all_hours')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/menu.php");
	return;
}

?>

<html>
<head><title>Menu</title>
<style type=text/css>
a { color: blue; }
</style>
</head>
<body>
<ul>
<li><a href=list.php>View Employees</a></li>
<li><a href=pps.php>View Pay Periods</a></li>
<li><a href=report.php>Hours worked report</a></li>
<br />
<li><a href=upload.php>Upload ADP Data</a></li>
<li><a href=importUTO.php>Import UTO Data</a></li>
<li><a href=salaryPTO.php>Update Salary PTO</a></li>
<br />
<li><a href=importWeekly.php>Import Weekly Hours</a></li>
<li><a href=weeklyReport.php>View Weekly Hours</a></li>
<br />
<li><a href=sync.php>Import New Employees</a></li>
</ul>
</body>
</html>
*/
