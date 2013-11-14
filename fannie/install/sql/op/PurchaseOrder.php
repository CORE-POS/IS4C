<?php
/*
Table: PurchaseOrder

Columns:
	orderID int
	vendorID int
	creationDate datetime
	placed tinyint
	placedDate datetime
	userID int

Depends on:
	none

Use:
Stores general an order from a vendor.
One or more records in purchaseOrderItems
should go with this record to list the
individual items to order.
*/
$CREATE['op.PurchaseOrder'] = "
	create table PurchaseOrder (
		orderID INT NOT NULL AUTO_INCREMENT,
		vendorID INT,
		creationDate DATETIME,
		placed TINYINT DEFAULT 0,
		placedDate DATETIME,
		userID INT,
		primary key (orderID),
		INDEX(placed)
	)
";
?>
