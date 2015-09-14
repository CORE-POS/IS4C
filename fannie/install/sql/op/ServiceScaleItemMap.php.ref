<?php
/*
Table: ServiceScaleItemMap

Columns:
    serviceScaleID int
    upc varchar

Depends on:
    ServiceScales
    scaleItems

Use:
Join table to record which items are
on which scales.
*/
$CREATE['op.ServiceScaleItemMap'] = "
    CREATE TABLE ServiceScaleItemMap (
        serviceScaleID INT, 
        upc VARCHAR(13),
        PRIMARY KEY (serviceScaleID, upc)
    )
";
