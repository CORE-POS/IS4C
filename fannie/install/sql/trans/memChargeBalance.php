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
	memIouToday (view)

Use:
This view lists real-time store charge
balances by membership. This view
gets pushed to the lanes as a table
to speed things up
The "mark" column indicates an account
whose balance has changed today
*/
$names = qualified_names();

$CREATE['trans.memChargeBalance'] = "
	CREATE VIEW memChargeBalance as
	SELECT   c.CardNo, 
	(CASE when a.balance is NULL then c.memDiscountLimit
		ELSE c.memDiscountLimit - a.balance END) as availBal,
	(CASE when a.balance is NULL then 0 ELSE a.balance END) as balance,
	CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END AS mark   
	FROM {$names['op']}.custdata as c left join newBalanceToday_cust as a ON c.CardNo = a.memnum
	where c.personNum = 1
";

if (!$con->table_exists("newBalanceToday_cust"))
	$CREATE['trans.memChargeBalance'] = "SELECT 1";
?>
