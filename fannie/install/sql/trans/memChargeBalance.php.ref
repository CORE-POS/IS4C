<?php
/*
View: memChargeBalance

Columns:
    CardNo int
    availBal (calculated) 
    balance (calculated)
    mark (calculated)

Depends on:
    core_op.custdata (table)
    ar_live_balance (view of t.dtransactions -> .v.dlog)

Use:
This view lists real-time store charge
 balances by membership.
This view gets pushed to the lanes as a table
 to speed things up
The "mark" column indicates an account
 whose balance has changed today

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 24Oct12 EL Fix ...
    * 24Oct2012 Eric Lee Comments, add Depended on by:
    *                    Code style.

*/

$names = qualified_names();

$CREATE['trans.memChargeBalance'] = "
    CREATE VIEW memChargeBalance as
    SELECT   c.CardNo, 
    (CASE when a.balance is NULL then c.ChargeLimit
        ELSE c.ChargeLimit - a.balance END) as availBal,
    (CASE when a.balance is NULL then 0 ELSE a.balance END) as balance,
    CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END AS mark   
    FROM {$names['op']}.custdata as c left join ar_live_balance as a ON c.CardNo = a.card_no
    where c.personNum = 1
";

if (!$con->table_exists("ar_live_balance"))
    $CREATE['trans.memChargeBalance'] = "SELECT 1";
?>
