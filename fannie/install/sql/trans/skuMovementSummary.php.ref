<?php
/*
Table: skuMovementSummary

Columns:

Depends on:
    transarchive (table)

Use:
Summary of item movement by vendorID and SKU
rather than UPC. It pulls from the previous
calendar quarter like transarchive. This table
is maintained for comparisons against Purchase Orders
data which is also maintained in terms of vendors
and SKUs. The same thing could be accomplished with
lots of joins but performance isn't great.

totalQty is total units sold. sold, returned,
and damaged are self-explanatory and should
add up to equal the total.
*/


$CREATE['trans.skuMovementSummary'] = "
    CREATE TABLE skuMovementSummary (
        vendorID INT,
        sku VARCHAR(13),
        totalQty DOUBLE,
        soldQty DOUBLE,
        returnedQty DOUBLE,
        damagedQty DOUBLE,
        PRIMARY KEY (vendorID, sku)
    )";
