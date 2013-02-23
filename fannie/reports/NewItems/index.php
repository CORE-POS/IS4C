<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

if (isset($_GET['date1'])){

	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$dept1 = isset($_REQUEST['dept1'])?$_REQUEST['dept1']:0;
	$dept2 = isset($_REQUEST['dept2'])?$_REQUEST['dept2']:0;
	$super = isset($_REQUEST['super'])?$_REQUEST['super']:'';

	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport'.$manu.'.xls"');
	}
	$sort = "entryDate, a.upc";
	if (isset($_REQUEST['sort'])) $sort = $_REQUEST['sort'];

	$query = "SELECT MIN(a.modified) AS entryDate, a.upc, p.description, p.department, d.dept_name
		FROM prodUpdateArchive AS a INNER JOIN products AS p ON a.upc=p.upc
		LEFT JOIN departments AS d ON d.dept_no=p.department
		LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
		WHERE ";
	if (is_numeric($super)){
		if ($super != -1)
			$query .= "s.superID=$super ";	
		else
			$query .= "1=1 ";
	}
	else {
		$query .= "p.department BETWEEN $dept1 AND $dept2 ";
	}
	$query .= "GROUP BY a.upc, p.description, p.department, d.dept_name
		HAVING MIN(a.modified) BETWEEN '$date1' AND '$date2'
		ORDER BY $sort";
	$result = $dbc->query($query);

	$qs = "<a href=index.php?date1={$_REQUEST['date1']}&date2={$_REQUEST['date2']}&dept1=$dept1&depts=$dept2&super=$super";
	if (!isset($_GET['excel'])){
		echo $qs."&sort=$sort&excel=yes>Save</a> to Excel<br />";
	}

	echo $date1." through ".$date2;
	echo "<table cellpadding=2 cellspacing=0 border=1>";
	echo "<tr>";
	printf("<th>Entry Date</th><th>UPC</th><th>Description</th><th>Dept #</th>
		<th>Dept Name</th></tr>");
	echo "</tr>";

	while($row = $dbc->fetch_row($result)){
		printf("<tr><td>%s</td><td>%s</td><td>%s</td>
			<td>%d</td><td>%s</td></tr>",
			$row['entryDate'],$row['upc'],
			$row['description'],
			$row['department'],$row['dept_name']);
	}
	echo "</table>";

	return;
}

$deptsQ = "select dept_no,dept_name from departments order by dept_no";
$deptsR = $dbc->query($deptsQ);
$deptsList = "";

$deptSubQ = "SELECT superID,super_name FROM MasterSuperDepts
		WHERE superID <> 0 
		group by superID,super_name
		ORDER BY superID";
$deptSubR = $dbc->query($deptSubQ);

$deptSubList = "";
while($deptSubW = $dbc->fetch_array($deptSubR)){
  $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
}
while ($deptsW = $dbc->fetch_array($deptsR))
  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";

$page_title = "Fannie : New Items Report";
$header = "New Items Report";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
	$('#buyerR:checked').each(function(){
		$('#buyerrow').show();
		$('#deptrow').hide();
	});
	$('#deptR:checked').each(function(){
		$('#deptrow').show();
		$('#buyerrow').hide();
	});
	sync('d1t','d1s');
	sync('d2t','d2s');

	$('#buyerR').click(function(){
		$('#buyerrow').show();
		$('#deptrow').hide();
	});

	$('#deptR').click(function(){
		$('#deptrow').show();
		$('#buyerrow').hide();
	});
});
function sync(id1,id2){
	$('#'+id1).val($('#'+id2).val());
}
</script>
<div id=main>	
<form method = "get" action="index.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr><td><input type=radio name=bytype checked id=buyerR><label for="buyerR">By buyer</label>
			<input type=radio name=bytype id=deptR><label for="deptR">By department</label>
		</td></tr>
		<tr id="buyerrow">
			<td>Buyer</td>
			<td><select name=super><option value=""></option>
			<option value="-1">(all)</option>
			<?php echo $deptSubList; ?>
			</select></td>
		</tr>
		<tr id="deptrow" style="display:none;">
			<td>
			<p><b>Dept Start</b></p>
			<p><b>Dept End</b></p>
			</td>
			<td>
			<p><input type=text name=dept1 size=3 id=d1t />
			<select id=d1s onchange="sync('d1t','d1s');">
			<?php echo $deptsList; ?>
			</select></p>
			<p><input type=text name=dept2 size=3 id=d2t />
			<select id=d2s onchange="sync('d2t','d2s');">
			<?php echo $deptsList; ?>
			</select></p>
			</td>
		</tr>
		<tr> 
			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>Date End</b></p>
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
		<td> <input type=submit name=submit value="Submit"> </td>
		<td><input type=checkbox name=excel /> Excel </td>
		<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>



