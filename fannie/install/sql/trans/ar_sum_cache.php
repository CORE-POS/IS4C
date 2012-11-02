<?php
/*
Table: ar_sum_cache

Columns:
	card_no int
	charges dbms currency
	payments dbms currency
	balance dbms currency

Depends on:
	ar_history_sum (view)

Use:
  Summary of all charges and payments per customer
  Same content as ar_history_sum

Maintained:
  Truncated and populated by from ar_history_sum
   by nightly.tablecache.php

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 24Oct2012 Eric Lee Add comments: Maintained:

*/

$CREATE['trans.ar_sum_cache'] = "
	CREATE TABLE ar_sum_cache (
	card_no INT,
	charges decimal(10,2),
	payments decimal(10,2),
	balance decimal(10,2),
	PRIMARY KEY (card_no)
	)
";
if ($dbms == "MSSQL"){
	$CREATE['trans.ar_sum_cache'] = str_replace("decimal(10,2)","money",$CREATE['trans.ar_history']);
}
?>
