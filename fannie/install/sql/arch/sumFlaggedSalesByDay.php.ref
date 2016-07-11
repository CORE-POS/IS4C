<?php
/*
Table: sumFlaggedSalesByDay

Columns:
    tdate date
    dept_ID int
    trans_status varchar
    numflag int
    charflag varchar
    total currency
    quantity double

Depends on:
    none

Use:
Summary table. Stores per-day sales
by department with various flagging
*/
$CREATE['arch.sumFlaggedSalesByDay'] = "
    CREATE TABLE sumFlaggedSalesByDay (
    tdate date,
    dept_ID int,
    trans_status varchar(2),
    numflag int,
    charflag varchar(2),
    total decimal(10,2),
    quantity decimal(10,2),
    PRIMARY KEY (tdate, dept_ID, trans_status, numflag, charflag)
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['arch.sumFlaggedSalesByDay'] = str_replace(" date"," datetime",$CREATE['arch.sumFlaggedSalesByDay']);
}
?>
