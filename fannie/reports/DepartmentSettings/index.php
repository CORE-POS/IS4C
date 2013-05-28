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

$page_title = "Fannie : Department Settings Report";
$header = "Department Settings Report";
if (!isset($_REQUEST['excel'])){
	include($FANNIE_ROOT.'src/header.html');
}

if (isset($_REQUEST['submit']) || isset($_REQUEST['super'])){
	$super = (isset($_REQUEST['super']))?$_REQUEST['super']:0;
	if ($super == 0)
		$super = ($_REQUEST['submit']=="Report by Super Department")?1:0;

	$join = "";
	$where = "";
	$link = "";	
	$args = array();
	if ($super == 1){
		$superID = $_REQUEST['superdept'];
		$join = "LEFT JOIN superdepts AS s ON d.dept_no=s.dept_ID";
		$where = "s.superID = ?";
		$args[] = $superID;
		$link = "&super=1&superdept=$superID";
	}
	else {
		$d1 = $_REQUEST['dept1'];
		$d2 = $_REQUEST['dept2'];
		$join = "";
		$where = "d.dept_no BETWEEN ? AND ?";
		$args = array($d1,$d2);
		$link = "&super=0&dept1=$d1&dept2=$d2";
	}

	$order = (isset($_REQUEST['order']))?$_REQUEST['order']:'d.dept_no';
	$dir = (isset($_REQUEST['dir']))?$_REQUEST['dir']:'asc';
	$odir = ($dir=='asc')?'desc':'asc';

	if (!isset($_REQUEST['excel'])){
		echo "<a href=index.php?order=$order&dir=$dir&excel=yes$link>Save to Excel</a>";
	}
	else {
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="departmentSettings.xls"');
	}

	echo "<table cellspacing=0 cellpadding=4 border=1><tr>";
	if (!isset($_REQUEST['excel'])){
		echo "<th><a href=index.php?order=d.dept_no&dir=".($order=='d.dept_no'?$odir:$dir)."$link>Dept #</a></th>";
		echo "<th><a href=index.php?order=d.dept_name&dir=".($order=='d.dept_name'?$odir:$dir)."$link>Dept Name</a></th>";
		echo "<th><a href=index.php?order=c.salesCode&dir=".($order=='c.salesCode'?$odir:$dir)."$link>Sales Code</a></th>";
		echo "<th><a href=index.php?order=m.margin&dir=".($order=='m.margin'?$odir:$dir)."$link>Margin</a></th>";
		echo "<th><a href=index.php?order=d.dept_tax&dir=".($order=='d.dept_tax'?$odir:$dir)."$link>Tax</a></th>";
		echo "<th><a href=index.php?order=d.dept_fs&dir=".($order=='d.dept_fs'?$odir:$dir)."$link>FS</a></th>";
	}
	else {
		echo "<th>Dept #</th><th>Dept Name</th><th>Sales Code</th><th>Margin</th><th>Tax</th><th>FS</th>";
	}
	echo "</tr>";

	$query = $dbc->prepare_statement("SELECT d.dept_no,d.dept_name,c.salesCode,m.margin,
		CASE WHEN d.dept_tax=0 THEN 'NoTax' ELSE t.description END as tax,
		CASE WHEN d.dept_fs=1 THEN 'Yes' ELSE 'No' END as fs
		FROM departments AS d LEFT JOIN taxrates AS t
		ON d.dept_tax = t.id LEFT JOIN deptSalesCodes AS c
		ON d.dept_no=c.dept_ID LEFT JOIN deptMargin AS m
		ON d.dept_no=m.dept_ID $join
		WHERE $where
		ORDER BY d.dept_no");
	$result = $dbc->exec_statement($query,$args);
	while($row = $dbc->fetch_row($result)){
		printf("<tr><td>%d</td><td>%s</td><td>%d</td><td>%.2f%%</td>
			<td>%s</td><td>%s</td></tr>",$row[0],
			(isset($_REQUEST['excel']))?$row[1]:"<a href=\"{$FANNIE_URL}item/departments/dept.php?did=$row[0]\">$row[1]</a>",
			$row[2],$row[3]*100,$row[4],$row[5]);
	}
	echo "</table>";
}
else {
$opts = "";
$prep = $dbc->prepare_statement("SELECT superID,super_name fROM superDeptNames ORDER BY super_name");
$resp = $dbc->exec_statement($prep);
while($row = $dbc->fetch_row($resp))
	$opts .= "<option value=$row[0]>$row[1]</option>";
$depts = "";
$prep = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
$resp = $dbc->exec_statement($prep);
$d1 = False;
while($row = $dbc->fetch_row($resp)){
	$depts .= "<option value=$row[0]>$row[0] $row[1]</option>";
	if ($d1 === False) $d1 = $row[0];
}
?>
<script src="<?php echo $FANNIE_URL; ?>src/jquery-1.2.6.min.js"
	type="text/javascript"></script>
<script src="dept.js" type="text/javascript"></script>
<form action=index.php method=post>
<fieldset title="Choose a super department">
<select name="superdept"><?php echo $opts; ?></select><p />
<input type=submit name=submit value="Report by Super Department" />
</fieldset>
<p />
<fieldset title="Choose a department range">
<input type=text size=4 name=dept1 id=dept1 value="<?php echo $d1; ?>" />
<select onchange="$('#dept1').val(this.value)">
<?php echo $depts; ?></select>
<p />
<input type=text size=4 name=dept2 id=dept2 value="<?php echo $d1; ?>" />
<select onchange="$('#dept2').val(this.value)">
<?php echo $depts; ?></select>
<p />
<input type=submit name=submit value="Report by Department Range" />
</fieldset>
</form>
<?php
}

if (!isset($_REQUEST['excel'])){
	include($FANNIE_ROOT.'src/footer.html');
}
?>
