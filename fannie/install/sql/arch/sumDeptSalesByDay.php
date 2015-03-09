<?php
/*
Table: sumDeptSalesByDay

Columns:
    tdate date
    dept_ID int
    total currency
    quantity double

Depends on:
    none

Use:
Summary table. Stores per-day sales
by UPC
*/
$CREATE['arch.sumDeptSalesByDay'] = "
    CREATE TABLE sumDeptSalesByDay (
    tdate date,
    dept_ID int,
    total decimal(10,2),
    quantity decimal(10,2),
    PRIMARY KEY (tdate, dept_ID)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumDeptSalesByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumDeptSalesByDay']);
}
?>
