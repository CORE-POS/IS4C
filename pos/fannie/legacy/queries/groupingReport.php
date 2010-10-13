<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');
include($FANNIE_ROOT.'src/select_dlog.php');

$deptQ = "select dept_no,dept_name from departments WHERE dept_no NOT IN (60) order by dept_no";
$deptR = $sql->query($deptQ);
$depts = array();
while ($deptW = $sql->fetch_array($deptR)){
	$depts[$deptW[0]] = $deptW[1];
}

if (isset($_REQUEST['submit'])){
	$depts = $_REQUEST['depts'];
	$upc = $_REQUEST['upc'];
	$date1 = $_REQUEST['date1'];
	$date2 = $_REQUEST['date2'];
	$filters = $_REQUEST['filters'];

	$dClause = "";
	foreach($_REQUEST['depts'] as $d){
		$dClause .= $d.",";
	}
	$dClause = "(".rtrim($dClause,",").")";


	$where = "d.department IN $dClause";
	$inv = "d.department NOT IN $dClause";
	if ($upc != "") {
		$upc = str_pad($upc,13,"0",STR_PAD_LEFT);
		$where = "d.upc = '$upc'";
		$inv = "d.upc <> '$upc'";
	}
	$dlog = select_dlog($date1,$date2);

	$filter = "";
	if (is_array($filters)){
		$fClause = "";
		foreach($filters as $f){
			$fClause .= $f.",";
		}
		$fClause = "(".rtrim($fClause,",").")";
		$filter = "AND d.department IN $fClause";
	}

	$sql->query("CREATE TABLE #groupingTemp (tdate varchar(11), trans_num varchar(15))");

	$loadQ = "INSERT INTO #groupingTemp
		SELECT convert(char(11),tdate,110) as tdate,
		trans_num FROM $dlog AS d
		WHERE $where AND tdate BETWEEN
		'$date1 00:00:00' AND '$date2 23:59:59'
		GROUP BY convert(char(11),tdate,110), trans_num";
	$sql->query($loadQ);

	$dataQ = "SELECT d.upc,p.description,t.dept_no,t.dept_name,
		SUM(d.quantity) AS quantity FROM
		$dlog AS d LEFT JOIN #groupingTemp AS g
		ON convert(char(11),d.tdate,110)=g.tdate
		AND g.trans_num = d.trans_num
		LEFT JOIN products AS p on d.upc=p.upc
		LEFT JOIN departments AS t
		ON d.department=t.dept_no
		WHERE $inv AND g.trans_num IS NOT NULL
		AND trans_type IN ('I','D')
		AND d.tdate BETWEEN
		'$date1 00:00:00' AND '$date2 23:59:59'
		AND d.trans_status=''
		$filter
		GROUP BY d.upc,p.description,t.dept_no,t.dept_name
		ORDER BY SUM(d.quantity) DESC";	
	$dataR = $sql->query($dataQ);

	if (isset($_REQUEST['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="groupingReport.xls"');
	}

	echo "<b>Corresponding sales for: ";
	if ($upc == "")
		echo "departments $dClause";
	else
		echo "UPC $upc";
	if ($filter != "")
		echo "<br />Filtered to departments $fClause";
	echo "<br />Period: $date1 to $date2<p />";
	echo "<table cellpadding=4 border=1>
		<tr><th>UPC</th><th>Desc</th><th>Dept</th><th>Qty</th></tr>";
	while($dataW = $sql->fetch_row($dataR)){
		printf("<tr><td>%s</td><td>%s</td><td>%d %s</td><td>%.2f</td></tr>",
			$dataW['upc'],$dataW['description'],$dataW['dept_no'],
			$dataW['dept_name'],$dataW['quantity']);
	}
	echo "</table>";

	$sql->query("DROP TABLE #groupingTemp");
	exit;
}

?>
<html>
<head>
	<title>Grouping Report</title>
<style type="text/css">
#inputset2 {
	display: none;
}
</style>
<script type="text/javascript">
function flipover(opt){
	if (opt == 'UPC'){
		document.getElementById('inputset1').style.display='none';
		document.getElementById('inputset2').style.display='block';
		document.forms[0].dept1.value='';
		document.forms[0].dept2.value='';
	}
	else {
		document.getElementById('inputset2').style.display='none';
		document.getElementById('inputset1').style.display='block';
		document.forms[0].upc.value='';
	}
}
</script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
</head>
<body>
<form action="groupingReport.php" method=post>
<select onchange="flipover(this.value);">
<option>Department</option>
<option>UPC</option>
</select>
<table border=0 cellspacing=5 cellpadding=3>
<tr>
	<td rowspan="2" valign=middle>
	<div id="inputset1">
	<b>Department(s)</b><br />
	<select size=7 multiple name=depts[]>
	<?php 
	foreach($depts as $no=>$name)
		echo "<option value=$no>$no $name</option>";	
	?>
	</select>
	</div>
	<div id="inputset2">
	<b>UPC</b>: <input type=text size=13 name=upc />
	</div>
	</td>
	<th>Start date</th>
	<td><input type="text" name="date1" onclick="showCalendarControl(this);"/></td>
</tr>
<tr>
	<th>End date</th>
	<td><input type="text" name="date2" onclick="showCalendarControl(this);"/></td>
</tr>
</table>
<hr />
<table border=0 cellspacing=5 cellpadding=3>
<tr>
	<td colspan="2"><b>Result Filter</b> (optional)</td>
</tr>
<tr>
	<td rowspan="2" valign=middle>
	<select size=7 multiple name=filters[]>
	<?php 
	foreach($depts as $no=>$name)
		echo "<option value=$no>$no $name</option>";	
	?>
	</select>
	</td>
</tr>
</table>
<hr />
<input type=submit name=submit value="Run Report" />
<input type=checkbox name=excel /> Excel
</form>
</body>
</html>
