<?php
/*
View: ar_live_balance

Columns:
    card_no int
    totcharges (calculated)
    totpayments (calculated)
    balance (calculated)
    mark (calculated)

Depends on:
    core_op.custdata (table)
    ar_history_sum (table)
    ar_history_today_sum (view)

Use:
This view lists real-time store charge
balances by membership. The column "mark"
indicates the balance changed today
*/
$names = qualified_names();

$CREATE['trans.ar_live_balance'] = "
    CREATE VIEW ar_live_balance AS
    SELECT   
    c.CardNo as card_no,
    (CASE WHEN a.charges IS NULL THEN 0 ELSE a.charges END)
    + (CASE WHEN t.charges IS NULL THEN 0 ELSE t.charges END)
    as totcharges,
    (CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END)
    + (CASE WHEN t.payments IS NULL THEN 0 ELSE t.payments END)
    as totpayments,
    (CASE WHEN a.balance IS NULL THEN 0 ELSE a.balance END)
    + (CASE WHEN t.balance IS NULL THEN 0 ELSE t.balance END)
    as balance,
    (CASE WHEN t.card_no IS NULL THEN 0 ELSE 1 END) as mark
    FROM 
    {$names['op']}.custdata as c left join
    ar_history_sum as a on c.CardNo=a.card_no and c.personNum=1
    left join ar_history_today_sum as t ON c.CardNo = t.card_no and c.personNum=1
    where c.personNum=1
";

if (!$con->table_exists("ar_history_today_sum"))
    $CREATE['trans.ar_live_balance'] = "SELECT 1";
?>
