<?php
/*
View: equity_live_balance

Columns:
    memnum int
    payments (calculated)
    startdate datetime

Depends on:
    core_op.meminfo (table)
    equit_history_sum (table)
    stockSum_today (view)

Use:
This view lists real-time equity
balances by membership
*/
$names = qualified_names();
$CREATE['trans.equity_live_balance'] = "
    CREATE VIEW equity_live_balance AS
        SELECT
        m.card_no as memnum,
        case
            when a.card_no is not null and b.card_no is not null
            then a.payments + b.totPayments
            when a.card_no is not null
            then a.payments
            when b.card_no is not null
            then b.totPayments
        end
        as payments,
        case when a.startdate is null then
        b.startdate else a.startdate end
        as startdate
        FROM {$names['op']}.meminfo as m LEFT JOIN
        equity_history_sum as a on a.card_no=m.card_no
        LEFT JOIN stockSumToday as b
        ON m.card_no=b.card_no
        WHERE a.card_no is not null OR b.card_no is not null
";

if (!$con->table_exists("stockSumToday"))
    $CREATE['trans.equity_live_balance'] = "SELECT 1";
