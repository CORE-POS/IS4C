<?php
include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="rangeReport.xls"');
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
    echo "Start Date<input name=date type=text id=date >";
    echo "End Date<input type=text name=date2>";
    echo "<input name=Submit type=submit value=submit>";
    echo "</form>";
}

$today = date("m/j/y");
// Calculate the previous day's date, old method just gave zero - Andy
$repDate = date('m/j/y', mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));
$repDate2 = $repDate;

$d1 = date("Y-m-d",strtotime('yesterday'));
$ddiff = "'$d1 00:00:00' AND '$d1 23:59:59'";
if(isset($_REQUEST['date']) && isset($_REQUEST['date2'])){
   $repDate = $_REQUEST['date'];
   $repDate2 = $_REQUEST['date2'];

   $t1 = strtotime($repDate);
   $t2 = strtotime($repDate2);
   $d1 = date('Y-m-d',($t1 ? $t1 : strtotime('yesterday')));
   $d2 = date('Y-m-d',($t2 ? $t2 : strtotime('yesterday')));

   $ddiff = "'$d1 00:00:00' AND '$d2 23:59:59'";
}
$dates = array($d1.' 00:00:00',$d2.' 23:59:59');

if (!isset($_GET['excel']))
    echo "<br /><a href=index.php?date=$repDate&date2=$repDate2&excel=yes>Click here for Excel version</a>";

echo '<br>Report run ' . $today. ' for ' . $repDate." to ".$repDate2."<br />";

$dlog = DTransactionsModel::selectDlog($repDate,$repDate2);
//var_dump($dlog);
$dlog = "trans_archive.dlogBig";
$ARCH = $FANNIE_SERVER_DBMS=='MSSQL' ? $FANNIE_ARCHIVE_DB.'.dbo.' : $FANNIE_ARCHIVE_DB.'.';

$tenderQ = $dbc->prepare_statement("SELECT t.TenderName,-sum(d.total) as total, SUM(d.quantity)
FROM {$ARCH}sumTendersByDay as d ,tenders as t 
WHERE d.tdate BETWEEN ? AND ?
AND d.tender_code = t.TenderCode
and d.total <> 0
GROUP BY t.TenderName");
$tenderR = $dbc->exec_statement($tenderQ,$dates);
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


$pCodeQ = $dbc->prepare_statement("SELECT d.salesCode,-1*sum(l.total) as total,min(l.dept_ID) 
FROM {$ARCH}sumDeptSalesByDay as l join departments as d on l.dept_ID = d.dept_no
WHERE tdate BETWEEN ? AND ?
AND l.dept_ID < 600 AND l.dept_ID <> 0
GROUP BY d.salesCode
order by d.salesCode");
$pCodeR = $dbc->exec_statement($pCodeQ,$dates);
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
FROM {$ARCH}sumDeptSalesByDay as l
WHERE tdate BETWEEN ? AND ?
AND l.dept_ID < 600 AND l.dept_ID <> 0");
$saleSumR = $dbc->exec_statement($saleSumQ,$dates);
echo "<br /><b><u>Total Sales</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($saleSumR)));

$otherQ = $dbc->prepare_statement("SELECT d.dept_ID,t.dept_name, -1*sum(total) as total 
FROM {$ARCH}sumDeptSalesByDay as d join departments as t ON d.dept_ID = t.dept_no
WHERE tdate BETWEEN ? AND ?
AND (d.dept_ID >300)AND d.dept_ID <> 0 
and d.dept_ID <> 610
and d.dept_ID not between 500 and 599
GROUP BY d.dept_ID, t.dept_name order by d.dept_ID");
$otherR = $dbc->exec_statement($otherQ,$dates);
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

$discQ = $dbc->prepare_statement("SELECT     m.memDesc, -1*SUM(d.total) AS Discount,count(*) 
FROM $dlog d INNER JOIN
       custdata c ON d.card_no = c.CardNo INNER JOIN
      memTypeID m ON c.memType = m.memTypeID
WHERE     (d.tdate BETWEEN ? AND ? ) 
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
and total <> 0
GROUP BY m.memDesc, d.upc ");
$discR = $dbc->exec_statement($discQ,$dates);
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

$taxSumQ = $dbc->prepare_statement("SELECT  -1*sum(total) as tax_collected
FROM $dlog as d 
WHERE tdate BETWEEN ? AND ?
AND (d.upc = 'tax')
GROUP BY d.upc");
$taxSumR = $dbc->exec_statement($taxSumQ,$dates);
echo "<br /><b><u>Actual Tax Collected</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($taxSumR)));

$transQ = $dbc->prepare_statement("SELECT SUM(d.total),SUM(d.quantity),SUM(d.transCount),m.memdesc
    FROM {$ARCH}sumMemTypeSalesByDay as d LEFT JOIN
    memTypeID as m ON m.memTypeID=d.memType
    WHERE d.tdate BETWEEN ? AND ?
    GROUP BY d.memType, m.memdesc");
$transR = $dbc->exec_statement($transQ,$dates);
$transinfo = array("Member"=>array(0,0.0,0.0,0.0,0.0),
           "Non Member"=>array(0,0.0,0.0,0.0,0.0),
           "Staff Member"=>array(0,0.0,0.0,0.0,0.0),
           "Staff NonMem"=>array(0,0.0,0.0,0.0,0.0));
while($row = $dbc->fetch_array($transR)){
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
