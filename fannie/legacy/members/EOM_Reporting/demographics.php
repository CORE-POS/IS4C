<?php
include('../../../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="demographics.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

$cached_output = DataCache::getFile("monthly");
if ($cached_output && !isset($_REQUEST['month'])){
	echo $cached_output;
	exit;
}

ob_start();

$year = date('Y');
$month = date('n');
$stamp = mktime(0,0,0,$month-1,1,$year);
$year = date("Y",$stamp);

if (isset($_REQUEST['month']) && isset($_REQUEST['year'])){
	$month = $_REQUEST['month'];
	$year = $_REQUEST['year'];
	$stamp = mktime(0,0,0,$month,1,$year);
}
$start = date("Y-m-01",$stamp);
$end = date("Y-m-t",$stamp);
$lm_span = "'$start 00:00:00' AND '$end 23:59:59'";
$lm_args = array($start.' 00:00:00', $end.' 23:59:59');

$dlog_lm = "trans_archive.dlogBig";

$stamp = mktime(0,0,0,date('m',$stamp)-2,1,date('Y',$stamp));
$start = date("Y-m-01",$stamp);
$lq_span = "'$start 00:00:00' AND '$end 23:59:59'";
$lq_args = array($start.' 00:00:00', $end.' 23:59:59');

$dlog_lq = "trans_archive.dlogBig";

$totalQ = "select year(m.start_date),c.Type from
	memDates as m left join custdata as c on m.card_no=c.CardNo
	and c.personNum=1 left join suspensions as s on
	s.cardno = m.card_no where c.memType=1 or s.memtype1 = 1
	order by year(m.start_date)";
$totalR = $sql->query($totalQ);

$totalMems = 0;
$totalActiveMems = 0;
$yearBuckets = array("1991 or earlier"=>array(0,0));
while ($totalW = $sql->fetch_row($totalR)){
	if ($totalW[0] <= 1991){
		if ($totalW[1] == 'PC')
			$yearBuckets["1991 or earlier"][0]++;
		$yearBuckets["1991 or earlier"][1]++;
	}
	else {
		if (empty($yearBuckets["$totalW[0]"]) || !isset($yearBuckets["$totalW[0]"]))
			$yearBuckets["$totalW[0]"] = array(0,0);
		if ($totalW[1] == 'PC')
			$yearBuckets["$totalW[0]"][0]++;
		$yearBuckets["$totalW[0]"][1]++;
	}

	$totalMems++;
	if ($totalW[1] == 'PC') $totalActiveMems++;
}

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Total members</th><td>$totalMems</td>";
echo "<th>Active</th><td>$totalActiveMems</td>";
echo "<td>".round(100*$totalActiveMems/$totalMems,2)."%</td></tr></table>";

echo "<p />";

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Activated</th><th>&nbsp;</th><th>Still active</th><th>&nbsp;</td></tr>";
foreach ($yearBuckets as $k=>$v){
	echo "<tr>";
	echo "<td align=left>$k</td>";
	echo "<td align=center>$v[1]</td>";
	echo "<td align=center>$v[0]</td>";
	echo "<td align=right>".round(100*$v[0]/$v[1],2)."%</td>";
	echo "</tr>";
}
echo "</table>";

$patronageLMQ = $sql->prepare("select d.card_no from $dlog_lm as d LEFT JOIN
		custdata as c on c.CardNo=d.card_no LEFT JOIN
		suspensions as s on s.cardno=d.card_no
		WHERE c.personNum=1 and (c.memType=1 or s.memtype1=1)
		AND register_no <> 30
		AND (tdate BETWEEN ? AND ?)
		GROUP BY d.card_no");
$patronageLM = $sql->num_rows($sql->execute($patronageLMQ, $lm_args));

$patronageLQQ = $sql->prepare("select d.card_no from $dlog_lq as d LEFT JOIN
		custdata as c on c.CardNo=d.card_no LEFT JOIN
		suspensions as s on s.cardno=d.card_no
		WHERE c.personNum=1 and (c.memType=1 or s.memtype1=1)
		and register_no <> 30
		AND tdate BETWEEN ? AND ?
		GROUP BY d.card_no");
$patronageLQ = $sql->num_rows($sql->execute($patronageLQQ, $lq_args));

$yearQ = "select y.card_no,month_no,-1*total,
	".$sql->monthdiff($sql->now(),'m.start_date')." 
	from YTD_Patronage_Speedup as y left join
	memDates as m on y.card_no=m.card_no
	where y.total <> 0
	order by y.card_no";
$yearR = $sql->query($yearQ);

$curNo = 0;
$curVisits = array(0,0,0,0,0,0,0,0,0,0,0,0,0);
$curLength=0;
$curSpending = 0;
$patronageLY = 0;

$patronageBuckets = array("More than 4"=>0,"3-4"=>0,"2-3"=>0,"1-2"=>0,"Less than 1"=>0);
$loyaltyBuckets = array("Over $5,000"=>0,
			"$4,000.01 - $5,000"=>0,
			"$3,000.01 - $4,000"=>0,
			"$2,000.01 - $3,000"=>0,
			"$1,000.01 - $2,000"=>0,
			"$1,000 or less"=>0);
while($yearW = $sql->fetch_row($yearR)){
	if ($yearW[0] != $curNo){
		if ($curNo != 0){
			if ($curSpending <= 1000) $loyaltyBuckets["$1,000 or less"]++;
			elseif ($curSpending <= 2000) $loyaltyBuckets["$1,000.01 - $2,000"]++;
			elseif ($curSpending <= 3000) $loyaltyBuckets["$2,000.01 - $3,000"]++;
			elseif ($curSpending <= 4000) $loyaltyBuckets["$3,000.01 - $4,000"]++;
			elseif ($curSpending <= 5000) $loyaltyBuckets["$4,000.01 - $5,000"]++;
			elseif ($curSpending > 5000) $loyaltyBuckets["Over $5,000"]++;

			$sum = 0;
			foreach($curVisits as $c) $sum+=$c;
			$avg = $sum/12.0;
			if ($curLength < 12)
				$avg = ($curLength==0) ? 0 : $sum / ((float)$curLength);
			if ($avg < 1) $patronageBuckets["Less than 1"]++;
			elseif ($avg < 2) $patronageBuckets["1-2"]++;
			elseif ($avg < 3) $patronageBuckets["2-3"]++;
			elseif ($avg < 4) $patronageBuckets["3-4"]++;
			elseif ($avg >= 4) $patronageBuckets["More than 4"]++;
			
			$patronageLY++;
		}
		$curVisits = array(0,0,0,0,0,0,0,0,0,0,0,0,0);
		$curSpending = 0;
		$curNo = $yearW[0];
		$curLength = $yearW[3];
	}
	$curSpending += $yearW[2];
	$curVisits[(int)$yearW[1]]++;
}
// last set
if ($curSpending <= 1000) $loyaltyBuckets["$1,000 or less"]++;
elseif ($curSpending <= 2000) $loyaltyBuckets["$1,000.01 - $2,000"]++;
elseif ($curSpending <= 3000) $loyaltyBuckets["$2,000.01 - $3,000"]++;
elseif ($curSpending <= 4000) $loyaltyBuckets["$3,000.01 - $4,000"]++;
elseif ($curSpending <= 5000) $loyaltyBuckets["$4,000.01 - $5,000"]++;
elseif ($curSpending > 5000) $loyaltyBuckets["Over $5,000"]++;

$sum = 0;
foreach($curVisits as $c) $sum+=$c;
$avg = $sum/12.0;
if ($curLength < 12)
	$avg = ($curLength==0) ? 0 : $sum / ((float)$curLength);
if ($avg < 1) $patronageBuckets["Less than 1"]++;
elseif ($avg < 2) $patronageBuckets["1-2"]++;
elseif ($avg < 3) $patronageBuckets["2-3"]++;
elseif ($avg < 4) $patronageBuckets["3-4"]++;
elseif ($avg >= 4) $patronageBuckets["More than 4"]++;

$patronageLY++;

echo "<p /><b>Participation (at least one purchase in the:)</b>";
echo "<table cellpadding=4 cellspacing=0 border=1>";
echo "<tr><th>Last Month</th><td>$patronageLM</td>";
echo "<td>".round(100*$patronageLM/$totalActiveMems,2)."%</td></tr>";
echo "<tr><th>Last 3 Months</th><td>$patronageLQ</td>";
echo "<td>".round(100*$patronageLQ/$totalActiveMems,2)."%</td></tr>";
echo "<tr><th>Last 12 Months</th><td>$patronageLY</td>";
echo "<td>".round(100*$patronageLY/$totalActiveMems,2)."%</td></tr>";
echo "</table>";

echo "<p /><b>Patronage (Avg. monthly visits in the last 12 months)</b>";
echo "<table cellpadding=4 cellspacing=0 border=1>";
foreach($patronageBuckets as $k=>$v){
	echo "<tr>";
	echo "<th>$k</th>";
	echo "<td>$v</td>";
	echo "<td>".round(100*$v/$totalActiveMems,2)."%</td>";
	echo "</tr>";
}
echo "</table>";

echo "<p /><b>Loyalty (Spending in the last 12 months)</b>";
echo "<table cellpadding=4 cellspacing=0 border=1>";
foreach($loyaltyBuckets as $k=>$v){
	echo "<tr>";
	echo "<th>$k</th>";
	echo "<td>$v</td>";
	echo "<td>".round(100*$v/$totalActiveMems,2)."%</td>";
	echo "</tr>";
}
echo "</table>";

$output = ob_get_contents();
ob_end_clean();
if (!isset($_RREQUEST['month']))
	DataCache::putFile('monthly',$output);
echo $output;

?>
