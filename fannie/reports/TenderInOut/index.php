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

if (isset($_GET['date1'])){
	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$code = $_GET['tendercode'];

	$sort = "tdate";
	if (isset($_GET['sort']))
		$sort = $_GET['sort'];
	$dir = "ASC";
	if (isset($_GET['dir'])){
		$dir = $_GET['dir'];
	}
	$otherdir = 'DESC';
	if ($dir == $otherdir)
		$otherdir = 'ASC';

	$dlog = select_dlog($date1,$date2);

	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="tenderUsage'.$code.'.xls"');
	}

	$query = "select tdate,trans_num,-total as total,card_no
		  FROM $dlog as t 
		  where t.trans_subtype = '$code' AND
		  trans_type='T' AND
		  tdate BETWEEN '$date1 00:00:00' AND '$date2 23:59:59'
		  order by $sort $dir";
	//echo $query;
	$result = $dbc->query($query);

	// make headers sort links
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
	echo "Report run ";
	echo $today;
	echo "</br>";
	echo "From ";
	print $date1;
	echo " to ";
	print $date2;
	echo "</br>";

	if (!isset($_GET['excel'])){
		echo "<a href=index.php?date1=$date1&date2=$date2&tendercode=$code&sort=$sort&dir=$dir&excel=yes>Save</a> to Excel<br />";
	}

	echo "<table cellpadding=2 cellspacing=0 border=1>";
	echo "<tr>";
	if (!isset($_GET['excel'])){
		if ($sort == "tdate"){
			echo "<th><a href=index.php?date1=".$date1."&date2=".$date2."&tendercode=".$code."&sort=".$sort."&dir=".$otherdir.">Date</a></th>";
		}
		else {
			echo "<th><a href=index.php?date1=".$date1."&date2=".$date2."&tendercode=".$code."&sort=t.tdate&dir=ASC>Date</a></th>";
		}
		echo "<th>Receipt#</th>";
		if ($sort == "card_no"){
			echo "<th><a href=index.php?date1=".$date1."&date2=".$date2."&tendercode=".$code."&sort=".$sort."&dir=".$otherdir.">Mem#</a></th>";
		}
		else {
			echo "<th><a href=index.php?date1=".$date1."&date2=".$date2."&tendercode=".$code."&sort=card_no&dir=ASC>Mem#</a></th>";
		}
		if ($sort == "total"){
			echo "<th><a href=index.php?date1=".$date1."&date2=".$date2."&tendercode=".$code."&sort=".$sort."&dir=".$otherdir.">Amount</a></th>";
		}
		else {
			echo "<th><a href=index.php?date1=".$date1."&date2=".$date2."&tendercode=".$code."&sort=total&dir=DESC>Amount</a></th>";
		}
	}
	else {
		echo "<th>Date</th><th>Receipt#</th><th>Mem#</th><th>Amount</th>";
	}
	$sumSales = 0.0;
	while ($row = $dbc->fetch_array($result)){
		echo "<tr>";
		echo "<td>".$row['tdate'].'</td>';
		echo "<td align=\"center\">".$row['trans_num']."</td>";
		echo "<td align=\"right\">".$row['card_no']."</td>";
		echo "<td align=\"right\">".sprintf('%.2f',$row['total'])."</td>";
		echo "</tr>";	
		$sumSales += $row['total'];
	}
	echo "<tr><th>Total</th><td colspan=2>&nbsp;</td>";
	echo "<td>".sprintf('%.2f',$sumSales)."</td></tr>";
	echo "</table>";

	return;
}

$page_title = "Fannie : Tender Usage";
$header = "Tender Usage Report";
include($FANNIE_ROOT.'src/header.html');
$tenders = array();
$r = $dbc->query("SELECT TenderCode,TenderName FROM tenders ORDER BY TenderName");
while($w = $dbc->fetch_row($r))
	$tenders[$w['TenderCode']] = $w['TenderName'];
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
</head>
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
			<td> <p><b>Tender</b></p>
			<p><b>Excel</b></p>
			</td>
			<td><p>
			<select name="tendercode">
			<?php foreach($tenders as $code=>$name){
				printf('<option value="%s">%s</option>',$code,$name);
			} ?>
			</select>
			</p>
			<p>
			<input type=checkbox name=excel id=excel /> 
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
		<!--<tr>
			<td> Select Dept/Buyer </td>
			<td colspan=3>
				<table width=100%><tr>
					<td><input type=radio name=buyer value=1>Bulk</td>
				       	<td><input type=radio name=buyer value=3>Cool</td>
				      	<td><input type=radio name=buyer value=4>Deli</td>
				      	<td><input type=radio name=buyer value=4>Grocery</td>
				      	<td><input type=radio name=buyer value=5>HBC</td></tr>
				      	<tr><td><input type=radio name=buyer value=6>Produce</td>
				      	<td><input type=radio name=buyer value=7>Marketing</td>
				      	<td><input type=radio name=buyer value=8>Meat</td>
				      	<td><input type=radio name=buyer value=9>Gen Merch</td>
				</tr></table>
			</td>
		</tr>-->
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>




