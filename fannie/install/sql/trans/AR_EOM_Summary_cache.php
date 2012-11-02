<?php
/*
Table: AR_EOM_Summary_cache

Columns:
	card_no int
	memName varchar
	priorBalance money
	threeMonthCharges money
	threeMonthPayments money
	threeMonthBalance money
	twoMonthCharges money
	twoMonthPayments money
	twoMonthBalance money
	lastMonthCharges money
	lastMonthPayments money
	lastMonthBalance money

Depends on:
	AR_EOM_Summary (view, of ar_history_backup)

Use:
View of customer start/end AR balances
over past few months

Maintenance:
cron/nightly.ar.php, after updating ar_history,
 truncates ar_history_backup and then appends all of ar_history
 to it, giving new data to the view AR_EOM_Summary.
 Then it truncates AR_EOM_Summary_cache and
  appends all of of the new AR_EOM_Summary to it.

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 22Oct2012 EL Add Maintenance section
  * 20Oct2012 Eric Lee Fix capitalization in array index.
	*                    Add MSSQL version.

*/

$CREATE['trans.AR_EOM_Summary_cache'] = "
	CREATE TABLE AR_EOM_Summary_cache (
	cardno int,
	memName varchar(100),
	priorBalance decimal(10,2),
	threeMonthCharges decimal(10,2),
	threeMonthPayments decimal(10,2),
	threeMonthBalance decimal(10,2),	
	twoMonthCharges decimal(10,2),
	twoMonthPayments decimal(10,2),
	twoMonthBalance decimal(10,2),	
	lastMonthCharges decimal(10,2),
	lastMonthPayments decimal(10,2),
	lastMonthBalance decimal(10,2),	
	PRIMARY KEY (cardno)
	)
";

// 20Oct2012 Eric Lee Added.
if ($dbms == "MSSQL"){
	$CREATE['trans.AR_EOM_Summary_cache'] = str_replace("decimal(10,2)","money",$CREATE['trans.AR_EOM_Summary_cache']);
}
