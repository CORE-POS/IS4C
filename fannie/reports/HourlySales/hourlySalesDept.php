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
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include($FANNIE_ROOT.'auth/login.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

if (isset($_GET['startDate'])){
	$sd = $_GET["startDate"];
	$startDate = $_GET['startDate']." 00:00:00";
	$ed = $_GET["endDate"];
	$endDate = $_GET['endDate']." 23:59:59";
	$dept = $_GET['deptStart'];
	$dept2 = $_GET['deptEnd'];

    $dlog = DTransactionsModel::selectDlog($startDate,$endDate);

        $hourlySalesQ = "SELECT min(".$dbc->dayofweek('d.tdate')."),".$dbc->hour('d.tdate').",sum(d.total),avg(d.total)
                         FROM $dlog as d left join 
			 {$FANNIE_OP_DB}{$dbconn}departments as t on d.department = t.dept_no
                         WHERE (d.trans_type = 'I' or d.trans_type = 'D') AND
			 d.tdate BETWEEN ? AND ?
			 and ".$dbc->hour('d.tdate')." between 7 and 20
                         AND t.dept_no BETWEEN ? AND ?
			 GROUP BY year(tdate),month(tdate),day(d.tdate),".$dbc->hour('tdate')."
			 ORDER BY year(tdate),month(tdate),day(d.tdate),".$dbc->hour('tdate');
	$args = array($startDate,$endDate,$dept,$dept2);
	if (isset($_GET['weekday'])){
			$hourlySalesQ = "SELECT min(".$dbc->dayofweek('d.tdate')."),".$dbc->hour('d.tdate').",sum(d.total),avg(d.total)
				 FROM $dlog as d left join 
				 {$FANNIE_OP_DB}{$dbconn}departments as t on d.department = t.dept_no
                 		 WHERE (d.trans_type = 'I' or d.trans_type = 'D') AND
				 d.tdate BETWEEN ? AND ?
				 and ".$dbc->hour('d.tdate')." between 7 and 20
 		 		 AND dept_no BETWEEN ? AND ?
				 GROUP BY ".$dbc->dayofweek('tdate').",".$dbc->hour('tdate')."
				 ORDER BY ".$dbc->dayofweek('tdate').",".$dbc->hour('tdate');
	}
	//echo $hourlySalesQ;

	if (isset($_GET['excel'])){
		header('Content-Type: application/ms-excel');
		header("Content-Disposition: attachment; filename=hourlySales-dept$dept-$dept2.xls");
	}
	echo "Hourly sales report for department(s) $dept - $dept2<br />";
	echo "$sd to $ed<br />";
	if (!isset($_GET['excel'])){
		if (isset($_GET['weekday']))
			echo "<a href=hourlySalesDept.php?startDate=$sd&endDate=$ed&deptStart=$dept&deptEnd=$dept2&weekday=$weekday&excel=yes>Save to Excel</a><br />";
		else
			echo "<a href=hourlySalesDept.php?startDate=$sd&endDate=$ed&deptStart=$dept&deptEnd=$dept2&excel=yes>Save to Excel</a><br />";
	}
	echo "<table cellspacing=0 cellpadding=2 border=1>";
	$colors=array('#ffffcc','#ffffff');
	$c = 0;
	echo "<tr><th>Day</th>";
	for ($i = 7; $i<=20; $i++){
		if ($i < 12) echo "<th bgcolor=$colors[$c] colspan=2>$i AM</th>";
		else if ($i == 12) echo "<th bgcolor=$colors[$c] colspan=2>$i PM</th>";
		else if ($i > 12) {
			echo "<th bgcolor=$colors[$c] colspan=2>";
			echo $i % 12;
			echo " PM</th>";
		}
		$c = ($c + 1) % 2;
	}
	echo "</tr>";
	echo "<tr><td>&nbsp;</td>";
	$c = 0;
	for ($i = 7; $i <=20; $i++, $c=($c+1)%2)
		echo "<td bgcolor=$colors[$c]>Total</td><td bgcolor=$colors[$c]>Avg</td>";
	echo "</tr>";
	
	//echo $hourlySalesQ."<br />";
	$prep = $dbc->prepare_statement($hourlySalesQ);
	$hourlySalesR = $dbc->exec_statement($prep,$args);
	$curDay = "";
	$expectedHour = 7;
	$c = 0;
	$days = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
	while ($hourlyW = $dbc->fetch_array($hourlySalesR)){
		if ($curDay != $hourlyW[0]){
			if ($curDay != ""){
				while ($expectedHour++ <= 20){
					echo "<td bgcolor=$colors[$c]>&nbsp;</td><td bgcolor=$colors[$c]>&nbsp;</td>";
					$c = ($c+1) %2;
				}
				echo "</tr>";
			}
			echo "<tr>";
			$curDay = $hourlyW[0];
			echo "<td>".$days[$curDay-1]."</td>";
			$expectedHour = 7;
			$c = 0;
		}	
		$hour = $hourlyW[1];
		$sum = $hourlyW[2];
		$avg = $hourlyW[3];
		while ($hour > $expectedHour){
			echo "<td bgcolor=$colors[$c]>&nbsp;</td>";
			echo "<td bgcolor=$colors[$c]>&nbsp;</td>";
			$expectedHour++;
			$c = ($c+1) %2;
		}
		echo "<td bgcolor=$colors[$c]>$sum</td>";
		echo "<td bgcolor=$colors[$c]>$avg</td>";
		$expectedHour++;
		$c = ($c+1) %2;
	}
	while ($expectedHour++ <= 20){
		echo "<td bgcolor=$colors[$c]>&nbsp;</td><td bgcolor=$colors[$c]>&nbsp;</td>";
		$c = ($c+1) % 2;
	}
	echo "</tr></table>";

}
else {
$page_title = "Fannie : Department Hourly Sales";
$header = "Department Hourly Sales";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
<script type="text/javascript">
function update(targetdiv,sourcediv){
	document.getElementById(targetdiv).value = document.getElementById(sourcediv).value;
}
</script>
<?php
	$deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
	$deptR = $dbc->exec_statement($deptQ);
	$depts = array();
	$dept_nos = array();
	$count = 0;
	while ($deptW = $dbc->fetch_array($deptR)){
		$dept_nos[$count] = $deptW[0];
		$depts[$count++] = $deptW[1];
	}
?>
<form name='addBatch' action = 'hourlySalesDept.php' method='GET'>
<table>
<tr><td>Dept. Start</td><td>
	<input type=text name=deptStart id=deptStart size=4 value=1 />
	<select id=deptStartSelect onchange="update('deptStart','deptStartSelect');">
	<?php
	for ($i = 0; $i < $count; $i++)
		echo "<option value=$dept_nos[$i]>$dept_nos[$i] $depts[$i]</option>";
	?>
	</select>
	</td>
     <td>Dept. End</td><td>
	<input type=text name=deptEnd id=deptEnd size=4 value=1 />
	<select id=deptEndSelect  onchange="update('deptEnd','deptEndSelect');">
	<?php
	for ($i = 0; $i < $count; $i++)
		echo "<option value=$dept_nos[$i]>$dept_nos[$i] $depts[$i]</option>";
	?>
	</select>
     </td>
</tr><tr>
	<td>Start Date</td>
     <td><input name="startDate" onfocus="this.value='';showCalendarControl(this);" type="text"></td>
	<td>End Date</td>
     <td><input name="endDate" onfocus="this.value='';showCalendarControl(this);" type="text"></td>
</tr><tr>
     <td colspan=2><input type=checkbox name=weekday value=1>Group by weekday?</td>
     <td><input type =submit name=submit value ="Get Report"></td></tr>
</table>
</form>

<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
