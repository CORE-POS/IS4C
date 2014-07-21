<?php
/*
View: ar_history_today_sum

Columns:
    card_no int
    charges dbms currency
    payments dbms currency
    balance dbms currency

Depends on:
    dlog (view)

Use:
Total charges and payments for the current day
by member number
*/
$dlist = ar_departments();
if (strlen($dlist) <= 2)
    $dlist = "(-999)";

$CREATE['trans.ar_history_today_sum'] = "
    CREATE VIEW ar_history_today_sum AS
    SELECT card_no,
    SUM(CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END) AS charges,
    SUM(CASE WHEN department IN $dlist THEN total ELSE 0 END) AS payments,
    SUM(CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END) 
    - SUM(CASE WHEN department IN $dlist THEN total ELSE 0 END) AS balance
    FROM dlog
    WHERE (trans_subtype='MI' OR department IN {$dlist})
    AND ".$con->datediff($con->now(),'tdate')."=0
    GROUP BY card_no
";

?>
