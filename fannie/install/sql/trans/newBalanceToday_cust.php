<?php
/*
View: newBalanceToday_cust

Columns:
	memnum int
	discounttype int
	ARCurrBalance dbms currency
	totcharges (calculated)
	totpayments (calculated)
	balance (calculated)

Depends on:
	is4c_op.custdata (table)
	memIouToday (view)

Use:
This view lists real-time store charge
balances by membership. There are some
extraneous columns here for historical
reasons; "balance" is the column of
interest.
*/
$names = qualified_names();

$CREATE['trans.newBalanceToday_cust'] = "
	CREATE VIEW newBalanceToday_cust AS
		SELECT   c.CardNo as memnum, c.Discount as discounttype,c.Balance as ARCurrBalance,
		(case when a.charges is NULL then 0 ELSE a.charges END) as totcharges,
		(CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END) as totpayments,
		(CASE when a.card_no is NULL then c.Balance ELSE (c.Balance -a.charges - a.payments)END) as balance
		FROM {$names['op']}.custdata as c left outer join memIouToday as a ON c.CardNo = a.card_no
		where c.personNum = 1
";

if (!$con->table_exists("memIouToday"))
	$CREATE['trans.newBalanceStockToday_cust'] = "SELECT 1";
?>
