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

if (isset($_REQUEST['deptStart'])){
	if (isset($_REQUEST['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="Margin Movement.xls"');
	}

	$dept1 = $_REQUEST['deptStart'];
	$dept2 = $_REQUEST['deptEnd'];
	$date1 = $_REQUEST['date1'];
	$date2 = $_REQUEST['date2'];

	$dlog = select_dtrans($date1,$date2);

	$query = $dbc->prepare_statement("SELECT d.upc,p.description,d.department,t.dept_name,
		sum(total) as total,sum(d.cost) as cost
		FROM $dlog AS d INNER JOIN products AS p
		ON d.upc=p.upc LEFT JOIN departments AS t 
		ON d.department=t.dept_no
		WHERE datetime BETWEEN '$date1 00:00:00' AND '$date2 23:59:59'
		AND d.department BETWEEN $dept1 AND $dept2
		AND d.discounttype=0
		AND d.cost <> 0
		AND trans_status NOT IN ('X','Z')
		AND emp_no <> 9999 and register_no <> 99
		GROUP BY d.upc,p.description,d.department,t.dept_name
		ORDER BY sum(total) DESC)");
	$args = array($date1,$date2,$dept1,$dept2);
	$result = $dbc->exec_statement($query,$args);
	$data = array();
	$sumT = 0;
	$sumC = 0;
	while($row = $dbc->fetch_row($result)){
		$data[$row['upc']] = array(
			"desc"=>$row['description'],
			"dept_no"=>$row['department'],
			"dept_name"=>$row['dept_name'],
			"ttl"=>$row['total'],
			"cost"=>$row['cost']
		);
		$data[$row['upc']]['margin'] = 100*($row['total']-$row['cost'])/$row['total'];
		$sumT += $row['total'];
		$sumC += $row['cost'];
	}
	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr><th>UPC</th><th>Desc</th><th colspan="2">Dept</th>';
	echo '<th>Cost</th><th>Sales</th><th>Margin</th>';
	echo '<th colspan="2">Contribution</th></tr>';
	foreach($data as $upc=>$row){
		printf('<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td>
			<td>$%.2f</td><td>$%.2f</td><td>%.2f%%</td>
			<td>$%.2f</td><td>%.2f%%</td></tr>',
			$upc,$row['desc'],$row['dept_no'],$row['dept_name'],
			$row['cost'],$row['ttl'],$row['margin'],
			$row['ttl']-$row['cost'],
			100*($row['ttl']-$row['cost'])/$sumT);
	}
	echo '</table>';
	exit;
}

$deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
$deptsR = $dbc->exec_statement($deptsQ);
$deptsList = "";
while ($deptsW = $dbc->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

$page_title = "Fannie: Margin";
$header = "Margin Report";
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




