<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (isset($_REQUEST['month1'])){
	$m1 = $_REQUEST['month1'];
	$m2 = $_REQUEST['month2'];
	$y1 = $_REQUEST['year1'];
	$y2 = $_REQUEST['year2'];

	$where = "";
	$select = "";
	$join = "";
	if ($_REQUEST['mtype'] == 'upc'){
		$vals = preg_split("/\D+/",$_REQUEST['upcs']);
		$where .= "t.upc IN (";
		foreach($vals as $v)
			$where .= "'".str_pad($v,13,'0',STR_PAD_LEFT)."',";
		$where = rtrim($where,",").")";
		$select .= "t.upc,p.description";
		$join = "LEFT JOIN products AS p ON p.upc=t.upc";
	}
	else {
		$where .= sprintf("t.department BETWEEN %d AND %d",$_REQUEST['dept1'],
			$_REQUEST['dept2']);
		$select .= "t.department,d.dept_name";
		$join = "LEFT JOIN departments AS d ON d.dept_no=t.department";
	}

	$months = array();
	$rows = array();
	while($m1 <= $m2 || $y1 < $y2){
		$table = $FANNIE_ARCHIVE_DB.".";
		if ($FANNIE_SERVER_DBMS == "MSSQL")
			$table .= "dbo.";
		$table .= "dlog".$y1.str_pad($m1,2,'0',STR_PAD_LEFT);

		$query = "SELECT $select,sum(t.quantity),sum(total) FROM
			$table as t $join
			WHERE t.trans_status <> 'M'
			AND $where
			GROUP BY $select
			ORDER BY $select";
		$result = $dbc->query($query);
		while($row = $dbc->fetch_row($result)){
			$mstr = $m1."/".$y1;
			if (!isset($months[$mstr])) $months[$mstr] = True;
			if (!isset($rows[$row[0]])){
				$rows[$row[0]] = array();
				$rows[$row[0]]['subtitle'] = $row[1];
			}
			$rows[$row[0]][$mstr] = array($row[2],$row[3]);
		}

		$m1 += 1;
		if ($m1 > 12){
			$m1 = 1;
			$y1 += 1;
		}
	}

	ob_start();
	echo "<table cellspacing=0 cellpadding=4 border=1>";
	echo "<tr><th>&nbsp;</th><th>&nbsp;</th>";
	foreach($months as $k=>$v)
		echo "<th>$k</th>";
	echo "</tr>";
	foreach($rows as $label=>$data){
		echo "<tr><th>$label</th>";
		echo "<td>".$data['subtitle']."</td>";
		foreach($months as $k=>$v){
			if (isset($data[$k])){
				if ($_REQUEST['results'] == "Sales")
					printf("<td>%.2f</td>",$data[$k][1]);
				else
					printf("<td>%.2f</td>",$data[$k][0]);
			}
			else
				echo "<td>&nbsp;</td>";
		}
		echo "</tr>";
	}
	echo "</table>";

	$str = ob_get_contents();
	ob_end_clean();

	if (isset($_REQUEST['excel'])){
		//include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
		include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');
		include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
		//$xls = ArrayToXls(HtmlToArray($str));
		$xls = ArrayToCsv(HtmlToArray($str));

		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="monthlyReport.xls"');
		echo $xls;
	}
	else
		echo $str;
}
else {
	$page_title = "Fannie : Month Over Month Movement";
	$header = "Month Over Month Movement";
	include($FANNIE_ROOT.'src/header.html');
	$depts = array();
	$q = "SELECT dept_no,dept_name FROM departments ORDER BY dept_no";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r))
		$depts[$w[0]] = $w[1];
	?>
	<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/jquery/jquery.js">
	</script>
	<script type="text/javascript">
	function recheck(){
		if ($('#upc').attr('checked')){
			$('#upctr').show();
			$('#depttr').hide();
			$('#depttr2').hide();
		}
		else {
			$('#upctr').hide();
			$('#depttr').show();
			$('#depttr2').show();
		}
	}
	$('document').ready(function(){
		recheck();
		$('#upc').click(recheck);
		$('#dept').click(recheck);
	});
	</script>
	<form action="index.php" method="get">
	<table>
	<tr>
	<td><select name="month1"><?php
	for($i=1;$i<13;$i++)
		printf("<option value=%d>%s</option>",$i,date("F",mktime(0,0,0,$i,1,2000)));
	?></select></td>
	<td><input type="text" size=4 name="year1" value="<?php echo date("Y"); ?>" /></td>
	<td>&nbsp;through&nbsp;</td>
	<td><select name="month2"><?php
	for($i=1;$i<13;$i++)
		printf("<option value=%d>%s</option>",$i,date("F",mktime(0,0,0,$i,1,2000)));
	?></select></td>
	<td><input type="text" size=4 name="year2" value="<?php echo date("Y"); ?>" /></td>
	</tr>
	<tr><td colspan="5">
	<b>Report for</b>:
	<input type="radio" id="upc" name="mtype" value="upc" checked /> UPC
	<input type="radio" id="dept" name="mtype" value="dept" /> Department
	&nbsp;&nbsp; Results in <select name=results><option>Sales</option><option>Quantity</option></select>
	</td></tr>
	<tr id="upctr"><td colspan="5">
	<b>UPC(s)</b>: <input type="text" name="upcs" size="35" />
	</td></tr>
	<tr id="depttr"><td align=right>
	<b>Department</b>:
	</td><td colspan="4">
	<select name="dept1"><?php
	foreach($depts as $k=>$v)
		printf("<option value=%d>%d %s</option>",$k,$k,$v);
	?>
	</select>
	</td><tr id="depttr2"><td align=right>
	through</td><td colspan="4">
	<select name="dept2"><?php
	foreach($depts as $k=>$v)
		printf("<option value=%d>%d %s</option>",$k,$k,$v);
	?>
	</select>
	</table>
	<br />
	<input type="submit" value="Run Report" />
	<input type="checkbox" name="excel" /> Excel
	</form>
	<?php
	include($FANNIE_ROOT.'src/footer.html');
}
?>
