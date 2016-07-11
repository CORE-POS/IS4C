<?php
/*
Table: vendorLoadScripts

Columns:
    vendorID int
    loadScript varchar

Depends on:
    vendors (table)

Use:
Mapping of scripts for loading vendor items
via CSV to vendor ID #s. This is so ID#s
don't have to match across stores

*/
$CREATE['op.vendorLoadScripts'] = "
    create table vendorLoadScripts (
        vendorID int,
        loadScript varchar(125),
        primary key (vendorID)
    )
";
?>
