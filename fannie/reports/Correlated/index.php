<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

$deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
$deptR = $dbc->exec_statement($deptQ);
$depts = array();
while ($deptW = $dbc->fetch_array($deptR)){
	$depts[$deptW[0]] = $deptW[1];
}

if (isset($_REQUEST['submit'])){
	$depts = $_REQUEST['depts'];
	$upc = $_REQUEST['upc'];
	$date1 = $_REQUEST['date1'];
	$date2 = $_REQUEST['date2'];
	$filters = $_REQUEST['filters'];

	$dClause = "";
	$dArgs = array();
	foreach($_REQUEST['depts'] as $d){
		$dClause .= "?,";
		$dArgs[] = $d;
	}
	$dClause = "(".rtrim($dClause,",").")";


	$where = "d.department IN $dClause";
	$inv = "d.department NOT IN $dClause";
	if ($upc != "") {
		$upc = str_pad($upc,13,"0",STR_PAD_LEFT);
		$where = "d.upc = ?";
		$inv = "d.upc <> ?";
		$dArgs = array($upc);
	}
	$dlog = select_dlog($date1,$date2);

	$filter = "";
	$fArgs = array();
	if (is_array($filters)){
		$fClause = "";
		foreach($filters as $f){
			$fClause .= "?,";
			$fArgs[] = $f;
		}
		$fClause = "(".rtrim($fClause,",").")";
		$filter = "AND d.department IN $fClause";
	}

	$query = $dbc->prepare_statement("CREATE TABLE groupingTemp (tdate varchar(11), emp_no int, register_no int, trans_no int)");
	$dbc->exec_statement($query);

	$dateConvertStr = ($FANNIE_SERVER_DBMS=='MSSQL')?'convert(char(11),d.tdate,110)':'convert(date(d.tdate),char)';

	$loadQ = $dbc->prepare_statement("INSERT INTO groupingTemp
		SELECT $dateConvertStr as tdate,
		emp_no,register_no,trans_no FROM $dlog AS d
		WHERE $where AND tdate BETWEEN ? AND ?
		GROUP BY $dateConvertStr, emp_no,register_no,trans_no");
	$dArgs[] = $date1.' 00:00:00';
	$dArgs[] = $date2.' 23:59:59';
	$dbc->exec_statement($loadQ,$dArgs);

	$dataQ = $dbc->prepare_statement("SELECT d.upc,p.description,t.dept_no,t.dept_name,
		SUM(d.quantity) AS quantity FROM
		$dlog AS d INNER JOIN groupingTemp AS g
		ON $dateConvertStr = g.tdate
		AND g.emp_no = d.emp_no
		AND g.register_no = d.register_no
		AND g.trans_no = d.trans_no
		LEFT JOIN products AS p on d.upc=p.upc
		LEFT JOIN departments AS t
		ON d.department=t.dept_no
		WHERE $inv 
		AND trans_type IN ('I','D')
		AND d.tdate BETWEEN ? AND ?
		AND d.trans_status=''
		$filter
		GROUP BY d.upc,p.description,t.dept_no,t.dept_name
		ORDER BY SUM(d.quantity) DESC");	
	foreach($fArgs as $f) $dArgs[] = $f;
	$dataR = $dbc->exec_statement($dataQ,$dArgs);

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
	while($dataW = $dbc->fetch_row($dataR)){
		printf("<tr><td>%s</td><td>%s</td><td>%d %s</td><td>%.2f</td></tr>",
			$dataW['upc'],$dataW['description'],$dataW['dept_no'],
			$dataW['dept_name'],$dataW['quantity']);
	}
	echo "</table>";

	$drop = $dbc->prepare_statement("DROP TABLE groupingTemp");
	$dbc->exec_statement($drop);
	exit;
}

$page_title = "Fannie : Correlated Movement";
$header = "Correlated Movement Report";
include($FANNIE_ROOT.'src/header.html');
?>
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
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
</head>
<form action="index.php" method=post>
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
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
