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
    header('Content-Disposition: attachment; filename="rangeReport.xls"');
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
    echo "<script src=\"../../../../src/javascript/jquery.js\"></script>";
    echo "<script src=\"../../../../src/javascript/jquery-ui.js\"></script>";
    echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"../../../../src/javascript/jquery-ui.css\">";
    echo "<script type\"text/javascript\">
        $(document).ready(function(){
            $('.date').datepicker();
        });
        </script>";
    echo "<form action=index.php name=datelist method=get>";
    echo "Start Date<input name=date class=date type=text id=date >";
    echo "End Date<input type=text name=date2 class=date>";
    echo "<input name=Submit type=submit value=submit>";
    echo "</form>";
}

$today = date("m/j/y");
// Calculate the previous day's date, old method just gave zero - Andy
$repDate = date('m/j/y', mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));
$repDate2 = $repDate;

$date1 = date("Y-m-d",strtotime('yesterday'));
$ddiff = "'$date1 00:00:00' AND '$date1 23:59:59'";
if (FormLib::get('date') !== '' && FormLib::get('date2') !== '') {
   $repDate = FormLib::get('date');
   $repDate2 = FormLib::get('date2');

   $stamp1 = strtotime($repDate);
   $stamp2 = strtotime($repDate2);
   $date1 = date('Y-m-d',($stamp1 ? $stamp1 : strtotime('yesterday')));
   $date2 = date('Y-m-d',($stamp2 ? $stamp2 : strtotime('yesterday')));

   $ddiff = "'$date1 00:00:00' AND '$date2 23:59:59'";
}
$dates = array($date1.' 00:00:00',$date2.' 23:59:59');
$date_ids = array(date('Ymd', strtotime($date1)), date('Ymd', strtotime($date2)));

if ($excel === false) {
    echo "<br /><a href=index.php?date=$repDate&date2=$repDate2&excel=yes>Click here for Excel version</a>";
}

echo '<br>Report run ' . $today. ' for ' . $repDate." to ".$repDate2."<br />";

$dlog = DTransactionsModel::selectDlog($repDate,$repDate2);
//var_dump($dlog);
$dlog = "trans_archive.dlogBig";
$WAREHOUSE = $FANNIE_PLUGIN_SETTINGS['WarehouseDatabase'] . ($FANNIE_SERVER_DBMS=='MSSQL' ? '.dbo.' : '.');

$tenderQ = $dbc->prepare("SELECT t.TenderName,-sum(d.total) as total, SUM(d.quantity)
FROM {$WAREHOUSE}sumTendersByDay as d ,tenders as t 
WHERE d.date_id BETWEEN ? AND ?
AND d.trans_subtype = t.TenderCode
and d.total <> 0
GROUP BY t.TenderName");
$tenderR = $dbc->execute($tenderQ,$date_ids);
$tenders = WfcLib::getTenders();
$mad = array(0.0,0);
while ($row = $dbc->fetch_row($tenderR)){
    if(isset($tenders[$row[0]])){
        $tenders[$row[0]][1] = $row[1];
        $tenders[$row[0]][2] = $row[2];
    } elseif ($row[0] == "MAD Coupon"){
        $mad[0] = $row[1];
        $mad[1] = $row[2];
    } else {
        $tenders[$row[0]] = array('00000', $row[1], $row[2]);
    }
} 

echo "<br /><b>Tenders</b>";
echo WfcLib::tablify($tenders,array(1,0,2,3),array("Account","Type","Amount","Count"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_RIGHT),2);


$pCodeQ = $dbc->prepare("SELECT d.salesCode,-1*sum(l.total) as total,min(l.department) 
FROM {$WAREHOUSE}sumDeptSalesByDay as l join departments as d on l.department = d.dept_no
WHERE date_id BETWEEN ? AND ?
AND l.department < 600 AND l.department <> 0
GROUP BY d.salesCode
order by d.salesCode");
$pCodeR = $dbc->execute($pCodeQ,$date_ids);
$pCodes = WfcLib::getPCodes();
while($row = $dbc->fetch_row($pCodeR)){
    if (isset($pCodes[$row[0]])) $pCodes[$row[0]][0] = $row[1];
    //else var_dump( $row[2] );
}
echo "<br /><b>Sales</b>";
echo WfcLib::tablify($pCodes,array(0,1),array("pCode","Sales"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),1);

$saleSumQ = $dbc->prepare("SELECT -1*sum(l.total) as totalSales
FROM {$WAREHOUSE}sumDeptSalesByDay as l
WHERE date_id BETWEEN ? AND ?
AND l.department < 600 AND l.department <> 0");
$saleSumR = $dbc->execute($saleSumQ,$date_ids);
echo "<br /><b><u>Total Sales</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($saleSumR)));

$otherQ = $dbc->prepare("SELECT d.department,t.dept_name, -1*sum(total) as total 
FROM {$WAREHOUSE}sumDeptSalesByDay as d join departments as t ON d.department = t.dept_no
WHERE date_id BETWEEN ? AND ?
AND (d.department >300)AND d.department <> 0 
and d.department <> 610
and d.department not between 500 and 599
GROUP BY d.department, t.dept_name order by d.department");
$otherR = $dbc->execute($otherQ,$date_ids);
$others = WfcLib::getOtherCodes();
while($row = $dbc->fetch_row($otherR)){
    $others["$row[0]"][1] = $row[1];
    $others["$row[0]"][2] = $row[2]; 
}
echo "<br /><b>Other</b>";
echo WfcLib::tablify($others,array(1,0,2,3),array("Account","Dept","Description","Amount"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY),3);

$discQ = $dbc->prepare("SELECT     m.memDesc, -1*SUM(d.total) AS Discount,count(*) 
FROM $dlog d INNER JOIN
       custdata c ON d.card_no = c.CardNo INNER JOIN
      memtype m ON c.memType = m.memtype
WHERE     (d.tdate BETWEEN ? AND ? ) 
    AND (d.upc = 'DISCOUNT') AND c.personnum= 1
and total <> 0
GROUP BY m.memDesc, d.upc ");
$discR = $dbc->execute($discQ,$dates);
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

$taxSumQ = $dbc->prepare("SELECT  -1*sum(total) as tax_collected
FROM $dlog as d 
WHERE tdate BETWEEN ? AND ?
AND (d.upc = 'tax')
GROUP BY d.upc");
$taxSumR = $dbc->execute($taxSumQ,$dates);
echo "<br /><b><u>Actual Tax Collected</u></b><br />";
echo sprintf("%.2f<br />",array_pop($dbc->fetch_row($taxSumR)));

$transQ = $dbc->prepare("SELECT SUM(d.total),SUM(d.quantity),SUM(d.transCount),m.memdesc
    FROM {$WAREHOUSE}sumMemTypeSalesByDay as d LEFT JOIN
    memtype as m ON m.memtype=d.memType
    WHERE d.date_id BETWEEN ? AND ?
    GROUP BY d.memType, m.memdesc");
$transR = $dbc->execute($transQ,$date_ids);
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
