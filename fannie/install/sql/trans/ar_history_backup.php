<?php
/*
Table: ar_history_backup

Columns:
	card_no int
	Charges dbms currency
	Payments dbms currency
	tdate datetime
	trans_num varchar

Depends on:
	dlog (view)
	ar_history (table)

Use:
Stores an extra copy of ar_history
*/
$CREATE['trans.ar_history_backup'] = "
	CREATE TABLE ar_history_backup (
		card_no int,
		Charges decimal(10,2),
		Payments decimal(10,2),
		tdate datetime,
		trans_num varchar(90)
	)
";
if ($dbms == "MSSQL"){
	$CREATE['trans.ar_history_backup'] = str_replace("decimal(10,2)","money",$CREATE['trans.ar_history_backup']);
}
?>
