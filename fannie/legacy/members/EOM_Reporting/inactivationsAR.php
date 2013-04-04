<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'src/functions.php');

if (isset($_GET['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="inactivationsAR.xls"');
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
}

include($FANNIE_ROOT.'cache/cache.php');
$cached_output = get_cache("monthly");
if (False && $cached_output){
	echo $cached_output;
	exit;
}

ob_start();

$query = "select a.* from AR_EOM_Summary as a
	left join custdata as c on a.cardno=c.cardno and c.personnum=1
	where c.type <> 'TERM' and c.memtype <> 9
	and c.type <> 'INACT'
	and twoMonthBalance > 1
	and c.balance <> 0
	and lastMonthPayments < twoMonthBalance
	order by convert(int,a.cardno)";
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
put_cache('monthly',$output);
echo $output;

?>
