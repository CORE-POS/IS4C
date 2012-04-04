<?php
/*
Table: CashPerformDay_cache

Columns:
	proc_date datetime
	emp_no int
	trans_num char
	startTime datetime
	endTime datetime
	transInterval int
	items int
	rings int
	cancels int
	card_no int

Depends on:
	CashPerformDay (view)

Use:
Stores a copy of CashPerformDay
(view itself tends to be slow)
*/
$CREATE['trans.CashPerformDay_cache'] = "
	CREATE TABLE CashPerformDay_cache 
	(proc_date datetime,
	emp_no smallint,
	trans_num varchar(25),
	startTime datetime,
	endTime datetime,
	transInterval int,
	items float,
	rings int,
	Cancels int,
	card_no int
	)
";
?>
