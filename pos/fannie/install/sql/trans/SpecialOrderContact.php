<?php
/*
Table: SpecialOrderContact

Columns:
	Same as meminfo

Depends on:
	PendingSpecialOrder

Use:
This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This table stores contact information
for non-member special orders. It's
structure is identical to meminfo for
simplicity. The order's order_id goes
into card_no.
*/

$CREATE['trans.SpecialOrderContact'] = duplicate_structure($dbms,'meminfo','SpecialOrderContact');
?>
