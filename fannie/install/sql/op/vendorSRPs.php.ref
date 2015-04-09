<?php
/*
Table: vendorSRPs

Columns:
    vendorID int
    upc varchar
    srp decimal(10,2)

Depends on:
    vendorItems (table)
    vendorDepartments (table)

Use:
This table contains SRPs for items
from a given vendor.

This could be calculated as items are imported
and stored in vendorItems, but in practice some
vendor catalogs are really big. Calculating SRPs
afterwards in a separate step reduces the chances
of hitting a PHP time or memory limit.
*/
$CREATE['op.vendorSRPs'] = "
    create table vendorSRPs (
        vendorID int,
        upc varchar(13),
        srp decimal(10,2),
        PRIMARY KEY (vendorID,upc),
        INDEX(vendorID),
        INDEX(upc)
    )
";
?>
