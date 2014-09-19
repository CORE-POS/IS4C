<?php
/*
Table: vendors

Columns:
    vendorID int
    vendorName varchar

Depends on:
    none

Use:
List of known vendors. Pretty simple.
*/
$CREATE['op.vendors'] = "
    create table vendors (vendorID int,
        vendorName varchar(50),
        primary key (vendorID)
    )
";
?>
