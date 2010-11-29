<?php
/*
Table: SpecialOrderHistory

Columns:
	order_id int
	entry_type varchar
	entry_date datetime
	entry_value text

Depends on:
	PendingSpecialOrder

Use:
This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This table stores a dated history for the order
*/
$CREATE['trans.SpecialOrderHistory'] = "
	CREATE TABLE SpecialOrderHistory (
		order_id int,
		entry_type varchar(20),
		entry_date datetime,
		entry_value text
	)
";
?>
