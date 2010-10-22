<?php
/*
Table: PurchaseOrder

Columns:
	name varchar(100)
	stamp datetime
	id int (auto increment)

Depends on:
	PurchaseOrderItems

Use:
This table is used for storing purchase orders.
Each order gets assigned an id here and a separate
table, PurchaseOrderItems, stores line items
from the order. 

Fannie doesn't really do perpetual inventory, so
this table isn't used much yet.
*/
$poQ = "CREATE TABLE PurchaseOrder (
	name varchar(100),
	stamp datetime,";
if ($dbms == "MSSQL")
	$poQ .= "id int IDENTITY (1,1) NOT NULL, primary key(id))";
else
	$poQ .= "id int auto_increment NOT NULL, primary key(id))";

$CREATE['op.PurchaseOrder'] = $poQ;
?>
