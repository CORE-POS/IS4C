<?php
include('../../../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="equityDue2.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

$cached_output = DataCache::getFile("monthly");
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

$query = "select m.card_no,CONCAT(c.FirstName,' ',c.LastName),m.start_date,
	DATE_ADD(m.start_date,INTERVAL 2 YEAR) as endDate,
	s.stockPurchase,s.tdate,n.payments
	from memDates as m left join
	custdata as c on c.CardNo=m.card_no and c.personNum=1
	left join is4c_trans.stockpurchases as s on m.card_no=s.card_no
	left join is4c_trans.equity_live_balance as n on m.card_no=n.memnum
	where ".$sql->monthdiff($sql->now(),'DATE_ADD(m.card_no,INTERVAL 2 YEAR)')." = 0
	and c.Type='PC' and n.payments < 100
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
			$temp = explode('-',$temp[0]);
			$fixeddate = $temp[1]."/".$temp[2]."/".$temp[0];
			echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
			echo "<td bgcolor=$backgrounds[$b]>$firstBuy</td>";
			$temp = explode(' ',$lastrow[5]);
			$temp = explode('-',$temp[0]);
			$fixeddate = $temp[1]."/".$temp[2]."/".$temp[0];
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
	$temp = explode('-',$temp[0]);
	$fixeddate = $temp[1]."/".$temp[2]."/".$temp[0];
	echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
	echo "<td bgcolor=$backgrounds[$b]>$firstBuy</td>";
	$temp = explode(' ',$lastrow[5]);
	$temp = explode('-',$temp[0]);
	$fixeddate = $temp[1]."/".$temp[2]."/".$temp[0];
	echo "<td bgcolor=$backgrounds[$b]>$fixeddate</td>";
	echo "<td bgcolor=$backgrounds[$b]>$lastrow[6]</td>";
	echo "</tr>";
}
echo "</table>";

$output = ob_get_contents();
ob_end_clean();
DataCache::putFile('monthly',$output);
echo $output;

?>
