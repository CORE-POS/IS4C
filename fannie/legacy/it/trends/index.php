<?php
include('../../../config.php');
header("Location: {$FANNIE_URL}reports/Trends/");
exit;

/* first take at requested per day
 * movement report. Not the desired data layout,
 * but functional. Could be useful for something else
 * prints all upcs for a given day rather than all days
 * for a given upc
 */

include_once('../../functions.php');

if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
include('../../db.php');

if ($_GET['dept1']){
	$dept1 = $_GET['dept1'];
	$dept2 = $_GET['dept2'];
	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$daysperrow = $_GET['daysperrow'];
	
	if (isset($_GET['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="movementDays.xls"');
	}
	
	$dlog = select_dlog($date1);

	$query = 	"select 
				year(d.tdate) as year,
				month(d.tdate) as month,
				day(d.tdate) as day,
				d.upc, p.description, 
				sum(d.total) as total 
				from $dlog as d left join products as p on d.upc = p.upc
				where d.department between $dept1 and $dept2
				and datediff(dd,d.tdate,'$date1') <= 0
				and datediff(dd,d.tdate,'$date2') >= 0
				group by year(d.tdate),month(d.tdate),day(d.tdate),
				d.upc,p.description
				order by year(d.tdate),month(d.tdate),day(d.tdate),sum(d.total) desc";
	$result = $sql->query($query);
	echo "<table>";
	$current_day = -1;
	$count = 0;
	$i = 0;
	$sum = 0;
	$data = array();
	echo "<tr>";
	while ($row = $sql->fetch_array($result)){	

		if ($current_day != $row['day']){
			for ($j = 0; $j < count($data); $j++){
				if ($j == 0){
					echo "<td valign=top align=left>";
					echo "<b>".$data[0]['month']."/".$data[0]['day']."/".$data[0]['year'];
					echo "</b><br />";
					echo "Total: $sum<br />";
					echo "<table border=1>";
				}
				echo "<tr>";
				echo "<td>".$data[$j]['upc']."</td>";
				echo "<td>".$data[$j]['description']."</td>";
				echo "<td>".$data[$j]['total']."</td>";
				echo "</tr>";
			}
			if ($current_day != -1){
				echo "</table></td>";
				$count++;
			}
		
			$sum = 0;
			$current_day = $row['day'];
			$data = array();
			$i = 0;
			if ($count % $daysperrow == 0)
				echo "</tr><tr>";
		}
		$data[$i] = $row;
		$sum += $row['total'];
		
		$i++;
	}
	for ($j = 0; $j < count($data); $j++){
		if ($j == 0){
			echo "<td valign=top>";
			echo "<b>".$data[0]['month']."/".$data[0]['day']."/".$data[0]['year'];
			echo "</b><br />";
			echo "Total: $sum<br />";
			echo "<table border=1>";
		}
		echo "<tr>";
		echo "<td>".$data[$j]['upc']."</td>";
		echo "<td>".$data[$j]['description']."</td>";
		echo "<td>".$data[$j]['total']."</td>";
		echo "</tr>";
	}
	if ($current_day != -1){
		echo "</table></td>";
		$count++;
	}
			
	echo "</tr></table>";
}
else {
	$deptsQ = "select dept_no,dept_name from departments order by dept_no";
	$deptsR = $sql->query($deptsQ);
	$deptsList = "";
	while ($deptsW = $sql->fetch_array($deptsR))
	  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";	
?>
<HTML>
<head>
<title>Query</title>
<link href="../../CalendarControl/CalendarControl.css"
      rel="stylesheet" type="text/css">
<script src="../../CalendarControl/CalendarControl.js"
        language="javascript"></script>
<script type="text/javascript">
function swap(src,dst){
	var val = document.getElementById(src).value;
	document.getElementById(dst).value = val;
}
</script>
</head>
<body>
<form method=get action=index.php>
<table><tr>
<td>Start department:</td><td>
<select id=dept1Sel onchange="swap('dept1Sel','dept1');">
<?php echo $deptsList ?>
</select>
<input type=text name=dept1 id=dept1 size=5 value=1 />
</td>
<td>Start date:</td><td><input type=text name=date1 onfocus="showCalendarControl(this);"/></td></tr>
<tr><td>End department:</td><td>
<select id=dept2Sel onchange="swap('dept2Sel','dept2');">
<?php echo $deptsList ?>
</select>
<input type=text name=dept2 id=dept2 size=5 value=1 />
</td>
<td>End date:</td><td><input type=text name=date2 onfocus="showCalendarControl(this);"/></td>
</tr></table><br />
Days shown per row: <input type=text name=daysperrow value=2 size=5 /> 
Excel <input type=checkbox name=excel /><br />
<input type=submit value=Submit />
</form>
<?php
}
?>
