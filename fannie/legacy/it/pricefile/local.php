<?php
include('../../../config.php');

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/tmp_dir.php');

$LC_COL=0;
$LOCAL_COL=1;

ini_set('auto_detect_line_endings',True);

if (isset($_POST["MAX_FILE_SIZE"])){
	$fn = sys_get_temp_dir()."/local_sheet.csv";
	move_uploaded_file($_FILES['upload']['tmp_name'],$fn);

	$fp = fopen($fn,"r");
	echo "<style type=\"text/css\">
	tr.local td { background: #ffffcc; }
	</style>";
	echo "<b>Data to import</b><br />";
	echo "<table cellspacing=0 cellpadding=3 border=1><tr>";
	echo "<th>Likecode</th><th>Description</th><th>Local</th>";
	echo "</tr>";
	echo "<form action=local.php method=post>";
	while (!feof($fp)){
		$line = fgets($fp);
		$data = csv_parser($line);

		if (!is_numeric($data[$LC_COL])) continue;

		$q = "select l.likeCodeDesc
			from likeCodes as l where
			l.likecode=".$data[$LC_COL]; 
		$r = $sql->query($q);
		if ($sql->num_rows($r) == 0){
			echo "<i>Error - unknown like code #".$data[$LC_COL]."</i><br />";
			continue;
		}
		$row = $sql->fetch_array($r);

		$local = 'No';
		if (!empty($data[$LOCAL_COL]) && $data[$LOCAL_COL] == 1) $local = '300';
		if (!empty($data[$LOCAL_COL]) && $data[$LOCAL_COL] == 2) $local = 'S.C.';

		if (!empty($data[$LOCAL_COL]))
			echo "<tr class=\"local\">";
		else
			echo "<tr>";
		echo "<td>".$data[$LC_COL]."</td><input type=hidden name=likecode[] value=\"".$data[$LC_COL]."\" />";
		echo "<td>".$row[0]."</td>";
		echo "<td><input type=text size=5 name=local[] value=\"".$local."\" /></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<input type=submit value=Submit /></form>";

	fclose($fp);
	unlink($fn);

}
else if (isset($_POST['likecode'])){
	$likecodes = $_POST['likecode'];
	$local = $_POST['local'];

	echo "<b>Peforming updates</b><br />";
	for ($i = 0; $i < count($likecodes); $i++){
		$lval = 0;
		if ($local[$i] == '300') $lval = 2;
		elseif ($local[$i] == 'S.C.') $lval = 1;
		$q = "update products as p left join upcLike as u on p.upc=u.upc
			set local=$lval
			where u.likecode=".$likecodes[$i];	
		echo "Setting likecode #".$likecodes[$i]." to local =>".$local[$i]."<br />";
		$sql->query($q);
	}

	echo "<b>Pushing updates to the lanes</b><br />";
	//$sql->query("exec productsUpdateAll");
}
else{
?>
<html>
<head>
<title>Upload Local Sheet</title>
</head>
<body>
Update local status by like code<br />
File format: CSV, Likecode in column A, Anything in B if local, blank in B if not
<p />
<form enctype="multipart/form-data" action="local.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
</body>
</html>
<?php
}
?>
