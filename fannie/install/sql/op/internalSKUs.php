<?php
/*
Table: internalSKUs

Columns:
    our_sku int
    vendor_sku varchar
    vendorID int
    upc varchar

Depends on:
    vendorItems (table)
    products (table)

Use:
Internal SKUs, sometimes also called "order codes",
are a shorthand for identifying items. They're
typically shorter than product UPCs and thus easier
to type. They may also be helpful if a vendor does
not provide SKUs or UPCs. As of 30Aug13, internal SKUs
are an optional feature and simply another means of
searching for & identifying items.
*/
$CREATE['op.internalSKUs'] = "
    create table internalSKUs (
        our_sku int,
        vendor_sku varchar(13),
        vendorID int,
        upc varchar(13),
        primary key (our_sku)
    )
";
?>
