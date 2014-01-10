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
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class BatchReport extends FannieReportPage 
{
    protected $header = "Select batch(es)";
    protected $title = "Fannie :: Batch Report";
    protected $report_cache = 'day';
    protected $report_headers = array('UPC','Description','$','Qty');
    protected $required_fields = array('batchID');

	function fetch_report_data(){
		global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		$bStart = FormLib::get_form_value('start','');
		$bEnd = FormLib::get_form_value('end','');

		/**
		  Assemble argument array and appropriate string
		  for an IN clause in a prepared statement
		*/
		$batchID = FormLib::get_form_value('batchID','0');
		$inArgs = array();
		$inClause = '(';
		foreach($batchID as $bID){
			$inClause .= '?,';
			$inArgs[] = $bID;
		}
		$inClause = rtrim($inClause,',').')';

		$batchInfoQ = 'SELECT batchName,
			year(startDate) as sy, month(startDate) as sm, day(startDate) as sd,
			year(endDate) as ey, month(endDate) as em, day(endDate) as ed
			FROM batches where batchID IN '.$inClause;
		$batchInfoP = $dbc->prepare_statement($batchInfoQ);
		$batchInfoR = $dbc->exec_statement($batchInfoP, $inArgs);

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
		
		$dlog = DTransactionsModel::selectDlog($bStart,$bEnd);
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumUpcSalesByDay";
		$bStart .= ' 00:00:00';
		$bEnd .= ' 23:59:59';

		$salesBatchQ ="select d.upc, b.description, sum(d.total) as sales, 
			 sum(d.quantity) as quantity
			 FROM $dlog as d left join batchMergeTable as b
			 ON d.upc = b.upc
			 WHERE 
			 b.batchID IN $inClause 
			 AND d.tdate BETWEEN ? AND ?
			 GROUP BY d.upc, b.description
			 ORDER BY d.upc";
		$salesBatchP = $dbc->prepare_statement($salesBatchQ);
		$inArgs[] = $bStart;
		$inArgs[] = $bEnd;
		$salesBatchR = $dbc->exec_statement($salesBatchP, $inArgs);

		/**
		  Simple report
		
		  Issue a query, build array of results
		*/
		$ret = array();
		while ($row = $dbc->fetch_array($salesBatchR)){
			$record = array();
			$record[] = $row['upc'];
			$record[] = $row['description'];
			$record[] = $row['sales'];
			$record[] = $row['quantity'];
			$ret[] = $record;
		}
		return $ret;
	}
	
	/**
	  Sum the quantity and total columns
	*/
	function calculate_footers($data){
		$sumQty = 0.0;
		$sumSales = 0.0;
		foreach($data as $row){
			$sumQty += $row[3];
			$sumSales += $row[2];
		}
		return array('Total',null,$sumSales,$sumQty);
	}

	function form_content()
    {
		global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

		$filter1 = FormLib::get_form_value('btype','');
		$filter2 = FormLib::get_form_value('owner','');

		$ownerQ = $dbc->prepare_statement("SELECT super_name FROM superDeptNames WHERE superID > 0
			ORDER BY superID");
		$ownerR = $dbc->exec_statement($ownerQ);
		$o_opts = "<option value=\"\">Select owner</option>";
		while($ownerW = $dbc->fetch_row($ownerR)){
			$o_opts .= sprintf("<option %s>%s</option>",
				((isset($_REQUEST['owner'])&&$_REQUEST['owner']==$ownerW[0])?'selected':''),
				$ownerW[0]);
		}

		$typeQ = $dbc->prepare_statement("SELECT batchTypeID,typeDesc FROM batchType ORDER BY batchTypeID");
		$typeR = $dbc->exec_statement($typeQ);
		$t_opts = "<option value=\"\">Select type</option>";
		while($typeW = $dbc->fetch_row($typeR)){
			$t_opts .= sprintf("<option %s value=%d>%s</option>",
				((isset($_REQUEST['btype'])&&$_REQUEST['btype']==$typeW[0])?'selected':''),
				$typeW[0],$typeW[1]);
		}

		
		echo "<b>Filter</b>: ";
		echo '<select id="typef" 
			onchange="location=\'BatchReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
		echo $t_opts;
		echo '</select>';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<select id="ownerf" 
			onchange="location=\'BatchReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
		echo $o_opts;
		echo '</select>';

		echo '<hr />';

		$batchQ = "SELECT b.batchID,batchName FROM batches as b
			LEFT JOIN batchowner as o ON b.batchID=o.batchID
			WHERE 1=1 ";
		$args = array();
		if ($filter1 !== ""){
			$batchQ .= " AND batchType=? ";
			$args[] = $filter1;
		}
		if ($filter2 !== ""){
			$batchQ .= " AND owner=? ";
			$args[] = $filter2;
		}
		$batchQ .= "ORDER BY b.batchID desc";
		$batchP = $dbc->prepare_statement($batchQ);
		$batchR = $dbc->exec_statement($batchP, $args);

		echo '<form action="BatchReport.php" method="get">';
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
		echo '<td><input type="checkbox" name="excel" value="xls" /></td></tr>';
		echo '<tr><td colspan="2"><input type="submit" value="Run Report" /></td></tr>';

		echo '</table></form>';
	}

	function report_description_content()
    {
		global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		$ret = array();
		$bStart = FormLib::get_form_value('start','');
		$bEnd = FormLib::get_form_value('end','');
		$batchID = FormLib::get_form_value('batchID','0');
		$inArgs = array();
		$inClause = '(';
		foreach($batchID as $bID){
			$inClause .= '?,';
			$inArgs[] = $bID;
		}
		$inClause = rtrim($inClause,',').')';
		$batchInfoQ = $dbc->prepare_statement("SELECT batchName,startDate as startDate,
			endDate as endDate FROM batches where batchID in $inClause");
		$batchInfoR = $dbc->exec_statement($batchInfoQ,$inArgs);
		$bName = "";
		while($batchInfoW = $dbc->fetch_array($batchInfoR)){
			$bName .= $batchInfoW['batchName']." ";
			if (empty($bStart))
				$bStart = $batchInfoW['startDate'];
			if (empty($bEnd))
				$bEnd = $batchInfoW['endDate'];
		}
		$ret[] = '<span style="font-size:150%;">'.$bName.'</span>';
		$ret[] = "<span style=\"color:black\">From: $bStart to: $bEnd</span>";
		return $ret;
	}
}

FannieDispatch::conditionalExec(false);

?>
