<?php
/*
Table: disableCoupon

Columns:
	upc varchar
	reason text

Depends on:
	none

Use:
Manually disable a specific manufacturer coupon UPC
*/
$CREATE['op.disableCoupon'] = "
	CREATE TABLE disableCoupon (
		upc varchar(13),
        threshold SMALLINT,
		reason text,
		primary key (upc)
	)
";

?>
