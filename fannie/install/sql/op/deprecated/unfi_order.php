<?php
/*
Table: unfi_order

Columns:
    unfi_sku varchar(13)
    brand varchar(30)
    item_desc varchar(3)
    pack varchar(10)
    pack_size varchar(20)
    upcc varchar(13)
    cat int
    wholesale double
    vd_cost double
    wfc_srp double

Depends on:
    none

Use:
This table stores items from the latest UNFI order.
SRPs are generated based on cost and desired margin
for each UNFI category (cat).

Deprecated. Use vendors (table) and vendorItems(table)
instead. Vendor-based functionality should
allow for more than one vendor.
*/
$CREATE['op.unfi_order'] = "
    CREATE TABLE unfi_order (
        unfi_sku varchar(12),
        brand varchar(50),
        item_desc varchar(50),
        pack varchar(10),
        pack_size varchar(20),
        upcc varchar(13),
        cat int,
        wholesale double,
        vd_cost double,
        wfc_srp double,
        PRIMARY KEY (upcc)
    )
";
?>
