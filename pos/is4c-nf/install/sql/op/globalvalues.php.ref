<?php
/*
Table: globalvalues

Columns:
	CashierNo int
	Cashier varchar
	LoggedIn int
	TransNo int
	TTLFlag int
	FntlFlag int
	TaxExempt int

Depends on:
	none

Use:
A small subset of session values. Storing this
in SQL ensures it will survive a browser crash
or reboot to pick up more-or-less when the transaction
left off.
*/
$CREATE['op.globalvalues'] = "
	CREATE TABLE globalvalues (
		CashierNo int,
		Cashier varchar(30),
		LoggedIn tinyint,
		TransNo int,
		TTLFlag tinyint,
		FntlFlag tinyint,
		TaxExempt tinyint
	)
";

?>
