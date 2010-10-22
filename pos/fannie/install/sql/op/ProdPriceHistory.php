<?php
/*
Table: ProdPriceHistory

Columns:
	upc varchar(13)
	modified datetime
	price decimal(10,2)
	uid int

Depends on:
	prodUpdate (table)

Use:
This table holds a compressed version of prodUpdate.
A entry is only made when an item's regular price setting
changes. uid is the user who made the change.
*/
$CREATE['op.ProdPriceHistory'] = "
	CREATE TABLE ProdPriceHistory (
		upc varchar(13),
		modified datetime,
		price decimal(10,2),
		uid int
	)
";
?>
