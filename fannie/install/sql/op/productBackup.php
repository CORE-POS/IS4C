<?php
/*
Table: productBackup

Columns:
    same as products

Depends on:
    products (table)

Use:
Stores an older snapshot of products
Easier to pull small bits of info from
instead of restoring an entire DB backup
*/
$CREATE['op.productBackup'] = duplicate_structure($dbms,
                    'products','productBackup');
?>
