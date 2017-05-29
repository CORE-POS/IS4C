<?php
use COREPOS\Fannie\API\item\StandardAccounting;
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

$storeInfo = FormLib::storePicker();
if ($excel === false) {
    echo "<form action=index.php name=datelist method=get>";
    echo "<input name=date type=text id=date value=\"" . FormLib::get('date') . "\">";

    echo "<input name=Submit type=submit value=submit>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo $storeInfo['html'];
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
$store = FormLib::get('store', false);
if ($store === false) {
    $clientIP = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    foreach ($FANNIE_STORE_NETS as $storeID => $range) {
        if (
            class_exists('\\Symfony\\Component\\HttpFoundation\\IpUtils')
            && \Symfony\Component\HttpFoundation\IpUtils::checkIp($clientIP, $range)
            ) {
            $store = $storeID;
        }
    }
    if ($store === false) {
        $store = 0;
    }
}
$dates = array($dstr.' 00:00:00',$dstr.' 23:59:59');
$store_dates = array($dstr.' 00:00:00',$dstr.' 23:59:59', $store);

if ($excel === false) {
    echo "<br /><a href=index.php?date=$repDate&store=$store&excel=yes>Click here for Excel version</a>";
}

echo '<br>Report run ' . $today. ' for ' . $repDate."<br />";
echo 'Store: ' . $storeInfo['names'][$store] . '<br />';

$dlog = DTransactionsModel::selectDlog($dstr);
$OP_DB = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_OP_DB.'.dbo.' : $FANNIE_OP_DB.'.';
$TRANS = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_TRANS_DB.'.dbo.' : $FANNIE_TRANS_DB.'.';

$tenderQ = $dbc->prepare("SELECT 
CASE WHEN d.trans_subtype IN ('CC','AX') then 'Credit Card' WHEN description='WIC' THEN 'WIC' ELSE t.TenderName END as TenderName,
-sum(d.total) as total, COUNT(d.total)
FROM $dlog as d ,{$OP_DB}tenders as t 
WHERE d.tdate BETWEEN ? AND ?
AND d.trans_status <>'X'  
AND d.trans_subtype = t.TenderCode
and d.total <> 0
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY CASE WHEN d.trans_subtype IN ('CC','AX') then 'Credit Card' WHEN description='WIC' THEN 'WIC' ELSE t.TenderName END");
$tenderR = $dbc->execute($tenderQ, $store_dates);
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

if ($store != 50) {
    echo '<br /><a href="../../../Paycards/PcDailyReport.php?date='. $dstr . '&store=' . $store . '">Integrated CC Supplement</a><br />';

    $couponQ = "
        SELECT SUM(-d.total) AS ttl,
            COUNT(d.total) AS num,
            CASE WHEN d.upc='PATREBDISC' THEN 'Rebate Check Discount' ELSE d.description END as name
        FROM $dlog AS d
        WHERE trans_type='T'
            AND trans_subtype='IC'
            AND d.tdate BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
        GROUP BY
            CASE WHEN d.upc='PATREBDISC' THEN 'Rebate Check Discount' ELSE d.description END
        ORDER BY
            CASE WHEN d.upc='PATREBDISC' THEN 'Rebate Check Discount' ELSE d.description END";
    $couponP = $dbc->prepare($couponQ);
    $couponR = $dbc->execute($couponP, $store_dates);
    $coupons = array();
    echo '<br /><b>InStore Coupon Supplement</b>';
    while ($couponsW = $dbc->fetch_row($couponR)) {
        $coupons[$couponsW['name']] = array($couponsW['ttl'], $couponsW['num']);
    }
    echo WfcLib::tablify($coupons, array(0,1,2), array('Name','Amount','Count'),
        array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT),1);
}


$pCodeQ = $dbc->prepare("SELECT s.salesCode,-1*sum(l.total) as total,min(l.department) , l.store_id
FROM $dlog as l 
INNER JOIN {$OP_DB}departments AS s ON l.department=s.dept_no
WHERE l.tdate BETWEEN ? AND ?
AND l.department < 600 AND l.department <> 0
AND l.trans_type <>'T'
AND " . DTrans::isStoreID($store, 'l') . "
GROUP BY s.salesCode, l.store_id
order by s.salesCode");
$pCodeR = $dbc->execute($pCodeQ, $store_dates);
$pCodes = WfcLib::getPCodes();
$data = array();
while($row = $dbc->fetch_row($pCodeR)){
    if (isset($pCodes[$row[0]])) {
        $code = StandardAccounting::extend($row[0], $row['store_id']);
        $data[$code] = array($row[1]);
    }
}
echo "<br /><b>Sales</b>";
echo WfcLib::tablify($data,array(0,1),array("pCode","Sales"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),1);

$saleSumQ = $dbc->prepare("SELECT -1*sum(l.total) as totalSales
FROM $dlog as l
WHERE l.tdate BETWEEN ? AND ?
AND l.department < 600 AND l.department <> 0
AND " . DTrans::isStoreID($store, 'l') . "
AND l.trans_type <> 'T'");
$saleSumR = $dbc->execute($saleSumQ, $store_dates);
echo "<br /><b><u>Total Sales</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($saleSumR)));

$returnsQ = $dbc->prepare("SELECT s.salesCode,-1*sum(L.total)as returns
FROM $dlog as L,departments as s
WHERE s.dept_no = L.department
AND L.tdate BETWEEN ? AND ?
AND(trans_status = 'R')
AND " . DTrans::isStoreID($store, 'L') . "
GROUP BY s.salesCode");
$returnsR = $dbc->execute($returnsQ, $store_dates);
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
           {$TRANS}voidTransHistory 
        WHERE tdate BETWEEN ? AND ?");
$voidTransR = $dbc->execute($voidTransQ,$dates);
$voids = array();
while($row = $dbc->fetch_row($voidTransR))
    $voids["$row[0]"] = array($row[1],$row[2]);
echo "<br /><b>Voids</b>";
echo WfcLib::tablify($voids,array(0,1,2),array("Original","Void","Total"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),2);

$otherQ = $dbc->prepare("SELECT d.department,t.dept_name, -1*sum(total) as total 
FROM $dlog as d left join departments as t ON d.department = t.dept_no
WHERE d.tdate BETWEEN ? AND ?
AND d.department > 300 AND 
(d.register_no <> 20 or d.department = 703)
and d.department <> 610
and d.department not between 500 and 599
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.department, t.dept_name order by d.department");
$otherR = $dbc->execute($otherQ, $store_dates);
$others = WfcLib::getOtherCodes();
while($row = $dbc->fetch_row($otherR)){
    $others["$row[0]"][1] = $row[1];
    $others["$row[0]"][2] = $row[2]; 
}
echo "<br /><b>Other</b>";
echo WfcLib::tablify($others,array(1,0,2,3),array("Account","Dept","Description","Amount"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),3);

$equityQ = $dbc->prepare("SELECT d.card_no,t.dept_name, -1*sum(total) as total 
FROM $dlog as d left join departments as t ON d.department = t.dept_no
WHERE d.tdate BETWEEN ? AND ?
AND d.department IN(991,992) AND d.register_no <> 20
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name");
$equityR = $dbc->execute($equityQ, $store_dates);
$equityrows = array();
while($row = $dbc->fetch_row($equityR)){
    $newrow = array("00-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2]);
    array_push($equityrows,$newrow);
}
echo "<br /><b>Equity Payments by Member Number</b>";
echo WfcLib::tablify($equityrows,array(1,2,3,4),array("Account","MemNum","Description","Amount"),
    array(0,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));

$arQ = $dbc->prepare("SELECT d.card_no,CASE WHEN d.department = 990 THEN 'AR PAYMENT' ELSE 'STORE CHARGE' END as description, 
-1*sum(total) as total, count(card_no) as transactions 
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND (d.department =990 OR d.trans_subtype = 'MI') and 
(d.register_no <> 20 or d.department <> 990)
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.card_no,d.department order by department,card_no");
$arR = $dbc->execute($arQ, $store_dates);
$ar_rows = array();
while($row = $dbc->fetch_row($arR)){
    $newrow = array("01-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2],$row[3]);
    array_push($ar_rows,$newrow);
}
echo "<br /><b>AR Activity by Member Number</b>";
echo WfcLib::tablify($ar_rows,array(1,2,3,4,5),array("Account","MemNum","Description","Amount","Transactions"),
    array(0,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT));

$discQ = $dbc->prepare("SELECT     m.memDesc, -1*SUM(d.total) AS Discount,count(*) 
FROM $dlog d INNER JOIN
       custdata c ON d.card_no = c.CardNo INNER JOIN
      memtype m ON c.memType = m.memtype
WHERE d.tdate BETWEEN ? AND ?
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
and total <> 0
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY m.memDesc, d.upc ");
$discR = $dbc->execute($discQ, $store_dates);
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

$deliTax = 0.0225;
$checkQ = $dbc->prepare("select ".$dbc->datediff("?","'2008-07-01'"));
$checkR = $dbc->execute($checkQ,array($repDate));
$diff = array_pop($dbc->fetch_row($checkR));
if ($diff < 0) $deliTax = 0.025;

$checkQ = $dbc->prepare("select ".$dbc->datediff("?","'2012-11-01'"));
$checkR = $dbc->execute($checkQ,array($repDate));
$diff = array_pop($dbc->fetch_row($checkR));
$deliTax = 0.0325;
$deliTax = 0.02775; 

$stateTax = 0.06875;
$cityTax = 0.01;
$deliTax = 0.0225;
$countyTax = 0.005;
if (strtotime($repDate) >= strtotime('2008-07-01')) {
    $deliTax = 0.025;
} 
if (strtotime($repDate) >= strtotime('2012-11-01')) {
    $deliTax = 0.0225;
} 
if (strtotime($repDate) >= strtotime('2013-06-01')) {
    $deliTax = 0.01775; 
} 
if (strtotime($repDate) >= strtotime('2014-08-01')) {
    $deliTax = 0.0225;
} 
if (strtotime($repDate) <= strtotime('2015-04-01')) {
    $countyTax = 0;
}

$taxQ = $dbc->prepare("
SELECT 
    (CASE WHEN d.tax = 1 THEN 'Non Deli Sales' ELSE 'Deli Sales' END) as type, 
    sum(total) as taxable_sales,
    $cityTax*(sum(total)) as city_tax,
    $deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END)) as deli_tax,
    $stateTax*(sum(total)) as state_tax,
    $countyTax*(SUM(total)) AS county_tax
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND d.tax <> 0 
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.tax ORDER BY d.tax DESC");
$taxR = $dbc->execute($taxQ, $store_dates);
$taxes = array();
while($row = $dbc->fetch_row($taxR))
    $taxes["$row[0]"] = array(
        -1*$row['taxable_sales'],
        -1*$row['city_tax'],
        -1*$row['deli_tax'],
        -1*$row['county_tax'],
        -1*$row['state_tax'],
        -1*($row['city_tax']+$row['county_tax']+$row['state_tax']+$row['deli_tax'])
    );
echo "<br /><b>Sales Tax</b>";
echo WfcLib::tablify($taxes,array(0,1,2,3,4,5,6),
    array(
        "&nbsp;",
        "Taxable Sales",
        sprintf("City Tax (%.2f%%)", $cityTax*100),
        sprintf("Deli Tax (%.2f%%)", $deliTax*100),
        sprintf("County Tax (%.2f%%)", $countyTax*100),
        sprintf("State Tax (%.3f%%)", $stateTax*100),
        "Total Tax"
    ),
    array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,
          WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));

$taxSumQ = $dbc->prepare("SELECT  -1*sum(total) as tax_collected
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND (d.upc = 'tax')
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.upc");
$taxSumR = $dbc->execute($taxSumQ, $store_dates);
echo "<br /><b><u>Actual Tax Collected</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($taxSumR)));

$transQ = $dbc->prepare("select q.trans_num,sum(q.quantity) as items,transaction_type, sum(q.total) from
    (
    select trans_num,card_no,quantity,total,
        m.memdesc as transaction_type
    from $dlog as d
    left join custdata as c on d.card_no = c.cardno
    left join memtype as m on c.memtype = m.memtype
    WHERE d.tdate BETWEEN ? AND ?
    AND trans_type in ('I','D')
    and upc <> 'RRR'
    and c.personNum=1
    AND " . DTrans::isStoreID($store, 'd') . "
    ) as q 
    group by q.trans_num,q.transaction_type");
$transR = $dbc->execute($transQ, $store_dates);
$transinfo = array("Member"=>array(0,0.0,0.0,0.0,0.0),
           "Non Member"=>array(0,0.0,0.0,0.0,0.0),
           "Staff Member"=>array(0,0.0,0.0,0.0,0.0),
           "Staff NonMem"=>array(0,0.0,0.0,0.0,0.0));
while($row = $dbc->fetchRow($transR)){
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
echo WfcLib::tablify($transinfo,array(0,1,2,3,4,5),
    array("Type","Transactions","Items","Average items/transaction","$","$/transaction"),
    array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,
        WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));

?>
</body></html>
