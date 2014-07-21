<?php
/*
Table: PurchaseOrderSummary

Columns:
    vendorID INT
    sku VARCHAR
    totalReceived INT
    casesReceived INT
    oldest DATETIME
    newest DATETIME

Depends on:
    PurchaseOrder, PurchaseOrderItems

Use:
Stores total quantities ordered for recent
orders where "recent" covers the same
timeframe as transarchive. Calculating this
on the fly can be prohibitively slow.

totalReceived is in individual units for comparison
against sales records. casesReceived is in cases.

numOrders indicates how many times the item has
been ordered. Credits are counted separately as
numCredits. oldest and newest are bounds on when
the item has been ordered.
*/
$CREATE['op.PurchaseOrderSummary'] = "
    create table PurchaseOrderSummary (
        vendorID INT,
        sku VARCHAR(13),
        totalReceived INT,
        casesReceived INT,
        numOrders INT,
        numCredits INT,
        oldest DATETIME,
        newest DATETIME,
        primary key (vendorID, sku)
    )
";
?>
