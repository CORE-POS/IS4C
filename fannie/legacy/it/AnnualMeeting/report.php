<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/Credentials/OutsideDB.is4c.php');

if (isset($_REQUEST['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="AnnualMtg2011.xls"');
	ob_start();
}
else {
	echo '<a href="report.php?excel=yes">Save as Excel</a><br /><br />';
}

$q = "SELECT tdate,r.card_no,name,email,
	phone,guest_count,child_count,
	SUM(CASE WHEN m.subtype=1 THEN 1 ELSE 0 END) as chicken,
	SUM(CASE WHEN m.subtype=2 THEN 1 ELSE 0 END) as veg
	FROM registrations AS r LEFT JOIN
	regMeals AS m ON r.card_no=m.card_no
	GROUP BY tdate,r.card_no,name,email,
	phone,guest_count,child_count
	ORDER BY tdate";
$r = $dbc->query($q);
echo '<table cellspacing="0" cellpadding="4" border="1">
	<tr>
	<th>Reg. Date</th><th>Owner#</th><th>Last Name</th><th>First Name</th>
	<th>Email</th><th>Ph.</th><th>Adults</th><th>Chicken</th><th>Curry</th><th>Kids</th>
	<th>Details</th>
	</tr>';
$sum = 0;
$ksum = 0;
$xsum = 0;
$vsum = 0;
while($w = $dbc->fetch_row($r)){
	if (!strstr($w['email'],'@') && !preg_match('/\d+/',$w['email']) &&
		$w['email'] != 'no email'){
		$w['name'] .= ' '.$w['email'];	
		$w['email'] = '';
	}
	$ln = ""; $fn="";
	if (strstr($w['name'],' ')){
		$w['name'] = trim($w['name']);
		$parts = explode(' ',$w['name']);
		if (count($parts) > 1){
			$ln = $parts[count($parts)-1];
			for($i=0;$i<count($parts)-1;$i++)
				$fn .= ' '.$parts[$i];
		}
		else if (count($parts) > 0)
			$ln = $parts[0];
	}
	else
		$ln = $w['name'];
	printf('<tr><td>%s</td><td>%d</td><td>%s</td><td>%s</td>
		<td>%s</td><td>%s</td><td>%d</td><td>%d</td>
		<td>%d</td><td>%d</td><td>%s</td></tr>',
		$w['tdate'],$w['card_no'],$ln,$fn,$w['email'],
		$w['phone'],$w['guest_count']+1,$w['chicken'],$w['veg'],$w['child_count'],
		(isset($_REQUEST['excel'])?'':'<a href=index.php?card_no='.$w['card_no'].'>Details</a>')
	);
	$sum += ($w['guest_count']+1);
	$ksum += $w['child_count'];
	$xsum += $w['chicken'];
	$vsum += $w['veg'];
}
echo '<tr><th colspan="6" align="right">Totals</th>';
echo '<td>'.$sum.'</td>';
echo '<td>'.$xsum.'</td>';
echo '<td>'.$vsum.'</td>';
echo '<td>'.$ksum.'</td>';
echo '<td>&nbsp;</td>';
echo '</table>';

if (isset($_REQUEST['excel'])){
	$output = ob_get_contents();
	ob_end_clean();

	include($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
	include($FANNIE_ROOT.'src/ReportConvert/ArrayToXls.php');
	$array = HtmlToArray($output);
	$xls = ArrayToXls($array);
	
	echo $xls;
}

?>
