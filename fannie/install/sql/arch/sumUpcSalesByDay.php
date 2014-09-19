<?php
/*
Table: sumUpcSalesByDay

Columns:
    tdate date
    upc varchar
    total currency,
    quantity double

Depends on:
    none

Use:
Summary table. Stores per-day sales
by UPC
*/
$CREATE['arch.sumUpcSalesByDay'] = "
    CREATE TABLE sumUpcSalesByDay (
    tdate date,
    upc varchar(13),
    total decimal(10,2),
    quantity decimal(10,2),
    PRIMARY KEY (tdate, upc)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumUpcSalesByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumUpcSalesByDay']);
}
?>
