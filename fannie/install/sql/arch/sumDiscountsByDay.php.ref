<?php
/*
Table: sumDiscountsByDay

Columns:
    tdate date
    memType int
    total currency
    transCount int

Depends on:
    none

Use:
Summary table. Stores per-day transaction
discounts by member type
*/
$CREATE['arch.sumDiscountsByDay'] = "
    CREATE TABLE sumDiscountsByDay (
    tdate date,
    memType smallint,
    total decimal(10,2),
    transCount int,
    PRIMARY KEY (tdate, memType)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumDiscountsByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumDiscountsByDay']);
}
?>
