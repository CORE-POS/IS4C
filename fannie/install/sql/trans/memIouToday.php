<?php
/*
View: memIouToday

Columns:
	card_no int
	charges (calculated)
	payments (calculated)

Depends on:
	dlog (view)

Use:
This view lists charge account activity
for the current day. It exists to 
calculate balances in real time.

The view's construction depends on Fannie's
Store Charge Department configuration
*/
$dlist = ar_departments();

$CREATE['trans.memIouToday'] = "
	CREATE VIEW memIouToday AS
		SELECT card_no,
		SUM(CASE WHEN trans_subtype='MI' THEN total ELSE 0 END) as charges,
		SUM(CASE WHEN department IN $dlist THEN total ELSE 0 END) as payments
		FROM dlog WHERE ".$con->datediff($con->now(),'tdate')." = 0
		AND (trans_subtype='MI' OR department IN $dlist)
		GROUP BY card_no
";

if (empty($dlist)){
	$CREATE['trans.memIouToday'] = "CREATE VIEW memIouToday AS 
		SELECT 1 as card_no,0 as charges, 0 as payments";
}

?>
