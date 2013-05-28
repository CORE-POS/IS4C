<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

	$date1 = $_GET['date1'];
        $date32 =$_GET['date2'];
	$date2 = $_GET['date2'];
	$deptStart = $_GET['deptStart'];
	$deptEnd = $_GET['deptEnd'];
	$sort = $_GET['sort'];
	
	$dir = 'DESC';
	if (isset($_GET['dir']))
		$dir = $_GET['dir'];
	$order = 'sum(t.total)';
	if (isset($_GET['order']))
		$order = $_GET['order'];
	$revdir = 'ASC';
	if ($dir == 'ASC')
		$revdir = 'DESC';
	
	if(isset($_GET['buyer'])){
	   $buyer = $_GET['buyer'];
	}

	if(isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport.xls"');
	}

	ob_start();

	echo "<html><head><title>Query Results</title>";
	echo "</head>";

	echo "<body>";
		
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
	echo "Report summed by ";
	echo $_GET['sort'] . " on ";
	echo "<br>";
	echo $today;
	echo "<br>";
	echo "From ";
	print $date1;
	echo " to ";
	print $date2;
	if (!isset($_REQUEST['excel'])){
	echo "<br>";
		if(isset($buyer) && $buyer != 0){
		   echo "    Buyer/Dept: ";
		   $buyerQ = $dbc->prepare_statement("SELECT super_name as name FROM superDeptNames where superID = ?");
		   $buyerR = $dbc->exec_statement($buyerQ,array($buyer));
		   $buyerW = $dbc->fetch_array($buyerR);
		   $buyName = $buyerW['name'];
		   echo $buyName;
		}else{
		   echo "    Department range: ";
		   print $deptStart;
		   echo " to ";	
		   print $deptEnd;
		}
		echo "<br>";
		echo "<a href=report.php?excel=1&buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd&date1=$date1&date2=$date2&sort=$sort&order=$order&dir=$dir>Save</a> to Excel<br />";
	}
	
	$dlog = select_dlog($date1,$date2);

	$date2a = $date2 . " 23:59:59";
	$date1a = $date1 . " 00:00:00";
	//decide what the sort index is and translate from lay person to mySQL table label

	$args = array();
	if($sort == "Date"){
		$query = "";
		if(isset($buyer) && $buyer > 0){
			$query = "SELECT year(tdate),month(tdate),day(tdate),
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  WHERE s.superID = ? AND trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY year(tdate),month(tdate),day(tdate)
			  ORDER BY year(tdate),month(tdate),day(tdate)";
			$args = array($buyer);
		}
		else if (isset($buyer) && $buyer == -1){
			$query = "SELECT year(tdate),month(tdate),day(tdate),
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  WHERE trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY year(tdate),month(tdate),day(tdate)
			  ORDER BY year(tdate),month(tdate),day(tdate)";
		}
		else if (isset($buyer) && $buyer == -2){
			$query = "SELECT year(tdate),month(tdate),day(tdate),
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  WHERE s.superID <> 0 AND trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY year(tdate),month(tdate),day(tdate)
			  ORDER BY year(tdate),month(tdate),day(tdate)";
		}
		else {
			$query = "SELECT year(tdate),month(tdate),day(tdate),
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  WHERE t.department BETWEEN ? AND ?
			  AND trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY year(tdate),month(tdate),day(tdate)
			  ORDER BY year(tdate),month(tdate),day(tdate)";
			$args = array($deptStart,$deptEnd);
		}
		$args[] = $date1a;
		$args[] = $date2a;
		$prep = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($prep,$args);
		echo "<table border=1>\n"; //create table
		echo "<tr>";
		echo "<th>Day</th><th>$</th><th># Open Rings</th><th>%</th>";
		echo "</tr>\n";//create table header
		
		while ($w = $dbc->fetch_row($result)) { //create array from query
		
			printf("<tr><td>%d/%d/%d</td><td>%.2f</td><td>%.2f</td><td>%.2f%%</td></tr>",
				$w[1],$w[2],$w[0],$w['total'],$w['qty'],$w['percentage']*100);
		//convert row information to strings, enter in table cells
		
		}
		
		echo "</table>\n";//end table

	}else if ($sort=="Department"){ //create alternate query if not sorting by PLU
		$query="";
		$args = array();
		if(isset($buyer) && $buyer>0){
			$query = "SELECT t.department,d.dept_name,
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  LEFT JOIN departments AS d ON t.department=d.dept_no
			  WHERE s.superID = ? AND trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY t.department,d.dept_name
			  ORDER BY t.department,d.dept_name";
			$args = array($buyer);
		}
		else if (isset($buyer) && $buyer == -1){
			$query = "SELECT t.department,d.dept_name,
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  LEFT JOIN departments AS d ON t.department=d.dept_no
			  WHERE trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY t.department,d.dept_name
			  ORDER BY t.department,d.dept_name";
		}
		else if (isset($buyer) && $buyer == -2){
			$query = "SELECT t.department,d.dept_name,
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  LEFT JOIN departments AS d ON t.department=d.dept_no
			  WHERE s.superID <> 0 AND trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY t.department,d.dept_name
			  ORDER BY t.department,d.dept_name";
		}
		else {
			$query = "SELECT t.department,d.dept_name,
			  SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) as total,
			  SUM(CASE WHEN trans_type='D' THEN abs(quantity) ELSE 0 END) as qty,
			  SUM(CASE WHEN trans_type='D' THEN 1.0 ELSE 0.0 END) /
			  SUM(CASE WHEN trans_type IN ('I','D') THEN 1.0 ELSE 0.0 END) as percentage
			  FROM $dlog as t 
			  LEFT JOIN MasterSuperDepts AS s ON t.department = s.dept_ID
			  LEFT JOIN departments AS d ON t.department=d.dept_no
			  WHERE t.department BETWEEN ? AND ?
			  AND trans_type IN ('I','D')
			  AND tdate BETWEEN ? AND ?
			  GROUP BY t.department,d.dept_name
			  ORDER BY t.department,d.dept_name";
			$args = array($deptStart,$deptEnd);
		}
		//echo $query;
		$args[] = $date1a;
		$args[] = $date2a;
		$prep = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($prep,$args);

		echo "<table border=1>\n";//create table
		echo "<tr>";
		echo "<th>Dept.</th><th>Name</th><th>$</th><th># Open Rings</th><th>%</th>";
		echo "</tr>";
	
		while ($w = $dbc->fetch_row($result)) { //create array from query
			printf("<tr><td>%d</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f%%</td></tr>",
				$w['department'],$w['dept_name'],$w['total'],
				$w['qty'],$w['percentage']*100);
		}
		echo "</table>\n";

	}

	$output = ob_get_contents();
	ob_end_clean();

	if (!isset($_REQUEST['excel'])){
		echo $output;
		echo "</body></html>";
	}
	else {
		include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
		//include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
		include($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');
		$array = HtmlToArray($output);
		//$xls = ArrayToXls($array);
		$xls = ArrayToCsv($array);
		echo $xls;
	}
?>
