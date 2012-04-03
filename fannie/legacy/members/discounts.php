<?php
include('../../config.php');

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$query = "
select card_no,
SUM(CASE WHEN trans_status = 'M' and trans_type in ('I','D') then total else 0 end) 
as memDiscount,
SUM(CASE WHEN trans_subtype='MA' then total else 0 end) as madCoup,
SUM(CASE WHEN trans_status = '' and trans_type in ('I','D') then total else 0 end)
as memPurchases
from dlog_90_view
where card_no <> 11
and ".$sql->monthdiff($sql->now(),'tdate')." = 1
group by card_no
having
SUM(CASE WHEN trans_status = 'M' and trans_type in ('I','D') then total else 0 end) <> 0
or SUM(CASE WHEN trans_subtype='MA' then total else 0 end) <> 0
order by convert(int,card_no)";
$result = $sql->query($query);

echo "<table cellspacing=0 cellpadding=3 border=1>";
echo "<tr><th>Mem#</th><th>Sales</th><th>Coupon</th><th>Purchases</th><th>% Saved</th><th>$ Saved</th></tr>";
$tP=0;
$tS=0;
$tC=0;
$tD=0;

$c=0;
$aPurch = 0;
$aPercent = 0;
$aDollar = 0;
while ($row = $sql->fetch_row($result)){
	$purch = $row[3];
	$MoS = -1*$row[1];
	$MAD = -1*$row[2];
	$savings = round(100*((float)($MoS+$MAD))/$purch,2);
	$dollars = round($purch*$savings/100,2);
	echo "<tr>";
	echo "<td>$row[0]</td>";
	echo "<td>$MoS</td>";
	echo "<td>$MAD</td>";
	echo "<td>$purch</td>";
	echo "<td>$savings</td>";
	echo "<td>$dollars</td>";
	echo "</tr>";
	$tP += $purch;
	$tS += $MoS;
	$tC += $MAD;
	$tD += $dollars;
	
	$aPurch += $purch;
	$aPercent += $savings;
	$aDollar += $dollars;
	$c += 1;
}
echo "<tr><th>Total</th>";
echo "<th>$tS</th><th>$tC</th><th>$tP</th>";
echo "<th>".round(100*((float)($tS+$tC))/$tP,2)."</th>";
echo "<th>".round(((float)($tS+$tC))/$tP*$tD,2)."</th></tr>";
echo "</table>";
echo "<br />";
echo "<b>Average percent saved</b>: ".round($aPercent/$c,2)."<br />";
echo "<b>Average dollars saved</b>: ".round($aDollar/$c,2)."<br />";
echo "<b>Average spending</b>: ".round($aPurch/$c,2)."<br />";
echo "<b>Avg. percent saved * Avg. spending</b>: ".round(($aPercent/$c)*($aPurch/$c/100),2);
?>
