<?php
/*
Table: custdataBackup

Columns:
    same as custdata

Depends on:
    products (table)

Use:
Stores an older snapshot of custdata
Easier to pull small bits of info from
instead of restoring an entire DB backup
*/
$CREATE['op.custdataBackup'] = duplicate_structure($dbms,
                    'custdata','custdataBackup');
?>
