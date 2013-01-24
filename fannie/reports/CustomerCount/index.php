<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
header('Location: CustomerCountReport.php');

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_REQUEST['submit'])){
	$d1 = $_REQUEST['date1'];
	$d2 = $_REQUEST['date2'];

	$dlog = select_dlog($d1,$d2);

	if (isset($_REQUEST['excel'])){
		header("Content-Disposition: inline; filename=customers_{$d1}_{$d2}.xls");
		header("Content-type: application/vnd.ms-excel; name='excel'");
	}
	else{
		printf("<a href=index.php?date1=%s&date2=%s&submit=yes&excel=yes>Save to Excel</a>",
			$d1,$d2);
	}

	$sales = "SELECT year(tdate) as year, month(tdate) as month,
			day(tdate) as day,max(memType) as memType,trans_num
			FROM $dlog as t
			WHERE 
			tdate BETWEEN '$d1 00:00:00' AND '$d2 23:59:59'
			and trans_type in ('I','D')
			AND upc <> 'RRR'
			group by year(tdate),month(tdate),day(tdate),trans_num
			order by year(tdate),month(tdate),day(tdate),max(memType)";
	$data = array();
	$result = $dbc->query($sales);
	while($row = $dbc->fetch_row($result)){
		$stamp = date("M j, Y",mktime(0,0,0,$row['month'],$row['day'],$row['year']));
		if (!isset($data[$stamp])) $data[$stamp] = array("ttl"=>0);
		if (!isset($data[$stamp][$row['memType']])) $data[$stamp][$row['memType']] = 0;
		$data[$stamp]["ttl"]++;
		$data[$stamp][$row['memType']]++;
	}

	$cols = array(0=>"NonMember",1=>"Member",2=>"Business",3=>"Staff Member",
			4=>"Nabs",9=>"Staff NonMem");

	$placeholder = isset($_REQUEST['excel'])?'':'&nbsp;';

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr><th>Date</th>';
	foreach($cols as $k=>$label)
		echo '<th>'.$label.'</th>';
	echo '<th>Total</th></tr>';
	$sum = 0;
	foreach($data as $date=>$row){
		echo '<tr><td>'.$date.'</td>';
		foreach($cols as $k=>$v){
			if (isset($row[$k])) echo '<td>'.$row[$k].'</td>';
			else echo '<td>'.$placeholder.'</td>';
		}
		echo '<td>'.$row["ttl"].'</td></tr>';
		$sum += $row["ttl"];
	}
	echo '<tr><td>Grand Total</td>';
	echo '<td colspan="'.count($cols).'">'.$placeholder.'</td>';
	echo '<td>'.$sum.'</td></tr>';
	echo '</table>';

			
}
else {

$page_title = "Fannie : Customer Report";
$header = "Customer Report";
include($FANNIE_ROOT.'src/header.html');
$lastMonday = "";
$lastSunday = "";

$ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
while($lastMonday == "" || $lastSunday == ""){
	if (date("w",$ts) == 1 && $lastSunday != "")
		$lastMonday = date("Y-m-d",$ts);
	elseif(date("w",$ts) == 0)
		$lastSunday = date("Y-m-d",$ts);
	$ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));	
}
?>
<script type="text/javascript"
	src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js">
</script>
<form action=index.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
<th>Start Date</th>
<td><input type=text name=date1 onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" /></td>
</tr><tr>
<th>End Date</th>
<td><input type=text name=date2 onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" /></td>
</tr><tr>
<td>Excel <input type=checkbox name=excel /></td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
