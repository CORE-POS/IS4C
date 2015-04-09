<?php
/*
Table: suspended

Columns:
    identical to dtransactions

Depends on:
    dtransactions (table)

Use:
This table exists so that transactions that
are suspended at one register can be resume
at another.

When a transaction is suspended, that register's
localtemptrans table is copied here. When a transaction
is resumed, appropriate rows are sent from here
to that register's localtemptrans table.
*/
$CREATE['trans.suspended'] = duplicate_structure($dbms,
                    'dtransactions','suspended');
?>
