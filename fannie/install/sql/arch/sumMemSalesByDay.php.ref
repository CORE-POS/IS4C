<?php
/*
Table: sumMemSalesByDay

Columns:
    tdate date
    card_no int
    total currency
    quantity double
    transCount int

Depends on:
    none

Use:
Summary table. Stores per-day sales
by member account
*/
$CREATE['arch.sumMemSalesByDay'] = "
    CREATE TABLE sumMemSalesByDay (
    tdate date,
    card_no int,
    total decimal(10,2),
    quantity decimal(10,2),
    transCount int,
    PRIMARY KEY (tdate, card_no)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumMemSalesByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumMemSalesByDay']);
}
?>
