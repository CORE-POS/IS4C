<?php
/*
Table: unfiCategories

Columns:
    categoryID int
    name varchar
    margin double
    testing double

Depends on:
    unfi_order

Use:
Margins for UNFI categories are used to
calculate new SRPs for the table unfi_order.

Deprecated. Use vendorDepartments instead.
It's identical except not locked to one
vendor.
*/
$CREATE['op.unfiCategories'] = "
    CREATE TABLE unfiCategories (
        categoryID int,
        name varchar(50),
        margin double,
        testing double,
        PRIMARY KEY (categoryID)
    )
";
?>
