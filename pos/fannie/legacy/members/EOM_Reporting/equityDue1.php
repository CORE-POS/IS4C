<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="equityDue1.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

include($FANNIE_ROOT.'cache/cache.php');
$cached_output = get_cache("monthly");
if ($cached_output){
	echo $cached_output;
	exit;
}

ob_start();

$months = array(
"Jan"=>"01",
"Feb"=>"02",
"Mar"=>"03",
"Apr"=>"04",
"May"=>"05",
"Jun"=>"06",
"Jul"=>"07",
"Aug"=>"08",
"Sep"=>"09",
"Oct"=>"10",
"Nov"=>"11",
"Dec"=>"12",
);

$query = "select m.card_no,c.firstname+' '+c.lastname,m.start_date,
	dateadd(yy,2,m.start_date) as endDate,
	s.stockPurchase,s.tdate,n.payments
	from memDates as m left join
	custdata as c on c.cardno=m.card_no and c.personnum=1
	left join stockPurchases as s on m.card_no=s.card_no
	left join newBalanceStockToday_test as n on m.card_no=n.memnum
	where datediff(mm,getdate(),dateadd(yy,2,m.start_date)) = 1
	and c.type='PC' and n.payments < 100
	order by m.card_no,s.tdate";

echo "<table border=1 cellpadding=0 cellspacing=0>\n";
$headers = array('Mem Num','Name','Opening Date','Ending Date',
'First Buy Ammount','Last Buy Date','Total Equity');
echo "<tr>";
foreach($headers as $h)
	echo "<th width=120><font size=2>$h</font></th>";
echo "</tr>";

$backgrounds = array('#ffffcc','#ffffff');
$b = 0;

$result = $sql->query($query);
$curMem=-1;
$firstBuy=0;
$lastrow = array();
while($row = $sql->fetch_row($result)){
	if ($curMem != $row[0]){
		if ($curMem != -1){
			echo "<tr>";
			echo "<td bgcolor=$backgrounds[$b]>$lastrow[0]</td>";
			echo "<td bgcolor=$backgrounds[$b]>$lastrow[1]</td>";
			echo "<td bgcolor=$backgrounds[$b]>$lastrow[2]</td>";
			$temp = explode(' ',$lastrow[3]);
			$fixeddate = $months[$temp[0]]."/".$temp[1]."/".$temp[2];
			echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
			echo "<td bgcolor=$backgrounds[$b]>$firstBuy</td>";
			$temp = explode(' ',$lastrow[5]);
			$fixeddate = $months[$temp[0]]."/".$temp[1]."/".$temp[2];
			echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
			echo "<td bgcolor=$backgrounds[$b]>$lastrow[6]</td>";
			echo "</tr>";
			$b = ($b+1)%2;
		}
		$curMem = $row[0];
		$firstBuy = $row[4];
	}
	$lastrow = $row;	
}
if (count($lastrow) > 0){
	echo "<tr>";
	echo "<td bgcolor=$backgrounds[$b]>$lastrow[0]</td>";
	echo "<td bgcolor=$backgrounds[$b]>$lastrow[1]</td>";
	echo "<td bgcolor=$backgrounds[$b]>$lastrow[2]</td>";
	$temp = explode(' ',$lastrow[3]);
	$fixeddate = $months[$temp[0]]."/".$temp[1]."/".$temp[2];
	echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
	echo "<td bgcolor=$backgrounds[$b]>$firstBuy</td>";
	$temp = explode(' ',$lastrow[5]);
	$fixeddate = $months[$temp[0]]."/".$temp[1]."/".$temp[2];
	echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
	echo "<td bgcolor=$backgrounds[$b]>$lastrow[6]</td>";
	echo "</tr>";
}
echo "</table>";

$output = ob_get_contents();
ob_end_clean();
put_cache('monthly',$output);
echo $output;

?>
