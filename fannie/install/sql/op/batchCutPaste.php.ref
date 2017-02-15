<?php
/*
Table: batchCutPaste

Columns:
    batchID int
    upc varchar(13)
    uid int

Depends on:
    batchList (table)

Use:
This table is a clipboard for batches. uid is the logged in user, 
so multiple people can "cut" to their own clipboard.

You should probably truncate this table occasionally. I do
daily.
*/
$CREATE['op.batchCutPaste'] = "
    CREATE TABLE batchCutPaste (
        batchID int,
        upc varchar(13),
        uid int,
        tdate DATETIME,
        PRIMARY KEY (batchID,upc,uid)
    )
";
?>
