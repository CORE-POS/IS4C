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

include('../../src/mysql_connect.php');
include('../../src/select_dlog.php');

if (isset($_REQUEST['batchID'])){
	$batchID = "(".$_REQUEST['batchID'].")";
	if (is_array($_REQUEST['batchID'])){
		$batchID = "(";
		foreach($_REQUEST['batchID'] as $bid)
			$batchID .= $bid.",";
		$batchID = rtrim($batchID,",").")";	
	}

	$batchInfoQ = "SELECT batchName,
			year(startDate) as sy, month(startDate) as sm, day(startDate) as sd,
			year(endDate) as ey, month(endDate) as em, day(endDate) as ed
			FROM batches where batchID IN $batchID";
	$batchInfoR = $dbc->query($batchInfoQ);

	if(isset($_GET['excel'])){
	   header('Content-Type: application/ms-excel');
	   header('Content-Disposition: attachment; filename="batchSales.xls"');
	}
	$bStart = isset($_REQUEST['start'])?$_REQUEST['start']:'';
	$bEnd = isset($_REQUEST['end'])?$_REQUEST['end']:'';
	$bName = "";
	while($batchInfoW = $dbc->fetch_array($batchInfoR)){
		$bName .= $batchInfoW['batchName']." ";
		if (empty($bStart)) {
			$bStart = sprintf("%d-%02d-%02d",$batchInfoW['sy'],
				$batchInfoW['sm'],$batchInfoW['sd']);
		}
		if (empty($bEnd)){ 
			$bEnd = sprintf("%d-%02d-%02d",$batchInfoW['ey'],
				$batchInfoW['em'],$batchInfoW['ed']);
		}
	}

	echo "<h2>$bName</h2>";
	echo "<p><font color=black>From: </font> $bStart <font color=black>to: </font> $bEnd</p>";

	$dlog = select_dlog($bStart,$bEnd);

	if(!isset($_GET['excel'])){
	   echo "<p class=excel><a href=batchReport.php?batchID=$batchID&excel=1&startDate=$bStart&endDate=$bEnd>Click here for Excel version</a></p>";
	}

	$bStart .= " 00:00:00";
	$bEnd .= " 23:59:59";

	$salesBatchQ ="select d.upc, b.description, sum(d.total) as sales, 
		 sum(case when d.trans_status in ('M','V') then d.itemQtty else d.quantity end) as quantity
		 FROM $dlog as d left join batchMergeTable as b
		 ON d.upc = b.upc
		 WHERE d.tdate BETWEEN '$bStart' and '$bEnd' 
		 AND b.batchID IN $batchID 
		 AND d.trans_status <> 'M'
		 GROUP BY d.upc, b.description
		 ORDER BY d.upc";

	$salesBatchR= $dbc->query($salesBatchQ);

	$i = 0;

	echo "<table border=0 cellpadding=1 cellspacing=0 ><th>UPC<th>Description<th>$ Sales<th>Quantity";
	while($salesBatchW = $dbc->fetch_array($salesBatchR)){
		$upc = $salesBatchW['upc'];
		$desc = $salesBatchW['description'];
		$sales = $salesBatchW['sales'];
		$qty = $salesBatchW['quantity'];
		$imod = $i%2;
   
		if($imod==1){
			$rColor= '#ffffff';
		}else{
			$rColor= '#ffffcc';
		}

		echo "<tr bgcolor=$rColor><td width=120>$upc</td><td width=300>$desc</td><td width=50>$sales</td><td width=50 align=right>$qty</td></tr>";
		$i++;
	}
	echo "</table>";
}
else {
	$header = "Select batch(es)";
	$page_title = "Fannie :: Batch Report";
	include("../../src/header.html");

	echo '<script type="text/javascript"
		src="'.$FANNIE_URL.'src/CalendarControl.js">
		</script>';
	echo '<script type="text/javascript">';
	?>
	function refilter(){
		var v1 = $('#typef :selected').val();
		var v2 = $('#ownerf :selected').val();
	
		location = 'index.php?owner='+v2+'&btype='+escape(v1);
	}
	<?php
	echo '</script>';

	$filter1 = (isset($_REQUEST['btype'])&&!empty($_REQUEST['btype']))?'AND batchType='.$_REQUEST['btype']:'';
	$filter2 = (isset($_REQUEST['owner'])&&!empty($_REQUEST['owner']))?'AND owner='.$dbc->escape($_REQUEST['owner']):'';

	$ownerQ = "SELECT super_name FROM superDeptNames WHERE superID > 0
		ORDER BY superID";
	$ownerR = $dbc->query($ownerQ);
	$o_opts = "<option value=\"\">Select owner</option>";
	while($ownerW = $dbc->fetch_row($ownerR)){
		$o_opts .= sprintf("<option %s>%s</option>",
			((isset($_REQUEST['owner'])&&$_REQUEST['owner']==$ownerW[0])?'selected':''),
			$ownerW[0]);
	}

	$typeQ = "SELECT batchTypeID,typeDesc FROM batchType ORDER BY batchTypeID";
	$typeR = $dbc->query($typeQ);
	$t_opts = "<option value=\"\">Select type</option>";
	while($typeW = $dbc->fetch_row($typeR)){
		$t_opts .= sprintf("<option %s value=%d>%s</option>",
			((isset($_REQUEST['btype'])&&$_REQUEST['btype']==$typeW[0])?'selected':''),
			$typeW[0],$typeW[1]);
	}

	echo "<b>Filter</b>: ";
	echo '<select id="typef" onchange="refilter();">';
	echo $t_opts;
	echo '</select>';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<select id="ownerf" onchange="refilter();">';
	echo $o_opts;
	echo '</select>';

	echo '<hr />';
	
	$batchQ = "SELECT b.batchID,batchName FROM batches as b
		LEFT JOIN batchOwner as o ON b.batchID=o.batchID
		WHERE 1=1
		$filter1 $filter2	
		ORDER BY b.batchID desc";
	$batchR = $dbc->query($batchQ);

	echo '<form action="index.php" method="get">';
	echo '<table cellspacing="2" cellpadding=2" border="0">';
	echo '<tr><td rowspan="4">';
	echo '<select size="15" multiple name=batchID[]>';
	while($batchW = $dbc->fetch_row($batchR)){
		printf('<option value="%d">%s</option>',
			$batchW['batchID'],$batchW['batchName']);
	}
	echo '</select>';
	echo '</td>';
	echo '<th>Start Date</th>';
	echo '<td><input name="start" onfocus="showCalendarControl(this);" /></td></tr>';
	echo '<tr><th>End Date</th>';
	echo '<td><input name="end" onfocus="showCalendarControl(this);" /></td></tr>';
	echo '<tr><th>Excel</th>';
	echo '<td><input type="checkbox" name="excel" /></td></tr>';
	echo '<tr><td colspan="2"><input type="submit" value="Run Report" /></td></tr>';

	echo '</table></form>';

	include("../../src/footer.html");
}

?>
