<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/OverShortTools/OverShortDepositSlips.php');
exit;

require($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (isset($_GET['startDate'])){
	$start = $_GET["startDate"];
	$end = $_GET["endDate"];

	$fs = 12;

	$pdf = new FPDF("P","mm","A4");	
	$pdf->SetFont('Arial','',$fs);
	$pdf->SetMargins(5,5,5);
	$pdf->SetAutoPageBreak(True,5);
	$pdf->AddPage();

	$dateClause = $start;
	if ($start != $end)
		$dateClause = $start." ".$end;

	include('../../db.php');
	$sql->query("use $FANNIE_TRANS_DB");

	$query = "select checks from dailyChecks where
		date BETWEEN '$start' AND '$end'
		order by 
	      case when id >= 68 then id+1
	      when id = 43 then 68
	      else id end";
	$result = $sql->query($query);
	$acc = array();
	$counts = array();
	$ckSum = 0;
	$width = 30;
	$i = 0;
	$num = 1;
	$breakon = 1;
	while($row = $sql->fetch_row($result)){
		$real = $num;
		if (($num-1) % 6 == 0 && $num > 0 && $num != $breakon){
			$pdf->AddPage();
			$breakon = $num;
		}
		if ($num > 6){
			$real = ($num%6);
			if ($real == 0) $real = 6;
		}
		$vals = explode(",",$row[0]);

		$extra = array();

		$vcount = 0;
		foreach($vals as $v){
			if (is_numeric($v)) $vcount++;
		}

		if ($vcount + count($acc) <= 57){
			foreach($vals as $v){
				if (is_numeric($v)) array_push($acc,$v);
			}
			$i = $i+1;
		}
		else {
			foreach($vals as $v){
				if (is_numeric($v)) array_push($extra,$v);
			}
			while ($i % 4 != 0) $i++;
		}
		if ($i % 4 == 0){
			$str1 = "WFC #$num\n";
			$sum = 0;
			$str = "";
			for($j=0;$j<57;$j++){
				if ($j < count($acc)){
					$str .= sprintf("%.2f",$acc[$j]);
					$sum += $acc[$j];
				}
				$str .= "\n";
			}
			$str2 = "TTL: ".sprintf("%.2f",$sum);
			$str3 = "CT: ".count($acc);
			array_push($counts,$sum);
			$ckSum += $sum;
			
			$j = 1;
			if ($real == 1) $j = 0;
			$k = 6;
			if ($real == 6) $k = 2;
			$pdf->SetXY(($width-0)*$j + ($width+7)*($real-1-$j),10);
			$pdf->MultiCell($width+($j==0?-1:$k),5,$str1,'R','L');
			$pdf->SetX(($width-0)*$j+ ($width+7)*($real-1-$j));
			$pdf->SetFontSize($fs-1);
			$pdf->MultiCell($width+($j==0?-1:$k),4.35,$str,'R','L');
			$pdf->SetX(($width-0)*$j+ ($width+7)*($real-1-$j));
			$pdf->SetFont('Arial','B',$fs-1);
			$pdf->MultiCell($width+($j==0?-1:$k),5,$str2,'R','L');
			$pdf->SetFont('Arial','',$fs-1);
			$pdf->SetX(($width-0)*$j+ ($width+7)*($real-1-$j));
			$pdf->MultiCell($width+($j==0?-1:$k),5,$str3,'R','L');
			
			$acc = array();
			$num++;
		}
		if (count($extra) > 0){
			foreach($extra as $e) array_push($acc,$e);
			$i = $i+1;
		}
	}
	$str1 = "WFC #$num\n";
	$real = $num;
	if ($num % 7 == 0 && $num > 0) $pdf->AddPage();
	if ($num > 6){
		$real = ($num%6);
		if ($real == 0) $real = 6;
	}

	if (count($acc) > 0){
		$sum = 0;
		$str = "";
		for($j=0;$j<57;$j++){
			if ($j < count($acc)){
				$str .= sprintf("%.2f",$acc[$j]);
				$sum += $acc[$j];
			}
			$str .= "\n";
		}
		$str2 = "TTL: ".sprintf("%.2f",$sum);
		$str3 = "CT: ".count($acc);
		array_push($counts,$sum);
		$ckSum += $sum;
	
		$j = 1;
		if ($real == 1) $j = 0;
		$k = 6;
		if ($real == 6) $k = 2;
		$pdf->SetXY(($width-0)*$j + ($width+7)*($real-1-$j),10);
		$pdf->MultiCell($width+($j==0?-1:$k),5,$str1,'R','L');
		$pdf->SetX(($width-0)*$j+ ($width+7)*($real-1-$j));
		$pdf->SetFontSize($fs-1);
		$pdf->MultiCell($width+($j==0?-1:$k),4.35,$str,'R','L');
		$pdf->SetX(($width-0)*$j+ ($width+7)*($real-1-$j));
		$pdf->SetFont('Arial','B',$fs);
		$pdf->MultiCell($width+($j==0?-1:$k),5,$str2,'R','L');
		$pdf->SetFont('Arial','',$fs);
		$pdf->SetX(($width-0)*$j+ ($width+7)*($real-1-$j));
		$pdf->MultiCell($width+($j==0?-1:$k),5,$str3,'R','L');
	}

	if ($num <= 6 || $num > 10) $pdf->AddPage();

	/* shift last box over a bit */
	$width += 3;

	$pdf->SetXY(($width+2)*4 + 5,10);
	$pdf->SetFillColor(0,0,0);
	$pdf->SetTextColor(255,255,255);
	$pdf->SetFontSize(12);
	$str = "Whole Foods Community\nCO-OP Deposit Slip\n";
	$str .= trim(file_get_contents("acct"),"\r\n")."\n\n";
	$str .= "Date\t".date("m/d/y")."\n";
	$pdf->MultiCell(55,5,$str,0,'C',1);
	
	$pdf->SetTextColor(0,0,0);
	$str = "";
	for($i=0;$i<10 || $i < count($counts); $i++){
		$str .= "Check # ".($i+1).":";
		if ($i < count($counts))
			$str .= "\t\t$counts[$i]";
		$str .= "\n";
	}
	$pdf->SetX(($width+2)*4 + 5);
	$pdf->MultiCell(55,7,$str,'LR','L');

	$dbstack = array('buyAmount'=>array(),
			 'depositAmount'=>array());
	$dbQ = "SELECT rowName,denomination,amt FROM dailyDeposit WHERE
		dateStr = '$dateClause' AND rowName IN ('buyAmount','depositAmount')";
	$dbR = $sql->query($dbQ);
	while($dbW = $sql->fetch_row($dbR)){
		$dbstack[$dbW[0]][$dbW[1]] = $dbW[2];
	}

	$coin = 0;
	if (isset($dbstack['depositAmount']['0.01'])) $coin += $dbstack['depositAmount']['0.01'];
	if (isset($dbstack['depositAmount']['0.05'])) $coin += $dbstack['depositAmount']['0.05'];
	if (isset($dbstack['depositAmount']['0.10'])) $coin += $dbstack['depositAmount']['0.10'];
	if (isset($dbstack['depositAmount']['0.25'])) $coin += $dbstack['depositAmount']['0.25'];
	$junk = 0;
	if (isset($dbstack['depositAmount']['Junk'])) $junk += $dbstack['depositAmount']['Junk'];
	$cash = 0;
	if (isset($dbstack['depositAmount']['1.00'])) $cash += $dbstack['depositAmount']['1.00'];
	if (isset($dbstack['depositAmount']['5.00'])) $cash += $dbstack['depositAmount']['5.00'];
	if (isset($dbstack['depositAmount']['10.00'])) $cash += $dbstack['depositAmount']['10.00'];
	if (isset($dbstack['depositAmount']['20.00'])) $cash += $dbstack['depositAmount']['20.00'];
	if (isset($dbstack['depositAmount']['50.00'])) $cash += $dbstack['depositAmount']['50.00'];
	if (isset($dbstack['depositAmount']['100.00'])) $cash += $dbstack['depositAmount']['100.00'];


	$pdf->SetX(($width+2)*4 + 5);
	$pdf->Cell(15,7,'Checks','L',0,'L');
	$pdf->Cell(40,7,"\t$".sprintf('%.2f',$ckSum),'TBR',1);
	//$pdf->Cell(40,7,"",'TBR',1);
	$pdf->SetX(($width+2)*4 + 5);
	$pdf->Cell(15,7,'Coin','L',0,'L');
	$pdf->Cell(40,7,"\t$".sprintf('%.2f',$coin),'TBR',1);
	$pdf->SetX(($width+2)*4 + 5);
	$pdf->Cell(15,7,'Cash','L',0,'L');
	$pdf->Cell(40,7,"\t$".sprintf('%.2f',$cash),'TBR',1);
	$pdf->SetX(($width+2)*4 + 5);
	$pdf->Cell(15,7,'Other','L',0,'L');
	$pdf->Cell(40,7,"\t$".sprintf('%.2f',$junk),'TBR',1);
	$pdf->SetX(($width+2)*4 + 5);
	$pdf->Cell(15,7,'Total','L',0,'L');
	$pdf->Cell(40,7,"\t$".sprintf('%.2f',$junk+$cash+$coin+$ckSum),'TBR',1);

	$pdf->SetTextColor(255,255,255);
	$pdf->SetX(($width+2)*4+5);
	$pdf->MultiCell(55,5,"Change Request",0,'C',1);

	$pdf->SetTextColor(0,0,0);
	$denoms = array('0.01','0.05','0.10','0.25','1.00','5.00','10.00');
	$total = array_sum($dbstack['buyAmount']);
	if (!empty($dbstack['buyAmount'])){
		if ($dbstack['buyAmount']['0.01'] > 50)
			$total = $dbstack['buyAmount']['0.01'];
		foreach($denoms as $d){
			$pdf->SetX(($width+2)*4+5);
			$pdf->Cell(10,7,'$','L',0,'L');
			$pdf->Cell(10,7,"$d",0,0,'R');
			$pdf->Cell(35,7,$dbstack['buyAmount'][$d],'RB',1,'C');
		}
	}
	$pdf->SetX(($width+2)*4+5);
	$pdf->Cell(20,7,"Total:",'LB',0,'R');
	$pdf->Cell(35,7,sprintf('%.2f',$total),'RB',1,'C');

	$pdf->Output('deposit.pdf','I');
}
else {
?>

<html>
<head>
	<title>Print deposit slips</title>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
</head>
<body>
<form action=depositslips.php method=get>
<table>
<tr><th>Start</th><td><input type=text name=startDate onfocus="this.value='';showCalendarControl(this);" /></td></tr>
<tr><th>End</th><td><input type=text name=endDate onfocus="this.value='';showCalendarControl(this);" /></td></tr>
</table>
<input type=submit value="Generate slips" />
</form>
</body>
</html>

<?php
}
?>
