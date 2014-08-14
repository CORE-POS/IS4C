<?php
/*
Table: sumTendersByDay

Columns:
    tdate date
    tender_code varchar
    total currency,
    quantity int 

Depends on:
    none

Use:
Summary table. Stores per-day tender
usage by tender code
*/
$CREATE['arch.sumTendersByDay'] = "
    CREATE TABLE sumTendersByDay (
    tdate date,
    tender_code varchar(2),
    total decimal(10,2),
    quantity int,
    PRIMARY KEY (tdate, tender_code)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumTendersByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumTendersByDay']);
}
?>
