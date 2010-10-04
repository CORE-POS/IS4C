<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
/*
if(isset($_GET['sort'])){
  if(isset($_GET['XL'])){
     header("Content-Disposition: inline; filename=deptSales.xls");
     header("Content-Description: PHP3 Generated Data");
     header("Content-type: application/vnd.ms-excel; name='excel'");
  }
}*/

include('../config.php');
require_once('../src/mysql_connect.php');
require_once('../src/functions.php');
require_once('../src/select_dlog.php');
// include('../src/datediff.php');

if(isset($_POST['submit'])){
	foreach ($_POST AS $key => $value) {
		$$key = $value;
		//echo $key ." : " .  $value"<br>";
	}
echo "<body>";

setlocale(LC_MONETARY, 'en_US');
	$today = date("F d, Y");	

// Page header

	echo "Report run on ";
	echo $today;
	echo "</br>";
	echo "For ";
	print $date1;
	echo " through ";
	print $date2;
	echo "</br></br></br>";

/*
	if(!isset($_GET['XL'])){
	echo "<p><a href='deptSales.php?XL=1&sort=$sort&date1=$date1&date2=$date2&deptStart=$deptStart&deptEnd=$deptEnd&pluReport=$pluReport&order=$order'>Dump to Excel Document</a></p>";
	
	} 
*/	
		
	// Check year in query, match to a dlog table
	$table = select_dlog($date1,$date2);
	$dbcon = ".";
	if ($FANNIE_SERVER_DBMS == 'MSSQL') $dbconn = ".dbo.";

	$date2a = $date2 . " 23:59:59";
	$date1a = $date1 . " 00:00:00";

	include('reportFunctions.php');
	$gross = gross($table,$date1a,$date2a);

	if (isset($sales)) {
		$staff_total = staff_total($table,$date1a,$date2a);
		$MADcoupon = MADcoupon($table,$date1a,$date2a);
		$totalDisc = $staff_total + $MADcoupon;

		$ICQ = "SELECT ROUND(SUM(total),2) AS coupons
			FROM $table
			WHERE tdate >= '$date1a' AND tdate <= '$date2a'
			AND trans_subtype IN('IC')";

			$ICR = $dbc->query($ICQ);
			$row = $dbc->fetch_row($ICR);
			$IC = $row[0];
			if (is_null($IC)) {
				$IC = 0;
			}

		$MCQ = "SELECT ROUND(SUM(total),2) AS coupons
			FROM $table
			WHERE tdate >= '$date1a' AND tdate <= '$date2a'
			AND trans_subtype IN('MC','CP')";

			$MCR = $dbc->query($MCQ);
			$row = $dbc->fetch_row($MCR);
			$MC = $row[0];
			if (is_null($MC)) {
				$MC = 0;
			}

		$TCQ = "SELECT ROUND(SUM(total),2) AS coupons
			FROM $table
			WHERE tdate >= '$date1a' AND tdate <= '$date2a'
			AND trans_subtype IN('TC')";

			$TCR = $dbc->query($TCQ);
			$row = $dbc->fetch_row($TCR);
			$TC = $row[0];
			if (is_null($TC)) {
				$TC = 0;
			}

		$coupons = $IC + $MC + $TC;

		$strchgQ = "SELECT ROUND(SUM(d.total),2) AS strchg
			FROM $table AS d
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			AND d.trans_subtype IN('MI')";

			$strchgR = $dbc->query($strchgQ);
			$row = $dbc->fetch_row($strchgR);
			$strchg = $row[0];
			if (is_null($strchg)) {
				$strchg = 0;
			}

		$otherQ = "SELECT ROUND(SUM(d.total),2) as other
			FROM $table AS d
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			AND d.department IN(990)";

			$otherR = $dbc->query($otherQ);
			$row = $dbc->fetch_row($otherR);
			$other = $row[0];
			if (is_null($other)) {
				$other = 0;
			}

		$net = $gross + $totalDisc + $coupons + $strchg ;


		 // sales of inventory departments
		$invtotalsQ = "SELECT d.department,t.dept_name,ROUND(sum(d.total),2) AS total,ROUND((SUM(d.total)/$gross)*100,2) as pct
			FROM $table AS d, {$FANNIE_OP_DB}{$dbconn}departments AS t
			WHERE d.department = t.dept_no
			AND d.tdate >= '$date1a' AND d.tdate <= '$date2a' 
			AND d.department < 600 AND d.department <> 0
			AND d.trans_subtype NOT IN('IC','MC','CP')
			GROUP BY d.department, t.dept_name
			ORDER BY d.department";

		// Sales for non-inventory departments 
		$noninvtotalsQ = "SELECT d.department,t.dept_name,ROUND(sum(total),2) as total, count(d.tdate) AS count
			FROM $table as d left join {$FANNIE_OP_DB}{$dbconn}departments as t ON d.department = t.dept_no
			WHERE tdate >= '$date1a' AND tdate <= '$date2a' 
			AND d.department >= 600  AND d.department <> 0
			GROUP BY d.department, t.dept_name";
		
		echo "<h2>Income / Expenses</h2>\n
			<table border=0>\n<tr><td><b>sales (gross) total</b></td><td align=right><b>".money_format('%n',$gross)."</b></td></tr>\n
			<tr><td>totalDisc</td><td align=right>".money_format('%n',$totalDisc)."</td></tr>\n
			<tr><td>coupon & gift cert. tenders</td><td align=right>".money_format('%n',$coupons)."</td></tr>\n
			<tr><td>store charges</td><td align=right>".money_format('%n',$strchg)."</td></tr>\n
			<tr><td>chg pmts</td><td align=right>".money_format('%n',$other)."</td></tr>\n
			<tr><td>&nbsp;</td><td align=right>+___________</td></tr>\n
			<tr><b><td><b>net total</b></td><td align=right><b>".money_format('%n',$net)."</b></td></b></tr>\n
			</table>\n";
			
		echo '</b></td></tr></table><h4>Inventory Department Totals</h4>';
		echo '<p>';
		select_to_table($invtotalsQ,1,'FFFFFF');
		echo '</p>';
		echo '<h4>Non-Inventory Department Totals</h4>';
		select_to_table($noninvtotalsQ,1,'FFFFFF');
	} 
			
	if(isset($tender)) {
		if ($gross == 0 || !$gross ) $gross = 1;

		$tendertotalsQ = "SELECT t.TenderName as tender_type,ROUND(-sum(d.total),2) as total,ROUND((-SUM(d.total)/$gross)*100,2) as pct
			FROM $table as d , {$FANNIE_OP_DB}{$dbconn}tenders as t 
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			AND d.trans_subtype = t.TenderCode
			GROUP BY t.TenderName";
	
		// $gross = 0;
	
		$transcountQ = "SELECT COUNT(*) as transactionCount
			FROM $table AS d
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			GROUP BY year(tdate),month(tdate),day(tdate),trans_num";
	
		$transcountR = $dbc->query($transcountQ);
		$count = $dbc->num_rows($transcountR);
	
		$basketsize = round($gross/$count,2);
		
		echo '<h4>Tender Report + Basket Size</h4>';
		select_to_table($tendertotalsQ,1,'FFFFFF');
		echo '<br><p>Transaction count&nbsp;&nbsp;=&nbsp;&nbsp;<b>'.$count;
		echo '</b></p><p>Basket size&nbsp;&nbsp;=&nbsp;&nbsp;<b>'.money_format('%n',$basketsize);
		echo '</p>';
	
	}		
			
	if(isset($discounts)) {
		
		echo "<h2>Membership & Discount Totals</h2>\n
			<table border=0>\n<font size=2>\n<tr><td>staff total</td><td align=right>".money_format('%n',$staff_total)."</td></tr>\n
			<tr><td>MAD coupon</td><td align=right>".money_format('%n',$MADcoupon)."</td></tr>\n
			<tr><td>&nbsp;</td><td align=right>+___________</td></tr>\n
			<tr><td><b>total discount</td><td align=right>".money_format('%n',$totalDisc)."</b></td></tr></font>\n
			</table>\n";

		// // Discounts by member type;
		$memtypeQ = "SELECT m.memDesc as memberType,ROUND(-SUM(d.total),2) AS discount 
			FROM $table d INNER JOIN {$FANNIE_OP_DB}{$dbconn}custdata c ON d.card_no = c.CardNo 
			INNER JOIN {$FANNIE_OP_DB}{$dbconn}memtype m ON c.memType = m.memtype
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a' 
			AND d.upc = 'DISCOUNT'
			GROUP BY m.memDesc, d.upc";
			
		echo '</b></p><h4>Discounts By Member Type</h4>';
		select_to_table($memtypeQ,1,'FFFFFF');
	
		// Sales by member type;
		$memtypeQ = "SELECT m.memDesc as sales_by_memtype,(ROUND(SUM(d.total),2)) AS sales, ROUND((SUM(d.total)/$gross)*100,2) as pct
			FROM $table d, {$FANNIE_OP_DB}{$dbconn}memtype m
			WHERE d.memtype = m.memtype
			AND d.tdate >= '$date1a' AND d.tdate <= '$date2a' 
			AND d.department < 600 AND d.department <> 0
			GROUP BY m.memDesc";	
		
		echo "</b></p>\n<h4>Gross Sales By Member Type</h4>\n";
		select_to_table($memtypeQ,1,'FFFFFF');
	}
	
	if(isset($equity)){	
	
		$sharetotalsQ = "SELECT d.tdate AS datetime, d.emp_no AS emp_no, d.card_no AS cardno,c.LastName AS lastname,ROUND(sum(total),2) as total 
			FROM $table as d, {$FANNIE_OP_DB}{$dbconn}custdata AS c
			WHERE d.card_no = c.CardNo AND c.personNum=1
			AND d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			AND d.department IN (991,992)
			GROUP BY d.tdate,d.emp_no,d.card_no,c.LastName
			ORDER BY d.tdate";

		$sharetotalQ = "SELECT ROUND(SUM(d.total),2) AS Total_share_pmt
			FROM $table AS d
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			AND d.department IN (991,992)";

		$sharetotalR = $dbc->query($sharetotalQ);
		$row = $dbc->fetch_row($sharetotalR);
		$sharetotal = $row[0];

		$sharecountQ = "SELECT COUNT(d.total) AS peopleshareCount
			FROM $table AS d
			WHERE d.tdate >= '$date1a' AND d.tdate <= '$date2a'
			AND d.department IN (991,992)";
				
		$sharecountR = $dbc->query($sharecountQ);
		$row = $dbc->fetch_row($sharecountR);
		$sharecount = $row[0];
		
		echo '<h1>Equity Report</h1>';
		echo '<h4>Sales of Member Shares</h4>';
		echo '<p>Total member share payments = <b>'.$sharetotal;
		echo '</b></p><p>Member Share count&nbsp;&nbsp;=&nbsp;&nbsp;<b>'.$sharecount;
		echo '</b></p>';
		select_to_table($sharetotalsQ,1,'FFFFFF');
		
	}

//
// PHP INPUT DEBUG SCRIPT  -- very helpful!
//

// function debug_p($var, $title) 
// {
//     print "<p>$title</p><pre>";
//     print_r($var);
//     print "</pre>";
// }  
// 
// debug_p($_REQUEST, "all the data coming in");

} else {
	
	$page_title = 'Fannie - Reporting';
	$header = 'Period Report';
	include('../src/header.html');
	
	echo '<script src="../src/CalendarControl.js" language="javascript"></script>
		<form method="post" action="period.php" target="_blank">		
		<h2>Period Report</h2>
		<table border="0" cellspacing="5" cellpadding="5">
			<tr> 
				<td>
					<p><b>Date Start</b> </p>
			    	<p><b>End</b></p>
			    </td>
				<td>
			    	<p><input type=text size=10 name=date1 onclick="showCalendarControl(this);"></p>
	            	<p><input type=text size=10 name=date2 onclick="showCalendarControl(this);"></p>
			    </td>
			</tr>
			<tr> 

			</tr>		
			<tr>
				<td><p>Sales totals</p></td>
				<td><input type="checkbox" value="1" name="sales"></td>
			</tr>
			<tr>
				<td><p>Tender report & basket-size</p></td>
				<td><input type="checkbox" value="1" name="tender"></td>
			</tr>
			<tr>
				<td><p>Discount report</p></td>
				<td><input type="checkbox" value="1" name="discounts"></td>
			</tr>
			<tr>
				<td><p>Equity report - DETAILED</p></td>
				<td><input type="checkbox" value="1" name="equity"></td>
			</tr>
			<tr> 
				<td> <input type=submit name=submit value="Submit"> </td>
				<td> <input type=reset name=reset value="Start Over"> </td>
			</tr>
		</table>
	</form>';
	
	include('../src/footer.html');
}


?>
