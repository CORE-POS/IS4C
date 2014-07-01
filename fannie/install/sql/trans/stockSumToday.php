<?php
/*
View: stockSumToday

Columns:
    card_no int
    totPayments (calculated)
    startdate datetime

Depends on:
    dlog (view)

Use:
This view lists equity activity
for the current day. It exists to 
calculate balances in real time.

The view's construction depends on Fannie's
Equity Department configuration
*/
$dlist = equity_departments();
if (strlen($dlist) <= 2)
    $dlist = "(-999)";

$CREATE['trans.stockSumToday'] = "
    CREATE VIEW stockSumToday AS
        SELECT card_no,
        SUM(CASE WHEN department IN $dlist THEN total ELSE 0 END) AS totPayments,
        MIN(tdate) AS startdate
        FROM dlog WHERE ".$con->datediff($con->now(),'tdate')." = 0
        AND department IN $dlist
        GROUP BY card_no
";
