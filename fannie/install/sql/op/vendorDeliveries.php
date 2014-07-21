<?php
/*
Table: vendorDeliveries

Columns:
    vendorID int
    frequency varchar
    regular int
    nextDelivery datetime
    nextNextDelivery datetime
    sunday int
    monday int
    tuesday int
    wednesday int
    thursday int
    friday int
    saturday int
    

Depends on:
    none

Use:
Schedule of vendor deliveries
*/
$CREATE['op.vendorDeliveries'] = "
    create table vendorDeliveries (
        vendorID int,
        frequency VARCHAR(10),
        regular TINYINT DEFAULT 1,
        nextDelivery DATETIME,
        nextNextDelivery DATETIME,
        sunday TINYINT DEFAULT 0,
        monday TINYINT DEFAULT 0,
        tuesday TINYINT DEFAULT 0,
        wednesday TINYINT DEFAULT 0,
        thursday TINYINT DEFAULT 0,
        friday TINYINT DEFAULT 0,
        saturday TINYINT DEFAULT 0,
        PRIMARY KEY (vendorID)
    )
";

