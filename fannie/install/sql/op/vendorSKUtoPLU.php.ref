<?php
/*
Table: vendorSKUtoPLU

Columns:
    vendorID int
    sku varchar
    upc varchar

Depends on:
    vendors (table)
    vendorItems (table)
    products (table)

Use:
Table mapping vendor SKUs to 
store UPCs. Most commonly used
for bulk items sold by a PLU rather
than the vendor package UPC.
*/
$CREATE['op.vendorSKUtoPLU'] = "
    create table vendorSKUtoPLU (vendorID int,
        sku varchar(10),
        upc varchar(13),
        primary key (vendorID, sku),
        index (vendorID),
        index (sku)
    )
";
?>
