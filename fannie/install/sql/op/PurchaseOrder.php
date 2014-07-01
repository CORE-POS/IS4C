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
    vendorOrderID varchar
    vendorInvoiceID varchar

Depends on:
    none

Use:
Stores general an order from a vendor.
One or more records in purchaseOrderItems
should go with this record to list the
individual items to order.

vendorOrderID and vendorInvoiceID are memo
fields. If the vendor puts numbers or other
identifiers on orders and/or invoices those
values can be saved here for reference.
*/
$CREATE['op.PurchaseOrder'] = "
    create table PurchaseOrder (
        orderID INT NOT NULL AUTO_INCREMENT,
        vendorID INT,
        creationDate DATETIME,
        placed TINYINT DEFAULT 0,
        placedDate DATETIME,
        userID INT,
        vendorOrderID VARCHAR(25),
        vendorInvoiceID VARCHAR(25),
        primary key (orderID),
        INDEX(placed)
    )
";
?>
