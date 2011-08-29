<?php
include('../../../config.php');

require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('upload_hours_data')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/upload.php");
	return;
}

require($FANNIE_ROOT.'src/csv_parser.php');
require('db.php');
$db = hours_dbconnect();

$ADP_COL = 0;
$HOURS_COL = 1;

$colors = array("one","two");

echo "<html>
<head><title>Upload Data</title>
<style type=text/css>
.one {
	background: #ffffff;
}
.one td {
	text-align: right;
}
.two {
	background: #ffffcc;
}
.two td {
	text-align: right;
}
</style>
</head>
<body bgcolor=#cccccc>";

if (isset($_POST["MAX_FILE_SIZE"])){
	$filename = md5(time());
	move_uploaded_file($_FILES['upload']['tmp_name'],"tmp/$filename");
	
	$pp = $_POST["ppID"];

	$fp = fopen("tmp/$filename","r");
	$c = 1;
	echo "<form action=importUTO.php method=post>";
	echo "<input type=hidden name=pp value=\"$pp\" />";
	echo "<table cellpadding=4 cellspacing=0 border=1>";
	echo "<tr class=one><th>ADP ID</th><th>UTO Hours</th></tr>";
	while (!feof($fp)){
		$line = fgets($fp);

		$fields = csv_parser($line);
		if (count($fields) == 0) continue;

		$adpID = $fields[$ADP_COL];
		if (!is_numeric($adpID)) continue;

		$checkQ = "select empID from employees where adpID=$adpID";
		$checkR = $db->query($checkQ);
		if ($db->num_rows($checkR) < 1){
			echo "Notice: ADP ID #$adpID doesn't match any current employee.";
			echo "Data for this ID is being omitted.<br />";
			continue;
		}

		$hours = $fields[$HOURS_COL];
		if ($hours == "") $hours = 0;
		
		echo "<tr class=$colors[$c]>";
		echo "<td>$adpID</td><td>$hours</td>";
		echo "</tr>";

		echo "<input type=hidden name=data[] value=\"$adpID,$hours\" />";
		
		$c = ($c+1)%2;
	}
	echo "</table>";
	echo "<input type=submit value=\"Import Data\">";
	
	fclose($fp);
	unlink("tmp/$filename");
	return;	
}
elseif (isset($_POST["data"])){
	$datalines = $_POST["data"];
	$ppID = $_POST["pp"];
	
	foreach ($datalines as $line){
		$fields = explode(",",$line);
		$eIDQ = "select empID from employees where adpID=$fields[0]";
		$eIDR = $db->query($eIDQ);
		if ($db->num_rows($eIDR) < 1) continue;
		$empID = array_pop($db->fetch_row($eIDR));

		$upQ = "update ImportedHoursData set UTOHours=$fields[1] where empID=$empID and periodID=$ppID LIMIT 1";
		$upR = $db->query($upQ);
	}

	echo "UTO data import complete!<br />";
	echo "<a href=list.php>View Employees</a><br />";
	echo "<a href=pps.php>View Pay Periods</a>";
	
	return;
}

?>

<form enctype="multipart/form-data" action="importUTO.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Pay Period: <select name=ppID>
<?php
$ppQ = "select dateStr,periodID from payperiods order by periodID desc";
$ppR = $db->query($ppQ);
while($ppW = $db->fetch_row($ppR)) echo "<option value=$ppW[1]>$ppW[0]</option>";
?>
</select><p />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>

</body>
</html>
