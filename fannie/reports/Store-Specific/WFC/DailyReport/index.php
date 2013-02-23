<?php
include('../../../../config.php');

include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="dailyReport.xls"');
}
$ALIGN_RIGHT = 1;
$ALIGN_LEFT = 2;
$ALIGN_CENTER = 4;
$TYPE_MONEY = 8;

if (!isset($_GET['excel'])){
?>

<html>
<head>
<style type=text/css>
td {
	font-size: .9em;
}
td.left {
	padding-right: 4em;
	text-align: left;
}
td.right {
	padding-left: 4em;
	text-align: right;
}
td.center {
	padding-left: 2em;
	padding-right: 2em;
	text-align: center;
}
</style>
</head>
<body bgcolor='#ffffff'><font size=2> 
<?php
}

if (!isset($_GET['excel'])){
	echo "<form action=index.php name=datelist method=get>";
	echo "<input name=date type=text id=date >";

	echo "<input name=Submit type=submit value=submit>";
	echo "</form>";
}

$today = date("m/j/y");
// Calculate the previous day's date, old method just gave zero - Andy
$repDate = date('m/j/y', mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));

$dstr = date("Y-m-d",strtotime("yesterday"));
if(isset($_GET['date'])){
   $repDate = $_GET['date'];
   $t1 = strtotime($repDate);
   if ($t1) $dstr = date("Y-m-d",$t1);
}

if (!isset($_GET['excel']))
	echo "<br /><a href=index.php?date=$repDate&excel=yes>Click here for Excel version</a>";

echo '<br>Report run ' . $today. ' for ' . $repDate."<br />";

$dlog = select_dlog($dstr);
$OP = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_OP_DB.'.dbo.' : $FANNIE_OP_DB.'.';
$TRANS = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_TRANS_DB.'.dbo.' : $FANNIE_TRANS_DB.'.';

$tenderQ = "SELECT t.TenderName,-sum(d.total) as total, COUNT(d.total)
FROM $dlog as d ,{$OP}tenders as t 
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
AND d.trans_status <>'X'  
AND d.Trans_Subtype = t.TenderCode
and d.total <> 0
GROUP BY t.TenderName";
$tenderR = $dbc->query($tenderQ);
$tenders = array("Cash"=>array(10120,0.0,0),
		"Check"=>array(10120,0.0,0),
		"Credit Card"=>array(10120,0.0,0),
		"EBT CASH."=>array(10120,0.0,0),
		"EBT FS"=>array(10120,0.0,0),
		"Gift Card"=>array(21205,0.0,0),
		"GIFT CERT"=>array(21200,0.0,0),
		"InStore Charges"=>array(10710,0.0,0),
		"Pay Pal"=>array(10120,0.0,0),
		"Coupons"=>array(10740,0.0,0),
		"InStoreCoupon"=>array(67710,0.0,0),
		"RRR Coupon"=>array(63380,0.0,0));
$mad = array(0.0,0);
while ($row = $dbc->fetch_row($tenderR)){
	if(isset($tenders[$row[0]])){
		$tenders[$row[0]][1] = $row[1];
		$tenders[$row[0]][2] = $row[2];
	}
	elseif ($row[0] == "MAD Coupon"){
		$mad[0] = $row[1];
		$mad[1] = $row[2];
	}
} 

echo "<br /><b>Tenders</b>";
echo tablify($tenders,array(1,0,2,3),array("Account","Type","Amount","Count"),
	     array($ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT),2);


$pCodeQ = "SELECT s.salesCode,-1*sum(l.total) as total,min(l.department) 
FROM $dlog as l 
INNER JOIN {$OP}deptSalesCodes AS s ON l.department=s.dept_ID
WHERE ".$dbc->date_equals('l.tdate',$dstr)." 
AND l.department < 600 AND l.department <> 0
AND l.trans_type <>'T'
GROUP BY s.salesCode
order by s.salesCode";
$pCodeR = $dbc->query($pCodeQ);
$pCodes = array("41201"=>array(0.0),
		"41205"=>array(0.0),
		"41300"=>array(0.0),
		"41305"=>array(0.0),
		"41310"=>array(0.0),
		"41315"=>array(0.0),
		"41400"=>array(0.0),
		"41405"=>array(0.0),
		"41407"=>array(0.0),
		"41410"=>array(0.0),
		"41415"=>array(0.0),
		"41420"=>array(0.0),
		"41425"=>array(0.0),
		"41430"=>array(0.0),
		"41435"=>array(0.0),
		"41440"=>array(0.0),
		"41445"=>array(0.0),
		"41500"=>array(0.0),
		"41505"=>array(0.0),
		"41510"=>array(0.0),
		"41515"=>array(0.0),
		"41520"=>array(0.0),
		"41525"=>array(0.0),
		"41530"=>array(0.0),
		"41600"=>array(0.0),
		"41605"=>array(0.0),
		"41610"=>array(0.0),
		"41640"=>array(0.0),
		"41645"=>array(0.0),
		"41700"=>array(0.0),
		"41705"=>array(0.0));
while($row = $dbc->fetch_row($pCodeR)){
	if (isset($pCodes[$row[0]])) $pCodes[$row[0]][0] = $row[1];
	//else var_dump( $row[2] );
}
echo "<br /><b>Sales</b>";
echo tablify($pCodes,array(0,1),array("pCode","Sales"),
	     array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY),1);

$saleSumQ = "SELECT -1*sum(l.total) as totalSales
FROM $dlog as l
WHERE ".$dbc->date_equals('l.tdate',$dstr)." 
AND l.department < 600 AND l.department <> 0
AND l.trans_type <> 'T'";
$saleSumR = $dbc->query($saleSumQ);
echo "<br /><b><u>Total Sales</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($saleSumR)));

$returnsQ = "SELECT s.salesCode,-1*sum(L.total)as returns
FROM $dlog as L,deptSalesCodes as s
WHERE s.dept_ID = L.department
AND ".$dbc->date_equals('L.tdate',$dstr)." 
AND(trans_status = 'R')
GROUP BY s.salesCode";
$returnsR = $dbc->query($returnsQ);
$returns = array();
while($row = $dbc->fetch_row($returnsR))
	$returns["$row[0]"] = array($row[1]);
echo "<br /><b>Returns</b>";
echo tablify($returns,array(0,1),array("pCode","Sales"),
	     array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY),1);

// idea here is to get everything to the right of the
// RIGHT MOST space, hence the reverse
$voidTransQ = "SELECT RIGHT(description,".
		$dbc->locate("' '","REVERSE(description)")."-1),
	       trans_num,-1*total from
	       {$TRANS}voidTransHistory where ".$dbc->date_equals('tdate',$dstr); 
$voidTransR = $dbc->query($voidTransQ);
$voids = array();
while($row = $dbc->fetch_row($voidTransR))
	$voids["$row[0]"] = array($row[1],$row[2]);
echo "<br /><b>Voids</b>";
echo tablify($voids,array(0,1,2),array("Original","Void","Total"),
	     array($ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY),2);

$otherQ = "SELECT d.department,t.dept_name, -1*sum(total) as total 
FROM $dlog as d left join departments as t ON d.department = t.dept_no
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
AND d.department > 300 AND 
(d.register_no <> 20 or d.department = 703)
and d.department <> 610
and d.department not between 500 and 599
GROUP BY d.department, t.dept_name order by d.department";
$otherR = $dbc->query($otherQ);
$others = array("600"=>array("64410","SUPPLIES",0.0),
		"604"=>array("&nbsp;","MISC PO",0.0),
		"700"=>array("63320","TOTES",0.0),
		"703"=>array("&nbsp;","MISCRECEIPT",0.0),
		"708"=>array("42225","CLASSES",0.0),
		"800"=>array("&nbsp;","IT Corrections",0.0),
		"881"=>array("42231","MISC #1",0.0),
		"882"=>array("42232","MISC #2",0.0),
		"900"=>array("21200","GIFTCERT",0.0),
		"902"=>array("21205","GIFTCARD",0.0),
		"990"=>array("10710","ARPAYMEN",0.0),
		"991"=>array("31110","CLASS B Equity",0.0),
		"992"=>array("31100","CLASS A Equity",0.0));
while($row = $dbc->fetch_row($otherR)){
	$others["$row[0]"][1] = $row[1];
	$others["$row[0]"][2] = $row[2]; 
}
echo "<br /><b>Other</b>";
echo tablify($others,array(1,0,2,3),array("Account","Dept","Description","Amount"),
	     array($ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY),3);

$equityQ = "SELECT d.card_no,t.dept_name, -1*sum(total) as total 
FROM $dlog as d left join departments as t ON d.department = t.dept_no
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
AND d.department IN(991,992) AND d.register_no <> 20
GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name";
$equityR = $dbc->query($equityQ);
$equityrows = array();
while($row = $dbc->fetch_row($equityR)){
	$newrow = array("00-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2]);
	array_push($equityrows,$newrow);
}
echo "<br /><b>Equity Payments by Member Number</b>";
echo tablify($equityrows,array(1,2,3,4),array("Account","MemNum","Description","Amount"),
	array(0,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY));

$arQ = "SELECT d.card_no,CASE WHEN d.department = 990 THEN 'AR PAYMENT' ELSE 'STORE CHARGE' END as description, 
-1*sum(total) as total, count(card_no) as transactions 
FROM $dlog as d 
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
AND (d.department =990 OR d.trans_subtype = 'MI') and 
(d.register_no <> 20 or d.department <> 990)
GROUP BY d.card_no,d.department order by department,card_no";
$arR = $dbc->query($arQ);
$ar_rows = array();
while($row = $dbc->fetch_row($arR)){
	$newrow = array("01-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2],$row[3]);
	array_push($ar_rows,$newrow);
}
echo "<br /><b>AR Activity by Member Number</b>";
echo tablify($ar_rows,array(1,2,3,4,5),array("Account","MemNum","Description","Amount","Transactions"),
	array(0,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT));

$discQ = "SELECT     m.memDesc, -1*SUM(d.total) AS Discount,count(*) 
FROM $dlog d INNER JOIN
       custdata c ON d.card_no = c.CardNo INNER JOIN
      memTypeID m ON c.memType = m.memTypeID
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
and total <> 0
GROUP BY m.memDesc, d.upc ";
$discR = $dbc->query($discQ);
$discounts = array("MAD Coupon"=>array(66600,$mad[0],$mad[1]),
		   "Staff Member"=>array(61170,0.0,0),
		   "Staff NonMem"=>array(61170,0.0,0),
		   "Member"=>array(66600,0.0,0));
while($row = $dbc->fetch_row($discR)){
	$discounts[$row[0]][1] = $row[1];
	$discounts[$row[0]][2] = $row[2];
}
echo "<br /><b>Discounts</b>";
echo tablify($discounts,array(1,0,2,3),array("Account","Type","Amount","Count"),
	     array($ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT),2);

$deliTax = 0.0225;
$checkQ = "select ".$dbc->datediff("'$repDate'","'2008-07-01'");
$checkR = $dbc->query($checkQ);
$diff = array_pop($dbc->fetch_row($checkR));
if ($diff < 0) $deliTax = 0.025;

$checkQ = "select ".$dbc->datediff("'$repDate'","'2012-11-01'");
$checkR = $dbc->query($checkQ);
$diff = array_pop($dbc->fetch_row($checkR));
$deliTax = 0.0325;


$taxQ = "SELECT (CASE WHEN d.tax = 1 THEN 'Non Deli Sales' ELSE 'Deli Sales' END) as type, sum(total) as taxable_sales,
.01*(sum(CASE WHEN d.tax = 1 THEN total ELSE 0 END)) as city_tax_nonDeli,
$deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END)) as city_tax_Del, 
.065*(sum(total)) as state_tax,
((.01*(sum(CASE WHEN d.tax = 1 THEN total ELSE 0 END))) + ($deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END))) + (.065*(sum(total)))) as total_tax 
FROM $dlog as d 
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
AND d.tax <> 0 
GROUP BY d.tax ORDER BY d.tax DESC";
$taxR = $dbc->query($taxQ);
$taxes = array();
while($row = $dbc->fetch_row($taxR))
	$taxes["$row[0]"] = array(-1*$row[1],-1*$row[2],-1*$row[3],-1*$row[4],-1*$row[5]);
echo "<br /><b>Sales Tax</b>";
echo tablify($taxes,array(0,1,2,3,4,5),array("&nbsp;","Taxable Sales","City Tax","Deli Tax","State Tax","Total Tax"),
	array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY,
	      $ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY));

$taxSumQ = "SELECT  -1*sum(total) as tax_collected
FROM $dlog as d 
WHERE ".$dbc->date_equals('d.tdate',$dstr)." 
AND (d.upc = 'tax')
GROUP BY d.upc";
$taxSumR = $dbc->query($taxSumQ);
echo "<br /><b><u>Actual Tax Collected</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($taxSumR)));

$transQ = "select q.trans_num,sum(q.quantity) as items,transaction_type, sum(q.total) from
	(
	select trans_num,card_no,quantity,total,
        m.memdesc as transaction_type
	from $dlog as d
	left join custdata as c on d.card_no = c.cardno
	left join memTypeID as m on c.memtype = m.memTypeID
	WHERE ".$dbc->date_equals('d.tdate',$dstr)." AND 
	trans_type in ('I','D')
	and upc <> 'RRR'
	and c.personNum=1
	) as q 
	group by q.trans_num,q.transaction_type";
$transR = $dbc->query($transQ);
$transinfo = array("Member"=>array(0,0.0,0.0,0.0,0.0),
		   "Non Member"=>array(0,0.0,0.0,0.0,0.0),
		   "Staff Member"=>array(0,0.0,0.0,0.0,0.0),
		   "Staff NonMem"=>array(0,0.0,0.0,0.0,0.0));
while($row = $dbc->fetch_array($transR)){
	if (!isset($transinfo[$row[2]])) continue;
	$transinfo[$row[2]][0] += 1;
	$transinfo[$row[2]][1] += $row[1];
	$transinfo[$row[2]][3] += $row[3];
}
$tSum = 0;
$tItems = 0;
$tDollars = 0;
foreach(array_keys($transinfo) as $k){
	$transinfo[$k][2] = round($transinfo[$k][1]/$transinfo[$k][0],2);
	$transinfo[$k][4] = round($transinfo[$k][3]/$transinfo[$k][0],2);
	$tSum += $transinfo[$k][0];
	$tItems += $transinfo[$k][1];
	$tDollars += $transinfo[$k][3];
}
$transinfo["Totals"] = array($tSum,$tItems,round($tItems/$tSum,2),$tDollars,round($tDollars/$tSum,2));
echo "<br /><b>Transaction information</b>";
echo tablify($transinfo,array(0,1,2,3,4,5),
	array("Type","Transactions","Items","Average items/transaction","$","$/transaction"),
	array($ALIGN_LEFT,$ALIGN_RIGHT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY,
		$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY));

function tablify($data,$col_order,$col_headers,$formatting,$sum_col=-1){
	$sum = 0;
	$ret = "";
	
	$ret .= "<table cellspacing=0 cellpadding=4 border=1><tr>";
	$i = 0;
	foreach ($col_headers as $c){
		while ($formatting[$i] == 0) $i++;
		$ret .= cellify("<u>".$c."</u>",$formatting[$i++]&7);
	}
	$ret .= "</tr>";

	foreach(array_keys($data) as $k){
		$ret .= "<tr>";
		foreach($col_order as $c){
			if($c == 0) $ret .= cellify($k,$formatting[$c]);
			else $ret .= cellify($data[$k][$c-1],$formatting[$c]);

			if ($sum_col != -1 && $c == $sum_col)
				$sum += $data[$k][$c-1];
		}
		$ret .= "</tr>";
	}
	if (count($data) == 0){
		$ret .= "<tr>";
		$ret .= "<td colspan=".count($col_headers)." class=center>";
		$ret .= "No results to report"."</td>";
		$ret .= "</tr>";
	}

	if ($sum_col != -1 && count($data) > 0){
		$ret .= "<tr>";
		foreach($col_order as $c){
			if ($c+1 == $sum_col) $ret .= "<td>Total</td>";
			elseif ($c == $sum_col) $ret .= cellify($sum,$formatting[$c]);
			else $ret .= "<td>&nbsp;</td>";
		}
		$ret .= "</tr>";
	}

	$ret .= "</table>";

	return $ret;
}

function cellify($data,$formatting){
	$ALIGN_RIGHT = 1;
	$ALIGN_LEFT = 2;
	$ALIGN_CENTER = 4;
	$TYPE_MONEY = 8;
	$ret = "";
	if ($formatting & $ALIGN_LEFT) $ret .= "<td class=left>";
	elseif ($formatting & $ALIGN_RIGHT) $ret .= "<td class=right>";
	elseif ($formatting & $ALIGN_CENTER) $ret .= "<td class=center>";

	if ($formatting & $TYPE_MONEY) $ret .= sprintf("%.2f",$data);
	else $ret .= $data;

	$ret .= "</td>";

	return $ret;
}
?>
</body></html>
