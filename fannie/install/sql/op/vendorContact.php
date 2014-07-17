<?php
/*
Table: vendorContact

Columns:
    vendorID int
    phone varchar
    fax varchar
    email varchar
    website varchar
    notes text

Depends on:
    none

Use:
Information about how to contact a vendor
*/
$CREATE['op.vendorContact'] = "
    create table vendorContact (
        vendorID int,
        phone   VARCHAR(15),
        fax VARCHAR(15),
        email   VARCHAR(50),
        website VARCHAR(100),
        notes   TEXT,
        primary key (vendorID)
    )
";
?>
