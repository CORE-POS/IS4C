<?php

include('../../../config.php');
require('db.php');
$sql = db_connect();

$positionQ = "select positionID,name from positions order by case when name='Any' then '' else name end";
$positionR = $sql->query($positionQ);
$positions = array();
$openings = array();
while($positionW = $sql->fetch_row($positionR)){
	$positions["$positionW[0]"] = array(False,$positionW[1]);
	$openings["$positionW[0]"] = array(False,$positionW[1]);
}

$deptQ = "select deptID,name from departments order by name";
$deptR = $sql->query($deptQ);
$depts = array();
while ($deptW = $sql->fetch_row($deptR))
	$depts["$deptW[0]"] = array(False,$deptW[1]);

$today = date("Y-m-d");
$fname = "";
$lname = "";
$pc_date = date("Y-m-d");
$internal = 0;
$bestpractices = 0;
$referral = "";
$hired = 0;

$ERRORS = "";

$appID = -1;
if (isset($_GET["appID"]) || isset($_POST["appID"])){
	if (isset($_POST["appID"])) $appID = $_POST["appID"];
	else $appID = $_GET["appID"];

	if (isset($_POST["submit"])){
		if (isset($_POST["appDate"])) $today = $_POST["appDate"];
		if (isset($_POST["fname"])) $fname = $_POST["fname"];
		if (isset($_POST["lname"])) $lname = $_POST["lname"];
		foreach($_POST["applied_for"] as $p) $positions["$p"][0] = True;
		foreach($_POST["openings"] as $p) $openings["$p"][0] = True;
		foreach($_POST["sent_to"] as $p) $depts["$p"][0] = True;
		if (isset($_POST["postcard_date"])) $pc_date = $_POST["postcard_date"];
		if (isset($_POST["internal"])) $internal = 1;
		if (isset($_POST["bestpractices"])) $bestpractices = 1;
		if (isset($_POST["referral"])) $referral = $_POST["referral"];
		if (isset($_POST["hired"])) $hired = 1;

		if ($pc_date == '') $pc_date = "NULL";
		else $pc_date = "'$pc_date'";

		$applyStr = "";
		foreach($positions as $k=>$v){
			if ($v[0]) $applyStr .= $k.",";
		}
		$applyStr = substr($applyStr,0,strlen($applyStr)-1);

		$openStr = "";
		foreach($openings as $k=>$v){
			if ($v[0]) $openStr .= $k.",";
		}
		$openStr = substr($openStr,0,strlen($openStr)-1);

		$deptStr = "";
		foreach($depts as $k=>$v){
			if ($v[0]) $deptStr .= $k.",";
		}
		$deptStr = substr($deptStr,0,strlen($deptStr)-1);

		if ($appID == "-1"){
			$insQ = "INSERT INTO applicants (first_name,last_name,app_date,postcard_date,internal,
				best_practices,positions,openings,sent_to,referral,hired) VALUES (?, ?,
				?,?,?,?,?,?,?,?,?)");
			$sql->execute($insQ, array($fname, $lname, $today, $pc_date, $internal, $bestpractices, $applyStr, $openStr, $deptStr, $referral, $hired));
		}
		else {
			$upQ = $sql->prepare("UPDATE applicants SET
				first_name=?,
				last_name=?,
				app_date=?,
				postcard_date=?,
				internal=?,
				best_practices=?,
				positions=?,
				openings=?,
				sent_to=?,
				referral=?,
				hired=?
				WHERE appID=?");
			echo $upQ;
			$sql->execute($upQ, array($fname, $lname, $today, $pc_date, $internal, $bestpractices, $applyStr, $openStr, $deptStr, $referral, $hired, $appID));
		}
	}

	$dataQ = $sql->prepare("SELECT * FROM applicants WHERE appID=?");
	$dataR = $sql->execute($dataQ, array($appID));
	if ($sql->num_rows($dataR) == 0 && $appID != -1)
		$ERRORS .= "Warning: No data found for applicant #$appID<br />";
	else if ($appID != -1){
		$dataW = $sql->fetch_row($dataR);
		$fname = $dataW[1];
		$lname = $dataW[2];
		$today = $dataW[3];
		$pc_date = $dataW[4];
		$internal = $dataW[5];
		$bestpractices = $dataW[6];
		foreach(explode(",",$dataW[7]) as $i) $positions["$i"][0] = True;
		foreach(explode(",",$dataW[8]) as $i) $openings["$i"][0] = True;
		foreach(explode(",",$dataW[9]) as $i) $depts["$i"][0] = True;
		$referral = $dataW[10];
		$hired = $dataW[11];
	}
}

?>

<html>
<head>
	<title>Edit applicant</title>
</head>
<body>
<?php echo $ERRORS ?>
<form method=post action=newApp.php>
<table cellspacing=0 cellpadding=4 border=1>
<tr>
	<th>Application Date</th>
	<td colspan=3><input type=text name=appDate value="<?php echo $today ?>" /></td>
</tr>
<tr>
	<th>First Name</th>
	<td><input type=text name=fname value="<?php echo $fname?>" /></td>
	<th>Last Name</th>
	<td><input type=text name=lname value="<?php echo $lname?>"/></td>
</tr>
<tr>
	<th valign=top>Positions applied for</th>
	<td>
	<?php foreach ($positions as $k=>$v) { 
		echo "<input type=checkbox name=applied_for[] value=$k ";
		if ($v[0]) echo "checked ";
		echo "/> $v[1]<br />"; 
	} ?>
	</td>
	<th valign=top>Curent openings</th>
	<td>
	<?php foreach ($openings as $k=>$v) { 
		echo "<input type=checkbox name=openings[] value=$k ";
		if ($v[0]) echo "checked ";
		echo "/> $v[1]<br />"; 
	} ?>
	</td>
</tr>
<tr>
	<th valign=top rowspan=3>Forwarded to<br />department managers</th>
	<td rowspan=3>
	<?php foreach ($depts as $k=>$v) { 
		echo "<input type=checkbox name=sent_to[] value=$k ";
		if ($v[0]) echo "checked ";
		echo "/> $v[1]<br />"; 
	} ?>
	</td>
	<th>Postcard sent</th>
	<td><input type=text name=postcard_date value="<?php echo $pc_date ?>" /></td>
</tr>
<tr>
	<th>Internal</th>
	<td><input type=checkbox name=internal <?php echo ($internal==1)?'checked':''?> /></td>
</tr>
<tr>
	<th>Best Practices</th>
	<td><input type=checkbox name=bestpractices <?php echo ($bestpractices==1)?'checked':''?> /></td>
</tr>
<tr>
	<th>Referral</th>
	<td><input type=text name=referral value="<?php echo $referral?>" /></td>
	<th>Hired</th>
	<td><input type=checkbox name=hired <?php echo ($hired==1)?'checked':''?> /></td>
</tr>
</table>
<input type=hidden name=appID value="<?php echo $appID?>" />
<input type=submit value="<?php echo ($appID==-1)?'Add applicant':'Edit applicant'; ?>" name=submit />
<input type=submit value="Cancel &amp; Go Back" onclick="top.location='list.php'; return false;" />
</form>

</body>
