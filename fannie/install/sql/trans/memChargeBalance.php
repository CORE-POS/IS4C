<?php
/*
View: memChargeBalance

Columns:
	CardNo int
	availBal (calculated) 
	balance (calculated)
	mark (calculated)

Depends on:
	core_op.custdata (table)
	memIouToday (view of t.dtransactions -> .v.dlog)

Depended on by:
  newBalanceToday_cust

Use:
This view lists real-time store charge
 balances by membership.
This view gets pushed to the lanes as a table
 to speed things up
The "mark" column indicates an account
 whose balance has changed today

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 24Oct12 EL Fix ...
	* 24Oct2012 Eric Lee Comments, add Depended on by:
	*                    Code style.

*/

$names = qualified_names();

$CREATE['trans.memChargeBalance'] = "
	CREATE VIEW memChargeBalance as
	SELECT   c.CardNo, 
		(CASE when a.balance is NULL then c.memDiscountLimit
			ELSE c.memDiscountLimit - a.balance END)              AS availBal,
		(CASE when a.balance is NULL then 0 ELSE a.balance END) AS balance,
		CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END         AS mark   
	FROM {$names['op']}.custdata    AS c
   LEFT JOIN newBalanceToday_cust AS a ON c.CardNo = a.memnum
	WHERE c.personNum = 1
";

if (!$con->table_exists("newBalanceToday_cust"))
	$CREATE['trans.memChargeBalance'] = "SELECT 1";
?>
