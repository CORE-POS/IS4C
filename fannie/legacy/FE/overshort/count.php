<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/OverShortTools/OverShortSafecountPage.php');
exit;

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');
$sql->query("use is4c_trans");
require($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET["action"])){
	$out = $_GET["action"]."`";

	switch($_GET["action"]){
	case 'loader':
		$d1 = $_GET['date1'];
		$d2 = $_GET['date2'];
		
		$dateStr = $d1." ".$d2;
		if ($d1 == $d2) $dateStr = $d1;

		$out .= displayUI($dateStr);
		break;
	case 'save':
		$d1 = $_GET['date1'];
		$d2 = $_GET['date2'];
		
		$dateStr = $d1." ".$d2;
		if ($d1 == $d2) $dateStr = $d1;
		
		$out .= save($dateStr,
			     $_GET['changeOrder'],
			     $_GET['openSafeCount'],
			     $_GET['closeSafeCount'],
			     $_GET['buyAmount'],
			     $_GET['dropAmount'],	
			     $_GET['depositAmount'],
			     $_GET['atmAmount']);
	
	}
	echo $out;
	return;
}

function save($dateStr,$changeOrder,$openSafeCount,$closeSafeCount,$buyAmount,$dropAmount,$depositAmount,$atmAmount){
	saveInputs($dateStr,'changeOrder',$changeOrder);
	saveInputs($dateStr,'openSafeCount',$openSafeCount);
	saveInputs($dateStr,'closeSafeCount',$closeSafeCount);
	saveInputs($dateStr,'buyAmount',$buyAmount);
	saveInputs($dateStr,'dropAmount',$dropAmount);
	saveInputs($dateStr,'depositAmount',$depositAmount);
	saveInputs($dateStr,'atm',$atmAmount);
	
	return 'Saved';
}

function saveInputs($dateStr,$row,$data){
	global $sql;

	$temp = explode('|',$data);
	foreach($temp as $t){
		$temp2 = explode(':',$t);
		if (count($temp2) < 2) continue;
		$denom = $temp2[0];
		$amt = $temp2[1];
		
		if ($amt == '') continue;

		$checkQ = "SELECT amt FROM dailyDeposit WHERE dateStr='$dateStr' AND rowName='$row' AND denomination='$denom'";
		if ($sql->num_rows($sql->query($checkQ)) == 0){
			$insQ = "INSERT INTO dailyDeposit VALUES ('$dateStr','$row','$denom',$amt)";
			$sql->query_all($insQ);
		}
		else {
			$upQ = "UPDATE dailyDeposit SET amt=$amt WHERE dateStr='$dateStr' AND rowName='$row' AND denomination='$denom'";
			$sql->query_all($upQ);
		}
	}
}

function displayUI($dateStr){
	global $sql;

	$startDate = $dateStr;
	$endDate = $dateStr;
	if (strstr($dateStr," ")){
		$temp = explode(" ",$dateStr);
		$startDate = $temp[0];
		$endDate = $temp[1];
	}

	$holding = array('changeOrder'=>array(),
			'openSafeCount'=>array(),
			'closeSafeCount'=>array(),
			'dropAmount'=>array(),
			'atm'=>array('fill'=>0,'reject'=>0)
			);

	$denoms = array('0.01','0.05','0.10','0.25','Junk','1.00','5.00','10.00','20.00','50.00','100.00','Checks');
	foreach($denoms as $d){
		foreach($holding as $k=>$v){
			$holding[$k]["$d"] = 0;
		}
	}

	$dataQ = "select rowName,denomination,amt from dailyDeposit where datestr='$dateStr' and rowName <> 'buyAmount'";
	$dataR = $sql->query($dataQ);
	while($dataW = $sql->fetch_row($dataR)){
		$holding[$dataW[0]][$dataW[1]] = $dataW[2];
	}

	$actualTotal = 0;
	$accountableTotal = 0;
	$buyAmountTotal = 0;
	
	$ret = "<h3>$dateStr</h3>";
	$ret .= "<table cellspacing=0 border=1 cellpadding=4><tr><td>&nbsp;</td>";
	foreach ($denoms as $d) $ret .= "<th>$d</th>";
	$ret .= "<th>Total</th></tr>";

	$ret .= "<tr class=color><th>Change Order</th>";
	$sum = 0;
	foreach($denoms as $d){ 
		if ($d == 'Checks' || $d == "100.00" || $d == "50.00" || $d == "20.00" || $d == "Junk") 
			$ret .= "<td>&nbsp;</td>";
		else{
			$ret .= "<td><input size=4 type=text id=changeOrder$d value=".$holding['changeOrder'][$d];
			$ret .= " onchange=\"updateChangeOrder('$d');\" /></td>";
			$sum += $holding['changeOrder'][$d];
		}
	}
	$ret .= "<td id=changeOrderTotal>$sum</td></tr>";

	$ret .= "<tr><th>Open Safe Count</th>";
	$sum = 0;
	foreach($denoms as $d){
		if ($d == 'Checks') 
			$ret .= "<td>&nbsp;</td>";
		else{
			$ret .= "<td><input size=4 type=text id=\"safeCount1$d\" value=".$holding['openSafeCount'][$d];
			$ret .= " onchange=\"updateOpenSafeCount('$d');\" /></td>";
			$sum += $holding['openSafeCount'][$d];
		}
	}
	$ret .= "<td id=safeCount1Total>$sum</td></tr>";

	$dateClause = " ".$sql->date_equals('date',$dateStr)." ";
	if (strstr($dateStr," ")){
		$dates = explode(" ",$dateStr);
		$dateClause = " date BETWEEN '$dates[0] 00:00:00' AND '$dates[1] 23:59:59' ";
	}
	$countQ = "SELECT tender_type,sum(amt) from dailyCounts where tender_type in ('CA','CK','SCA') and $dateClause GROUP BY tender_type";
	$countR = $sql->query($countQ);
	$osCounts = array('CA'=>0,'CK'=>0,'SCA'=>0);
	while($countW = $sql->fetch_row($countR))
		$osCounts[$countW[0]] = $countW[1];

	$bagQ = "SELECT sum(amt) FROM dailyCounts WHERE $dateClause AND tender_type='SCA'";
	$start_cash = array_pop($sql->fetch_row($sql->query($bagQ)));
	$bags = round($start_cash / 168.00);
	//$osCounts['CA'] -= 168*$bags;

	$ret .= "<tr class=color><th>Total change fund</th>";
	$sum = 0;
	foreach($denoms as $d){
		if ($d == "Checks"){
			$ret .= "<td>&nbsp;</td>";
		}
		else {
			$val = $holding['changeOrder'][$d] + $holding['openSafeCount'][$d];
			$ret .= "<td id=cashInTills$d>$val</td>";
			$sum += $val;
		}
	}
	$ret .= "<td id=cashInTillsTotal>$sum</td></tr>";
	$accountableTotal += $sum;

	$ret .= "<tr><th>Drop Amount</th>";
	foreach($denoms as $d){
		if ($d == "1.00"){
			$ret .= "<td id=dropAmount1.00>".$holding['dropAmount'][$d]."</td>";
		}
		else if ($d == "Checks"){
			$ret .= "<td id=dropAmountChecks>".$osCounts['CK']."</td>";
		}
		else {
			$ret .= "<td><input size=4 type=text id=dropAmount$d value=".$holding['dropAmount'][$d];
			$ret .= " onchange=\"updateDropAmount('$d');\" /></td>";
		}
	}
	$val = ($osCounts['CA'] + $osCounts['CK']);
	$ret .= "<td id=dropAmountTotal>".round($val,2)."</td></tr>";
	$buyAmountTotal -= $val;
	$accountableTotal += $val;

	$ret .= "<tr class=\"color\"><th>ATM</th>";
	$ret .= "<td colspan=\"7\">&nbsp;</td>";
	$ret .= "<td>Fill:</td>";
	$ret .= "<td><input size=4 type=text id=atmFill value=\"".$holding['atm']['fill']."\"
			onchange=\"updateAtmAmounts();\" /></td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td>Reject:</td>";
	$ret .= "<td><input size=4 type=text id=atmReject value=\"".$holding['atm']['reject']."\"
			onchange=\"updateAtmAmounts();\" /></td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "</tr>";

	$accountableTotal += ($holding['atm']['reject'] - $holding['atm']['fill']);
	
	$ret .= "<tr><th>Fill Amount</th>";
	$ret .= "<td id=fill0.01>".(1*$bags)."</td>";
	$ret .= "<td id=fill0.05>".(2*$bags)."</td>";
	$ret .= "<td id=fill0.10>".(5*$bags)."</td>";
	$ret .= "<td id=fill0.25>".(10*$bags)."</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=fill1.00>".(50*$bags)."</td>";
	$ret .= "<td id=fill5.00>".(50*$bags)."</td>";
	$ret .= "<td id=fill10.00>".(50*$bags)."</td>";
	$ret .= "<td colspan=4>&nbsp;</td>";
	$ret .= "<td id=fillTotal>".(168*$bags)."</td></tr>";

	$accountableTotal -= (168*$bags);

	$fills = array('0.01'=>1,'0.05'=>2,'0.10'=>5,'0.25'=>10,'1.00'=>50,'5.00'=>50,'10.00'=>50);
	$pars = array("0.01"=>40,"0.05"=>60,"0.10"=>200,"0.25"=>700,"1.00"=>1700,"5.00"=>500,"10.00"=>800);

	$ret .= "<tr class=\"color\"><th>Deposit Amount</th>";
	$sum = 0;
	$depositAmount = array();
	foreach($denoms as $d){
		if ($d == 'Checks'){
			$ret .= "<td id=depositAmount$d>".$osCounts['CK']."</td>";
			$sum += $osCounts['CK'];
			$depositAmount['Checks'] = $osCounts['CK'];
		}
		else if ($d == '100.00' || $d == '50.00' || $d == 'Junk'){
			$ret .= "<td id=depositAmount$d>".($holding['openSafeCount'][$d] + $holding['dropAmount'][$d])."</td>";
			$sum += ($holding['openSafeCount'][$d] + $holding['dropAmount'][$d]);
			$depositAmount[$d] = $holding['openSafeCount'][$d]+$holding['dropAmount'][$d];
		}
		else if ($d == '20.00'){
			$atmTtl = $holding['openSafeCount'][$d] + $holding['dropAmount'][$d] 
				- $holding['atm']['fill'] + $holding['atm']['reject'];
			$ret .= "<td id=depositAmount$d>".$atmTtl."</td>";
			$sum += $atmTtl;
			$depositAmount[$d] = $atmTtl;
		}
		else if ($d == '10.00'){
			$val = $holding['changeOrder'][$d] + $holding['openSafeCount'][$d] + $holding['dropAmount'][$d] - $pars['10.00'] - (50*$bags);
			$val = round($val,2);
			if ($val < 0) $val = 0;
			$ret .= "<td id=depositAmount$d>".$val."</td>";
			$sum += $val;
			$depositAmount[$d] = $val;
		}
		else if ($d == '5.00'){
			$val = $holding['changeOrder'][$d] + $holding['openSafeCount'][$d] + $holding['dropAmount'][$d] - $pars['5.00'] - (50*$bags);
			$val = round($val,2);
			if ($val < 0) $val = 0;
			$ret .= "<td id=depositAmount$d>".$val."</td>";
			$sum += $val;
			$depositAmount[$d] = $val;
		}
		else if ($d == '1.00'){
			$ret .= "<td id=depositAmount$d>0</td>";
			$val = round($val,2);
			$depositAmount[$d] = 0;
		}
		else if ($d == '0.25'){
			$val = $holding['dropAmount'][$d] - ( ((int)($holding['dropAmount'][$d]/10)) * 10 );
			$val = round($val,2);
			$ret .= "<td id=depositAmount$d>".$val."</td>";
			$sum += $val;
			$depositAmount[$d] = $val;
		}
		else if ($d == '0.10'){
			$val = $holding['dropAmount'][$d] - ( ((int)($holding['dropAmount'][$d]/5)) * 5 );
			$val = round($val,2);
			$ret .= "<td id=depositAmount$d>".$val."</td>";
			$sum += $val;
			$depositAmount[$d] = $val;
		}
		else if ($d == '0.05'){
			$val = $holding['dropAmount'][$d] - ( ((int)($holding['dropAmount'][$d]/2)) * 2 );
			$val = round($val,2);
			$ret .= "<td id=depositAmount$d>".$val."</td>";
			$sum += $val;
			$depositAmount[$d] = $val;
		}
		else if ($d == '0.01'){
			$val = $holding['dropAmount'][$d] - ( ((int)($holding['dropAmount'][$d]/0.50)) * 0.50 );
			$val = round($val,2);
			$ret .= "<td id=depositAmount$d>".$val."</td>";
			$sum += $val;
			$depositAmount[$d] = $val;
		}
	}
	$ret .= "<td id=depositAmountTotal>$sum</td></tr>";
	$buyAmountTotal += $sum;
	$accountableTotal -= $sum;
	
	$ret .= "<tr><th>Close Safe Count</th>";
	$sum = 0;
	foreach($denoms as $d){
		if ($d == 'Checks' || $d == "Junk") 
			$ret .= "<td>&nbsp;</td>";
		else{
			$ret .= "<td><input size=4 type=text id=safeCount2$d value=".$holding['closeSafeCount'][$d];
			$ret .= " onchange=\"updateCloseSafeCount('$d');\" /></td>";
			$sum += $holding['closeSafeCount'][$d];
		}
	}
	$ret .= "<td id=safeCount2Total>$sum</td></tr>";
	$actualTotal += $sum;

	$parTTL = 0; foreach($pars as $k=>$v) $parTTL += $v;
	$ret .= "<tr class=\"color\"><th>Par Amounts</th>";
	$ret .= "<td id=par0.01>".$pars['0.01']."</td>";
	$ret .= "<td id=par0.05>".$pars['0.05']."</td>";
	$ret .= "<td id=par0.10>".$pars['0.10']."</td>";
	$ret .= "<td id=par0.25>".$pars['0.25']."</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=par1.00>".$pars['1.00']."</td>";
	$ret .= "<td id=par5.00>".$pars['5.00']."</td>";
	$ret .= "<td id=par10.00>".$pars['10.00']."</td>";
	$ret .= "<td colspan=4>&nbsp;</td>";
	$ret .= sprintf("<td>%.2f</td></tr>",$parTTL);

	$buyAmounts = array("0.01"=>0,"0.05"=>0,"0.10"=>0,"0.25"=>0,"1.00"=>0,"5.00"=>0,"10.00"=>0);
	foreach ($buyAmounts as $k=>$v){
		$val = $pars[$k];
		$val -= $holding['changeOrder'][$k];
		$val -= $holding['openSafeCount'][$k];
		$val -= $holding['dropAmount'][$k];
		$val += $depositAmount[$k];
		$val += ($fills[$k]*$bags);
		if ($val < 0) $val = 0;
		$buyAmounts[$k] = $val;
	}
	$overage = 0;
	while($buyAmounts['1.00'] % 50 != 0){
		$buyAmounts['1.00'] -= 1;
		$overage += 1;
	}
	while($buyAmounts['5.00'] % 5 == 0 && $buyAmounts['5.00'] % 50 != 0){ 
		$buyAmounts['5.00'] -= 5;
		$overage += 5;
	}
	while($buyAmounts['10.00'] % 10 == 0 && $buyAmounts['10.00'] % 50 != 0){ 
		$buyAmounts['10.00'] -= 10;
		$overage += 10;
	}

	$overs = denom_overage($overage);
	$buyAmounts['0.25'] += $overs['0.25'];
	$buyAmounts['0.10'] += $overs['0.10'];
	$buyAmounts['0.05'] += $overs['0.05'];
	$buyAmounts['0.01'] += $overs['0.01'];

	$ret .= "<tr><th>Buy Amount</th>";
	foreach ($denoms as $d){
		if (isset($buyAmounts[$d]))
			$ret .= "<td id=buyAmount$d>".$buyAmounts[$d]."</td>";
		else
			$ret .= "<td>&nbsp;</td>";
	}
	$ret .= "<td id=buyAmountTotal>".array_sum($buyAmounts)."</td></tr>";

	$dlog = select_dlog($startDate,$endDate);
	$dlog = "trans_archive.dlogBig";
	$posTotalQ = "SELECT -1*sum(d.total) FROM $dlog as d WHERE ".str_replace(" date "," d.tdate ",$dateClause)." AND d.trans_subtype IN ('CA','CK')";
	$posTotal = array_pop($sql->fetch_row($sql->query($posTotalQ)));

	$ret .= "<tr class=\"color\"><th>Over/Shorts</th>";
	$ret .= "<td><i>Count total</i></td><td>".round(($osCounts['CA']+$osCounts['CK'] - $osCounts['SCA'] ),2)."</td>";
	$ret .= "<td><i>POS total</i></td><td>".$posTotal."</td>";
	$ret .= "<td><i>Variance</i></td><td>".round(($osCounts['CA']+$osCounts['CK']) - $osCounts['SCA'] -$posTotal,2)."</td>";
	$ret .= "<td><i>Actual</i></td><td id=actualTotal>$actualTotal</td>";
	$ret .= "<td><i>Accountable</i></td><td id=accountableTotal>".round($accountableTotal,2)."</td>";
	$ret .= "<td><i>Variance</i></td><td id=aaVariance>".round($actualTotal - $accountableTotal,2)."</td>";
	$ret .= "<td>&nbsp;</td></tr>";	


	$dailies = array();
	$countQ = "SELECT YEAR(date),MONTH(date),DAY(date),
			SUM(CASE WHEN tender_type IN ('CA','CK') THEN amt
				WHEN tender_type = 'SCA' THEN -amt
				ELSE 0 end) AS total
			FROM dailyCounts WHERE date BETWEEN
			'$startDate 00:00:00' AND '$endDate 23:59:59'
			GROUP BY YEAR(date),MONTH(date),DAY(date)";
	$posQ = "SELECT YEAR(tdate),MONTH(tdate),DAY(tdate),
			SUM(case when trans_subtype in ('CA','CK') then -total ELSE 0 END) as total
			FROM $dlog AS d WHERE tdate BETWEEN
			'$startDate 00:00:00' AND '$endDate 23:59:59'
			GROUP BY YEAR(tdate),MONTH(tdate),DAY(tdate)";
	$countR = $sql->query($countQ);
	while($row = $sql->fetch_row($countR)){
		$d = $row[0]."-".str_pad($row[1],2,'0',STR_PAD_LEFT)."-".str_pad($row[2],2,'0',STR_PAD_LEFT);
		if (!isset($dailies[$d])) $dailies[$d] = array(0,0);
		$dailies[$d][0] = $row[3];
	}
	$posR = $sql->query($posQ);
	while($row = $sql->fetch_row($posR)){
		$d = $row[0]."-".str_pad($row[1],2,'0',STR_PAD_LEFT)."-".str_pad($row[2],2,'0',STR_PAD_LEFT);
		if (!isset($dailies[$d])) $dailies[$d] = array(0,0);
		$dailies[$d][1] = $row[3];
	}
	$num = 0;
	foreach($dailies as $k=>$v){
		if ($num % 2 == 0){
			if ($num != 0) $ret .= "</tr>";
			if ($num % 4 == 0) $ret .= "<tr>";
			else $ret .= "<tr class=\"color\">";
		}
		$ret .= sprintf("<th>%s</th><td><i>Count</i></td><td>%.2f</td>
				<td><i>POS</i></td><td>%.2f</td><td><i>Variance
				</i></td><td>%.2f</td>",$k,$v[0],$v[1],($v[0]-$v[1]));
		$num++;
	}
	if ($num % 2 != 0)
		$ret .= "<td colspan=7>&nbsp;</td>";
	$ret .= "</tr>";

	$ret .= "</table>";
	$ret .= "<input type=hidden id=savedDate1 value=\"$startDate\" />";
	$ret .= "<input type=hidden id=savedDate2 value=\"$endDate\" />";
	$ret .= "<input type=submit value=Save onclick=\"save();\" />";
	
	
	return $ret;
}

function denom_overage($overage){
	$ret = array("0.25"=>0,"0.10"=>0,"0.05"=>0,"0.01"=>0);

	$ret["0.25"] = floor($overage / 10.0)*10;
	$overage = $overage % 10;
	$ret["0.10"] = floor($overage / 5.0)*5;
	$overage = $overage % 5;
	$ret["0.05"] = floor($overage / 2.0)*2;
	$overage = $overage % 2;
	$ret["0.01"] = floor($overage / 0.50)*0.50;
	
	return $ret;
}

?>

<html>
<head>
	<title>Count</title>
<script type=text/javascript src=count.js></script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
<style type=text/css>
tr.color {
	background: #ffffcc;
}
div#display td, th, h3 {
    color: #000000;
}
</style>
</head>
<body>

<div id=input>
<table>
<tr>
	<th>Start Date</th><td><input type=text id=startDate onfocus="this.value='';showCalendarControl(this);" /></td>
	<td>
	<input type=submit Value=Load onclick="loader();" />
	</td>
</tr>
<tr>
	<th>End Date</th><td><input type=text id=endDate onfocus="this.value='';showCalendarControl(this);" /></td>
</tr>
</table>
</div>

<hr />

<div id=display></div>

</body>
</html>
