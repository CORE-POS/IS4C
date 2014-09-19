<?php
include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

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

$storeInfo = FormLib::storePicker();
if (!isset($_GET['excel'])){
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
if(isset($_GET['date'])){
   $repDate = $_GET['date'];
   $t1 = strtotime($repDate);
   if ($t1) $dstr = date("Y-m-d",$t1);
}
$store = FormLib::get('store', 0);
$dates = array($dstr.' 00:00:00',$dstr.' 23:59:59');
$store_dates = array($dstr.' 00:00:00',$dstr.' 23:59:59', $store);


if (!isset($_GET['excel']))
    echo "<br /><a href=index.php?date=$repDate&excel=yes>Click here for Excel version</a>";

echo '<br>Report run ' . $today. ' for ' . $repDate."<br />";
echo 'Store: ' . $storeInfo['names'][$store] . '<br />';

$dlog = DTransactionsModel::selectDlog($dstr);
$OP = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_OP_DB.'.dbo.' : $FANNIE_OP_DB.'.';
$TRANS = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_TRANS_DB.'.dbo.' : $FANNIE_TRANS_DB.'.';

$tenderQ = $dbc->prepare_statement("SELECT 
CASE WHEN d.trans_subtype IN ('CC','AX') then 'Credit Card' ELSE t.TenderName END as TenderName,
-sum(d.total) as total, COUNT(d.total)
FROM $dlog as d ,{$OP}tenders as t 
WHERE d.tdate BETWEEN ? AND ?
AND d.trans_status <>'X'  
AND d.trans_subtype = t.TenderCode
and d.total <> 0
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY CASE WHEN d.trans_subtype IN ('CC','AX') then 'Credit Card' ELSE t.TenderName END");
$tenderR = $dbc->exec_statement($tenderQ, $store_dates);
$tenders = array("Cash"=>array(10120,0.0,0),
        "Check"=>array(10120,0.0,0),
        "Electronic Check"=>array(10120,0.0,0),
        "Credit Card"=>array(10120,0.0,0),
        "EBT CASH."=>array(10120,0.0,0),
        "EBT FS"=>array(10120,0.0,0),
        "Gift Card"=>array(21205,0.0,0),
        "GIFT CERT"=>array(21200,0.0,0),
        "InStore Charges"=>array(10710,0.0,0),
        "Pay Pal"=>array(10120,0.0,0),
        "Coupons"=>array(10740,0.0,0),
        "InStoreCoupon"=>array(67710,0.0,0),
        "Store Credit"=>array(21200,0.0,0),
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

if ($store != 50) {
    $stamp = strtotime($dstr);
    $creditQ = "SELECT 1 as num, 
            MAX(CASE WHEN q.mode IN ('retail_alone_credit','Credit_Return') THEN -amount ELSE amount END) as ttl,
            CASE WHEN q.refNum LIKE '%-%' THEN 'FAPS' ELSE 'Mercury' END as proc
        FROM is4c_trans.efsnetRequest AS q LEFT JOIN is4c_trans.efsnetResponse AS r ON q.refNum=r.refNum
        LEFT JOIN is4c_trans.efsnetRequestMod AS m
        ON q.date=m.date AND q.cashierNo=m.cashierNo AND q.laneNo=m.laneNo
        AND q.transNo=m.transNo and q.transID=m.transID
        WHERE q.date=? and r.httpCode=200 and m.date IS NULL AND
        (r.xResultMessage LIKE '%approved%' OR r.xResultMessage LIKE '%PENDING%')
        AND q.CashierNo <> 9999 AND q.laneNo <> 99
        GROUP BY q.refNum";
    $creditP = $dbc->prepare_statement($creditQ);
    $creditR = $dbc->exec_statement($creditP, array( date('Ymd',$stamp) ));
    $cTallies = array('FAPS'=>array(0.0,0),'Mercury'=>array(0.0,0),
        'Non-integrated'=>array(0.0,0));
    while($creditW = $dbc->fetch_row($creditR)){
        $cTallies[$creditW['proc']][0] += $creditW['ttl'];
        $cTallies[$creditW['proc']][1]++;
    }
    $nonQ = "SELECT count(*) as num, sum(-total) as ttl, 'Non-integrated' as proc
        FROM $dlog as d LEFT JOIN 
        (SELECT * FROM is4c_trans.efsnetResponse WHERE date=?
        and httpCode=200 and 
        (xResultMessage LIKE '%approved%' OR xResultMessage LIKE '%PENDING%')
        ) AS r ON d.register_no=r.laneNo and d.emp_no=r.cashierNo and d.trans_no=r.transNo
        and d.trans_id=r.transID
        WHERE d.trans_type='T' AND d.trans_subtype='CC' AND r.transID IS NULL 
        AND d.tdate BETWEEN ? AND ?";
    $nonP = $dbc->prepare_statement($nonQ);
    $nonR = $dbc->exec_statement($nonP, array( date('Ymd',$stamp), $dates[0], $dates[1] ));
    if ($dbc->num_rows($nonR) > 0){
        $non = $dbc->fetch_row($nonR);
        $cTallies['Non-integrated'] = array($non['ttl'],$non['num']);
    }
    echo '<br /><b>Integrated CC Supplement</b>';
    echo tablify($cTallies,array(0,1,2),array('Processor','Amount','Count'),
        array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT),1);
}


$pCodeQ = $dbc->prepare_statement("SELECT s.salesCode,-1*sum(l.total) as total,min(l.department) 
FROM $dlog as l 
INNER JOIN {$OP}departments AS s ON l.department=s.dept_no
WHERE l.tdate BETWEEN ? AND ?
AND l.department < 600 AND l.department <> 0
AND l.trans_type <>'T'
AND " . DTrans::isStoreID($store, 'l') . "
GROUP BY s.salesCode
order by s.salesCode");
$pCodeR = $dbc->exec_statement($pCodeQ, $store_dates);
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

$saleSumQ = $dbc->prepare_statement("SELECT -1*sum(l.total) as totalSales
FROM $dlog as l
WHERE l.tdate BETWEEN ? AND ?
AND l.department < 600 AND l.department <> 0
AND " . DTrans::isStoreID($store, 'l') . "
AND l.trans_type <> 'T'");
$saleSumR = $dbc->exec_statement($saleSumQ, $store_dates);
echo "<br /><b><u>Total Sales</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($saleSumR)));

$returnsQ = $dbc->prepare_statement("SELECT s.salesCode,-1*sum(L.total)as returns
FROM $dlog as L,departments as s
WHERE s.dept_no = L.department
AND L.tdate BETWEEN ? AND ?
AND(trans_status = 'R')
AND " . DTrans::isStoreID($store, 'L') . "
GROUP BY s.salesCode");
$returnsR = $dbc->exec_statement($returnsQ, $store_dates);
$returns = array();
while($row = $dbc->fetch_row($returnsR))
    $returns["$row[0]"] = array($row[1]);
echo "<br /><b>Returns</b>";
echo tablify($returns,array(0,1),array("pCode","Sales"),
         array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY),1);

// idea here is to get everything to the right of the
// RIGHT MOST space, hence the reverse
$voidTransQ = $dbc->prepare_statement("SELECT RIGHT(description,".
        $dbc->locate("' '","REVERSE(description)")."-1),
           trans_num,-1*total from
           {$TRANS}voidTransHistory 
        WHERE tdate BETWEEN ? AND ?");
$voidTransR = $dbc->exec_statement($voidTransQ,$dates);
$voids = array();
while($row = $dbc->fetch_row($voidTransR))
    $voids["$row[0]"] = array($row[1],$row[2]);
echo "<br /><b>Voids</b>";
echo tablify($voids,array(0,1,2),array("Original","Void","Total"),
         array($ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY),2);

$otherQ = $dbc->prepare_statement("SELECT d.department,t.dept_name, -1*sum(total) as total 
FROM $dlog as d left join departments as t ON d.department = t.dept_no
WHERE d.tdate BETWEEN ? AND ?
AND d.department > 300 AND 
(d.register_no <> 20 or d.department = 703)
and d.department <> 610
and d.department not between 500 and 599
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.department, t.dept_name order by d.department");
$otherR = $dbc->exec_statement($otherQ, $store_dates);
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

$equityQ = $dbc->prepare_statement("SELECT d.card_no,t.dept_name, -1*sum(total) as total 
FROM $dlog as d left join departments as t ON d.department = t.dept_no
WHERE d.tdate BETWEEN ? AND ?
AND d.department IN(991,992) AND d.register_no <> 20
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.card_no, t.dept_name ORDER BY d.card_no, t.dept_name");
$equityR = $dbc->exec_statement($equityQ, $store_dates);
$equityrows = array();
while($row = $dbc->fetch_row($equityR)){
    $newrow = array("00-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2]);
    array_push($equityrows,$newrow);
}
echo "<br /><b>Equity Payments by Member Number</b>";
echo tablify($equityrows,array(1,2,3,4),array("Account","MemNum","Description","Amount"),
    array(0,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY));

$arQ = $dbc->prepare_statement("SELECT d.card_no,CASE WHEN d.department = 990 THEN 'AR PAYMENT' ELSE 'STORE CHARGE' END as description, 
-1*sum(total) as total, count(card_no) as transactions 
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND (d.department =990 OR d.trans_subtype = 'MI') and 
(d.register_no <> 20 or d.department <> 990)
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.card_no,d.department order by department,card_no");
$arR = $dbc->exec_statement($arQ, $store_dates);
$ar_rows = array();
while($row = $dbc->fetch_row($arR)){
    $newrow = array("01-".str_pad($row[0],7,"0",STR_PAD_LEFT),$row[0],$row[1],$row[2],$row[3]);
    array_push($ar_rows,$newrow);
}
echo "<br /><b>AR Activity by Member Number</b>";
echo tablify($ar_rows,array(1,2,3,4,5),array("Account","MemNum","Description","Amount","Transactions"),
    array(0,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT));

$discQ = $dbc->prepare_statement("SELECT     m.memDesc, -1*SUM(d.total) AS Discount,count(*) 
FROM $dlog d INNER JOIN
       custdata c ON d.card_no = c.CardNo INNER JOIN
      memTypeID m ON c.memType = m.memTypeID
WHERE d.tdate BETWEEN ? AND ?
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
and total <> 0
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY m.memDesc, d.upc ");
$discR = $dbc->exec_statement($discQ, $store_dates);
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
$checkQ = $dbc->prepare_statement("select ".$dbc->datediff("?","'2008-07-01'"));
$checkR = $dbc->exec_statement($checkQ,array($repDate));
$diff = array_pop($dbc->fetch_row($checkR));
if ($diff < 0) $deliTax = 0.025;

$checkQ = $dbc->prepare_statement("select ".$dbc->datediff("?","'2012-11-01'"));
$checkR = $dbc->exec_statement($checkQ,array($repDate));
$diff = array_pop($dbc->fetch_row($checkR));
$deliTax = 0.0325;
$deliTax = 0.02775; 

$stateTax = 0.0685;
$cityTax = 0.01;
$deliTax = 0.0225;
if (strtotime($repDate) >= strtotime('2008-07-01')) {
    $deliTax = 0.025;
} elseif (strtotime($repDate) >= strtotime('2012-11-01')) {
    $deliTax = 0.0325;
} elseif (strtotime($repDate) >= strtotime('2013-06-01')) {
    $deliTax = 0.02775; 
} elseif (strtotime($repDate) >= strtotime('2014-08-01')) {
    $deliTax = 0.0325;
}

$taxQ = $dbc->prepare_statement("SELECT (CASE WHEN d.tax = 1 THEN 'Non Deli Sales' ELSE 'Deli Sales' END) as type, sum(total) as taxable_sales,
.01*(sum(CASE WHEN d.tax = 1 THEN total ELSE 0 END)) as city_tax_nonDeli,
$deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END)) as city_tax_Del, 
.0685*(sum(total)) as state_tax,
((.01*(sum(CASE WHEN d.tax = 1 THEN total ELSE 0 END))) + ($deliTax*(sum(CASE WHEN d.tax = 2 THEN total ELSE 0 END))) + (.0685*(sum(total)))) as total_tax 
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND d.tax <> 0 
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.tax ORDER BY d.tax DESC");
$taxR = $dbc->exec_statement($taxQ, $store_dates);
$taxes = array();
while($row = $dbc->fetch_row($taxR))
    $taxes["$row[0]"] = array(-1*$row[1],-1*$row[2],-1*$row[3],-1*$row[4],-1*$row[5]);
echo "<br /><b>Sales Tax</b>";
echo tablify($taxes,array(0,1,2,3,4,5),array("&nbsp;","Taxable Sales","City Tax","Deli Tax","State Tax","Total Tax"),
    array($ALIGN_LEFT,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY,
          $ALIGN_RIGHT|$TYPE_MONEY,$ALIGN_RIGHT|$TYPE_MONEY));

$taxSumQ = $dbc->prepare_statement("SELECT  -1*sum(total) as tax_collected
FROM $dlog as d 
WHERE d.tdate BETWEEN ? AND ?
AND (d.upc = 'tax')
AND " . DTrans::isStoreID($store, 'd') . "
GROUP BY d.upc");
$taxSumR = $dbc->exec_statement($taxSumQ, $store_dates);
echo "<br /><b><u>Actual Tax Collected</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($taxSumR)));

$transQ = $dbc->prepare_statement("select q.trans_num,sum(q.quantity) as items,transaction_type, sum(q.total) from
    (
    select trans_num,card_no,quantity,total,
        m.memdesc as transaction_type
    from $dlog as d
    left join custdata as c on d.card_no = c.cardno
    left join memTypeID as m on c.memtype = m.memTypeID
    WHERE d.tdate BETWEEN ? AND ?
    AND trans_type in ('I','D')
    and upc <> 'RRR'
    and c.personNum=1
    AND " . DTrans::isStoreID($store, 'd') . "
    ) as q 
    group by q.trans_num,q.transaction_type");
$transR = $dbc->exec_statement($transQ, $store_dates);
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
