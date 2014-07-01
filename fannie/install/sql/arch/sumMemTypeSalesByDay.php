<?php
/*
Table: sumMemTypeSalesByDay

Columns:
    tdate date
    memType int
    total currency
    quantity double
    transCount int

Depends on:
    none

Use:
Summary table. Stores per-day sales
by member type
*/
$CREATE['arch.sumMemTypeSalesByDay'] = "
    CREATE TABLE sumMemTypeSalesByDay (
    tdate date,
    memType smallint,
    total decimal(10,2),
    quantity decimal(10,2),
    transCount int,
    PRIMARY KEY (tdate, memType)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumMemTypeSalesByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumMemTypeSalesByDay']);
}
?>
