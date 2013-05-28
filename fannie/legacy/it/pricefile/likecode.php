<?php

include('../../../config.php');
require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/tmp_dir.php');
if (!function_exists("updateProductAllLanes")) include($FANNIE_ROOT.'legacy/queries/laneUpdates.php');

$LC_COL=0;
$PRICE_COL=1;

if (isset($_POST["MAX_FILE_SIZE"])){
	$fn = sys_get_temp_dir()."/lc_sheet.csv";
	move_uploaded_file($_FILES['upload']['tmp_name'],$fn);

	$fp = fopen($fn,"r");
	echo "<b>Data to import</b><br />";
	echo "<table cellspacing=0 cellpadding=3 border=1><tr>";
	echo "<th>Likecode</th><th>Description</th><th>Current Price</th><th>New Price</th>";
	echo "</tr>";
	echo "<form action=likecode.php method=post>";
	while (!feof($fp)){
		$line = fgets($fp);
		$data = csv_parser($line);

		if (!is_numeric($data[$LC_COL])) continue;

		$q = "select l.likeCodeDesc,min(p.normal_price) from products as p
			left join upcLike as u on u.upc=p.upc 
			left join likeCodes as l on l.likeCode=u.likeCode where
			u.likeCode=".$data[$LC_COL]." group by
			u.likeCode, l.likeCodeDesc
			order by count(*) desc";
		$r = $sql->query($q);
		if ($sql->num_rows($r) == 0){
			echo "<i>Error - unknown like code #".$data[$LC_COL]."</i><br />";
			continue;
		}
		$row = $sql->fetch_array($r);

		echo "<tr>";
		echo "<td>".$data[$LC_COL]."</td><input type=hidden name=likecode[] value=\"".$data[$LC_COL]."\" />";
		echo "<td>".$row[0]."</td>";
		echo "<td>".$row[1]."</td>";
		echo "<td><input type=text size=5 name=price[] value=\"".$data[$PRICE_COL]."\" /></td>";
		//echo "<td><input type=text size=5 name=scale[] value=\"".$data[$SCALE_COL]."\" /></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<input type=submit value=Submit /></form>";

	fclose($fp);
	unlink($fn);

}
else if (isset($_POST['likecode'])){
	$likecodes = $_POST['likecode'];
	$prices = $_POST['price'];
	//$scales = $_POST['scale'];

	echo "<b>Peforming updates</b><br />";
	for ($i = 0; $i < count($likecodes); $i++){
		$q = "update products as p left join upcLike as u on p.upc=u.upc
			SET normal_price=".trim($prices[$i],' $').", modified=".$sql->now()."
			where u.likeCode=".$likecodes[$i];	
		echo "Setting likecode #".$likecodes[$i]." to $".$prices[$i]."<br />";
		$sql->query($q);

		$q2 = "SELECT upc FROM upcLike WHERE likeCode=".$likecodes[$i];
		$r2 = $sql->query($q2);
		while($w2 = $sql->fetch_row($r2))
			updateProductAllLanes($w2['upc']);
	}
}
else{
?>
<html>
<head>
<title>Upload Price Sheet</title>
</head>
<body>
<form enctype="multipart/form-data" action="likecode.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
</body>
</html>
<?php
}
?>
