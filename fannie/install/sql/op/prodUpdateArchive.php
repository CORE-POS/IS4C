<?php
/*
Table: prodUpdateArchive

Columns:
    same as prodUpdate

Depends on:
    prodUpdate (table)

Use:
prodUpdate gets written a lot, so it's
saved here are truncated regularly for
the sake of speed
*/
$CREATE['op.prodUpdateArchive'] = duplicate_structure($dbms,
                    'prodUpdate','prodUpdateArchive');
?>
