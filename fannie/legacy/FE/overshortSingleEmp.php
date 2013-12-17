<?php
include('../../config.php');

$date = $_GET['date'];
$emp_no = $_GET['emp_no'];

header('Location: '.$FANNIE_URL.'modules/plugins2.0/OverShortTools/OverShortDayPage.php?action=date&arg='.$date.'&emp_no='.$emp_no);
exit;

require($FANNIE_ROOT.'auth/login.php');
$user = validateUserQuiet('overshorts');

/*
 * check isset too in case 20 minute login expired while data
 * was being entered.
 */
if (!$user && !isset($_POST['action'])){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/FE/overshort.php");
	return;
}

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');

$dlog = DTransactionsModel::selectDlog($date);
$dlog = "trans_archive.dlogBig";

$query = "select firstname,emp_no from employees where emp_no = $emp_no";
$result = $sql->query($query);
$row = $sql->fetch_array($result);

$output = "<h3 id=currentdate>$date</h3>";
$output .= "<form onsubmit=\"save(); return false;\">";
$output .= "<table border=1 cellspacing=2 cellpadding=2><tr>";
$output .= "<th>Name</th><th>&nbsp;</th><th>Total</th><th>Counted Amt</th><th>Over/Short</th></tr>";

$sql->query("USE is4c_trans");

$q = "SELECT -1*sum(total) AS total,emp_no,trans_subtype FROM $dlog
WHERE ".$sql->date_equals('tdate',$date)." 
GROUP BY emp_no,trans_subtype";
$r = $sql->query($q);
$posttl = array();
while($w = $sql->fetch_row($r)){
	if (!isset($posttl[$w['emp_no']])) $posttl[$w['emp_no']] = array();
	$posttl[$w['emp_no']][$w['trans_subtype']] = $w['total'];
}
    
$perCashierTotal = 0;
$perCashierCountTotal = 0;
$perCashierOSTotal = 0;

/*
$caQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
      and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'CA'";
$caR = $sql->query($caQ);
$caW = $sql->fetch_array($caR);
*/
$caW = array((isset($posttl[$row[1]]['CA'])) ? $posttl[$row[1]]['CA'] : 0);
if (empty($caW[0]))
  $caW[0] = 0;
$perCashierTotal += $caW[0];
      
/*
$ckQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
      and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'CK'";
$ckR = $sql->query($ckQ);
$ckW = $sql->fetch_array($ckR);
*/
$ckW = array((isset($posttl[$row[1]]['CK'])) ? $posttl[$row[1]]['CK'] : 0);
if (empty($ckW[0]))
  $ckW[0] = 0;
$perCashierTotal += $ckW[0];
      
/*
$ccQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
      and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'CC'";
$ccR = $sql->query($ccQ);
$ccW = $sql->fetch_array($ccR);
*/
$ccW = array((isset($posttl[$row[1]]['CC'])) ? $posttl[$row[1]]['CC'] : 0);
if (empty($ccW[0]))
  $ccW[0] = 0;
$perCashierTotal += $ccW[0];
      
/*
$miQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'MI'";
$miR = $sql->query($miQ);
$miW = $sql->fetch_array($miR);
*/
$miW = array((isset($posttl[$row[1]]['MI'])) ? $posttl[$row[1]]['MI'] : 0);
if (empty($miW[0]))
  $miW[0] = 0;
$perCashierTotal += $miW[0];
      
/*
$tcQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'TC'";
$tcR = $sql->query($tcQ);
$tcW = $sql->fetch_array($tcR);
*/
$tcW = array((isset($posttl[$row[1]]['TC'])) ? $posttl[$row[1]]['TC'] : 0);
if (empty($tcW[0]))
  $tcW[0] = 0;
$perCashierTotal += $tcW[0];
      
/*
$gdQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'GD'";
$gdR = $sql->query($gdQ);
$gdW = $sql->fetch_array($gdR);
*/
$gdW = array((isset($posttl[$row[1]]['GD'])) ? $posttl[$row[1]]['GD'] : 0);
if (empty($gdW[0]))
  $gdW[0] = 0;
$perCashierTotal += $gdW[0];
      	
/*
$efQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'EF'";
$efR = $sql->query($efQ);
$efW = $sql->fetch_array($efR);
*/
$efW = array((isset($posttl[$row[1]]['EF'])) ? $posttl[$row[1]]['EF'] : 0);
if (empty($efW[0]))
  $efW[0] = 0;
$perCashierTotal += $efW[0];
      	
/*
$ecQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'EC'";
$ecR = $sql->query($ecQ);
$ecW = $sql->fetch_array($ecR);
*/
$ecW = array((isset($posttl[$row[1]]['EC'])) ? $posttl[$row[1]]['EC'] : 0);
if (empty($ecW[0]))
  $ecW[0] = 0;
$perCashierTotal += $ecW[0];
      	
/*
$cpQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'CP'";
$cpR = $sql->query($cpQ);
$cpW = $sql->fetch_array($cpR);
*/
$cpW = array((isset($posttl[$row[1]]['CP'])) ? $posttl[$row[1]]['CP'] : 0);
if (empty($cpW[0]))
  $cpW[0] = 0;
$perCashierTotal += $cpW[0];
      	
      
/*
$icQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
	 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'IC'";
$icR = $sql->query($icQ);
$icW = $sql->fetch_array($icR);
*/
$icW = array((isset($posttl[$row[1]]['IC'])) ? $posttl[$row[1]]['IC'] : 0);
$icTotal = $icW[0];
if (empty($icW[0]))
$icW[0] = 0;
$perCashierTotal += $icW[0];

$noteQ = "select note from dailyNotes where emp_no=$row[1] and date='$date'";
$noteR = $sql->query($noteQ);
$noteW = $sql->fetch_array($noteR);
$note = $noteW[0];

$output .= "<input type=hidden name=cashier value=\"$row[1]\" />";
      
$output .= "<tr><td>$row[0]</td><td>Starting cash</td><td>n/a</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='SCA'";
$fetchR = $sql->query($fetchQ);
$startcash = 0;
if ($sql->num_rows($fetchR) == 0)
      	$output .= "<td><input type=text id=startingCash$row[1] onchange=\"calcOS('Cash',$row[1]);\" /></td><td>n/a</td></tr>";
else {
      	$startcash = array_pop($sql->fetch_array($fetchR));
      	$output .= "<td><input type=text id=startingCash$row[1] value=\"";
      	$output .= $startcash;
      	$output .= "\" onchange=\"calcOS('Cash',$row[1]);\" /></td><td>n/a</td></tr>";
	$perCashierCountTotal -= $startcash;
}
      
$output .= "<tr><td>&nbsp;</td><td>Cash</td><td id=dlogCash$row[1]>$caW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='CA'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
	$output .= "<td><input type=text id=countCash$row[1] name=countCash onchange=\"calcOS('Cash',$row[1]);\" /></td>";
	$output .= "<td id=osCash$row[1]>&nbsp;</td></tr>";
	$output .= "<input type=hidden name=osCashHidden id=osCash$row[1]Hidden />";
}
else {
	$cash = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countCash$row[1] name=countCash onchange=\"calcOS('Cash',$row[1]);\" value=\"$cash\"/></td>";
	$os = round($cash - $caW[0] - $startcash,2);
	$output .= "<td id=osCash$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osCashHidden id=osCash$row[1]Hidden value=\"$os\" />";
		
	$perCashierCountTotal += $cash;
	$perCashierOSTotal += $os;
}
      
$output .= "<tr><td>&nbsp;</td><td>Check</td><td id=dlogCheck$row[1]>$ckW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='CK'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countCheck$row[1] name=countCheck onchange=\"calcOS('Check',$row[1]);\" /></td>";
      $output .= "<td id=osCheck$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osCheckHidden id=osCheck$row[1]Hidden />";
}
else {
	$check = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countCheck$row[1] name=countCheck onchange=\"calcOS('Check',$row[1]);\" value=\"$check\" /></td>";
	$os = round($check - $ckW[0],2);
	$output .= "<td id=osCheck$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osCheckHidden id=osCheck$row[1]Hidden value=\"$os\" />";
		
	$perCashierCountTotal += $check;
	$perCashierOSTotal += $os;
}
      
$output .= "<tr><td>&nbsp;</td><td>Credit</td><td id=dlogCredit$row[1]>$ccW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='CC'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countCredit$row[1] name=countCredit onchange=\"calcOS('Credit',$row[1]);\" /></td>";
      $output .= "<td id=osCredit$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osCreditHidden id=osCredit$row[1]Hidden />";
}
else {
	$credit = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countCredit$row[1] name=countCredit onchange=\"calcOS('Credit',$row[1]);\" value=\"$credit\" /></td>";
	$os = round($credit - $ccW[0],2);
	$output .= "<td id=osCredit$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osCreditHidden id=osCredit$row[1]Hidden value=\"$os\" />";
	    	
	$perCashierCountTotal += $credit;
	$perCashierOSTotal += $os;
}
      
$output .= "<tr><td>&nbsp;</td><td>Store Charge</td><td id=dlogMI$row[1]>$miW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='MI'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countMI$row[1] name=countMI onchange=\"calcOS('MI',$row[1]);\" /></td>";
      $output .= "<td id=osMI$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osMIHidden id=osMI$row[1]Hidden />";
}
else {
	$mi = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countMI$row[1] name=countMI onchange=\"calcOS('MI',$row[1]);\" value=\"$mi\" /></td>";
	$os = round($mi - $miW[0],2);
	$output .= "<td id=osMI$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osMIHidden id=osMI$row[1]Hidden value=\"$os\" />";
	
	$perCashierCountTotal += $mi;
	$perCashierOSTotal += $os;
}
      
$output .= "<tr><td>&nbsp;</td><td>EBT Food</td><td id=dlogEF$row[1]>$efW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='EF'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countEF$row[1] name=countEF onchange=\"calcOS('EF',$row[1]);\" /></td>";
      $output .= "<td id=osEF$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osEFHidden id=osEF$row[1]Hidden />";
}
else {
	$ef = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countEF$row[1] name=countEF onchange=\"calcOS('EF',$row[1]);\" value=\"$ef\" /></td>";
	$os = round($ef - $efW[0],2);
	$output .= "<td id=osEF$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osEFHidden id=osEF$row[1]Hidden value=\"$os\" />";
	    	
	$perCashierCountTotal += $ef;
	$perCashierOSTotal += $os;
}

$output .= "<tr><td>&nbsp;</td><td>EBT Cash</td><td id=dlogEC$row[1]>$ecW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='EC'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countEC$row[1] name=countEC onchange=\"calcOS('EC',$row[1]);\" /></td>";
      $output .= "<td id=osEC$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osECHidden id=osEC$row[1]Hidden />";
}
else {
	$ec = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countEC$row[1] name=countEC onchange=\"calcOS('EC',$row[1]);\" value=\"$ec\" /></td>";
	$os = round($ec - $ecW[0],2);
	$output .= "<td id=osEC$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osECHidden id=osEC$row[1]Hidden value=\"$os\" />";
	    	
	$perCashierCountTotal += $ec;
	$perCashierOSTotal += $os;
}

$output .= "<tr><td>&nbsp;</td><td>Gift Card</td><td id=dlogGD$row[1]>$gdW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='GD'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countGD$row[1] name=countGD onchange=\"calcOS('GD',$row[1]);\" /></td>";
      $output .= "<td id=osGD$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osGDHidden id=osGD$row[1]Hidden />";
}
else {
	$gd = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countGD$row[1] name=countGD onchange=\"calcOS('GD',$row[1]);\" value=\"$gd\" /></td>";
	$os = round($gd - $gdW[0],2);
	$output .= "<td id=osGD$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osGDHidden id=osGD$row[1]Hidden value=\"$os\" />";
	    	
	$perCashierCountTotal += $gd;
	$perCashierOSTotal += $os;
}
	  
$output .= "<tr><td>&nbsp;</td><td>Gift Certificate</td><td id=dlogTC$row[1]>$tcW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='TC'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countTC$row[1] name=countTC onchange=\"calcOS('TC',$row[1]);\" /></td>";
      $output .= "<td id=osTC$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osTCHidden id=osTC$row[1]Hidden />";
}
else {
	$tc = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countTC$row[1] name=countTC onchange=\"calcOS('TC',$row[1]);\" value=\"$tc\" /></td>";
	$os = round($tc - $tcW[0],2);
	$output .= "<td id=osTC$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osTCHidden id=osTC$row[1]Hidden value=\"$os\" />";
	    	
	$perCashierCountTotal += $tc;
	$perCashierOSTotal += $os;
}
	  
$output .= "<tr><td>&nbsp;</td><td>Coupons</td><td id=dlogCP$row[1]>$cpW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='CP'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
      $output .= "<td><input type=text id=countCP$row[1] name=countCP onchange=\"calcOS('CP',$row[1]);\" /></td>";
      $output .= "<td id=osCP$row[1]>&nbsp;</td></tr>";
      $output .= "<input type=hidden name=osCPHidden id=osCP$row[1]Hidden />";
}
else {
	$cp = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countCP$row[1] name=countCP onchange=\"calcOS('CP',$row[1]);\" value=\"$cp\" /></td>";
	$os = round($cp - $cpW[0],2);
	$output .= "<td id=osCP$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osCPHidden id=osCP$row[1]Hidden value=\"$os\" />";
	    	
	$perCashierCountTotal += $cp;
	$perCashierOSTotal += $os;
}
	  
$output .= "<tr><td>&nbsp;</td><td>InStore Coupons</td><td id=dlogIC$row[1]>$icW[0]</td>";
$fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='IC'";
$fetchR = $sql->query($fetchQ);
if ($sql->num_rows($fetchR) == 0){
	$output .= "<td><input type=text id=countIC$row[1] name=countIC onchange=\"calcOS('IC',$row[1]);\" /></td>";
	$output .= "<td id=osIC$row[1]>&nbsp;</td></tr>";
	$output .= "<input type=hidden name=osICHidden id=osIC$row[1]Hidden />";
}
else {
	$ic = array_pop($sql->fetch_array($fetchR));
	$output .= "<td><input type=text id=countIC$row[1] name=countIC onchange=\"calcOS('IC',$row[1]);\" value=\"$ic\" /></td>";
	$os = $ic - $icW[0];
	$output .= "<td id=osIC$row[1]>$os</td></tr>";
	$output .= "<input type=hidden name=osICHidden id=osIC$row[1]Hidden value=\"$os\" />";

	$countICTotal = $ic;
	$osICTotal = $os;

	$perCashierCountTotal += $ic;
	$perCashierOSTotal += $os;
}


$perCashierTotal = round($perCashierTotal,2);
$perCashierCountTotal = round($perCashierCountTotal,2);
$perCashierOSTotal = round($perCashierOSTotal,2);

$output .= "<tr><td>&nbsp;</td><td>Cashier totals</td>";
$output .= "<td>$perCashierTotal</td>";
$output .= "<td id=countTotal$row[1]>$perCashierCountTotal</td>";
$output .= "<td id=osTotal$row[1]>$perCashierOSTotal</td>";
$output .= "<tr><td>&nbsp;</td><td>Notes</td><td colspan=3</td>";
$output .= "<textarea rows=5 cols=35 id=note$row[1]>$note</textarea></td></tr>";

?>
<html>
<head><title>Overshorts</title>
<style>
#forms {

}

</style>
</head>

<body>
<div id="forms">
<?php echo $output ?>
</div>
</body>
</html>
