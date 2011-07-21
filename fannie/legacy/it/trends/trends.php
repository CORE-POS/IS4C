<?php
include('../../../config.php');
header("Location: {$FANNIE_URL}reports/Trends/");
exit;

include_once('../../functions.php');

if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
include('../../db.php');

if (isset($_GET['type'])){
	$date1 = "";
	$date2 = "";
	$dept1 = 0;
	$dept2 = 0;
	$manufacturer = "";
	$man_type = "";
	$upc = "";
	switch ($_GET["type"]){
	case 'dept':
		$date1 = $_GET["date1d"];
		$date2 = $_GET["date2d"];
		$dept1 = $_GET["dept1"];
		$dept2 = $_GET["dept2"];
		break;
	case 'manu':
		$date1 = $_GET["date1m"];
		$date2 = $_GET["date2m"];
		$manufacturer = $_GET["manufacturer"];	
		$man_type = $_GET["mtype"];
		break;
	case 'upc':
		$date1 = $_GET["date1u"];
		$date2 = $_GET["date2u"];
		$upc = str_pad($_GET["upc"],13,'0',STR_PAD_LEFT);
		break;
	}	

	if (isset($_GET['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="movementDays.xls"');
	}

	$dlog = select_dlog($date1,$date2);
	
	$query = "";
	switch ($_GET["type"]){
	case 'dept':
		$query = "select 
			year(d.tdate) as year,
			month(d.tdate) as month,
			day(d.tdate) as day,
			d.upc, p.description, 
			sum(d.quantity) as total 
			from $dlog as d left join products as p on d.upc = p.upc
			where d.department between $dept1 and $dept2
			and datediff(dd,d.tdate,'$date1') <= 0
			and datediff(dd,d.tdate,'$date2') >= 0
			group by year(d.tdate),month(d.tdate),day(d.tdate),
			d.upc,p.description
			order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
		break;
	case 'manu':
		if ($man_type == "name"){
			$query = "select 
				year(d.tdate) as year,
				month(d.tdate) as month,
				day(d.tdate) as day,
				d.upc, p.description, 
				sum(d.quantity) as total 
				from $dlog as d left join products as p on d.upc = p.upc
				left join prodExtra as x on p.upc = x.upc
				where x.manufacturer = '$manufacturer' 
				and datediff(dd,d.tdate,'$date1') <= 0
				and datediff(dd,d.tdate,'$date2') >= 0
				group by year(d.tdate),month(d.tdate),day(d.tdate),
				d.upc,p.description
				order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
		}
		else {
			$query = "select 
				year(d.tdate) as year,
				month(d.tdate) as month,
				day(d.tdate) as day,
				d.upc, p.description, 
				sum(d.quantity) as total 
				from $dlog as d left join products as p on d.upc = p.upc
				where p.upc like '%$manufacturer%' 
				and datediff(dd,d.tdate,'$date1') <= 0
				and datediff(dd,d.tdate,'$date2') >= 0
				group by year(d.tdate),month(d.tdate),day(d.tdate),
				d.upc,p.description
				order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
		}
		break;
	case 'upc':
		$query = "select 
			year(d.tdate) as year,
			month(d.tdate) as month,
			day(d.tdate) as day,
			d.upc, p.description, 
			sum(d.quantity) as total 
			from $dlog as d left join products as p on d.upc = p.upc
			where p.upc = '$upc' 
			and datediff(dd,d.tdate,'$date1') <= 0
			and datediff(dd,d.tdate,'$date2') >= 0
			group by year(d.tdate),month(d.tdate),day(d.tdate),
			d.upc,p.description
			order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
		break;
		
	}
	//echo $query;
	$result = $sql->query($query);
	
	// let python get dates for me - easier
	exec("./trends.py $date1 $date2",$datelist);
	$dates = explode(",",$datelist[0]);
	
	echo "<table border=1><tr>";
	echo "<th>UPC</th><th>Description</th>";
	foreach ($dates as $i)
		echo "<th>$i</th>";
	echo "</tr>";
	
	$current_upc = "";
	$current_desc = "";
	$data = array();
	// track upc while going through the rows, storing 
	// all data about a given upc before printing
	while ($row = $sql->fetch_array($result)){	
		if ($current_upc != $row['upc']){
			// print the data
			if ($current_upc != ""){
				echo "<tr>";
				echo "<td>".$current_upc."</td>";
				echo "<td>".$current_desc."</td>";
				foreach ($dates as $i){
					if (isset($data[$i]))
						echo "<td align=center>".$data[$i]."</td>";
					else
						echo "<td align=center>0</td>";
				}
				echo "</tr>";
			}
			// update 'current' values and clear data
			$current_upc = $row['upc'];
			$current_desc = $row['description'];
			$data = array();
		}
		// get a yyyy-mm-dd format date from sql results
		$year = $row['year'];
		$month = str_pad($row['month'],2,'0',STR_PAD_LEFT);
		$day = str_pad($row['day'],2,'0',STR_PAD_LEFT);
		$datestr = $year."-".$month."-".$day;
		
		// index result into data based on date string
		// this is to properly place data in the output table
		// even when there are 'missing' days for a given upc
		$data[$datestr] = $row['total'];
	}
	// print the last data set
	echo "<tr>";
	echo "<td>".$current_upc."</td>";
	echo "<td>".$current_desc."</td>";
	foreach ($dates as $i){
		if (isset($data[$i]))
			echo "<td align=center>".$data[$i]."</td>";
		else
			echo "<td align=center>0</td>";
	}
	echo "</tr>";
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

function doShow(which){
	var current = document.getElementById("current").value.charAt(0);
	var curDate1 = document.getElementById("date1"+current).value;
	var curDate2 = document.getElementById("date2"+current).value;

	if (which == "manu"){
		document.getElementById('dept_version').style.display='none';
		document.getElementById('manu_version').style.display='block';
		document.getElementById('upc_version').style.display='none';

		document.getElementById("date1m").value = curDate1;
		document.getElementById("date2m").value = curDate2;
	}
	else if (which == "dept"){
		document.getElementById('dept_version').style.display='block';
		document.getElementById('manu_version').style.display='none';
		document.getElementById('upc_version').style.display='none';

		document.getElementById("date1d").value = curDate1;
		document.getElementById("date2d").value = curDate2;
	}
	else if (which == "upc"){
		document.getElementById('dept_version').style.display='none';
		document.getElementById('manu_version').style.display='none';
		document.getElementById('upc_version').style.display='block';

		document.getElementById("date1u").value = curDate1;
		document.getElementById("date2u").value = curDate2;
	}
	document.getElementById("current").value = which;
}

function loader(){
	document.getElementById('manu_version').style.display='none';
	document.getElementById('upc_version').style.display='none';
}
</script>
</head>
<body onload="loader();">
<form method=get action=trends.php>
<input type=hidden id=current value=dept />
<b>Type</b>: <input type=radio name=type checked value=dept onclick="doShow('dept');" />Department
<input type=radio name=type value=manu onclick="doShow('manu');" />Manufacturer
<input type=radio name=type value=upc onclick="doShow('upc');" />Single item<br />

<div id=dept_version>
<table><tr>
<td>Start department:</td><td>
<select id=dept1Sel onchange="swap('dept1Sel','dept1');">
<?php echo $deptsList ?>
</select>
<input type=text name=dept1 id=dept1 size=5 value=1 />
</td>
<td>Start date:</td><td><input type=text id=date1d name=date1d onfocus="showCalendarControl(this);"/></td></tr>
<tr><td>End department:</td><td>
<select id=dept2Sel onchange="swap('dept2Sel','dept2');">
<?php echo $deptsList ?>
</select>
<input type=text name=dept2 id=dept2 size=5 value=1 />
</td>
<td>End date:</td><td><input type=text id=date2d name=date2d onfocus="showCalendarControl(this);"/></td>
</tr></table>
</div>

<div id=manu_version>
<table><tr>
<td>Manufacturer:</td><td>
<input type=text name=manufacturer />
</td>
<td>Start date:</td><td><input type=text id=date1m name=date1m onfocus="showCalendarControl(this);"/></td></tr>
<tr><td></td><td>
<input type=radio name=mtype value=name checked />Name
<input type=radio name=mtype value=prefix />UPC prefix
</td>
<td>End date:</td><td><input type=text id=date2m name=date2m onfocus="showCalendarControl(this);"/></td>
</tr></table>
</div>

<div id=upc_version>
<table><tr>
<td>UPC:</td><td>
<input type=text name=upc />
</td>
<td>Start date:</td><td><input type=text id=date1u name=date1u onfocus="showCalendarControl(this);"/></td></tr>
<tr><td></td><td>
</td>
<td>End date:</td><td><input type=text id=date2u name=date2u onfocus="showCalendarControl(this);"/></td>
</tr></table>
</div>

<br />
Excel <input type=checkbox name=excel /><br />
<input type=submit value=Submit />
</form>
</body>
</html>
<?php
}
?>
