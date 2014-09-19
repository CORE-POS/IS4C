<?php
/*
Table: scaleItems

Columns:
    plu varchar
    price float
    itemdesc varchar
    exceptionprice float
    weight tinyint
    bycount tinyint
    tare float
    shelflife int
    text text
    reportingClass varchar
    label int
    graphics int

Depends on:
    none

Use:
This holds info for deli-scale items. It's
formatted to match what the Hobart
DataGateWeigh file wants to see in a CSV
*/
$CREATE['op.scaleItems'] = "
    CREATE TABLE scaleItems (
        plu varchar(13),
        price float,
        itemdesc varchar(100),
        exceptionprice float,
        weight tinyint,
        bycount tinyint,
        tare float,
        shelflife int,
        text text,
        reportingClass varchar(6),
        label int,
        graphics int,
        PRIMARY KEY (plu)
    )
";
?>
