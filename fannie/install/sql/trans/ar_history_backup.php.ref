<?php
/*
Table: ar_history_backup

Columns:
    card_no int
    charges dbms currency
    payments dbms currency
    tdate datetime
    trans_num varchar

Depends on:
    dlog (view)
    ar_history (table)

Depended on by:
Table AR_EOM_Summary

Use:
Stores an extra copy of ar_history

Maintenance:
cron/nightly.ar.php, after updating ar_history,
 truncates and then appends all of ar_history

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 22Oct2012 Eric Lee Add Maintenance: section, add Depended on by:

*/

$CREATE['trans.ar_history_backup'] = "
    CREATE TABLE ar_history_backup (
        card_no int,
        charges decimal(10,2),
        payments decimal(10,2),
        tdate datetime,
        trans_num varchar(90)
    )
";
if ($dbms == "MSSQL"){
    $CREATE['trans.ar_history_backup'] = str_replace("decimal(10,2)","money",$CREATE['trans.ar_history_backup']);
}
?>
