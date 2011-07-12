<?php
/*
Table: prodDepartmentHistory

Columns:
	upc varchar(13)
	modified datetime
	dept_ID int
	uid int

Depends on:
	prodUpdate (table)

Use:
This table holds a compressed version of prodUpdate.
A entry is only made when an item's department setting
changes. uid is the user who made the change.
*/
$CREATE['op.prodDepartmentHistory'] = "
	CREATE TABLE prodDepartmentHistory (
		upc varchar(13),
		modified datetime,
		dept_ID int,
		uid int
	)
";
?>
