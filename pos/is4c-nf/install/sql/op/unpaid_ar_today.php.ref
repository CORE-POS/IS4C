<?php
/*
Table: unpaid_ar_today

Columns:
	card_no int
	old_balance currency
	recent_payments currency

Depends on:
	custdata (table)

Use:
Listing of overdue balances. Authoritative,
up-to-the-second data is on the server
but checking a local table is faster if
slightly stale data is acceptable
*/
$CREATE['op.unpaid_ar_today'] = "
	CREATE TABLE unpaid_ar_today (
		card_no int,
		old_balance real,
		recent_payments real,
		primary key (card_no)
	)
";

?>
