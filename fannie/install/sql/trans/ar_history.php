<?php
/*
Table: ar_history

Columns:
	card_no int
	Charges dbms currency
	Payments dbms currency
	tdate datetime
	trans_num varchar

Depends on:
	dlog (view)

Use:
This table stores charges and payments on
a customer's in-store charge account. This 
table should be updated in conjunction with
any day-end polling system to copy appropriate
rows from dtransactions to ar_history
*/
$CREATE['trans.ar_history'] = "
	CREATE TABLE ar_history (
		card_no int,
		Charges decimal(10,2),
		Payments decimal(10,2),
		tdate datetime,
		trans_num varchar(90),
		INDEX (card_no)
	)
";
if ($dbms == "MSSQL"){
	$CREATE['trans.ar_history'] = str_replace("decimal(10,2)","money",$CREATE['trans.ar_history']);
}
?>
