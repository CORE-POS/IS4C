<?php
//header('Content-Type: application/ms-excel');
//header('Content-Disposition: attachment; filename="EOMreport.xls"');
include('../../../../config.php');

include($FANNIE_ROOT.'src/functions.php');
//include('./datediff.php');
include($FANNIE_ROOT.'cache/cache.php');

if (isset($_GET["excel"])){
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="EOMreport.xls"');
$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF']; // grab excel from cache
$_SERVER['REQUEST_URI'] = str_replace("index.php","",$_SERVER['REQUEST_URI']);
}

$today = date("m/j/y");
$uoutput = "<html>
<body bgcolor='#ffffff'> <font size=2>";
$uoutput .= '<br>Report run ' . $today; 
echo $uoutput;

$year = date('Y');
$month = date('n');
$stamp = mktime(0,0,0,$month-1,1,$year);
$dlog = "trans_archive.dbo.dlog".date("Ym",$stamp);

$output = get_cache("monthly");
if (!$output){
	ob_start();

	$dateQ = "select min(tdate),max(tdate) from $dlog where datediff(mm,getdate(),tdate) = -1";
	$dateR = $dbc->query($dateQ);
	$dateW = $dbc->fetch_array($dateR);
	$date = substr($dateW[0],0,strpos($dateW[0],":")-3);
	$date1 = substr($dateW[1],0,strpos($dateW[1],":")-3);
	echo ' for period <br>from: <b>'. $date . '</b> to: <b>' . $date1 . '</b><br>';

	$query1="select t.department,
	s.superID,
	c.salesCode,d.dept_name,
	SUM(t.total)
	FROM $dlog as t LEFT JOIN
	departments as d ON t.department = d.dept_no
	LEFT JOIN MasterSuperDepts AS s
	ON s.dept_ID = d.dept_no	
	LEFT JOIN deptSalesCodes AS c
	ON c.dept_ID = d.dept_no
	WHERE datediff(mm,getdate(),t.tDate) = -1
	AND t.Department < 600
	AND t.department <> 0
	AND t.trans_type <> 'T'
	GROUP BY
	s.superID,t.department,d.dept_name,c.salesCode
	order by s.superID,t.department";

	$query15 = "SELECT s.superID,sum(l.total) as total 
	FROM $dlog as l left join departments as d on l.department = d.dept_no
	LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
	WHERE datediff(mm,getdate(),l.tDate) = -1 
	AND l.department < 600 AND l.department <> 0
	AND l.trans_type <> 'T'
	GROUP BY s.superID
	order by s.superID";

	$query16 = "SELECT sum(l.total) as totalSales
	FROM $dlog as l 
	WHERE datediff(mm,getdate(),l.tDate) = -1 
	AND l.department < 600 AND l.department <> 0
	AND l.trans_type <> 'T'";

	$query2 = "SELECT t.TenderName,-sum(d.total) as total, COUNT(d.total)
	FROM $dlog d ,Tenders as t 
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND d.trans_status <>'X'  
	AND d.Trans_Subtype = t.TenderCode
	and t.TenderName <> 'MAD Coupon'
	and d.total <> 0
	GROUP BY t.TenderName";

	$query3 = "SELECT c.salesCode,s.superID,sum(l.total) as total 
	FROM $dlog as l left join MasterSuperDepts AS s ON
	l.department = s.dept_ID LEFT JOIN deptSalesCodes AS c
	ON l.department = c.dept_ID
	WHERE datediff(mm,getdate(),l.tDate) = -1 
	AND l.department < 600 AND l.department <> 0
	AND l.trans_type <> 'T'
	GROUP BY c.salesCode,s.superID
	order by c.salesCode,s.superID";

	$query4 = "SELECT sum(l.total) as totalSales
	FROM $dlog as l 
	WHERE datediff(mm,getdate(),l.tDate) = -1 
	AND l.department < 600 AND l.department <> 0
	AND l.trans_type <> 'T'";

	$query5 = "SELECT d.department,t.dept_name, sum(total) as total 
	FROM $dlog as d join departments as t ON d.department = t.dept_no
	LEFT JOIN MasterSuperDepts AS m ON t.dept_no=m.dept_ID
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.department >300)AND d.Department <> 0
	AND m.superID = 0
	AND d.trans_type IN('I','D') and 
	(d.register_no <> 20 or d.department = 703)
	GROUP BY d.department, t.dept_name";

	$query6 = "SELECT d.card_no,t.dept_name, sum(total) as total 
	FROM $dlog as d join departments  as t ON d.department = t.dept_no
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.department =991)AND d.Department <> 0
	GROUP BY d.card_no, t.dept_name";

	$query7 = "SELECT d.card_no,t.dept_name, sum(total) as total 
	FROM $dlog as d join departments  as t ON d.department = t.dept_no
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.department =990)AND d.Department <> 0 and d.register_no <> 20
	GROUP BY d.card_no, t.dept_name";

	$query13 = "SELECT   m.memDesc,SUM(d.total) AS Sales
	FROM         $dlog d INNER JOIN
			      custData c ON d.card_no = c.CardNo INNER JOIN
			      memTypeID m ON c.memType = m.memTypeID
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.department < 600) AND d.department <> 0 AND (c.personnum= 1 or c.personnum is null)
	AND d.trans_type <> 'T'
	GROUP BY m.memDesc
	ORDER BY m.memDesc";

	$query21 = "SELECT m.memdesc, COUNT(d.cust_ID)
	FROM dheader d join custdata c on d.cust_ID = c.cardno join memtypeID m on c.memtype = m.memtypeID
	WHERE datediff(mm,getdate(),proc_date)=-1 AND (cust_ID NOT BETWEEN 5500 and 5950 and cust_id < 9000) AND personnum = 1
	GROUP BY m.memdesc";

	$query20 = "SELECT   SUM(d.total) AS Sales 
			FROM $dlog d LEFT JOIN
			custData c ON d.card_no = c.CardNo LEFT JOIN
			memTypeID m ON c.memType = m.memTypeID
			WHERE datediff(mm,getdate(),d.tDate) = -1 
			AND (d.department < 600) AND d.department <> 0 
			AND d.trans_type <> 'T'
			AND (c.personnum= 1 or c.personnum is null)";

	$query12 = "SELECT d.salesCode,sum(L.total)as returns
	FROM $dlog as L,deptSalesCodes as d
	WHERE d.dept_ID = L.department
	 AND datediff(mm,getdate(),L.tDate)=-1 
	AND(trans_status = 'R' OR upc LIKE '%dp606')
	GROUP BY d.salesCode";

	$query14 = "SELECT 'Total Sales', sum(l.total) as totalSales
	FROM $dlog as l 
	WHERE datediff(mm,getdate(),l.tDate) = -1 
	AND l.department < 600 AND l.department <> 0
	AND l.trans_status = 'R'";

	$query8 = "SELECT     m.memDesc, SUM(d.total) AS Discount 
	FROM         $dlog d INNER JOIN
			      custData c ON d.card_no = c.CardNo INNER JOIN
			      memTypeID m ON c.memType = m.memTypeID
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.upc = 'DISCOUNT') AND c.personnum= 1
	GROUP BY c.memType, m.memDesc, d.upc
	ORDER BY c.memType";

	$query9 = "SELECT     d.upc, SUM(d.total) AS discount
	FROM         $dlog d INNER JOIN
			      custData c ON d.card_no = c.CardNo INNER JOIN
			      memTypeID m ON c.memType = m.memTypeID
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.upc = 'DISCOUNT') AND c.personnum = 1
	GROUP BY d.upc";

	$queryMAD = "select 'MAD Coupon',sum(d.total),count(*) as discount
	from $dlog as d
	where datediff(mm,getdate(),tdate) = -1
	and trans_status <> 'X'
	and trans_subtype = 'MA'";

	$query11 = "SELECT  sum(total) as tax_collected
	FROM $dlog as d 
	WHERE datediff(mm,getdate(),d.tDate) = -1 
	AND (d.upc = 'tax')
	GROUP BY d.upc";

	$query23="SELECT d.salesCode,sum(l.total) as total,card_no, 
	(sum(l.total)-(sum(l.total)* CONVERT(money,m.margin))) as cost
	FROM $dlog as l left join deptSalesCodes as d on l.department = d.dept_ID
	LEFT JOIN deptMargin AS m ON m.dept_ID = l.department
	WHERE datediff(mm,getdate(),tDate) = -1 
	AND (l.department < 600 or l.department = 902) AND l.department <> 0
	AND l.trans_type <> 'T'
	AND card_no BETWEEN 5500 AND 5950
	GROUP BY d.salesCode,card_no,m.margin
	order by card_no,d.salesCode";

	$query22="SELECT d.salesCode,sum(l.total) as total,
	(sum(l.total)-(sum(l.total)* CONVERT(money,m.margin))) as cost
	FROM $dlog as l left join deptSalesCodes as d on l.department = d.dept_ID
	LEFT JOIN deptMargin AS m ON m.dept_ID = l.department
	WHERE datediff(mm,getdate(),tDate) = -1 
	AND (l.department < 600 or l.department = 902) AND l.department <> 0
	AND l.trans_type <> 'T'
	AND card_no BETWEEN 5500 AND 5950
	GROUP BY d.salesCode,m.margin
	order by d.salesCode";

	$queryRRR = "
	SELECT sum(case when volSpecial is null then 0 else volSpecial end) as qty
	from
	transarchive as t
	where upc = 'RRR'
	and datediff(mm,getdate(),datetime) = -1 
	and emp_no <> 9999 and register_no <> 99
	and trans_status <> 'X'";


	echo '<font size = 3>';
	echo '<br>';
	echo 'Sales by department';
	echo '<br>---------------------------';
	echo '<table><td width=120><u><font size=2><b>Dept No</b></u></font></td>
	      <td width=120><u><font size=2><b>Department</b></u></font></td>
	      <td width=120><u><font size=2><b>pCode</b></u></font></td>
		<td width=120><u><font size=2><b>Group</b></u></font></td>
	      <td width=120><u><font size=2><b>Sales</b></u></font></td>
		</table>';
	select_to_table($query1,0,'ffffff');
	echo '<b>Total Sales by Group</b>';
	select_to_table($query15,0,'ffffff');

	echo '<font size = 2>';
	echo '<br>';
	echo 'Tenders';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>Type</b></u></font></td>
	      <td width=120><u><font size=2><b>Amount</b></u></font></td>
	      <td width=120><u><font size=2><b>Count</b></u></font></td></table>';
	select_to_table($query2,0,'ffffff');
	echo '<br>';
	echo 'Sales';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>pCode</b></u></font></td>
	      <td width=120><u><font size=2><b>Sales</b></u></font></td></table>';
	select_to_table($query3,0,'ffffff');
	echo '<b>Total Sales</b>';

	select_to_table($query4,0,'ffffff');

	echo '<br>';
	echo 'Other income';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>Dept</b></u></font></td>
	      <td width=120><u><font size=2><b>Description</b></u></font></td>
	      <td width=120><u><font size=2><b>Amount</b></u></font></td></table>';
	select_to_table($query5,0,'ffffff');
	echo 'Discounts';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
	      <td width=120><u><font size=2><b>Discounts</b></u></font></td></table>';
	select_to_table($query8,0,'ffffff');
	select_to_table($query9,0,'ffffff');
	select_to_table($queryMAD,0,'ffffff');
	echo '<br>';
	echo 'Member Sales';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
	      <td width=120><u><font size=2><b>Sales</b></u></font></td></table>';
	select_to_table($query13,0,'ffffff');
	select_to_table($query20,0,'ffffff');
	echo '<br>';
	echo 'Nabs';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>pCode</b></u></font></td>
	      <td width=120><u><font size=2><b>Retail</b></u></font></td>
	      <td>Dept Number</td><td>WholeSale</td></table>';
	select_to_table($query23,0,'ffffff');
	select_to_table($query22,0,'ffffff');
	echo '<br>';
	echo 'Transactions';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>Mem Type</b></u></font></td>
	      <td width=120><u><font size=2><b>Transactions</b></u></font></td></table>';
	select_to_table($query21,0,'ffffff');
	echo '<br>';
	echo '<br>';
	echo 'Sales Tax';
	echo '<br>------------------------------';
	echo '<table><td width=120><u><font size=2><b>Taxable Sales</b></u></font></td>
	      <td width=120><u><font size=2><b>Total Tax</b><u></font></td>
	      <td width=120><u><font size=2><b>State Taxable</b></u></font></td>
	      <td width=120><u><font size=2><b>State Tax</b></u></font></td>
	      <td width=120><u><font size=2><b>City Taxable</b></u></font></td>
	      <td width=120><u><font size=2><b>City Tax</b></u></font></td>
	      <td width=120><u><font size=2><b>Deli Taxable</b></u></font></td>
	      <td width=120><u><font size=2><b>Deli Tax</b></u></font></td></table>';
	$queryCorrect = "select TaxableSales,TotalTax,StateTaxable,StateTax,CityTaxable,CityTax,DeliTaxable,DeliTax
			from taxReport_corrected";
	select_to_table($queryCorrect,0,'ffffff');
	echo '<br>';
	echo '<b>Actual Tax Collected</b>';
	select_to_table($query11,0,'ffffff');

	echo '<br>';
	echo '<b>RRR Coupons Redeemed</b>';
	select_to_table($queryRRR,0,'ffffff');

	echo '</font>';
	echo "</font>
		</body>
		</html>";

	$output = ob_get_contents();
	put_cache("monthly",$output);
	ob_end_clean();
}

echo $output;
?>
