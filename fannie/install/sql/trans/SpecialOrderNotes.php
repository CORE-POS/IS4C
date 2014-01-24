<?php
/*
Table: SpecialOrderNotes

Columns:
	order_id int
	notes text

Depends on:
	PendingSpecialOrder

Use:

DEPRECATED SOON: See SpecialOrders

This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This table stores generic notes about the
order (example: description of desired item)
*/
$CREATE['trans.SpecialOrderNotes'] = "
	CREATE TABLE SpecialOrderNotes (
		order_id int,
		notes text,
		superID int,
		PRIMARY KEY (order_id)
	)
";
?>
