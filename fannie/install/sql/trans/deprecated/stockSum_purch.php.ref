<?php
/*
View: stockSum_purch

Columns:
    card_no int
    totPayments (calculated)
    startdate datetime

Depends on:
    stockpurchases (table)

Use:
This view just sums stockpurchases
to get per-member totals. It exists to 
calculate balances in real time.
*/
$CREATE['trans.stockSum_purch'] = "
    CREATE VIEW stockSum_purch AS
        SELECT card_no,
        SUM(stockPurchase) AS totPayments,
        MIN(tdate) AS startdate
        FROM stockpurchases
        GROUP BY card_no
";
?>
