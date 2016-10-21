<?php
/*
View: unpaid_ar_today

Columns:
    card_no int
    old_balance (calculated)
    recent_payments (calculated)
    mark (calculated)

Depends on:
    ar_history (table)
    unpaid_ar_balances (view of t.ar_history)
    ar_history_today_sum (view of t.dtransactions via v.dlog)

Depended on by:
  cron/LanePush/UpdateUnpaidAR.php
   to update each lane opdata.unpaid_ar_today.recent_payments

Use:
This view adds payments from the current
day to the view unpaid_ar_balances

The logic is pretty WFC-specific, but the 
general idea is to notify customers that they
have a balance overdue at checkout

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 24Oct2012 Eric Lee Comments Add Depended on by:, add to Depends on:, code style

*/

$CREATE['trans.unpaid_ar_today'] = "
    CREATE VIEW unpaid_ar_today AS
    SELECT u.card_no,u.old_balance,
    case when m.card_no is null then
        u.recent_payments else
        m.payments+u.recent_payments
        end as recent_payments,
    case when m.card_no is null then 0 else 1 end as mark
    from unpaid_ar_balances as u
    left join ar_history_today_sum as m
    on u.card_no=m.card_no
";

if (!$con->table_exists("ar_history_today_sum"))
    $CREATE['trans.unpaid_ar_today'] = "SELECT 1";
?>
