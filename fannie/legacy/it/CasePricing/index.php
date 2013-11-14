<?php
include('../../../config.php');
require($FANNIE_ROOT.'auth/login.php');
require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/tmp_dir.php');

// 04Oct13 - no longer in use?
return;

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");

include('../../db.php');
$ms = $sql;
include($FANNIE_ROOT.'src/Credentials/OutsideDB.data1.wfc.php');

if (isset($_POST["MAX_FILE_SIZE"])){
	$fn = sys_get_temp_dir()."/cases.csv";
	move_uploaded_file($_FILES['upload']['tmp_name'],$fn);

	$fp = fopen($fn,"r");
	echo "<b>Data to import</b><br />";
	echo "<table cellspacing=0 cellpadding=3 border=1><tr>";
	echo "<th>Likecode</th><th>Description</th><th>Status</th>";
	echo "<th>Price</th><th>Local</th><th>Supplier</th>";
	echo "<th>Origin</th><th colspan=2>Case Size</th><th>Case Price</th>";
	echo "<form action=index.php method=post>";
	while (!feof($fp)){
		$line = fgets($fp);
		$data = csv_parser($line);

		if (!is_numeric($data[0])) continue;
		
		echo "<tr>";
		printf("<td>%s</td><input type=hidden name=lc[] value=\"%s\" />", $data[0],$data[0]);
		printf("<td>%s</td><input type=hidden name=desc[] value=\"%s\" />", $data[1],$data[1]);
		printf("<td>%s</td><input type=hidden name=status[] value=\"%s\" />", $data[2],$data[2]);
		$data[3] = ltrim($data[3],"$");
		printf("<td>%s</td><input type=hidden name=prices[] value=\"%s\" />", $data[3],$data[3]);
		printf("<td>%s</td><input type=hidden name=local[] value=\"%s\" />", $data[4],$data[4]);
		printf("<td>%s</td><input type=hidden name=dist[] value=\"%s\" />", $data[5],$data[5]);
		printf("<td>%s</td><input type=hidden name=origins[] value=\"%s\" />", $data[6],$data[6]);
		printf("<td>%s</td><input type=hidden name=caseq1[] value=\"%s\" />", $data[7],$data[7]);
		printf("<td>%s</td><input type=hidden name=caseq2[] value=\"%s\" />", $data[8],$data[8]);
		printf("<td>%s</td><input type=hidden name=caseprices[] value=\"%s\" />", $data[9],$data[9]);
	}
	echo "</table>";
	echo "<input type=submit value=Submit /></form>";

	fclose($fp);
	unlink($fn);
}
else if (isset($_POST['lc'])){
	$lcs = $_POST["lc"];
	$descs = $_POST["descs"];
	$status = $_POST["status"];
	$prices = $_POST["prices"];
	$local = $_POST["local"];
	$dist = $_POST["dist"];
	$origins = $_POST["origins"];
	$caseq1 = $_POST["caseq1"];
	$caseq2 = $_POST["caseq2"];
	$caseprices = $_POST["caseprices"];

	// remove current case pricing
	$q = "UPDATE prodExtra SET case_quantity='',case_cost=0, case_info=''
		WHERE upc not in ('00000000899991','0000000089992','0000000089993')";
	$ms->query($q);
	$q = "UPDATE prodExtra SET case_qty='',case_price=0, case_info=''
		WHERE upc not in ('00000000899991','0000000089992','0000000089993')";
	$sql->query($q);

	for ($i=0;$i<count($lcs);$i++){
		$upcR = $ms->query("SELECT TOP 1 upc FROM upcLike WHERE likecode=".$lcs[$i]." ORDER BY upc");
		$upc = array_pop($ms->fetch_array($upcR));

		$l = (strtolower($local[$i])=='y')?1:0;
		$qty = $caseq1[$i]." ".$caseq2[$i];
		$info = $status[$i].":".$dist[$i].":".$origins[$i];
		$localUpdate = "UPDATE prodExtra SET location='$l',
				case_cost=".$caseprices[$i].",
				case_quantity='$qty',
				case_info='$info',
				cost=$prices[$i]
				FROM prodExtra as x INNER JOIN upcLike as u
				on x.upc = u.upc
				WHERE u.likeCode=".$lcs[$i];
		$remoteUpdate = "UPDATE prodExtra SET location='$l',
				case_price=".$caseprices[$i].",
				case_qty='$qty',
				case_info='$info',
				cost=$prices[$i]
				WHERE upc='$upc'";

		$ms->query($localUpdate);
		$sql->query($remoteUpdate);
	}
	echo "Case pricing updated!";
}
else {

// update extra items, if present
if (isset($_POST["update_extra"])){
	$upcs = $_POST["upc"];
	$prices = $_POST["price"];
	$status = $_POST["status"];
	$dists = $_POST["dist"];
	$origins = $_POST["origin"];
	$descs = $_POST["desc"];
	$csizes = $_POST["csize"];
	$cprices = $_POST["cprice"];
	for ($i=0;$i<count($upcs);$i++){
		if ($prices[$i] == ""){
			$msUp1 = sprintf("UPDATE Products SET description='RESERVED' WHERE upc='%s'",$upcs[$i]);
			$msUp2 = sprintf("UPDATE prodExtra SET case_quantity='',case_cost=0 WHERE upc='%s'",$upcs[$i]);
			$myUp1 = sprintf("UPDATE Products SET description='RESERVED' WHERE upc='%s'",$upcs[$i]);
			$myUp2 = sprintf("UPDATE prodExtra SET case_qty='',case_price=0 WHERE upc='%s'",$upcs[$i]);
			$ms->query($msUp1);
			$ms->query($msUp2);
			$sql->query($myUp1);
			$sql->query($myUp2);
		}
		else {
			$msUp1 = sprintf("UPDATE Products SET description='%s' WHERE upc='%s'",$descs[$i],$upcs[$i]);
			$msUp2 = sprintf("UPDATE prodExtra SET case_info='%s',case_cost=%s,location='%s',case_quantity='%s',cost=%s WHERE upc='%s'",
					$status[$i].":".$dists[$i].":".$origins[$i],$prices[$i],
					(isset($_POST["local".$upcs[$i]]))?"1":"0",$csizes[$i],$cprices[$i],$upcs[$i]);
			$myUp1 = sprintf("UPDATE Products SET description='%s' WHERE upc='%s'",$descs[$i],$upcs[$i]);
			$myUp2 = sprintf("UPDATE prodExtra SET case_info='%s',case_price=%s,location='%s',case_qty='%s',cost=%s WHERE upc='%s'",
					$status[$i].":".$dists[$i].":".$origins[$i],$prices[$i],
					(isset($_POST["local".$upcs[$i]]))?"1":"0",$csizes[$i],$cprices[$i],$upcs[$i]);
			$ms->query($msUp1);
			$ms->query($msUp2);
			$sql->query($myUp1);
			$sql->query($myUp2);
		}
	}
}

if (isset($_POST["update_msg"])){
	$msg = str_replace("'","''",$_POST["msg"]);
	$sql->query("UPDATE MotD SET msg='$msg' WHERE id='motd'");
}

// current data gathering
$query = "select p.upc,p.description,x.location,x.case_quantity,x.case_cost,
	x.cost,x.case_info
	from products as p left join prodExtra as x on p.upc=x.upc
	where p.upc in ('0000000089991','0000000089992','0000000089993')
	order by p.upc";
$items = array();
$result = $ms->query($query);
while($row = $ms->fetch_row($result)){
	array_unshift($items,array());
	$items[0]["upc"] = $row[0];
	if ($row[1] == "RESERVED"){
		$items[0]["desc"] = "";
		$items[0]["local"] = "";
		$items[0]["status"] = "";
		$items[0]["dist"] = "";
		$items[0]["origin"] = "";
		$items[0]["price"] = "";
		$items[0]["cprice"] = "";
		$items[0]["csize"] = "";
	}
	else{
		$items[0]["desc"] = $row[1];
		$items[0]["local"] = $row[2];
		$temp = explode(":",$row[6]);
		$items[0]["status"] = $temp[0];
		$items[0]["dist"] = (isset($temp[1])?$temp[1]:'');
		$items[0]["origin"] = (isset($temp[2])?$temp[2]:'');
		$items[0]["cprice"] = $row[4];
		$items[0]["price"] = $row[5];
		$items[0]["csize"] = $row[3];
	}
}

$msg = array_pop($sql->fetch_row($sql->query("SELECT msg FROM MotD")));

?>

<html>
<head>
	<title>Upload Case Pricing CSV</title>
</head>
<body>
<h3>Upload a CSV file for case pricing</h3>
<form action=index.php method=post enctype="multipart/form-data">
<input type=hidden name=MAX_FILE_SIZE value=2097152 />
Filename: <input type=file id=file name=upload />
<input type=submit value="Upload File" />
</form>
<hr />
<h3>Update Message Box</h3>
<form action=index.php method=post>
<textarea name=msg cols=28 rows=7><?php echo $msg ?></textarea>
<br />
<input type=submit name=update_msg value="Update Message Box" />
</form>
<hr />
<h3>Extra Items</h3>
<form action=index.php method=post>
<table cellspacing=0 cellpadding=4 border=1>
<tr><th>Status</th><th>Description</th><th>Local</th><th>Best $</th>
<th>Origin</th><th>Cs Size</th><th>Cs Price</th>
<th>Unit Price</th></tr>
<?php
foreach($items as $item){
	echo "<tr>";
	printf("<td><input type=text size=2 value=\"%s\" name=status[] /></td>
		<td><input type=text value=\"%s\" name=desc[] /></td>
		<td><input type=checkbox name=local%s %s /></td>
		<td><input type=text size=6 name=dist[] value=\"%s\" /></td>
		<td><input type=text size=6 name=origin[] value=\"%s\" /></td>
		<td><input type=text size=6 name=csize[] value=\"%s\" /></td>
		<td><input type=text size=4 name=cprice[] value=\"%s\" /></td>
		<td><input type=text size=4 name=price[] value=\"%s\" /></td>
		<input type=hidden name=upc[] value=\"%s\" />",
		$item["status"],$item["desc"],$item["upc"],($item["local"]==1)?"checked":"",
		$item["dist"],$item["origin"],$item["csize"],$item["cprice"],$item["price"],$item["upc"]);
	echo "</tr>";
}
?>
</table>
<input type=submit name=update_extra value="Update Extra Items" />
</form>
</body>

<?php
}
?>
