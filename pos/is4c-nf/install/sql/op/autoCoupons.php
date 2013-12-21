<?php
/*
Table: autoCoupons

Columns:
	coupID int

Depends on:
    houseCoupons
	houseCouponItems

Use:
Apply coupons to transactions automatically

*/
$CREATE['op.autoCoupons'] = "
	CREATE TABLE autoCoupons (
		coupID int,
		PRIMARY KEY (coupID)
	)
";

