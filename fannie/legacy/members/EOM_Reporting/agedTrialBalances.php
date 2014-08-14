<?php
include('../../../config.php');
include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/functions.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="agedTrialBalances.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

$cached_output = DataCache::getFile("monthly");
if ($cached_output){
	echo $cached_output;
	exit;
}

ob_start();

$query = "select a.* from is4c_trans.AR_EOM_Summary as a
	left join custdata as c on a.cardno=c.CardNo and c.personNum=1
	where c.Type <> 'TERM' 
	and (
	priorBalance <> 0 or
	threeMonthCharges <> 0 or
	threeMonthPayments <> 0 or
	threeMonthBalance <> 0 or
	twoMonthCharges <> 0 or
	twoMonthPayments <> 0 or
	twoMonthBalance <> 0 or
	lastMonthCharges <> 0 or
	lastMonthPayments <> 0 or
	lastMonthBalance <> 0
	)
	order by a.cardno";
$headers = array('Mem Num','Name','Prior Balance',
	'Charge','Payment','Balance',
	'Charge','Payment','Balance',
	'Charge','Payment','Balance');

echo "<table border=1 cellpadding=0 cellspacing=0>\n";
echo "<tr><th colspan=3>&nbsp;</th>";
echo "<th colspan=3>3 Months Prior</th>";
echo "<th colspan=3>2 Months Prior</th>";
echo "<th colspan=3>Last Month</th></tr>";

select_to_table2($query,array(),0,'#ffffcc',120,0,0,$headers,True);

$output = ob_get_contents();
ob_end_clean();
DataCache::putFile('monthly',$output);
echo $output;

?>
