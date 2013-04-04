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

	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$deptStart = $_GET['deptStart'];
	$deptEnd = $_GET['deptEnd'];
	
	$buyer = -999;	
	if(isset($_GET['buyer'])){
	   $buyer = $_GET['buyer'];
	}

	if(isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="deptTransReport.xls"');
	}

	ob_start();

	$dlog = select_dlog($date1, $date2);

	$queryAll = "SELECT YEAR(tdate) AS year, MONTH(tdate) AS month, DAY(tdate) AS day,
		COUNT(DISTINCT trans_num) as trans_count
		FROM $dlog AS d 
		WHERE tdate BETWEEN ? AND ?
		GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
		ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate)";
	$argsAll = array($date1.' 00:00:00',$date2.' 23:59:59');

	$querySelected = "SELECT YEAR(tdate) AS year, MONTH(tdate) AS month, DAY(tdate) AS day,
		COUNT(DISTINCT trans_num) as trans_count
		FROM $dlog AS d ";
	if ($buyer != -999)
		$querySelected .= " LEFT JOIN superdepts AS s ON d.department=s.dept_ID ";
	$querySelected .= " WHERE tdate BETWEEN ? AND ? ";
	$argsSel = $argsAll;
	if ($buyer != -999){
		$querySelected .= " AND s.superID=? ";
		$argsSel[] = $buyer;
	}
	else{
		$querySelected .= " AND department BETWEEN ? AND ?";
		$argsSel[] = $deptStart;
		$argsSel[] = $deptEnd;
	}
	$querySelected .= " GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)";

	$dataset = array();

	$prep = $dbc->prepare_statement($queryAll);
	$result = $dbc->exec_statement($prep,$argsAll);
	while($row = $dbc->fetch_row($result)){
		$datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
		$dataset[$datestr] = array('ttl'=>$row['trans_count'],'sub'=>0);
	}

	$prep = $dbc->prepare_statement($querySelected);
	$result = $dbc->exec_statement($prep,$argsSel);
	while($row = $dbc->fetch_row($result)){
		$datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
		if (isset($dataset[$datestr]))
		$dataset[$datestr]['sub'] =$row['trans_count'];
	}

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr><th>Date</th><th># Matching Trans</th><th># Total Trans</th></tr>';
	foreach($dataset as $date => $count){
		printf('<tr><td>%s</td><td align="right">%d</td><td align="right">%d</td></tr>',
			$date,$count['sub'],$count['ttl']);
	}
	echo '</table>';

	$output = ob_get_contents();
	ob_end_clean();
	
	if (!isset($_REQUEST['excel'])){
		echo $output;
	}
	else {
		include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
		include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
		$array = HtmlToArray($output);
		$xls = ArrayToXls($array);
		echo $xls;
	}
?>
