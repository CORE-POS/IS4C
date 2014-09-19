<?php
/*
Table: ar_history

Columns:
    card_no int
    charges dbms currency
    payments dbms currency
    tdate datetime
    trans_num varchar

Depends on:
    transarchive (table), i.e. dlog_15 (table)
    was: dlog (view)

Depended on by:
  table ar_history_backup and its descendents
  view ar_history_sum and its descendents

Use:
  This table stores charges and payments on
   a customer's in-store charge account.

Maintenance:
This table should be updated in conjunction with
 any day-end polling system to copy appropriate
 rows from transarchive to ar_history
cron/nightly.ar.php appends selected columns from
 appropriate rows from dlog_15 (i.e. dtransactions)

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 22Oct2012 Eric Lee Change Depends on:
    *                    Break Maintenance section of comments from Use:
    *                     add note about cronjob

*/

$CREATE['trans.ar_history'] = "
    CREATE TABLE ar_history (
        card_no int,
        charges decimal(10,2),
        payments decimal(10,2),
        tdate datetime,
        trans_num varchar(90),
        INDEX (card_no)
    )
";
if ($dbms == "MSSQL"){
    $CREATE['trans.ar_history'] = str_replace("decimal(10,2)","money",$CREATE['trans.ar_history']);
}
?>
