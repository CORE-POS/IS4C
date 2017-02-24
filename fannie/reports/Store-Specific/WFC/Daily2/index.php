<?php
include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);
if (!class_exists('WfcLib')) {
    require(dirname(__FILE__) . '/../WfcLib.php');
}


$excel = FormLib::get('excel') !== '' ? true : false;
if ($excel) {
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="dailyReport.xls"');
} else {
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

if ($excel === false) {
    echo "<form action=index.php name=datelist method=get>";
    echo "<input name=date type=text id=date >";

    echo "<input name=Submit type=submit value=submit>";
    echo "</form>";
}

$today = date("m/j/y");
// Calculate the previous day's date, old method just gave zero - Andy
$repDate = date('m/j/y', mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));

$dstr = date("Y-m-d",strtotime("yesterday"));
if (FormLib::get('date') !== '') {
   $repDate = FormLib::get('date');
   $stamp = strtotime($repDate);
   if ($stamp) $dstr = date("Y-m-d",$stamp);
}

if ($excel === false) {
    echo "<br /><a href=index.php?date=$repDate&excel=yes>Click here for Excel version</a>";
}

echo '<br>Report run ' . $today. ' for ' . $repDate."<br />";

$dlog = DTransactionsModel::selectDlog($dstr);
$OP_DB = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_OP_DB.'.dbo.' : $FANNIE_OP_DB.'.';
$TRANS = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_TRANS_DB.'.dbo.' : $FANNIE_TRANS_DB.'.';
$WAREHOUSE = $FANNIE_PLUGIN_SETTINGS['WarehouseDatabase'] . ($FANNIE_SERVER_DBMS=='MSSQL' ? '.dbo.' : '.');
$date_id = date('Ymd', strtotime($dstr));

$tenderQ = $dbc->prepare("SELECT t.TenderName,-sum(d.total) as total, d.quantity
FROM {$WAREHOUSE}sumTendersByDay as d ,{$OP_DB}tenders as t 
WHERE d.date_id=?
AND d.trans_subtype = t.TenderCode
and d.total <> 0
GROUP BY t.TenderName");
$tenderR = $dbc->execute($tenderQ,array($date_id));
$tenders = WfcLib::getTenders();
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
echo WfcLib::tablify($tenders,array(1,0,2,3),array("Account","Type","Amount","Count"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT),2);


$pCodeQ = $dbc->prepare("SELECT s.salesCode,-1*sum(l.total) as total,min(l.department) 
FROM {$WAREHOUSE}sumDeptSalesByDay as l
INNER JOIN {$OP_DB}departments AS s ON l.department=s.dept_no
WHERE l.date_id=?
AND l.department < 600 AND l.department <> 0
GROUP BY s.salesCode
order by s.salesCode");
$pCodeR = $dbc->execute($pCodeQ,array($date_id));
$pCodes = WfcLib::getPCodes();
while($row = $dbc->fetch_row($pCodeR)){
    if (isset($pCodes[$row[0]])) $pCodes[$row[0]][0] = $row[1];
}
echo "<br /><b>Sales</b>";
echo WfcLib::tablify($pCodes,array(0,1),array("pCode","Sales"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),1);

$saleSumQ = $dbc->prepare("SELECT -1*sum(l.total) as totalSales
FROM {$WAREHOUSE}sumDeptSalesByDay as l
WHERE l.date_id = ?
AND l.department < 600 AND l.department <> 0");
$saleSumR = $dbc->execute($saleSumQ,array($date_id));
echo "<br /><b><u>Total Sales</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($saleSumR)));

$returnsQ = $dbc->prepare("SELECT s.salesCode,-1*sum(L.total)as returns
FROM $dlog as L,departments as s
WHERE s.dept_no = L.department
AND L.tdate BETWEEN ? AND ?
AND(trans_status = 'R')
GROUP BY s.salesCode");
$dates = array($dstr.' 00:00:00',$dstr.' 23:59:59');
$returnsR = $dbc->execute($returnsQ,$dates);
$returns = array();
while($row = $dbc->fetch_row($returnsR))
    $returns["$row[0]"] = array($row[1]);
echo "<br /><b>Returns</b>";
echo WfcLib::tablify($returns,array(0,1),array("pCode","Sales"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),1);

// idea here is to get everything to the right of the
// RIGHT MOST space, hence the reverse
$voidTransQ = $dbc->prepare("SELECT RIGHT(description,".
        $dbc->locate("' '","REVERSE(description)")."-1),
           trans_num,-1*total from
           {$TRANS}voidTransHistory where tdate BETWEEN ? AND ?");
$voidTransR = $dbc->execute($voidTransQ,$dates);
$voids = array();
while($row = $dbc->fetch_row($voidTransR))
    $voids["$row[0]"] = array($row[1],$row[2]);
echo "<br /><b>Voids</b>";
echo WfcLib::tablify($voids,array(0,1,2),array("Original","Void","Total"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),2);

$otherQ = $dbc->prepare("SELECT d.department,t.dept_name, -1*sum(total) as total 
FROM {$WAREHOUSE}sumDeptSalesByDay as d left join departments as t ON d.department = t.dept_no
WHERE d.date_id=?
AND d.department > 300 
and d.department <> 610
and d.department not between 500 and 599
GROUP BY d.department, t.dept_name order by d.department");
$otherR = $dbc->execute($otherQ,array($date_id));
$others = WfcLib::getOtherCodes();
while($row = $dbc->fetch_row($otherR)){
    $others["$row[0]"][1] = $row[1];
    $others["$row[0]"][2] = $row[2]; 
}
echo "<br /><b>Other</b>";
echo WfcLib::tablify($others,array(1,0,2,3),array("Account","Dept","Description","Amount"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),3);

$equityQ = $dbc->prepare("SELECT d.card_no,t.dept_name, -1*sum(stockPurchase) as total 
FROM {$TRANS}stockpurchases as d left join departments as t ON d.dept = t.dept_no
WHERE d.tdate BETWEEN ? AND ?
GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name");
$equityR = $dbc->execute($equityQ,$dates);
$equityrows = array();
while($row = $dbc->fetch_row($equityR)){
    $newrow = array("00-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2]);
    array_push($equityrows,$newrow);
}
echo "<br /><b>Equity Payments by Member Number</b>";
echo WfcLib::tablify($equityrows,array(1,2,3,4),array("Account","MemNum","Description","Amount"),
    array(0,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));

$arQ = $dbc->prepare("SELECT d.card_no,'STORE CHARGE' as description,
SUM(d.Charges),
count(card_no) as transactions 
FROM {$TRANS}ar_history as d 
WHERE d.tdate BETWEEN ? AND ?
AND d.Charges <> 0
GROUP BY d.card_no

UNION ALL

SELECT d.card_no,'AR PAYMENT' as description,
-1*SUM(d.Payments),
count(card_no) as transactions 
FROM {$TRANS}ar_history as d 
WHERE d.tdate BETWEEN ? AND ?
AND d.Payments <> 0
GROUP BY d.card_no

ORDER BY description DESC, card_no");
$arR = $dbc->execute($arQ,array($dates[0],$dates[1],$dates[0],$dates[1]));
$ar_rows = array();
while($row = $dbc->fetch_row($arR)){
    $newrow = array("01-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2],$row[3]);
    array_push($ar_rows,$newrow);
}
echo "<br /><b>AR Activity by Member Number</b>";
echo WfcLib::tablify($ar_rows,array(1,2,3,4,5),array("Account","MemNum","Description","Amount","Transactions"),
    array(0,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT));

$discQ = $dbc->prepare("SELECT m.memDesc, -1*SUM(d.total) AS Discount,SUM(transCount)
FROM {$WAREHOUSE}sumDiscountsByDay AS d INNER JOIN
      memtype m ON d.memType = m.memtype
WHERE d.date_id=?
GROUP BY m.memDesc");
$discR = $dbc->execute($discQ,array($date_id));
$discounts = array("MAD Coupon"=>array(66600,$mad[0],$mad[1]),
           "Staff Member"=>array(61170,0.0,0),
           "Staff NonMem"=>array(61170,0.0,0),
           "Member"=>array(66600,0.0,0));
while($row = $dbc->fetch_row($discR)){
    $discounts[$row[0]][1] = $row[1];
    $discounts[$row[0]][2] = $row[2];
}
echo "<br /><b>Discounts</b>";
echo WfcLib::tablify($discounts,array(1,0,2,3),array("Account","Type","Amount","Count"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT),2);

$deliTax = 0.0325;
$checkQ = $dbc->prepare("select ".$dbc->datediff("?","'2008-07-01'"));
$checkR = $dbc->execute($checkQ,array($repDate));
$diff = array_pop($dbc->fetch_row($checkR));
if ($diff < 0) $deliTax = 0.025;


$taxQ = $dbc->prepare("SELECT (CASE WHEN d.tax = 1 THEN 'Non Deli Sales' ELSE 'Deli Sales' END) as type, sum(total) as taxable_sales,
.01*(sum(CASE WHEN d.tax = 1 THEN total ELSE 0 END)) as city_tax_nonDeli,
$deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END)) as city_tax_Del, 
.065*(sum(total)) as state_tax,
((.01*(sum(CASE WHEN d.tax = 1 THEN total ELSE 0 END))) + ($deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END))) + (.065*(sum(total)))) as total_tax 
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND d.tax <> 0 
GROUP BY d.tax ORDER BY d.tax DESC");
$taxR = $dbc->execute($taxQ,$dates);
$taxes = array();
while($row = $dbc->fetch_row($taxR))
    $taxes["$row[0]"] = array(-1*$row[1],-1*$row[2],-1*$row[3],-1*$row[4],-1*$row[5]);
echo "<br /><b>Sales Tax</b>";
echo WfcLib::tablify($taxes,array(0,1,2,3,4,5),array("&nbsp;","Taxable Sales","City Tax","Deli Tax","State Tax","Total Tax"),
    array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,
          WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));

$taxSumQ = $dbc->prepare("SELECT  -1*sum(total) as tax_collected
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND (d.upc = 'tax')
GROUP BY d.upc");
$taxSumR = $dbc->execute($taxSumQ,$dates);
echo "<br /><b><u>Actual Tax Collected</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($taxSumR)));

$transQ = $dbc->prepare("SELECT d.total,d.quantity,d.transCount,m.memdesc
    FROM {$WAREHOUSE}sumMemTypeSalesByDay as d LEFT JOIN
    memtype as m ON m.memtype=d.memType
    WHERE d.date_id=?");
$transR = $dbc->execute($transQ,array($date_id));
$transinfo = array("Member"=>array(0,0.0,0.0,0.0,0.0),
           "Non Member"=>array(0,0.0,0.0,0.0,0.0),
           "Staff Member"=>array(0,0.0,0.0,0.0,0.0),
           "Staff NonMem"=>array(0,0.0,0.0,0.0,0.0));
while($row = $dbc->fetchRow($transR)){
    if (!isset($transinfo[$row[3]])) continue;
    $transinfo[$row[3]] = array($row[2],$row[1],
        round($row[1]/$row[2],2),$row[0],
        round($row[0]/$row[2],2)
    );
}
$tSum = 0;
$tItems = 0;
$tDollars = 0;
foreach(array_keys($transinfo) as $k){
    $tSum += $transinfo[$k][0];
    $tItems += $transinfo[$k][1];
    $tDollars += $transinfo[$k][3];
}
$transinfo["Totals"] = array($tSum,$tItems,round($tItems/$tSum,2),$tDollars,round($tDollars/$tSum,2));
echo "<br /><b>Transaction information</b>";
echo WfcLib::tablify($transinfo,array(0,1,2,3,4,5),
    array("Type","Transactions","Items","Average items/transaction","$","$/transaction"),
    array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,
        WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));
?>
</body></html>
