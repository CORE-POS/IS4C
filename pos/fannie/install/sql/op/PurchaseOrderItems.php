<?php
/*
Table: PurchaseOrderItems

Columns:
	upc varchar(13)
	vendor_id int,
	order_id int,
	quantity int

Depends on:
	PurchaseOrder (table)
	vendors (table)

Use:
This table is used for storing items from a
purchase order. Each UPC gets its own line.

Fannie doesn't really do perpetual inventory, so
this table isn't used much yet.
*/
$CREATE['op.PurchaseOrderItems'] = "
	CREATE TABLE PurchaseOrderItems (
		upc varchar(13),
		vendor_id int,
		order_id int,
		quantity int
	)
";
?>
