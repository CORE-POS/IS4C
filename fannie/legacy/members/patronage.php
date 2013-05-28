<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include_once('functMem.php');
include_once('headerTest.php');

$memID = $_GET['memID'];
//$memID = 175;

$patQ = "SELECT FY,purchase AS Purchases,discounts as Discounts,rewards as Rewards,net_purch as 'Net Purchases',tot_pat as 'Total Patronage',cash_pat as 'Cash Portion',equit_pat as 'Equity Portion' FROM patronage where cardno = $memID ORDER BY FY";

$sumPatQ = "SELECT cardno as 'Mem #',SUM(tot_pat) as Total,SUM(cash_pat) as 'Cash',sum(equit_pat) as 'Equity' FROM patronage where cardno = $memID group by cardno";

head_to_table($patQ,1,'FFFFCC');
echo "<br><br>Historical Totals:";
head_to_table($sumPatQ,1,'FFFFCC');

?>
