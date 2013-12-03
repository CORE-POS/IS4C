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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

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
		$upc = BarcodeLib::padUPC($_GET['upc']);
		break;
	case 'likecode':
		$date1 = $_GET["date1l"];
		$date2 = $_GET["date2l"];
		$lc = $_GET["likeCode"];	
		$lc2 = $_GET["likeCode2"];
		break;
	}	

	if (isset($_GET['excel'])){
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="movementDays.xls"');
	}

	$dlog = select_dlog($date1,$date2);
	
	$query = "";
	$args = array();
	switch ($_GET["type"]){
	case 'dept':
		$query = "select 
			year(d.tdate) as year,
			month(d.tdate) as month,
			day(d.tdate) as day,
			d.upc, p.description, 
			sum(d.quantity) as total 
			from $dlog as d left join products as p on d.upc = p.upc
			where d.department between ? AND ?
			AND d.tdate BETWEEN ? AND ?
			and trans_status <> 'M'
			group by year(d.tdate),month(d.tdate),day(d.tdate),
			d.upc,p.description
			order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
		$args = array($dept1,$dept2);
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
				where x.manufacturer = ?
				AND d.tdate BETWEEN ? AND ?
				and trans_status <> 'M'
				group by year(d.tdate),month(d.tdate),day(d.tdate),
				d.upc,p.description
				order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
			$args = array($manufacturer);
		}
		else {
			$query = "select 
				year(d.tdate) as year,
				month(d.tdate) as month,
				day(d.tdate) as day,
				d.upc, p.description, 
				sum(d.quantity) as total 
				from $dlog as d left join products as p on d.upc = p.upc
				where p.upc like ?
				AND d.tdate BETWEEN ? AND ?
				and trans_status <> 'M'
				group by year(d.tdate),month(d.tdate),day(d.tdate),
				d.upc,p.description
				order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
			$args = array('%'.$manufacturer.'%');
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
			where p.upc = ?
			AND d.tdate BETWEEN ? AND ?
			and trans_status <> 'M'
			group by year(d.tdate),month(d.tdate),day(d.tdate),
			d.upc,p.description
			order by d.upc,year(d.tdate),month(d.tdate),day(d.tdate)";
		$args = array($upc);
		break;

	case 'likecode':
		$query = "select 
			year(d.tdate) as year,
			month(d.tdate) as month,
			day(d.tdate) as day,
			p.likeCode as upc, l.likeCodeDesc as description,
			sum(d.quantity) as total 
			from $dlog as d left join upcLike as p on d.upc = p.upc
			left join likeCodes AS l ON p.likeCode=l.likeCode
			where p.likeCode BETWEEN ? AND ?
			AND d.tdate BETWEEN ? AND ?
			and trans_status <> 'M'
			group by year(d.tdate),month(d.tdate),day(d.tdate),
			p.likeCode, l.likeCodeDesc
			order by p.likeCode,year(d.tdate),month(d.tdate),day(d.tdate)";
		$args = array($lc,$lc2);
		break;
		
		
	}
	$args[] = $date1.' 00:00:00';
	$args[] = $date2.' 23:59:59';
	$prep = $dbc->prepare_statement($query);
	$result = $dbc->exec_statement($prep,$args);
	
	$dates = array();
	while($date1 != $date2) {
		$dates[] =  $date1;
		$parts = explode("-",$date1);
		if (count($parts) != 3) break;
		$date1 = date("Y-m-d",mktime(0,0,0,$parts[1],$parts[2]+1,$parts[0]));
	} 
	$dates[] = $date2;
	
	echo "<table border=1><tr>";
	echo "<th>UPC</th><th>Description</th>";
	foreach ($dates as $i)
		echo "<th>$i</th>";
	echo "<th>Total</th>";
	echo "</tr>";
	
	$current_upc = "";
	$current_desc = "";
	$data = array();
	// track upc while going through the rows, storing 
	// all data about a given upc before printing
	while ($row = $dbc->fetch_array($result)){	
		if ($current_upc != $row['upc']){
			// print the data
			if ($current_upc != ""){
				echo "<tr>";
				echo "<td>".$current_upc."</td>";
				echo "<td>".$current_desc."</td>";
				$sum = 0;
				foreach ($dates as $i){
					if (isset($data[$i])){
						echo "<td align=center>".$data[$i]."</td>";
						$sum += $data[$i];
					}
					else
						echo "<td align=center>0</td>";
				}
				printf("<td>%.2f</td>",$sum);
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
	$sum = 0;
	foreach ($dates as $i){
		if (isset($data[$i])){
			echo "<td align=center>".$data[$i]."</td>";
			$sum += $data[$i];
		}
		else
			echo "<td align=center>0</td>";
	}
	printf("<td>%.2f</td>",$sum);
	echo "</tr>";
	echo "</table>";
}
else {

	$page_title = "Fannie : Trends";
	$header = "Trend Report";
	include($FANNIE_ROOT.'src/header.html');

	$deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
	$deptsR = $dbc->exec_statement($deptsQ);
	$deptsList = "";
	while ($deptsW = $dbc->fetch_array($deptsR))
	  $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";	
?>
<script src="<?php echo $FANNIE_URL ?>src/CalendarControl.js"
        type="text/javascript"></script>
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
		document.getElementById('lc_version').style.display='none';

		document.getElementById("date1m").value = curDate1;
		document.getElementById("date2m").value = curDate2;
	}
	else if (which == "dept"){
		document.getElementById('dept_version').style.display='block';
		document.getElementById('manu_version').style.display='none';
		document.getElementById('upc_version').style.display='none';
		document.getElementById('lc_version').style.display='none';

		document.getElementById("date1d").value = curDate1;
		document.getElementById("date2d").value = curDate2;
	}
	else if (which == "upc"){
		document.getElementById('dept_version').style.display='none';
		document.getElementById('manu_version').style.display='none';
		document.getElementById('upc_version').style.display='block';
		document.getElementById('lc_version').style.display='none';

		document.getElementById("date1u").value = curDate1;
		document.getElementById("date2u").value = curDate2;
	}
	else if (which == "likecode"){
		document.getElementById('dept_version').style.display='none';
		document.getElementById('manu_version').style.display='none';
		document.getElementById('lc_version').style.display='block';
		document.getElementById('upc_version').style.display='none';

		document.getElementById("date1l").value = curDate1;
		document.getElementById("date2l").value = curDate2;
	}
	document.getElementById("current").value = which;
}
</script>

<form method=get action=index.php>
<input type=hidden id=current value=dept />
<b>Type</b>: <input type=radio name=type checked value=dept onclick="doShow('dept');" />Department
<input type=radio name=type value=manu onclick="doShow('manu');" />Manufacturer
<input type=radio name=type value=upc onclick="doShow('upc');" />Single item 
<input type=radio name=type value=likecode onclick="doShow('likecode');" />Like code<br />

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

<div id=manu_version style="display:none;">
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

<div id=upc_version style="display:none;">
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

<div id=lc_version style="display:none;">
<table><tr>
<td>LikeCode Start:</td><td>
<input type=text name=likeCode />
</td>
<td>Start date:</td><td><input type=text id=date1l name=date1l onfocus="showCalendarControl(this);"/></td></tr>
<tr>
<td>LikeCode End:</td><td>
<input type=text name=likeCode2 />
</td>
<td>End date:</td><td><input type=text id=date2l name=date2l onfocus="showCalendarControl(this);"/></td>
</tr></table>
</div>

<br />
Excel <input type=checkbox name=excel /><br />
<input type=submit value=Submit />
</form>
</body>
</html>
<?php
	include($FANNIE_ROOT.'src/footer.html');
}
?>
