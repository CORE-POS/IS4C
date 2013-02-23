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

	$date1 = $_GET['date1'];
        $date32 =$_GET['date2'];
	$date2 = $_GET['date2'];
	$deptStart = $_GET['deptStart'];
	$deptEnd = $_GET['deptEnd'];
	$sort = $_GET['sort'];
	
	$dir = 'DESC';
	if (isset($_GET['dir']))
		$dir = $_GET['dir'];
	$order = 'total';
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
	  header('Content-Disposition: attachment; filename="movementReport.csv"');
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
		   $buyerQ = "SELECT super_name as name FROM superDeptNames where superID = $buyer";
		   $buyerR = $dbc->query($buyerQ);
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
	$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumRingSalesByDay";
	if (substr($dlog,-4)=="dlog")
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."vRingSalesToday";

	$date2a = $date2 . " 23:59:59";
	$date1a = $date1 . " 00:00:00";
	//decide what the sort index is and translate from lay person to mySQL table label

	$groupBy="";$alias="";
	if($_GET['sort']=='Department'){
		
		$groupBy = "t.dept_ID,dept_name";
		
	}elseif($_GET['sort']=='Date') { 
		
		$groupBy = $dbc->dateymd('tdate');
		$alias = "tdate";

	}elseif($_GET['sort']=='Weekday'){

		$groupBy = $dbc->dayofweek("tdate").",CASE 
			WHEN ".$dbc->dayofweek("tdate")."=1 THEN 'Sun'
			WHEN ".$dbc->dayofweek("tdate")."=2 THEN 'Mon'
			WHEN ".$dbc->dayofweek("tdate")."=3 THEN 'Tue'
			WHEN ".$dbc->dayofweek("tdate")."=4 THEN 'Wed'
			WHEN ".$dbc->dayofweek("tdate")."=5 THEN 'Thu'
			WHEN ".$dbc->dayofweek("tdate")."=6 THEN 'Fri'
			WHEN ".$dbc->dayofweek("tdate")."=7 THEN 'Sat'
			ELSE 'Err' END";
		$alias = "DoW";
	
	}elseif($_GET['sort']=='PLU') {
		
		$groupBy = "upc";
	}
	
	$sort = $_GET['sort'];
	
	if($sort == "PLU"){
		$query = "";
		if(isset($buyer) && $buyer > 0){
		$query = "SELECT t.upc,p.description, 
				SUM(t.quantity) as qty,
				SUM(t.total) AS total,
				d.dept_no,d.dept_name,s.superID,x.distributor
			  FROM $sumTable as t LEFT JOIN products as p on t.upc = p.upc
			  LEFT JOIN departments as d on d.dept_no = t.dept 
			  LEFT JOIN superdepts AS s ON t.dept = s.dept_ID
			  LEFT JOIN prodExtra as x on t.upc = x.upc
			  WHERE s.superID = $buyer
			  AND tdate >= '$date1' AND tdate <= '$date2' GROUP BY t.upc,p.description,
			  d.dept_no,d.dept_name,s.superID,x.distributor ORDER BY $order $dir";
		}
		else if (isset($buyer) && $buyer == -1){
		$query = "SELECT t.upc,p.description, 
				SUM(t.quantity) as qty,
				SUM(t.total) AS total,
				d.dept_no,d.dept_name,s.superID,x.distributor
			  FROM  $sumTable as t LEFT JOIN products as p on t.upc = p.upc
			  LEFT JOIN departments as d on d.dept_no = t.dept 
			  LEFT JOIN MasterSuperDepts AS s ON t.dept = s.dept_ID
			  LEFT JOIN prodExtra as x on t.upc = x.upc
			  WHERE 
			  tdate >= '$date1' AND tdate <= '$date2' GROUP BY t.upc,p.description,
			  d.dept_no,d.dept_name,s.superID,x.distributor ORDER BY $order $dir";
		}
		else if (isset($buyer) && $buyer == -2){
		$query = "SELECT t.upc,p.description, 
				SUM(t.quantity) as qty,
				SUM(t.total) AS total,
				d.dept_no,d.dept_name,s.superID,x.distributor
			  FROM $sumTable as t LEFT JOIN products as p on t.upc = p.upc
			  LEFT JOIN departments as d on d.dept_no = t.dept 
			  LEFT JOIN MasterSuperDepts AS s ON t.dept = s.dept_ID
			  LEFT JOIN prodExtra as x on t.upc = x.upc
			  WHERE s.superID <> 0 and
			  tdate >= '$date1' AND tdate <= '$date2' GROUP BY t.upc,p.description,
			  d.dept_no,d.dept_name,s.superID,x.distributor ORDER BY $order $dir";
		}
		else {
		$query = "SELECT t.upc,p.description, 
				SUM(t.quantity) as qty,
				SUM(t.total) AS total,
				d.dept_no,d.dept_name,s.superID,x.distributor
			  FROM $sumTable as t LEFT JOIN products as p on t.upc = p.upc
			  LEFT JOIN departments as d on d.dept_no = t.dept 
			  LEFT JOIN MasterSuperDepts AS s ON t.dept = s.dept_ID
			  LEFT JOIN prodExtra as x on t.upc = x.upc
			  WHERE t.dept BETWEEN $deptStart AND $deptEnd
			  AND tdate >= '$date1' AND tdate <= '$date2' GROUP BY t.upc,p.description,
			  d.dept_no,d.dept_name,s.superID,x.distributor ORDER BY $order $dir";
		}
		//$query = fixup_dquery($query,$dlog);
		//echo $query;
		$result = $dbc->query($query);

		echo "<table border=1>\n"; //create table
		echo "<tr>";
		if (!isset($_GET['excel'])){
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd"
			    ."&date1=$date1&date2=$date2&sort=$sort&order=t.upc&dir=";
			if ($order == "t.upc")
				echo "$revdir>UPC</a></td>";
			else
				echo "ASC>UPC</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd"
			    ."&date1=$date1&date2=$date2&sort=$sort&order=p.description&dir=";
			if ($order == "p.description")
				echo "$revdir>Description</a></td>";
			else
				echo "ASC>Description</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd"
			    ."&date1=$date1&date2=$date2&sort=$sort&order=qty&dir=";
			if ($order == "qty")
				echo "$revdir>Qty</a></td>";
			else
				echo "DESC>Qty</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd"
			    ."&date1=$date1&date2=$date2&sort=$sort&order=total&dir=";
			if ($order == "total")
				echo "$revdir>Sales</a></td>";
			else
				echo "DESC>Sales</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd"
			    ."&date1=$date1&date2=$date2&sort=$sort&order=d.dept_no&dir=";
			if ($order == "d.dept_no")
				echo "$revdir>Dept</a></td>";
			else
				echo "ASC>Dept</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd"
			    ."&date1=$date1&date2=$date2&sort=$sort&order=d.dept_name&dir=";
			if ($order == "d.dept_name")
				echo "$revdir>Dept</a></td>";
			else
				echo "ASC>Dept</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd&date1=$date1&date2=$date2&sort=$sort&order=s.superID&dir=";
			if ($order = "s.superID")
				echo "$revdir>Sub dept</a></td>";
			else
				echo "ASC>Sub dept</a></td>";
			echo "<td><a href=report.php?buyer=$buyer&deptStart=$deptStart&deptEnd=$deptEnd&date1=$date1&date2=$date2&sort=$sort&order=distributor&dir=";
			if ($order = "distributor")
				echo "$revdir>Vendor</a></td>";
			else
				echo "ASC>Vendor</a></td>";
		}
		else {
			echo "<th>UPC</th><th>Description</th><th>Qty</th><th>Sales</th><th>Dept</th><th>Dept</th><th>Sub dept</th><th>Vendor</th>";
		}
		echo "</tr>\n";//create table header
		
		$dept_subs = array();
		$dsR = $dbc->query("SELECT super_name,superID FROM superDeptNames");
		while($dsW = $dbc->fetch_row($dsR))
			$dept_subs[$dsW[1]] = $dsW[0];

		while ($myrow = $dbc->fetch_row($result)) { //create array from query
		
		printf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",$myrow['upc'], $myrow['description'],$myrow['qty'],$myrow['total'],$myrow['dept_no'],$myrow['dept_name'],$dept_subs[$myrow['superID']],$myrow['distributor']==''?'&nbsp;':$myrow[7]);
		//convert row information to strings, enter in table cells
		
		}
		
		echo "</table>\n";//end table

	}else{ //create alternate query if not sorting by PLU
		$query="";
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumDeptSalesByDay";
		if (substr($dlog,-4)=="dlog")
			$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."vDeptSalesToday";
		$item = (!empty($alias)) ? $groupBy." AS ".$alias : $groupBy;
		$orderBy = (!empty($alias)) ? $alias : $groupBy;
		if(isset($buyer) && $buyer>0){
		 $query =  "SELECT $item,SUM(t.quantity) as Qty, SUM(total) as Sales "
                          ."FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID "
			  ."LEFT JOIN superdepts AS s ON s.dept_ID = t.dept_ID "
			  ."WHERE s.superID=$buyer AND tdate >= '$date1' AND tdate <= '$date2' "
			  ."GROUP BY $groupBy ORDER BY $orderBy";
		}
		else if (isset($buyer) && $buyer == -1){
		 $query =  "SELECT $item,SUM(t.quantity) as Qty, SUM(total) as Sales "
                          ."FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID "
			  ."WHERE tdate >= '$date1' AND tdate <= '$date2' "
			  ."GROUP BY $groupBy ORDER BY $orderBy";
		}
		else if (isset($buyer) && $buyer == -2){
		 $query =  "SELECT $item,SUM(t.quantity) as Qty, SUM(total) as Sales "
                          ."FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID "
			  ."LEFT JOIN MasterSuperDepts AS s ON s.dept_ID = t.dept_ID "
			  ."WHERE tdate >= '$date1' AND tdate <= '$date2' "
			  ."AND s.superID <> 0 "
			  ."GROUP BY $groupBy ORDER BY $orderBy";
		}
		else {
		 $query =  "SELECT $item,SUM(t.quantity) as Qty, SUM(total) as Sales "
                          ."FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID "
			  ."WHERE tdate >= '$date1' AND tdate <= '$date2' "
			  ."AND t.dept_ID BETWEEN $deptStart AND $deptEnd "
			  ."GROUP BY $groupBy ORDER BY $orderBy";
		}
		if ($sort == "Weekday"){
			$query = str_replace("as Sales",
					"as Sales,
					sum(t.quantity)/count(distinct(".$dbc->dateymd('tdate').")) as avg_qty,
					sum(total)/count(distinct(".$dbc->dateymd('tdate').")) as avg_ttl",
					$query);
		}
		//echo $query;
		//$query = fixup_dquery($query,$dlog);
		$result = $dbc->query($query);	

		$dtemp = explode("-",$date1);
		$ts = mktime(0,0,0,$dtemp[1],$dtemp[2],$dtemp[0]);
		
		echo "<table border=1>\n";//create table
		if ($sort == "Department")
			echo "<tr><td>$sort</td><td>Department</td><td>Qty</td><td>Sales</tr>\n";//create table header
		elseif ($sort == "Weekday"){
			echo "<tr><td colspan=2>$sort</td><td>Tot. Qty</td><td>Tot. Sales</td>
			<td>Avg. Qty</td><td>Avg. Sales</td></tr>\n";//create table header
		}
		else
			echo "<tr><td>$sort</td><td>Qty</td><td>Sales</tr>\n";//create table header
	
		while ($myrow = $dbc->fetch_row($result)) { //create array from query
			if ($sort == "Date" ){
				$myrow[0] = substr($myrow[0],4,2)."/".substr($myrow[0],6,2)."/"
					.substr($myrow[0],0,4)." ";
				while(date("m/d/Y ",$ts) != $myrow[0]){
					echo "<tr><td>";
					echo date("m/d/Y",$ts);
					echo "</td>";
					echo "<td>0</td><td>0</td></tr>";
					$ts = mktime(0,0,0,date("n",$ts),date("j",$ts)+1,date("Y",$ts));
				}
			}

			echo "<tr>";
			for ($i=0;$i<$dbc->num_fields($result);$i++){
				echo "<td";
				if ($i==0) echo " align=right";
				echo ">";
				if (is_numeric($myrow[$i]) && $myrow[$i] != floor($myrow[$i])) 
					printf('%.2f',$myrow[$i]);
				else echo $myrow[$i];
				echo '</td>';
			}
			echo "</tr>";
			$ts = mktime(0,0,0,date("n",$ts),date("j",$ts)+1,date("Y",$ts));
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
