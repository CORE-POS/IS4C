<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtUploadPage.php');
exit;
/*
require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('upload_hours_data')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/upload.php");
	return;
}

require($FANNIE_ROOT.'src/csv_parser.php');
require('db.php');
$db = hours_dbconnect();

$ADP_COL = 3;
$HOURS_COL = 6;
$TYPE_COL = 5;
$ALT_COL = 4;
$HEADERS = true;

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
	
	$pp = $_POST["pp"];

	$fp = fopen("tmp/$filename","r");
	$c = 1;
	echo "<form action=upload.php method=post>";
	echo "<b>Pay Period</b>: $pp<br />";
	echo "<input type=hidden name=pp value=\"$pp\" />";
	echo "<table cellpadding=4 cellspacing=0 border=1>";
	echo "<tr class=one><th>ADP ID</th><th>Reg. Hours</th><th>OT Hours</th>";
	echo "<th>PTO</th><th>UTO</th><th>Alt. Rate</th><th>Holiday</th></tr>";
	$rows = array();
	while (!feof($fp)){
		$line = fgets($fp);
		if ($HEADERS){
			$HEADERS = false;
			continue;
		}
		$fields = csv_parser($line);
		if (count($fields) == 0) continue;
		if (!isset($fields[$ADP_COL])) continue;

		$adpID = ltrim($fields[$ADP_COL],"U8U");
		if (!isset($rows[$adpID])){
			$rows[$adpID] = array(
				"regular"=>0.0,
				"overtime"=>0.0,
				"pto"=>0.0,
				"uto"=>0.0,
				"alt"=>0.0,
				"holiday"=>0.0
			);
		}

		$checkQ = "select empID from employees where adpID=$adpID";
		$checkR = $db->query($checkQ);
		if ($db->num_rows($checkR) < 1){
			echo "Notice: ADP ID #$adpID doesn't match any current employee.";
			echo "Data for this ID is being omitted.<br />";
			foreach($fields as $f) echo $f.' ';
			echo '<hr />';
			continue;
		}

		$hours = 0;
		if (is_numeric($fields[$HOURS_COL]))
			$hours = $fields[$HOURS_COL];

		switch(strtoupper($fields[$TYPE_COL])){
		case 'REGLAR':
			if (substr($fields[$ALT_COL],-1)=="0")
				$rows[$adpID]['regular'] += $hours;	
			else
				$rows[$adpID]['alt'] += $hours;
			break;
		case 'REGRT2':
			$rows[$adpID]['alt'] += $hours;
			break;
		case 'OVTIME':
			$rows[$adpID]['overtime'] += $hours;
			break;
		case 'PERSNL':
			$rows[$adpID]['pto'] += $hours;
			break;
		case 'UTO':
			$rows[$adpID]['uto'] += $hours;
			break;
		case 'WRKHOL':
			$rows[$adpID]['regular'] += $hours;
			break;
		case 'HOLDAY':
			$rows[$adpID]['holiday'] += $hours;
			break;
		default:
			echo "Unknown type: ".$fields[$TYPE_COL]."<br />";
		}	
		
	}

	foreach($rows as $adpID => $row){
		echo "<tr class=$colors[$c]>";
		echo "<td>$adpID</td><td>{$row['regular']}</td><td>{$row['overtime']}</td>";
		echo "<td>{$row['pto']}</td><td>{$row['uto']}</td><td>{$row['alt']}</td>";
		echo "<td>{$row['holiday']}</td>";
		echo "</tr>";

		printf("<input type=hidden name=data[] value=\"%d,%f,%f,%f,%f,%f,%f\" />",
			$adpID,$row['regular'],$row['overtime'],$row['pto'],
			$row['uto'],$row['alt'],$row['holiday']
		);
		
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
	$pp = $_POST["pp"];
	
	$ppIDQ = "select max(periodID)+1 from PayPeriods";
	$ppIDR = $db->query($ppIDQ);
	$ppID = array_pop($db->fetch_row($ppIDR));

	$ppQ = "INSERT INTO PayPeriods VALUES ($ppID,'$pp',YEAR(CURDATE()))";
	$ppR = $db->query($ppQ);

	foreach ($datalines as $line){
		$fields = explode(",",$line);
		$eIDQ = "select empID from employees where adpID=$fields[0]";
		$eIDR = $db->query($eIDQ);
		if ($db->num_rows($eIDR) < 1) continue;
		$empID = array_pop($db->fetch_row($eIDR));

		$insQ = "INSERT INTO ImportedHoursData VALUES ($empID,$ppID,year(curdate()),
			$fields[1],$fields[2],$fields[3],0,$fields[5],$fields[6],$fields[4])";
		$insR = $db->query($insQ);
	}

	$cuspQ = "UPDATE cusping as c left join employees as e
		on c.empID = e.empID
		SET e.PTOLevel=e.PTOLevel+1, e.PTOCutoff=$ppID
		where c.cusp = '!!!'";
	$cuspR = $db->query($cuspQ);

	echo "ADP data import complete!<br />";
	echo "<a href=list.php>View Employees</a><br />";
	echo "<a href=pps.php>View Pay Periods</a>";
	
	return;
}

?>

<form enctype="multipart/form-data" action="upload.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Pay Period: <input type=text name=pp /><p />
Holiday Hours: <select name=asHoliday><option value=1>As Holiday</option><option value=0>As Hours Worked</option>
</select><p />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>

</body>
</html>
*/
