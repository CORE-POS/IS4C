<?php
/*
View: unpaid_ar_balances

Columns:
    card_no int
    old_balance (calculated)
    recent_payments (calculated)

Depends on:
    ar_history (table)

Depended on by:
  unpaid_ar_today (view)

Use:
This view lists A/R balances older than
20 days and payments made in the last 20 days.

The logic is pretty WFC-specific, but the 
general idea is to notify customers that they
have a balance overdue at checkout

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 24Oct2012 Eric Lee Comments Add Depended on by:, code style

*/

$CREATE['trans.unpaid_ar_balances'] = "
    CREATE VIEW unpaid_ar_balances AS
    SELECT
        card_no,
        SUM(CASE WHEN ".$con->datediff('tdate',$con->now())." < -20 
                            AND card_no NOT BETWEEN 5000 AND 6099
                         THEN (charges - payments)
                         ELSE 0 END)                 AS old_balance,
        SUM(CASE WHEN ".$con->datediff('tdate',$con->now())." >= -20
                         THEN payments ELSE 0 END)   AS recent_payments
    FROM ar_history
    WHERE card_no <> 11
    GROUP by card_no
";
?>
