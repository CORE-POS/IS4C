<?php
/*
View: ar_history_sum

Columns:
	card_no int
	charges dbms currency
	payments dbms currency
	balance dbms currency

Depends on:
	ar_history (view)
	custdata (table)

Depended on by:
  Snapshot table derived from this: ar_sum_cache

Use:
  Summary of all charges and payments per customer
  (One row per customer.)

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 24Oct2012 Eric Lee Add comments Depended on by:

*/

$names = qualified_names();

$CREATE['trans.ar_history_sum'] = "
	CREATE VIEW ar_history_sum AS
	select
	c.CardNo as card_no,
	sum(case when charges is null then 0 else charges end) as charges,
	sum(case when payments is null then 0 else payments end) as payments,
	sum(case when charges is null then 0 else charges end)
	 - sum(case when payments is null then 0 else payments end) as balance
	from {$names['op']}.custdata as c
	left join ar_history as a
	on c.cardno=a.card_no and c.personNum=1
	group by c.CardNo
";
?>
