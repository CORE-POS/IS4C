<?php
/*
Table: equity_history_sum

Columns:
    card_no int
    payments dbms currency
    startdate datetime

Depends on:
    stockpurchases (table)

Use:
  Summary of all equity transactions
  (One row per customer.)

*/

$CREATE['trans.equity_history_sum'] = "
    CREATE TABLE equity_history_sum (
        card_no INT,
        payments DECIMAL(10,2),
        startdate DATETIME,
        PRIMARY KEY(card_no)
    )
";
?>
