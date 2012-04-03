<?php
/*
Table: houseCoupons

Columns:
	coupID int
	endDate datetime
	limit smallint
	memberOnly smallint
	discountType varchar
	discountValue double
	minType varchar
	minValue double
	department int

Depends on:
	houseCouponItems

Use:
WFC runs custom barcoded coupons with
upc prefix 499999, then the coupon ID
(zero padded to 5 digits). There's a
tool in legacy for putting these together
that may eventually make it into fannie.

minType and minValue specify how one
qualifies for the coupon - certain item(s),
purchase amount, etc

discountType and discountValue specify
how the discount is calculated

The nuts and bolts of this are in
the UPC.php parser module (IT CORE).
*/
$CREATE['op.houseCoupons'] = "
	CREATE TABLE houseCoupons (
		coupID int,
		endDate datetime,
		`limit` smallint,
		memberOnly smallint,
		discountType varchar(2),
		discountValue double,
		minType varchar(2),
		minValue double,
		department int
	)
";
if ($dbms == "MSSQL")
	$CREATE['op.houseCoupons'] = str_replace("`","",$CREATE['op.houseCoupons']);
?>
