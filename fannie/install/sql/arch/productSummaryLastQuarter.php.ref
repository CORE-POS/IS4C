<?php
/*
Table: productSummaryLastQuarter

Columns:
    productSummaryLastQuarterID int
    upc varchar
    qtyThisWeek
    totalThisWeek
    qtyLastQuarter
    totalLastQuarter
    percentageStoreSales
    percentageSuperDeptSales
    percentageDeptSales

Depends on:
    productWeeklyLastQuarter
    weeksLastQuarter

Use:
Provides per-item sales for the previous quarter.
See weeksLastQuarter for more information about
how the quarter is defined.

Quantity columns are number of items sold; total
columns are in monetary value. Percentages are
calculated in terms of monetary value.

Percentages in this table represent a weighted
average of sales - i.e., sales last week count more
heavily than sales ten weeks ago. The primary purpose
of this table and everything that feeds into it is
to forecast margin. The percentage captures how an
individual item contributes to margin, and weighting
over a longer period should capture long-term trends
while smoothing over random fluctuations.
*/
$CREATE['arch.productSummaryLastQuarter'] = "
    CREATE TABLE productSummaryLastQuarter (
    productSummaryLastQuarterID INT NOT NULL AUTO_INCREMENT,
    upc VARCHAR(13),
    qtyThisWeek DECIMAL(10,2),
    totalThisWeek DECIMAL(10,2),
    qtyLastQuarter DECIMAL(10,2),
    totalLastQuarter DECIMAL(10,2),
    percentageStoreSales DECIMAL(10,5),
    percentageSuperDeptSales DECIMAL(10,5),
    percentageDeptSales DECIMAL(10,5),
    PRIMARY KEY (upc),
    INDEX(productSummaryLastQuarterID)
    )
";

