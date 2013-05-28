<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
	$qty = $_GET['qty'];

	$dlog = select_dlog($date1,$date2);

	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport'.$upc.'.xls"');
	}


	$create = $dbc->prepare_statement("CREATE TABLE groupingTempBS (year int, month int, day int, trans_num varchar(25))");
	$dbc->exec_statement($create);

	$setupQ = $dbc->prepare_statement("INSERT INTO groupingTempBS
		SELECT year(tdate),month(tdate),day(tdate),trans_num
		FROM $dlog AS d WHERE tdate BETWEEN ? AND ?
		AND trans_type IN ('I','D')
		GROUP BY year(tdate),month(tdate),day(tdate),trans_num 
		HAVING COUNT(*) <= ?");
	$dbc->exec_statement($setupQ,array($date1.' 00:00:00',$date2.' 23:59:59',$qty));
	
	echo '<h3>Basket Size '.$qty.' or less</h3>';

	$reportQ = $dbc->prepare_statement("SELECT d.upc,description,sum(d.quantity),count(*),sum(total) FROM
		$dlog AS d INNER JOIN groupingTempBS as g ON
		year(tdate)=g.year AND month(tdate)=g.month AND
		day(tdate)=g.day AND d.trans_num=g.trans_num
		LEFT JOIN products AS p ON d.upc=p.upc
		WHERE trans_type IN ('I','D') GROUP BY
		d.upc,description HAVING sum(total) <> 0
		ORDER BY count(*) DESC");
	$reportR = $dbc->exec_statement($reportQ);

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr><th>UPC</th><th>Description</th><th># Trans</th><th>Qty</th><th>$</th></tr>';
	while($w = $dbc->fetch_row($reportR)){
		printf('<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
			$w[0],$w[1],$w[3],$w[2],$w[4]);
	}
	echo '</table>';

	$drop = $dbc->prepare_statement("DROP TABLE groupingTempBS");
	$dbc->exec_statement($drop);

	return;
}

$page_title = "Fannie : Basket Size Limited Movement";
$header = "Basket Size Limited Report";
include($FANNIE_ROOT.'src/header.html');
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
			<td> <p><b>Size Limit (Qty)</b></p>
			<p><b>Excel</b></p>
			</td>
			<td><p>
			<input type=text name=qty id=qty value="1"  />
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




