<?php
/*
View: unpaid_ar_balances

Columns:
	card_no int
	old_balance (calculated)
	recent_payments (calculated)

Depends on:
	ar_history (table)

Use:
This table lists A/R balances older than
20 days and payments made in the last 20 days.

The logic is pretty WFC-specific, but the 
general idea is to notify customers that they
have a balance overdue at checkout
*/
$CREATE['trans.unpaid_ar_balances'] = "
	CREATE VIEW unpaid_ar_balances AS
	select card_no,
	sum(case when ".$con->datediff('tdate',$con->now())." < -20 
	    and card_no not between 5000 and 6099 then
	    charges-payments else 0 end) as old_balance,
	sum(case when ".$con->datediff('tdate',$con->now())." >= -20 then
	    payments else 0 end) as recent_payments
	from ar_history
	where card_no <> 11
	group by card_no
";
?>
