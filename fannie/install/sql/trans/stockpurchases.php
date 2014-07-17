<?php
/*
Table: stockpurchases

Columns:
    card_no int
    stockPurchase dbms currency
    tdate datetime
    trans_num varchar
    dept int

Depends on:
    dlog (table)

Use:
This table equity related transaction info.
This table should be updated in conjunction with
any day-end polling system to copy appropriate
rows from dtransactions to stockpurchases
*/
$CREATE['trans.stockpurchases'] = "
    CREATE TABLE stockpurchases (
        card_no int,
        stockPurchase decimal(10,2),
        tdate datetime,
        trans_num varchar(90),
        dept int,
        INDEX(card_no)
    )
";
if ($dbms == "MSSQL"){
    $CREATE['trans.stockpurchases'] = str_replace("decimal(10,2)","money",$CREATE['trans.stockpurchases']);
}
?>
