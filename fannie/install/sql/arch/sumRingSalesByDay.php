<?php
/*
Table: sumRingSalesByDay

Columns:
    tdate date
    upc varchar
    total currency,
    quantity double

Depends on:
    none

Use:
Summary table. Stores per-day sales
by UPC and department. Unlike 
sumUpcSalesByDay, this table includes
open rings.
*/
$CREATE['arch.sumRingSalesByDay'] = "
    CREATE TABLE sumRingSalesByDay (
    tdate date,
    upc varchar(13),
    dept int,
    total decimal(10,2),
    quantity decimal(10,2),
    PRIMARY KEY (tdate, upc, dept),
    INDEX(upc),
    INDEX(dept),
    INDEX(tdate)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumRingSalesByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumRingSalesByDay']);
}
?>
