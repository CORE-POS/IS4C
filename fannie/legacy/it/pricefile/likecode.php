<?php

include('../../../config.php');
header("Location: {$FANNIE_URL}item/likecodes/LikeCodePriceUploadPage.php");
exit;

include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

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
    $q = $sql->prepare("select l.likeCodeDesc,min(p.normal_price) from products as p
        left join upcLike as u on u.upc=p.upc 
        left join likeCodes as l on l.likeCode=u.likeCode where
        u.likeCode=? group by
        u.likeCode, l.likeCodeDesc
        order by count(*) desc");
	while (!feof($fp)){
		$data = fgetcsv($fp);

		if (!is_numeric($data[$LC_COL])) continue;

		$r = $sql->execute($q, array($data[$LC_COL]));
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
    set_time_limit(0);
	$likecodes = $_POST['likecode'];
	$prices = $_POST['price'];
	//$scales = $_POST['scale'];

    $q = $sql->prepare("update products as p left join upcLike as u on p.upc=u.upc
        SET normal_price=?, modified=".$sql->now()."
        where u.likeCode=?");
    $q2 = $sql->prepare("SELECT upc FROM upcLike WHERE likeCode=?");
    $model = new ProductsModel($sql);
    echo '<html><body><p>';
	echo "<b>Peforming updates</b><br />";
	for ($i = 0; $i < count($likecodes); $i++){
		echo "Setting likecode #".$likecodes[$i]." to $".$prices[$i]."<br />";
        flush();
		$sql->execute($q, array(trim($prices[$i],' $'), $likecodes[$i]));

		$r2 = $sql->execute($q2, array($likecodes[$i]));
		while($w2 = $sql->fetch_row($r2)) {
            $model->upc($w2['upc']);
            $model->pushToLanes();
        }
	}
    echo '</p></body></html>';
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
