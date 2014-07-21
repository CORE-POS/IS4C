<?php
/*
View: newBalanceToday_cust

Columns:
    memnum int
    discounttype int
    ARCurrBalance dbms currency
    totcharges (calculated)
    totpayments (calculated)
    balance (calculated)

Depends on:
    core_op.custdata (table)
    memIouToday (view of t.dtransactions -> v.dlog)

Use:
This view lists real-time store charge
balances by membership. There are some
extraneous columns here for historical
reasons; "balance" is the column of
interest.

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 24Oct2012 Eric Lee Comments, add to Depends on:, format code.

*/

$names = qualified_names();

$CREATE['trans.newBalanceToday_cust'] = "
    CREATE VIEW newBalanceToday_cust AS
    SELECT   
        b.CardNo AS memnum, 
        0 AS discounttype,
        c.Balance AS ARCurrBalance,
        (CASE WHEN a.charges IS NULL THEN 0 ELSE a.charges END) AS totcharges,
        (CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END) AS totpayments,
        (CASE when a.card_no IS NULL THEN c.Balance ELSE (c.Balance - a.charges - a.payments)END) AS balance,
        (CASE WHEN a.card_no IS NULL THEN 0 ELSE 1 END) AS mark
    FROM 
    {$names['op']}.custdata as b left join
    ar_history_sum as c on b.CardNo=c.card_no and b.personNum=1
    left outer join memIouToday as a ON c.card_no = a.card_no and b.personNum=1
    where b.personNum=1
";

if (!$con->table_exists("memIouToday"))
    $CREATE['trans.newBalanceStockToday_cust'] = "SELECT 1";
?>
