<?php
/*
Table: productWeeklyLastQuarter

Columns:
    productWeeklyLastQuarterID int
    upc varchar
    quantity double
    total double
    percentageStoreSales
    percentageSuperDeptSales
    percentageDeptSales

Depends on:
    none

Use:
Per-item sales numbers for a given week. As always,
quantity is the number of items sold and total is
the monetary value. Percentages are calculated in
terms of monetary value.

This is essentially an intermediate calculation
for building productSummaryLastQuarter. The results
are saved here on the off-chance they prove useful
for something else.
*/
$CREATE['arch.productWeeklyLastQuarter'] = "
    CREATE TABLE productWeeklyLastQuarter (
    productWeeklyLastQuarterID INT NOT NULL AUTO_INCREMENT,
    upc VARCHAR(13),
    weekLastQuarterID INT,
    quantity DECIMAL(10,2),
    total DECIMAL(10,2),
    percentageStoreSales DECIMAL(10,5),
    percentageSuperDeptSales DECIMAL(10,5),
    percentageDeptSales DECIMAL(10,5),
    PRIMARY KEY (upc, weekLastQuarterID),
    INDEX(productWeeklyLastQuarterID)
    )
";

