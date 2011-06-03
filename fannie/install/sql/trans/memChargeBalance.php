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
	CREATE VIEW memChargeBalance AS
		SELECT   c.CardNo, 
		(CASE when a.card_no is NULL then c.memDiscountLimit - c.Balance 
			ELSE c.memDiscountLimit - (c.Balance -a.charges - a.payments)END) as availBal,
		(CASE when a.card_no is NULL then c.Balance ELSE (c.Balance -a.charges - a.payments)END) as balance,
		CASE WHEN a.card_no IS NULL THEN 0 ELSE 1 END AS mark
		FROM {$names['op']}.custdata as c left outer join memIouToday as a ON c.CardNo = a.card_no
		where c.personNum = 1
";

if (!$con->table_exists("memIouToday"))
	$CREATE['trans.memChargeBalance'] = "SELECT 1";
?>
