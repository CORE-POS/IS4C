<?php

include('../../../config.php');
require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

$now = mktime();
while(date("N",$now) != 1)
	$now = mktime(0,0,0,date("n",$now),date("j",$now)-1,date("Y",$now));

if (isset($_POST['submit'])){
	$queries = array();
	$sql->query("TRUNCATE TABLE produceWeeklyData");
    $query = $sql->prepare("INSERT INTO produceWeeklyData VALUES
        (?,?,?,?,?,?,?)");
	foreach($_POST as $key=>$value){
		if ($key == "submit") continue;
		list($sub,$id) = explode(":",$key,2);
		if (!empty($queries[$id])) continue;
        $args = array($id,
			(isset($_POST["M:$id"])?$_POST["M:$id"]:0),
			(isset($_POST["T:$id"])?$_POST["T:$id"]:0),
			(isset($_POST["W:$id"])?$_POST["W:$id"]:0),
			(isset($_POST["Th:$id"])?$_POST["Th:$id"]:0),
			(isset($_POST["F:$id"])?$_POST["F:$id"]:0),
			(isset($_POST["Sa:$id"])?$_POST["Sa:$id"]:0)
        );
		$sql->execute($query, $args);
	}

	if ($_POST['submit'] == "Archive"){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="order archive '.date("m-d-y",$now).'.xls"');
	}
}

?>
<html>
<head>
<style type="text/css">
table { text-align: center; }
th { width: 70px; background: #cccc99;}
td { width: 70px; background: #ffffff;}
tr.alt td {background: #ffffcc;}

/* define height and width of scrollable area. Add 16px to width for scrollbar          */
div.tableContainer {
	clear: both;
	height: 300px;
	overflow: auto;
	width: 950px;; 
}

/* Reset overflow value to hidden for all non-IE browsers. */
html>body div.tableContainer {
	overflow: hidden;
	width: 950px;
}

/* define width of table. IE browsers only                 */
div.tableContainer table {
	float: left;
}

/* define width of table. Add 16px to width for scrollbar.           */
/* All other non-IE browsers.                                        */
html>body div.tableContainer table {
	width: 850px;
}

/* set table header to a fixed position. WinIE 6.x only                                       */
/* In WinIE 6.x, any element with a position property set to relative and is a child of       */
/* an element that has an overflow property set, the relative value translates into fixed.    */
/* Ex: parent element DIV with a class of tableContainer has an overflow property set to auto */
thead.fixedHeader tr {
	position: relative
}

/* set THEAD element to have block level attributes. All other non-IE browsers            */
/* this enables overflow to work on TBODY element. All other non-IE, non-Mozilla browsers */
html>body thead.fixedHeader tr {
	display: block
}

/* define the table content to be scrollable                                              */
/* set TBODY element to have block level attributes. All other non-IE browsers            */
/* this enables overflow to work on TBODY element. All other non-IE, non-Mozilla browsers */
/* induced side effect is that child TDs no longer accept width: auto                     */
html>body tbody.scrollContent {
	display: block;
	height: 300px;
	overflow: auto;
	width: 934px;
}


</style>
</head>
<body>
<img src="spacer.png" alt="" /><br />
<form action="index.php" method="post">
<?php

printf("<i>Order for Week of: %s</i>",date("m/d/y",$now));
?>
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="submit" value="Archive" />
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="submit" value="Save" />
<div class="tableContainer">
<table border="1" cellpadding="0" cellspacing="0" width="100%" class="scrollTable">
<thead class="fixedHeader">
	<tr>
		<th style="width:100px;">Desc</th>
		<th>Price</th>
		<th>Unit</th>
		<th>Weekly</th>
		<th>Need</th>
		<th>M</th>
		<th>T</th>
		<th>W</th>
		<th>Th</th>
		<th>F</th>
		<th>Sa</th>
		<th>Notes</th>
		<th>LC</th>
	</tr>
</thead>
<tbody class="scrollContent">
<?php
$query = "SELECT `desc`,price,unit,quantity,p.likecode,p.id,
	monday,tuesday,wednesday,thursday,friday,saturday
	FROM produceWeekly p LEFT JOIN
	likecodeWeeklyQuantity l ON p.likecode=l.likeCode
	LEFT JOIN produceWeeklyData d
	ON p.id=d.id
	ORDER BY p.id";
$result = $sql->query($query);
$count = 0;
while($row = $sql->fetch_row($result)){
	if ($count % 2 == 0)
		echo "<tr>";
	else
		echo "<tr class=\"alt\">";
	$count++;
	if ($row[1] == "" && $row[2] == "")
		echo "<td colspan=\"13\">".$row[0]."</td>";
	else {
		$sum = 0;
		for($i=6;$i<12;$i++){
			if (!is_numeric($row[$i])) $row[$i] = 0;
			$sum += $row[$i];
		}
		if ($_POST['submit'] == "Archive"){
			printf("<td style=\"width:100px\">%s</td><td>%s</td><td>%s</td>
				<td>%s</td><td>%s</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%.2f</td>
				<td>%s</td>
				<td style=\"width:54px\">%s</td>",
				$row[0],
				($row[1]!="")?$row[1]:"&nbsp;",
				($row[2]!="")?$row[2]:"&nbsp;",
				($row[3]!="")?$row[3]:"&nbsp;",
				($row[3]!="")?$row[3]-$sum:"&nbsp;",
				$row[6],
				$row[7],
				$row[8],
				$row[9],
				$row[10],
				$row[11],
				"&nbsp;",
				($row[4]!="")?$row[4]:"&nbsp;");
		}
		else {
			printf("<td style=\"width:100px\">%s</td><td>%s</td><td>%s</td>
				<td>%s</td><td>%s</td>
				<td><input type=text size=4 value=\"%.2f\" name=\"M:%s\" /></td>
				<td><input type=text size=4 value=\"%.2f\" name=\"T:%s\" /></td>
				<td><input type=text size=4 value=\"%.2f\" name=\"W:%s\" /></td>
				<td><input type=text size=4 value=\"%.2f\" name=\"Th:%s\" /></td>
				<td><input type=text size=4 value=\"%.2f\" name=\"F:%s\" /></td>
				<td><input type=text size=4 value=\"%.2f\" name=\"Sa:%s\" /></td>
				<td>%s</td>
				<td style=\"width:54px\">%s</td>",
				$row[0],
				($row[1]!="")?$row[1]:"&nbsp;",
				($row[2]!="")?$row[2]:"&nbsp;",
				($row[3]!="")?$row[3]:"&nbsp;",
				($row[3]!="")?$row[3]-$sum:"&nbsp;",
				$row[6],$row[5],
				$row[7],$row[5],
				$row[8],$row[5],
				$row[9],$row[5],
				$row[10],$row[5],
				$row[11],$row[5],
				"&nbsp;",
				($row[4]!="")?$row[4]:"&nbsp;");
		}
	}
	echo "</tr>";
} 
?>
</tbody>
</table>
</div>
</form>

</body>
</html>
