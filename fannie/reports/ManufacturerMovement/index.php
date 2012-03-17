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

include('../../config2.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET['date1'])){

	$date1 = $_GET['date1']." 00:00:00";
	$date2 = $_GET['date2']." 23:59:59";
	$manu = $_GET['manu'];
	$type = $_GET['type'];
	$groupby = $_GET['groupby'];

	$sort = "sum(t.total)";
	if (isset($_GET['sort']))
		$sort = $_GET['sort'];
	$dir = "DESC";
	if (isset($_GET['dir'])){
		$dir = $_GET['dir'];
	}
	$otherdir = 'ASC';
	if ($dir == $otherdir)
		$otherdir = 'DESC';

	// because a series of datepart()s are being used to construct the date,
	// EACH one has to be sorted in the right direction
	$fixedsort = preg_replace("/\),/",") $dir, ",$sort);

	$dlog = select_dlog($date1,$date2);

	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport'.$manu.'.xls"');
	}

	$type_condition = "e.manufacturer like '%$manu%'";
	if ($type == 'prefix')
		$type_condition = "t.upc like '%$manu%'";	


	$manu = urlencode($manu);

	$query = "select t.upc,p.description,
		  sum(case when t.trans_status IN ('M') then t.itemqtty else t.quantity end) as qty,
		  sum(t.total),t.department,d.dept_name,s.superID
		  from $dlog as t left join products as p
		  on t.upc=p.upc left join prodExtra as e on p.upc = e.upc
		  left join departments as d on t.department = d.dept_no
		  left join MasterSuperDepts as s on d.dept_no = s.dept_ID
		  where $type_condition
		  and t.tdate between '$date1' and '$date2'
		  group by $groupby,p.description,t.department,d.dept_name,s.superID
		  order by $fixedsort $dir";
	$headers = array(7);
	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	if (!isset($_GET['excel'])){
		$headers[0] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=t.upc&dir=";
		if ($sort == 't.upc')
			$headers[0] .= "$otherdir>UPC</a>";
		else
			$headers[0] .= "ASC>UPC</a>";
		$headers[1] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=p.description&dir=";
		if ($sort == 'p.description')
			$headers[1] .= "$otherdir>Description</a>";
		else
			$headers[1] .= "ASC>Description</a>";
		$headers[2] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=sum(t.quantity)&dir=";
		if ($sort == 'sum(t.quantity)')
			$headers[2] .= "$otherdir>Qty</a>";
		else
			$headers[2] .= "DESC>Qty</a>";
		$headers[3] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=sum(t.total)&dir=";
		if ($sort == 'sum(t.total)')
			$headers[3] .= "$otherdir>Sales</a>";
		else
			$headers[3] .= "DESC>Sales</a>";
		$headers[4] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=t.department&dir=";
		if ($sort == 't.department')
			$headers[4] .= "$otherdir>Dept</a>";
		else
			$headers[4] .= "ASC>Dept</a>";
		$headers[5] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=d.dept_name&dir=";
		if ($sort == 'd.dept_name')
			$headers[5] .= "$otherdir>Dept</a>";
		else
			$headers[5] .= "ASC>Dept</a>";
		$headers[6] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=s.superID&dir=";
		if ($sort == 's.superID')
			$headers[6] .= "$otherdir>Subdept</a>";
		else
			$headers[6] .= "ASC>Subdept</a>";
	}
	else {
		$headers[0] = "UPC";
		$headers[1] = "Description";
		$headers[2] = "Qty";
		$headers[3] = "Sales";
		$headers[4] = "Dept";
		$headers[5] = "Dept";
		$headers[6] = "Subdept";
	}
	$headerCount = 7;
	if ($groupby == "year(t.tdate),month(t.tdate),day(t.tdate)"){
		$query = "select $groupby,sum(t.quantity),sum(t.total)
		  	  from products as p left join prodExtra as e on p.upc = e.upc
			  left join $dlog as t on p.upc = t.upc
			  where $type_condition
			  and t.tdate between '$date1' and '$date2'
			  group by $groupby
			  order by $fixedsort $dir";
		$headers[1] = $headers[2];
		$headers[2] = $headers[3];	
		if (!isset($_GET['excel'])){
			$headers[0] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=$groupby&dir=";
			if ($sort == $groupby)
				$headers[0] .= "$otherdir>Date</a>";
			else
				$headers[0] .= "ASC>Date</a>";
		}
		else {
			$headers[0] = "Date";
		}

		$headerCount = 3;	
	}
	else if ($groupby == "t.department"){
		$query = "select t.department,d.dept_name,sum(t.quantity),sum(t.total),s.superID
		  	  from products as p left join prodExtra as e on p.upc = e.upc
			  left join $dlog as t on p.upc = t.upc
			  left join departments as d on t.department = d.dept_no
			  left join MasterSuperDepts as s on d.dept_no=s.dept_ID
			  where $type_condition
			  and t.tdate between '$date1' and '$date2'
			  group by $groupby,d.dept_name,s.superID
			  order by $fixedsort $dir";
		$headers[0] = $headers[4];
		$headers[4] = $headers[6];
		if (!isset($_GET['excel'])){
			$headers[1] = "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=d.dept_name&dir=";
			if ($sort == 'd.dept_name')
				$headers[1] .= "$otherdir>Description</a>";
			else
				$headers[1] .= "ASC>Description</a>";
		}
		else {
			$headers[1] = "Description";
		}
		$headerCount = 5;	
	}
	//echo $query."<br />";
	$result = $dbc->query($query);

	// make headers sort links
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
	echo "Report summed by ";
	switch($groupby){
	case 't.upc':
		echo 'UPC'; break;
	case 't.department':
		echo 'department'; break;
	default:
		echo 'date'; break;
	}
	echo " on ";
	echo "</br>";
	echo $today;
	echo "</br>";
	echo "From ";
	print $date1;
	echo " to ";
	print $date2;
	echo "</br>";

	if (!isset($_GET['excel'])){
		echo "<a href=index.php?date1=$date1&date2=$date2&manu=$manu&type=$type&groupby=$groupby&sort=$sort&dir=$dir&excel=yes>Save</a> to Excel<br />";
	}

	echo "<table cellpadding=2 cellspacing=0 border=1>";
	echo "<tr>";
	for ($i = 0; $i < $headerCount; $i++)
		echo "<th>$headers[$i]</th>";
	echo "</tr>";
	while ($row = $dbc->fetch_array($result)){
		echo "<tr>";
		if ($groupby == "datepart(yy,t.tdate),datepart(mm,t.tdate),datepart(dd,t.tdate)"){
			echo "<td>$row[1]/$row[2]/$row[0]</td>";
			echo "<td>$row[3]</td>";
			echo "<td>$row[4]</td>";
		}
		else {
			for ($i = 0; $i < $headerCount; $i++)
				echo "<td>$row[$i]</td>";
		}
		echo "</tr>";	
	}
	echo "</table>";

	return;
}

$page_title = "Fannie : Manufacturer Movement";
$header = "Manufacturer Movement Report";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
<div id=main>	
<form method = "get" action="index.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<!--<tr>
			<td bgcolor="#CCFF66"><a href="csvQuery.php"><font color="#CC0000">Click 
here to create Excel Report</font></a></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>-->
		<tr> 
			<td> <p><b>Manufacturer</b></p>
			<p><b>Type</b></p>
			</td>
			<td><p>
			<input type=text name=manu id=manu  />
			</p>
			<p>
			<input type=radio name=type value=name checked />Name 
			<input type=radio name=type value=prefix />UPC Prefix
			</p>
			</td>

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
		<td><b>Sum report by</b></td>
		<td><select name=groupby>
		<option value="t.upc">UPC</option>
		<option value="year(t.tdate),month(t.tdate),day(t.tdate)">Date</option>
		<option value="t.department">Department</option>
		</select></td>
		</tr>
		<td> <input type=submit name=submit value="Submit"> </td>
		<td> <input type=reset name=reset value="Start Over"> 
		<input type=checkbox name=excel /> Excel </td>
		<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>



