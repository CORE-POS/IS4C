<?php
/*
Table: voidTransHistory

Columns:
    tdate datetime
    description varchar
    trans_num varchar
    total money

Depends on:
    none

Use:
Store transaction numbers for voided transactions
(they're identified by comment lines which are
excluded from dlog views)
*/
$CREATE['trans.voidTransHistory'] = "
    CREATE TABLE voidTransHistory (
        tdate datetime,
        description varchar(40),
        trans_num varchar(20),
        total decimal(10,2),
        INDEX(tdate)
    )
";
?>
