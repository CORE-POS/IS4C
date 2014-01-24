<?php
/*
Table: SpecialOrderStatus

Columns:
	order_id int
	status_flag int
	sub_status int

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

This table keeps track of order status
*/
$CREATE['trans.SpecialOrderStatus'] = "
	CREATE TABLE SpecialOrderStatus (
		order_id int,
		status_flag int,
		sub_status bigint,
		PRIMARY KEY (order_id)
	)
";
?>
