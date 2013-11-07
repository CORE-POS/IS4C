<?php

include('../../../config.php');
require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

require($FANNIE_ROOT.'src/csv_parser.php');
require($FANNIE_ROOT.'src/tmp_dir.php');

$LC_COL=0;
$PRICE_COL=1;

if (isset($_POST["MAX_FILE_SIZE"])){
	$fn = sys_get_temp_dir()."/lc_sheet.csv";
	move_uploaded_file($_FILES['upload']['tmp_name'],$fn);

	$fp = fopen($fn,"r");
	echo "<b>Data to import</b><br />";
	$typeQ = "select typeDesc,discType from batchType where batchTypeID=".$_POST["batchType"];
	$typeR = $sql->query($typeQ);
	$typeW = $sql->fetch_row($typeR);
	echo "<i>".$typeW[0]." batch running from ";
	echo $_POST["startDate"]." to ".$_POST["endDate"]."<br />";
	echo "<table cellspacing=0 cellpadding=3 border=1><tr>";
	echo "<th>Likecode</th><th>Description</th><th>Current Price</th><th>Sale Price</th>";
	echo "</tr>";
	echo "<form action=salesBatchLC.php method=post>";
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
		echo "</tr>";
	}
	echo "</table>";
	echo "<input type=hidden name=batchType value=\"".$_POST["batchType"]."\" />";
	echo "<input type=hidden name=batchName value=\"".$_POST["batchName"]."\" />";
	echo "<input type=hidden name=startDate value=\"".$_POST["startDate"]."\" />";
	echo "<input type=hidden name=endDate value=\"".$_POST["endDate"]."\" />";
	echo "<input type=hidden name=discount value=\"".$typeW[1]."\" />";
	echo "<input type=submit value=Submit /></form>";

	fclose($fp);
	unlink($fn);

}
else if (isset($_POST['likecode'])){
	$likecodes = $_POST['likecode'];
	$prices = $_POST['price'];
	$batchType = $_POST["batchType"];
	$batchName = $_POST["batchName"];
	$startDate = $_POST["startDate"];
	$endDate = $_POST["endDate"];
	$discount = $_POST["discount"];

	echo "<b>Creating batch</b><br />";
	$createQ = "insert into batches (startDate, endDate, batchName, batchType, discountType, priority) 
		values ('$startDate','$endDate','$batchName',$batchType,$discount,0)";
	$sql->query($createQ);
	$batchID = $sql->insert_id();

	$ownerQ = "insert into batchowner values ($batchID,'Produce')";
	$sql->query($ownerQ);

	echo "<b>Adding items</b><br />";
	for ($i = 0; $i < count($likecodes); $i++){
		$q = "insert into batchList (upc, batchID, salePrice, active, pricemethod, quantity) 
			VALUES ('LC".$likecodes[$i]."',$batchID,".ltrim($prices[$i],'$ ').",1,0,0)";
		echo "Setting likecode #".$likecodes[$i]." on sale for $".$prices[$i]."<br />";
		$sql->query($q);
	}

	echo "<a href=/it/newbatch>Go to batch page</a>";
}
else{
?>
<html>
<head>
<title>Upload Price Sheet</title>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
</head>
<body>
<form enctype="multipart/form-data" action="salesBatchLC.php" method="post">
<b>Create a new sales batch</b>:<br />
<table>
<tr>
	<td>Batch type</td>
	<td><select name=batchType>
	<?php
		$typesQ = "select batchTypeID,typeDesc from batchType where batchTypeID < 7 order by batchTypeID";
		$typesR = $sql->query($typesQ);
		while ($typesW = $sql->fetch_array($typesR))
			echo "<option value=".$typesW[0].">".$typesW[1]."</option>";
	?>
	</select></td>
	<td>Batch name</td>
	<td><input type=text name=batchName /></td>
</tr>
<tr>
	<td>Start Date</td>
	<td><input type=text name=startDate onfocus="showCalendarControl(this);" /></td>
	<td>End Date</td>
	<td><input type=text name=endDate onfocus="showCalendarControl(this);" /></td>
</tr>
<tr>
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
	<td colspan=1>Filename</td><td colspan=3><input type="file" id="file" name="upload" /></td>
</tr>
<tr>
	<td><input type="submit" value="Upload File" /></td>
</tr>
</table>

</form>
</body>
</html>
<?php
}
?>
