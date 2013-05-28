<?php
/*
View: houseCouponThisMonth

Columns:
	card_no  int
	upc	 varchar
	quantity float

Depends on:
	dlog_90_view (view)

Use:
List of custom coupons redeemed, per member
*/
$CREATE['trans.houseCouponThisMonth'] = "
	CREATE VIEW houseCouponThisMonth AS
	SELECT card_no,upc,sum(quantity) as quantity FROM
	dlog_90_view
	WHERE upc LIKE '00499999%'
	AND ".$con->monthdiff($con->now(),'tdate')."=0
	GROUP BY card_no,upc
";
?>
