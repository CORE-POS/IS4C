<?php
/*
Table: memchargebalance

Columns:
	CardNo int
	availBal currency
	balance currency

Depends on:
	custdata (Table)

Use:
View showing member charge balance. Authoritative,
up-to-the-second data is on the server but a local
lookup is faster if slightly stale data is acceptable.
*/
$CREATE['op.memchargebalance'] = "
	CREATE view memchargebalance as
		SELECT 
		c.CardNo AS CardNo,
		c.ChargeLimit - c.Balance AS availBal,	
		c.Balance as balance
		FROM custdata AS c WHERE personNum = 1
";

?>
