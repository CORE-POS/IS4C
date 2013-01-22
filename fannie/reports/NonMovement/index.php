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
header('Location: NonMovementReport.php');

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_REQUEST['deleteItem'])){
	foreach($_REQUEST['deleteItem'] as $upc){
		$delQ = sprintf("DELETE FROM products WHERE
				upc='%s'",$upc);
		$dbc->query($delQ);
	}
}

if (isset($_REQUEST['deptStart'])){
	if (isset($_REQUEST['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="No Movement.xls"');
	}

	$dept1 = $_REQUEST['deptStart'];
	$dept2 = $_REQUEST['deptEnd'];
	$date1 = $_REQUEST['date1'];
	$date2 = $_REQUEST['date2'];

	$tempName = "TempNoMove";
	$dlog = select_dlog($date1,$date2);

	$tempQ = "CREATE TABLE $tempName (upc varchar(13))";
	$dbc->query($tempQ);

	$insQ = "INSERT INTO $tempName
		SELECT d.upc FROM $dlog AS d
		WHERE trans_type='I'
		AND d.tdate BETWEEN '$date1 00:00:01' 
		AND '$date2 23:59:59'
		GROUP BY d.upc";
	if (isset($_REQUEST['netted']) && !empty($_REQUEST['netted']))
		$insQ .= " HAVING SUM(d.quantity) <> 0";
	if (!isset($_REQUEST['netted'])) $_REQUEST['netted'] = '';
	$dbc->query($insQ);

	$order = isset($_REQUEST['sort'])?$_REQUEST['sort']:'p.upc';
	$dir = isset($_REQUEST['dir'])?$_REQUEST['dir']:'ASC';

	$query = "SELECT p.upc,p.description,d.dept_no,
		d.dept_name FROM products AS p LEFT JOIN
		departments AS d ON p.department=d.dept_no
		WHERE p.upc NOT IN (select upc FROM $tempName)
		AND p.department
		BETWEEN $dept1 AND $dept2
		ORDER BY $order $dir";
	$result = $dbc->query($query);
	echo "<b>Items with no sales</b><br />
		$date1 through $date2<br />
		Departments: <i>$dept1</i> through <i>$dept2</i><br />";
	if (isset($_REQUEST['excel'])){
	echo "<table cellspacing=0 cellpadding=4 border=1>
		<tr>
		<th>UPC</td>
		<th>Desc</td>
		<th>Dept#</td>
		<th>Dept</td>
		</tr>";
	}
	else {
	echo "<form action=index.php method=post>";
	echo "<table cellspacing=0 cellpadding=4 border=1>
		<tr>
		<th><a href=index.php?date1=$date1&date2=$date2&deptStart=$dept1&deptEnd=$dept2&netted={$_REQUEST['netted']}&sort=p.upc>UPC</a></th>
		<th><a href=index.php?date1=$date1&date2=$date2&deptStart=$dept1&deptEnd=$dept2&netted={$_REQUEST['netted']}&sort=p.description>Desc</a></th>
		<th><a href=index.php?date1=$date1&date2=$date2&deptStart=$dept1&deptEnd=$dept2&netted={$_REQUEST['netted']}&sort=d.dept_no>Dept#</a></th>
		<th><a href=index.php?date1=$date1&date2=$date2&deptStart=$dept1&deptEnd=$dept2&netted={$_REQUEST['netted']}&sort=d.dept_name>Dept</a></th>
		<th>Delete</td>
		</tr>";
	}
	while($row = $dbc->fetch_row($result)){
		printf("<tr><td><a href={$FANNIE_URL}item/itemMaint.php?upc=%s target=_new%s>%s</a></td>
			<td>%s</td><td>%d</td><td>%s</td>",
			$row[0],$row[0],$row[0],$row[1],$row[2],$row[3]);
		if (!isset($_REQUEST['excel']))
			printf("<td><input type=checkbox name=deleteItem[] value=\"%s\" /></td>",
				$row[0]);
		echo "</tr>";
	}
	echo "</table>";
	if (!isset($_REQUEST['excel'])){
		printf("<input type=hidden name=date1 value=\"%s\" />
			<input type=hidden name=date2 value=\"%s\" />
			<input type=hidden name=deptStart value=\"%s\" />
			<input type=hidden name=deptEnd value=\"%s\" />
			<input type=hidden name=netted value=\"%s\" />",
			$date1,$date2,$dept1,$dept2,$_REQUEST['netted']);
		echo "<input type=submit value=\"Delete Selected Items\" />
			</form>";
	}

	$dbc->query("DROP TABLE $tempName");
	exit;
}

$deptsQ = "select dept_no,dept_name from departments order by dept_no";
$deptsR = $dbc->query($deptsQ);
$deptsList = "";
while ($deptsW = $dbc->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

$page_title = "Fannie: Non-Movement";
$header = "Non-Movement Report";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
<script type="text/javascript">
function swap(src,dst){
	var val = document.getElementById(src).value;
	document.getElementById(dst).value = val;
}
</script>
<div id=main>	
<form method = "get" action="index.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
			<td> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
 			<select id=deptStartSel onchange="swap('deptStartSel','deptStart');">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptStart id=deptStart size=5 value=1 />
			</p>
			<p>
			<select id=deptEndSel onchange="swap('deptEndSel','deptEnd');">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptEnd id=deptEnd size=5 value=1 />
			</p></td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<tr> 
			<td><b>Excel</b>
			</td><td>
			<input type=checkbox name=excel />
			</td>
			</td>
			<td rowspan=2 colspan=2>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)<!-- Output to CSV?</td>
		            <td><input type="checkbox" name="csv" value="yes">
			                        yes --> </td>
				</tr>
		<tr>
			<td>
			<b>Netted</td><td>
			<input type=checkbox name=netted />
		</tr>

		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>




