<?php
include('../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/OverShortTools/OverShortDayPage.php');
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
$sql->query("USE is4c_trans");

/* actions via POST are AJAX requests */
if (isset($_POST['action'])){
  switch($_POST['action']){
  case 'save':
	$date = $_POST['curDate'];
	$data = $_POST['data'];
	$user = $_POST['user'];
	$resolved = $_POST['resolved'];
	$notes = $_POST['notes'];
	
	$checkQ = "select username from overshortsLog where date='$date'";
	$checkR = $sql->query($checkQ);
	if ($sql->num_rows($checkR) == 0){
		$insQ = "insert into overshortsLog values ('$date','$user',$resolved)";
		$insR = $sql->query_all($insQ);
	}
	else {
		$upQ = "update overshortsLog set username='$user',resolved=$resolved where date='$date'";
		$upR = $sql->query_all($upQ);
	}
	
	save($date,$data);
	saveNotes($date,$notes);
	echo "saved";
	break;
  case 'date':
    $date = $_POST['arg'];
    $dlog = DTransactionsModel::selectDlog($date);
    if ($dlog != "is4c_trans.dlog")
	    $dlog = "trans_archive.dlogBig";
    /* determine who worked that day (and their first names) */
    $empsQ = "select e.firstname,d.emp_no from $dlog as d,is4c_op.employees as e where
              ".$sql->date_equals('d.tdate',$date)." and trans_type='T' and d.emp_no = e.emp_no
              group by d.emp_no,e.firstname order by e.firstname";
    $empsR=$sql->query($empsQ);
    $output = "<h3 id=currentdate>$date</h3>";
	//$output .= "<form onsubmit=\"excel(); return false;\" >";
	$output .= "<form onsubmit=\"save(); return false;\">";
    $output .= "<table border=1 cellspacing=2 cellpadding=2><tr>";
    $output .= "<th>Name</th><th>&nbsp;</th><th>Total</th><th>Counted Amt</th><th>Over/Short</th></tr>";
    
    /* global totals */
    $caTotal = 0;
    $ckTotal = 0;
    $ccTotal = 0;
    $miTotal = 0;
    $tcTotal = 0;
    $gdTotal = 0;
    $efTotal = 0;
    $ecTotal = 0;
    $cpTotal = 0;
    $icTotal = 0;
    $scTotal = 0;
    
    $countCATotal = 0;
    $countCKTotal = 0;
    $countCCTotal = 0;
    $countMITotal = 0;
    $countTCTotal = 0;
    $countGDTotal = 0;
    $countEFTotal = 0;
    $countECTotal = 0;
    $countCPTotal = 0;
    $countICTotal = 0;
    $countSCTotal = 0;
    
    $osCATotal = 0;
    $osCKTotal = 0;
    $osCCTotal = 0;
    $osMITotal = 0;
    $osTCTotal = 0;
    $osGDTotal = 0;
    $osEFTotal = 0;
    $osECTotal = 0;
    $osCPTotal = 0;
    $osICTotal = 0;
    $osSCTotal = 0;

    $overallTotal = 0;
    $overallCountTotal = 0;
    $overallOSTotal = 0;    

    /* get cash, check, and credit totals for each employee
       print them in a table along with input boxes for over/short */
    $q = "SELECT -1*sum(total) AS total,emp_no,
	CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
	AS trans_subtype
	FROM $dlog
	WHERE ".$sql->date_equals('tdate',$date)." 
	GROUP BY emp_no,
	CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END";
    $r = $sql->query($q);
    $posttl = array();
    while($w = $sql->fetch_row($r)){
	if (!isset($posttl[$w['emp_no']])) $posttl[$w['emp_no']] = array();
	$posttl[$w['emp_no']][$w['trans_subtype']] = $w['total'];
    }
    while ($row = $sql->fetch_array($empsR)){
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
      $caTotal += $caW[0];
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
      $ckTotal += $ckW[0];
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
      $ccTotal += $ccW[0];
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
      $miTotal += $miW[0];
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
      $tcTotal += $tcW[0];
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
      $gdTotal += $gdW[0];
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
      $efTotal += $efW[0];
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
      $ecTotal += $ecW[0];
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
      $cpTotal += $cpW[0];
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
      $icTotal += $icW[0];
      if (empty($icW[0]))
      	$icW[0] = 0;
      $perCashierTotal += $icW[0];

	/*
      $scQ = "select -1*sum(total) from $dlog where emp_no = $row[1]
      		 and datediff(dd,tdate,'$date') = 0 and trans_subtype = 'SC'";
      $scR = $sql->query($scQ);
      $scW = $sql->fetch_array($scR);
	*/
      $scW = array((isset($posttl[$row[1]]['SC'])) ? $posttl[$row[1]]['SC'] : 0);
      $scTotal += $scW[0];
      if (empty($scW[0]))
      	$scW[0] = 0;
      $perCashierTotal += $scW[0];

      $noteQ = "select note from dailyNotes where emp_no=$row[1] and date='$date'";
      $noteR = $sql->query($noteQ);
      $noteW = $sql->fetch_array($noteR);
      $note = stripslashes($noteW[0]);

      $output .= "<input type=hidden name=cashier value=\"$row[1]\" />";
      
      $output .= "<tr><td><a href=overshortSingleEmp.php?date=$date&emp_no=$row[1] target={$date}_{$row[1]}>$row[0]</a></td>";
      $output .= "<td>Starting cash</td><td>n/a</td>";
      $fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='SCA'";
      $fetchR = $sql->query($fetchQ);
      $startcash = 0;
      if ($sql->num_rows($fetchR) == 0)
      	$output .= "<td><input type=text id=startingCash$row[1] name=startingCash onchange=\"calcOS('Cash',$row[1]);\" /></td><td>n/a</td></tr>";
      else {
      	$startcash = array_pop($sql->fetch_array($fetchR));
      	$output .= "<td><input type=text id=startingCash$row[1] name=startingCash value=\"";
      	$output .= $startcash;
      	$output .= "\" onchange=\"calcOS('Cash',$row[1]);\" /></td><td>n/a</td></tr>";
	$perCashierCountTotal -= $startcash;
	$countCATotal -= $startcash;
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
		
		$countCATotal += $cash;
		$osCATotal += $os;
		
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
		
		$countCKTotal += $check;
		$osCKTotal += $os;

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
	    	
	    	$countCCTotal += $credit;
	    	$osCCTotal += $os;

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
	    	
	    	$countMITotal += $mi;
	    	$osMITotal += $os;

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
	    	
	    	$countEFTotal += $ef;
	    	$osEFTotal += $os;

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
	    	
	    	$countECTotal += $ec;
	    	$osECTotal += $os;

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
	    	
	    	$countGDTotal += $gd;
	    	$osGDTotal += $os;

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
	    	
	    	$countTCTotal += $tc;
	    	$osTCTotal += $os;

		$perCashierCountTotal += $tc;
		$perCashierOSTotal += $os;
	  }
	  
	  $output .= "<tr><td>&nbsp;</td><td>Coupons</td><td id=dlogCP$row[1]>".sprintF("%.2f",$cpW[0])."</td>";
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
	    	
	    	$countCPTotal += $cp;
	    	$osCPTotal += $os;

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
	    	
	    	$countICTotal += $ic;
	    	$osICTotal += $os;

		$perCashierCountTotal += $ic;
		$perCashierOSTotal += $os;
	  }

	  $output .= "<tr><td>&nbsp;</td><td>Store Credit</td><td id=dlogSC$row[1]>$scW[0]</td>";
	  $fetchQ = "select amt from dailyCounts where date='$date' and emp_no=$row[1] and tender_type='SC'";
          $fetchR = $sql->query($fetchQ);
	  if ($sql->num_rows($fetchR) == 0){
	      $output .= "<td><input type=text id=countSC$row[1] name=countSC onchange=\"calcOS('SC',$row[1]);\" /></td>";
	      $output .= "<td id=osSC$row[1]>&nbsp;</td></tr>";
	      $output .= "<input type=hidden name=osSCHidden id=osSC$row[1]Hidden />";
	  }
	  else {
	  	$sc = array_pop($sql->fetch_array($fetchR));
	  	$output .= "<td><input type=text id=countSC$row[1] name=countSC onchange=\"calcOS('SC',$row[1]);\" value=\"$sc\" /></td>";
	  	$os = $sc - $scW[0];
	    	$output .= "<td id=osSC$row[1]>$os</td></tr>";
	    	$output .= "<input type=hidden name=osSCHidden id=osSC$row[1]Hidden value=\"$os\" />";
	    	
	    	$countSCTotal += $sc;
	    	$osSCTotal += $os;

		$perCashierCountTotal += $sc;
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
	  
	  $output .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
      
    }
    /* add overall totals */
    $output .= "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
    
	$caTotal = round($caTotal,2);
	$countCATotal = round($countCATotal,2);
	$osCATotal = round($osCATotal,2);
    $output .= "<tr><td><b>Totals</b></td><td>Cash</td><td id=catotal>$caTotal</td>";
	$output .= "<td id=countCashTotal>$countCATotal</td>";
	$output .= "<td id=osCashTotal>$osCATotal</td></tr>";
	$overallTotal += $caTotal;
	$overallCountTotal += $countCATotal;
	$overallOSTotal += $osCATotal;
	
	$ckTotal = round($ckTotal,2);
	$countCKTotal = round($countCKTotal,2);
	$osCKTotal = round($osCKTotal,2);
    $output .= "<tr><td>&nbsp;</td><td>Checks</td><td id=cktotal>$ckTotal</td>";
	$output .= "<td id=countCheckTotal>$countCKTotal</td>";
	$output .= "<td id=osCheckTotal>$osCKTotal</td></tr>";
	$overallTotal += $ckTotal;
	$overallCountTotal += $countCKTotal;
	$overallOSTotal += $osCKTotal;
    
	$ccTotal = round($ccTotal,2);
	$countCCTotal = round($countCCTotal,2);
	$osCCTotal = round($osCCTotal,2);
    $output .= "<tr><td>&nbsp;</td><td>Credit</td><td id=cctotal>$ccTotal</td>";
	$output .= "<td id=countCreditTotal>$countCCTotal</td>";
	$output .= "<td id=osCreditTotal>$osCCTotal</td></tr>";
	$overallTotal += $ccTotal;
	$overallCountTotal += $countCCTotal;
	$overallOSTotal += $osCCTotal;
	
	$miTotal = round($miTotal,2);
	$countMITotal = round($countMITotal,2);
	$osMITotal = round($osMITotal,2);
    $output .= "<tr><td>&nbsp;</td><td>Store Charge</td><td id=mitotal>$miTotal</td>";
	$output .= "<td id=countMITotal>$countMITotal</td>";
	$output .= "<td id=osMITotal>$osMITotal</td></tr>";
	$overallTotal += $miTotal;
	$overallCountTotal += $countMITotal;
	$overallOSTotal += $osMITotal;
	
	$tcTotal = round($tcTotal,2);
	$countTCTotal = round($countTCTotal,2);
	$osTCTotal = round($osTCTotal,2);
    $output .= "<tr><td>&nbsp;</td><td>Gift Certificate</td><td id=tctotal>$tcTotal</td>";
	$output .= "<td id=countTCTotal>$countTCTotal</td>";
	$output .= "<td id=osTCTotal>$osTCTotal</td></tr>";
	$overallTotal += $tcTotal;
	$overallCountTotal += $countTCTotal;
	$overallOSTotal += $osTCTotal;
	
	$gdTotal = round($gdTotal,2);
	$countGDTotal = round($countGDTotal,2);
	$osGDTotal = round($osGDTotal,2);
    $output .= "<tr><td>&nbsp;</td><td>Gift Card</td><td id=gdtotal>$gdTotal</td>";
	$output .= "<td id=countGDTotal>$countGDTotal</td>";
	$output .= "<td id=osGDTotal>$osGDTotal</td></tr>";
	$overallTotal += $gdTotal;
	$overallCountTotal += $countGDTotal;
	$overallOSTotal += $osGDTotal;
	
	$efTotal = round($efTotal,2);
	$countEFTotal = round($countEFTotal,2);
	$osEFTotal = round($osEFTotal,2);
	$output .= "<tr><td>&nbsp;</td><td>EBT Food</td><td id=eftotal>$efTotal</td>";
	$output .= "<td id=countEFTotal>$countEFTotal</td>";
	$output .= "<td id=osEFTotal>$osEFTotal</td></tr>";
	$overallTotal += $efTotal;
	$overallCountTotal += $countEFTotal;
	$overallOSTotal += $osEFTotal;
	
	$ecTotal = round($ecTotal,2);
	$countECTotal = round($countECTotal,2);
	$osECTotal = round($osECTotal,2);
	$output .= "<tr><td>&nbsp;</td><td>EBT Cash</td><td id=ectotal>$ecTotal</td>";
	$output .= "<td id=countECTotal>$countECTotal</td>";
	$output .= "<td id=osECTotal>$osECTotal</td></tr>";
	$overallTotal += $ecTotal;
	$overallCountTotal += $countECTotal;
	$overallOSTotal += $osECTotal;
	
	$cpTotal = round($cpTotal,2);
	$countCPTotal = round($countCPTotal,2);
	$osCPTotal = round($osCPTotal,2);
	$output .= "<tr><td>&nbsp;</td><td>Coupons</td><td id=cptotal>$cpTotal</td>";
	$output .= "<td id=countCPTotal>$countCPTotal</td>";
	$output .= "<td id=osCPTotal>$osCPTotal</td></tr>";
	$overallTotal += $cpTotal;
	$overallCountTotal += $countCPTotal;
	$overallOSTotal += $osCPTotal;
	
	$icTotal = round($icTotal,2);
	$countICTotal = round($countICTotal,2);
	$osICTotal = round($osICTotal,2);
	$output .= "<tr><td>&nbsp;</td><td>InStore Coupons</td><td id=ictotal>$icTotal</td>";
	$output .= "<td id=countICTotal>$countICTotal</td>";
	$output .= "<td id=osICTotal>$osICTotal</td></tr>";
	$overallTotal += $icTotal;
	$overallCountTotal += $countICTotal;
	$overallOSTotal += $osICTotal;

	$scTotal = round($scTotal,2);
	$countSCTotal = round($countSCTotal,2);
	$osSCTotal = round($osSCTotal,2);
	$output .= "<tr><td>&nbsp;</td><td>Store Credit</td><td id=sctotal>$scTotal</td>";
	$output .= "<td id=countSCTotal>$countSCTotal</td>";
	$output .= "<td id=osSCTotal>$osSCTotal</td></tr>";
	$overallTotal += $scTotal;
	$overallCountTotal += $countSCTotal;
	$overallOSTotal += $osSCTotal;

	$overallTotal = round($overallTotal,2);
	$overallCountTotal = round($overallCountTotal,2);
	$overallOSTotal = round($overallOSTotal,2);
	$output .= "<tr><td><b>Grand totals</td><td>&nbsp;</td>";
	$output .= "<td id=overallTotal>$overallTotal</td>";
	$output .= "<td id=overallCountTotal>$overallCountTotal</td>";
	$output .= "<td id=overallOSTotal>$overallOSTotal</td></tr>";

	$noteQ = "select note from dailyNotes where date='$date' and emp_no = -1";
	$noteR = $sql->query($noteQ);
      	$noteW = $sql->fetch_array($noteR);
      	$note = $noteW[0];
	$output .= "<tr><td>&nbsp;</td><td>Notes</td><td colspan=3</td>";
	$output .= "<textarea rows=5 cols=35 id=totalsnote>$note</textarea></td></tr>";

    $output .= "</table>";
    
    $extraQ = "select username, resolved from overshortsLog where date='$date'";
    $extraR = $sql->query($extraQ);
    $extraW = $sql->fetch_array($extraR);
    $output .= "This date last edited by: <span id=lastEditedBy><b>$extraW[0]</b></span><br />";
    $output .= "<input type=submit value=Save />";
    $output .= "<input type=checkbox id=resolved ";
    if ($extraW[1] == 1)
    		$output .= "checked";
    	$output .= " /> Resolved";
	$output .= "</form>";

    /* "send" output back */
    echo $output;
    break;
  }
  
  return;
}

function save($date,$data){
	global $sql;
	$bycashier = explode(',',$data);

	foreach ($bycashier as $c){
		$temp = explode(':',$c);
		if (count($temp) != 2) continue;
		$cashier = $temp[0];
		$tenders = explode(';',$temp[1]);
		foreach($tenders as $t){
			$temp = explode('|',$t);
			$tender_type = $temp[0];
			$amt = rtrim($temp[1]);
			if ($amt != ''){
				$checkQ = "select emp_no from dailyCounts
						  where date='$date' and emp_no=$cashier and tender_type='$tender_type'";
				$checkR = $sql->query($checkQ);
				if ($sql->num_rows($checkR) == 0){
					$insQ = "insert into dailyCounts values ('$date',$cashier,'$tender_type',$amt)";
					$insR = $sql->query_all($insQ);
				}
				else {
					$upQ = "update dailyCounts set amt=$amt where date='$date' and emp_no=$cashier and tender_type='$tender_type'";
					$upR = $sql->query_all($upQ);
				}
			}
		}
	}	
}

function saveNotes($date,$notes){
	global $sql;
	$noteIDs = explode('`',$notes);
	foreach ($noteIDs as $n){
		$temp = explode('|',$n);
		$emp = $temp[0];
		$note = str_replace("'","''",urldecode($temp[1]));
		
		$checkQ = "select emp_no from dailyNotes where date='$date' and emp_no=$emp";
		$checkR = $sql->query($checkQ);
		if ($sql->num_rows($checkR) == 0){
			$insQ = "insert into dailyNotes values ('$date',$emp,'$note')";
			$insR = $sql->query_all($insQ);
		}
		else {
			$upQ = "update dailyNotes set note='$note' where date='$date' and emp_no=$emp";
			$upR = $sql->query_all($upQ);
		}
	}
}

?>
<html>
<head><title>Overshorts</title>
<script>

/* scripting probably needs a bit of commenting */

/* create XML request object (i.e., AJAX-ify) */
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

/* global variables */
var http = createRequestObject();   // the AJAX request object
var loading = 0;                    // signal that loading should be shown
var lock = 0;                       // lock (for synchronization)
var formstext = "";                 // reponse text stored globally
                                    // makes pseudo-threading easier
var lastaction;			    // the last action send

/* sends a request for the given action
   designed to call this page with arguments as HTTP GETs */
function sndReq(action) {
    var actions = action.split('&'); 
    lastaction = actions[0];
    
    http.open('POST', 'overshort.php', true);
    	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.setRequestHeader("Content-length", action.length+7);
	http.setRequestHeader("Connection", "close");
	http.onreadystatechange = handleResponse;
    http.send('action='+action);
}

/* handler function to catch AJAX responses
   turns off loading and store the reponse text globally
   so that the setFormsText function can set the response
   text as soon as the loading animation stops */
function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
        switch(lastaction){
        case 'date':
          loading = 0;
          formstext = response;
          setFormsText();
          break;
        case 'save':
          if (response == "saved")
          	alert('Data saved successfully');
          else
          	alert(response);
          break;
        }
    }
}

/* waits for the loading function to release the lock,
   then sets the reponse text in place */
function setFormsText(){
  if (!lock) 
    document.getElementById("forms").innerHTML = formstext;
  else
    setTimeout('setFormsText()',50)
}

/* the 'main' function, essentially
   this is called when a date is submitted
   the datefield is cleared (so the calendar script will work again correctly)
   the Loading display is initialized, loading flag set, and lock taken
   the global response text is also cleared
   both the loading animation and request are started */
function setdate(){
  var datefield = document.getElementById("date");
  var date = datefield.value;
  datefield.value="";
  document.getElementById("forms").innerHTML = "<span id=\"loading\">Loading</span>";
  loading = 1;
  lock = 1;
  formstext = "";
  sndReq('date&arg='+date); // additonal args added HTTP GET style
  loadingBar();
}

/* the loading animation
   appends periods to the Loading display
   releases the lock when loading stops */
function loadingBar(){
  if (loading){
    var text = document.getElementById("loading").innerHTML;
    if (text == "Loading.......")
      text = "Loading";
    else
      text = text+".";
    document.getElementById("loading").innerHTML = text;
    setTimeout('loadingBar()',100);
  }
  else {
    lock = 0;
  }
}

function calcOS(type,empID){
	var dlogAmt = document.getElementById('dlog'+type+empID).innerHTML;
	var countAmt = document.getElementById('count'+type+empID).value;
	
	if (countAmt.indexOf('+') != -1){
		var temp = countAmt.split('+');
		var countAmt = 0;
		for (var i = 0; i < temp.length; i++){
			countAmt += Number(temp[i]);
		}
		document.getElementById('count'+type+empID).value = Math.round(countAmt*100)/100;
	}
	
	var extraAmt = 0;
	if (type == 'Cash'){
		extraAmt = document.getElementById('startingCash'+empID).value;

		if (extraAmt.indexOf('+') != -1){
			var temp = extraAmt.split('+');
			var extraAmt = 0;
			for (var i = 0; i < temp.length; i++){
				extraAmt += Number(temp[i]);
			}
			document.getElementById('startingCash'+empID).value = Math.round(extraAmt*100)/100;
		}
	}
	
	var diff = Math.round((countAmt - dlogAmt - extraAmt)*100)/100;
	
	document.getElementById('os'+type+empID).innerHTML = diff;
	document.getElementById('os'+type+empID+'Hidden').value = diff;
	
	resum(type);
	cashierResum(empID);
}

function resum(type){
	var counts = document.getElementsByName('count'+type);
	var countSum = 0;
	for (var i = 0; i < counts.length; i++)
		countSum += Number(counts.item(i).value);

	if (type == 'Cash'){
		var startAmts = document.getElementsByName('startingCash');
		for (var i = 0; i < startAmts.length; i++)
			countSum -= Number(startAmts.item(i).value);
	}
	
	var osSum = 0;
	var oses = document.getElementsByName('os'+type+'Hidden');
	for (var i = 0; i < oses.length; i++)
		osSum += Number(oses.item(i).value);
		
	var oldcount = Number(document.getElementById('count'+type+'Total').innerHTML);
	var oldOS = Number(document.getElementById('os'+type+'Total').innerHTML);
	var newcount = Math.round(countSum*100)/100;
	var newOS = Math.round(osSum*100)/100;

	document.getElementById('count'+type+'Total').innerHTML = newcount;
	document.getElementById('os'+type+'Total').innerHTML = newOS;

	var overallCount = Number(document.getElementById('overallCountTotal').innerHTML);
	var overallOS = Number(document.getElementById('overallOSTotal').innerHTML);

	var newOverallCount = overallCount + (newcount - oldcount);
	var newOverallOS = overallOS + (newOS - oldOS);

	document.getElementById('overallCountTotal').innerHTML = Math.round(newOverallCount*100)/100;
	document.getElementById('overallOSTotal').innerHTML = Math.round(newOverallOS*100)/100;

}

function cashierResum(empID){
	var countSum = 0;
	countSum -= Number(document.getElementById('startingCash'+empID).value);
	var osSum = 0;
	var types = Array('Cash','Check','Credit','MI','TC','GD','EF','EC','CP','IC','SC'); 
	for (var i = 0; i < types.length; i++){
		//alert(types[i]+empID);
		countSum += Number(document.getElementById('count'+types[i]+empID).value);
		osSum += Number(document.getElementById('os'+types[i]+empID+'Hidden').value);
	}
	document.getElementById('countTotal'+empID).innerHTML = Math.round(countSum*100)/100;
	document.getElementById('osTotal'+empID).innerHTML = Math.round(osSum*100)/100;
}

function save(){
	var outstr = '';
	var notes = '';
	var emp_nos = document.getElementsByName('cashier');
	for (var i = 0; i < emp_nos.length; i++){
		var emp_no = emp_nos.item(i).value;
		outstr += emp_no+":";
		
		var startcash = document.getElementById('startingCash'+emp_no).value;
		outstr += "SCA|"+startcash+";";
		
		var cash = document.getElementById('countCash'+emp_no).value;
		outstr += "CA|"+cash+";";
		
		var check = document.getElementById('countCheck'+emp_no).value;
		outstr += "CK|"+check+";";
		
		var credit = document.getElementById('countCredit'+emp_no).value;
		outstr += "CC|"+credit+";";
		
		var mi = document.getElementById('countMI'+emp_no).value;
		outstr += "MI|"+mi+";";
		
		var tc = document.getElementById('countTC'+emp_no).value;
		outstr += "TC|"+tc+";";
		
		var gd = document.getElementById('countGD'+emp_no).value;
		outstr += "GD|"+gd+";";

		var ef = document.getElementById('countEF'+emp_no).value;
		outstr += "EF|"+ef+";";

		var ec = document.getElementById('countEC'+emp_no).value;
		outstr += "EC|"+ec+";";

		var cp = document.getElementById('countCP'+emp_no).value;
		outstr += "CP|"+cp+";";
		
		var ic = document.getElementById('countIC'+emp_no).value;
		outstr += "IC|"+ic+";";

		var sc = document.getElementById('countSC'+emp_no).value;
		outstr += "SC|"+sc;

		var note = document.getElementById('note'+emp_no).value;
		notes += emp_no + "|" + escape(note);
		outstr += ",";
		notes += "`";
	}
	var note = document.getElementById('totalsnote').value;
	notes += "-1|"+escape(note);
	
	var curDate = document.getElementById('currentdate').innerHTML;
	var user = document.getElementById('user').value;
	var resolved = 0;
	if (document.getElementById('resolved').checked)
		resolved = 1;

	document.getElementById('lastEditedBy').innerHTML="<b>"+user+"</b>";

	sndReq('save&curDate='+curDate+'&data='+outstr+'&user='+user+'&resolved='+resolved+'&notes='+notes);
}

</script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        type="text/javascript"></script>
<style>
#forms {

}

#loading {
  font-size: 125%;
  text-align: center;
}

a {
  color: blue;
}
</style>
</head>

<body>
<form onsubmit="setdate(); return false;" >
<b>Date</b>:<input type=text id=date onfocus="showCalendarControl(this);" />
<input type=submit value="Set" />
<input type=hidden id=user value="<?php echo $user ?>" />
</form>

<div id="forms">

</div>
</body>
</html>
